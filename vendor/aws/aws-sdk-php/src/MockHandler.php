<?php
namespace Aws;

use Aws\Exception\AwsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Exception;

/**
 * Returns promises that are rejected or fulfilled using a queue of
 * Aws\ResultInterface and Aws\Exception\AwsException objects.
 */
class MockHandler implements \Countable
{
    private $queue;
    private $lastCommand;
    private $lastRequest;
    private $onFulfilled;
    private $onRejected;

    /**
     * The passed in value must be an array of {@see Aws\ResultInterface} or
     * {@see AwsException} objects that acts as a queue of results or
     * exceptions to return each time the handler is invoked.
     *
     * @param array    $resultOrQueue
     * @param callable $onFulfilled Callback to invoke when the return value is fulfilled.
     * @param callable $onRejected  Callback to invoke when the return value is rejected.
     */
    public function __construct(
        array $resultOrQueue = [],
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ) {
        $this->queue = [];
        $this->onFulfilled = $onFulfilled;
        $this->onRejected = $onRejected;

        if ($resultOrQueue) {
            call_user_func_array([$this, 'append'], array_values($resultOrQueue));
        }
    }

    /**
     * Adds one or more variadic ResultInterface or AwsException objects to the
     * queue.
     */
    public function append()
    {
        foreach (func_get_args() as $value) {
            if ($value instanceof ResultInterface
                || $value instanceof Exception
                || is_callable($value)
            ) {
                $this->queue[] = $value;
            } else {
                throw new \InvalidArgumentException('Expected an Aws\ResultInterface or Exception.');
            }
        }
    }

    /**
     * Adds one or more \Exception or \Throwable to the queue
     */
    public function appendException()
    {
        foreach (func_get_args() as $value) {
            if ($value instanceof \Exception || $value instanceof \Throwable) {
                $this->queue[] = $value;
            } else {
                throw new \InvalidArgumentException('Expected an \Exception or \Throwable.');
            }
        }
    }

    public function __invoke(
        CommandInterface $command,
        RequestInterface $request
    ) {
        if (!$this->queue) {
            $last = $this->lastCommand
                ? ' The last command sent was ' . $this->lastCommand->getName() . '.'
                : '';
            throw new \RuntimeException('Mock queue is empty. Trying to send a '
                . $command->getName() . ' command failed.' . $last);
        }

        $this->lastCommand = $command;
        $this->lastRequest = $request;

        $result = array_shift($this->queue);

        if (is_callable($result)) {
            $result = $result($command, $request);
        }

        if ($result instanceof \Exception) {
            $result = new RejectedPromise($result);
        } else {
            // Add an effective URI and statusCode if not present.
            $meta = $result['@metadata'];
            if (!isset($meta['effectiveUri'])) {
                $meta['effectiveUri'] = (string) $request->getUri();
            }
            if (!isset($meta['statusCode'])) {
                $meta['statusCode'] = 200;
            }
            $result['@metadata'] = $meta;
            $result = Promise\Create::promiseFor($result);
        }

        $result->then($this->onFulfilled, $this->onRejected);

        return $result;
    }

    /**
     * Get the last received request.
     *
     * @return RequestInterface|null
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get the last received command.
     *
     * @return CommandInterface
     */
    public function getLastCommand()
    {
        return $this->lastCommand;
    }

    /**
     * Returns the number of remaining items in the queue.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->queue);
    }
}
