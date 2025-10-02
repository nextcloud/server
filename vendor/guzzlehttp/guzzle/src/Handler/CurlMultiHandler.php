<?php

namespace GuzzleHttp\Handler;

use Closure;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;

/**
 * Returns an asynchronous response using curl_multi_* functions.
 *
 * When using the CurlMultiHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the provided request options.
 *
 * @final
 */
class CurlMultiHandler
{
    /**
     * @var CurlFactoryInterface
     */
    private $factory;

    /**
     * @var int
     */
    private $selectTimeout;

    /**
     * @var int Will be higher than 0 when `curl_multi_exec` is still running.
     */
    private $active = 0;

    /**
     * @var array Request entry handles, indexed by handle id in `addRequest`.
     *
     * @see CurlMultiHandler::addRequest
     */
    private $handles = [];

    /**
     * @var array<int, float> An array of delay times, indexed by handle id in `addRequest`.
     *
     * @see CurlMultiHandler::addRequest
     */
    private $delays = [];

    /**
     * @var array<mixed> An associative array of CURLMOPT_* options and corresponding values for curl_multi_setopt()
     */
    private $options = [];

    /** @var resource|\CurlMultiHandle */
    private $_mh;

    /**
     * This handler accepts the following options:
     *
     * - handle_factory: An optional factory  used to create curl handles
     * - select_timeout: Optional timeout (in seconds) to block before timing
     *   out while selecting curl handles. Defaults to 1 second.
     * - options: An associative array of CURLMOPT_* options and
     *   corresponding values for curl_multi_setopt()
     */
    public function __construct(array $options = [])
    {
        $this->factory = $options['handle_factory'] ?? new CurlFactory(50);

        if (isset($options['select_timeout'])) {
            $this->selectTimeout = $options['select_timeout'];
        } elseif ($selectTimeout = Utils::getenv('GUZZLE_CURL_SELECT_TIMEOUT')) {
            @trigger_error('Since guzzlehttp/guzzle 7.2.0: Using environment variable GUZZLE_CURL_SELECT_TIMEOUT is deprecated. Use option "select_timeout" instead.', \E_USER_DEPRECATED);
            $this->selectTimeout = (int) $selectTimeout;
        } else {
            $this->selectTimeout = 1;
        }

        $this->options = $options['options'] ?? [];

        // unsetting the property forces the first access to go through
        // __get().
        unset($this->_mh);
    }

    /**
     * @param string $name
     *
     * @return resource|\CurlMultiHandle
     *
     * @throws \BadMethodCallException when another field as `_mh` will be gotten
     * @throws \RuntimeException       when curl can not initialize a multi handle
     */
    public function __get($name)
    {
        if ($name !== '_mh') {
            throw new \BadMethodCallException("Can not get other property as '_mh'.");
        }

        $multiHandle = \curl_multi_init();

        if (false === $multiHandle) {
            throw new \RuntimeException('Can not initialize curl multi handle.');
        }

        $this->_mh = $multiHandle;

        foreach ($this->options as $option => $value) {
            // A warning is raised in case of a wrong option.
            curl_multi_setopt($this->_mh, $option, $value);
        }

        return $this->_mh;
    }

    public function __destruct()
    {
        if (isset($this->_mh)) {
            \curl_multi_close($this->_mh);
            unset($this->_mh);
        }
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $easy = $this->factory->create($request, $options);
        $id = (int) $easy->handle;

        $promise = new Promise(
            [$this, 'execute'],
            function () use ($id) {
                return $this->cancel($id);
            }
        );

        $this->addRequest(['easy' => $easy, 'deferred' => $promise]);

        return $promise;
    }

    /**
     * Ticks the curl event loop.
     */
    public function tick(): void
    {
        // Add any delayed handles if needed.
        if ($this->delays) {
            $currentTime = Utils::currentTime();
            foreach ($this->delays as $id => $delay) {
                if ($currentTime >= $delay) {
                    unset($this->delays[$id]);
                    \curl_multi_add_handle(
                        $this->_mh,
                        $this->handles[$id]['easy']->handle
                    );
                }
            }
        }

        // Run curl_multi_exec in the queue to enable other async tasks to run
        P\Utils::queue()->add(Closure::fromCallable([$this, 'tickInQueue']));

        // Step through the task queue which may add additional requests.
        P\Utils::queue()->run();

        if ($this->active && \curl_multi_select($this->_mh, $this->selectTimeout) === -1) {
            // Perform a usleep if a select returns -1.
            // See: https://bugs.php.net/bug.php?id=61141
            \usleep(250);
        }

        while (\curl_multi_exec($this->_mh, $this->active) === \CURLM_CALL_MULTI_PERFORM) {
            // Prevent busy looping for slow HTTP requests.
            \curl_multi_select($this->_mh, $this->selectTimeout);
        }

        $this->processMessages();
    }

    /**
     * Runs \curl_multi_exec() inside the event loop, to prevent busy looping
     */
    private function tickInQueue(): void
    {
        if (\curl_multi_exec($this->_mh, $this->active) === \CURLM_CALL_MULTI_PERFORM) {
            \curl_multi_select($this->_mh, 0);
            P\Utils::queue()->add(Closure::fromCallable([$this, 'tickInQueue']));
        }
    }

    /**
     * Runs until all outstanding connections have completed.
     */
    public function execute(): void
    {
        $queue = P\Utils::queue();

        while ($this->handles || !$queue->isEmpty()) {
            // If there are no transfers, then sleep for the next delay
            if (!$this->active && $this->delays) {
                \usleep($this->timeToNext());
            }
            $this->tick();
        }
    }

    private function addRequest(array $entry): void
    {
        $easy = $entry['easy'];
        $id = (int) $easy->handle;
        $this->handles[$id] = $entry;
        if (empty($easy->options['delay'])) {
            \curl_multi_add_handle($this->_mh, $easy->handle);
        } else {
            $this->delays[$id] = Utils::currentTime() + ($easy->options['delay'] / 1000);
        }
    }

    /**
     * Cancels a handle from sending and removes references to it.
     *
     * @param int $id Handle ID to cancel and remove.
     *
     * @return bool True on success, false on failure.
     */
    private function cancel($id): bool
    {
        if (!is_int($id)) {
            trigger_deprecation('guzzlehttp/guzzle', '7.4', 'Not passing an integer to %s::%s() is deprecated and will cause an error in 8.0.', __CLASS__, __FUNCTION__);
        }

        // Cannot cancel if it has been processed.
        if (!isset($this->handles[$id])) {
            return false;
        }

        $handle = $this->handles[$id]['easy']->handle;
        unset($this->delays[$id], $this->handles[$id]);
        \curl_multi_remove_handle($this->_mh, $handle);
        \curl_close($handle);

        return true;
    }

    private function processMessages(): void
    {
        while ($done = \curl_multi_info_read($this->_mh)) {
            if ($done['msg'] !== \CURLMSG_DONE) {
                // if it's not done, then it would be premature to remove the handle. ref https://github.com/guzzle/guzzle/pull/2892#issuecomment-945150216
                continue;
            }
            $id = (int) $done['handle'];
            \curl_multi_remove_handle($this->_mh, $done['handle']);

            if (!isset($this->handles[$id])) {
                // Probably was cancelled.
                continue;
            }

            $entry = $this->handles[$id];
            unset($this->handles[$id], $this->delays[$id]);
            $entry['easy']->errno = $done['result'];
            $entry['deferred']->resolve(
                CurlFactory::finish($this, $entry['easy'], $this->factory)
            );
        }
    }

    private function timeToNext(): int
    {
        $currentTime = Utils::currentTime();
        $nextTime = \PHP_INT_MAX;
        foreach ($this->delays as $time) {
            if ($time < $nextTime) {
                $nextTime = $time;
            }
        }

        return ((int) \max(0, $nextTime - $currentTime)) * 1000000;
    }
}
