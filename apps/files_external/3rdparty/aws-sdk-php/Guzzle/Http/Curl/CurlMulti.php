<?php

namespace Guzzle\Http\Curl;

use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Event;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\RequestInterface;

/**
 * Send {@see RequestInterface} objects in parallel using curl_multi
 */
class CurlMulti extends AbstractHasDispatcher implements CurlMultiInterface
{
    /** @var resource cURL multi handle. */
    protected $multiHandle;

    /** @var array Attached {@see RequestInterface} objects. */
    protected $requests;

    /** @var \SplObjectStorage RequestInterface to CurlHandle hash */
    protected $handles;

    /** @var array Hash mapping curl handle resource IDs to request objects */
    protected $resourceHash;

    /** @var array Queued exceptions */
    protected $exceptions = array();

    /** @var array Requests that succeeded */
    protected $successful = array();

    /** @var array cURL multi error values and codes */
    protected $multiErrors = array(
        CURLM_BAD_HANDLE      => array('CURLM_BAD_HANDLE', 'The passed-in handle is not a valid CURLM handle.'),
        CURLM_BAD_EASY_HANDLE => array('CURLM_BAD_EASY_HANDLE', "An easy handle was not good/valid. It could mean that it isn't an easy handle at all, or possibly that the handle already is in used by this or another multi handle."),
        CURLM_OUT_OF_MEMORY   => array('CURLM_OUT_OF_MEMORY', 'You are doomed.'),
        CURLM_INTERNAL_ERROR  => array('CURLM_INTERNAL_ERROR', 'This can only be returned if libcurl bugs. Please report it to us!')
    );

    public function __construct()
    {
        $this->multiHandle = curl_multi_init();
        // @codeCoverageIgnoreStart
        if ($this->multiHandle === false) {
            throw new CurlException('Unable to create multi handle');
        }
        // @codeCoverageIgnoreEnd
        $this->reset();
    }

    public function __destruct()
    {
        if (is_resource($this->multiHandle)) {
            curl_multi_close($this->multiHandle);
        }
    }

    public function add(RequestInterface $request)
    {
        $this->requests[] = $request;
        // If requests are currently transferring and this is async, then the
        // request must be prepared now as the send() method is not called.
        $this->beforeSend($request);
        $this->dispatch(self::ADD_REQUEST, array('request' => $request));

        return $this;
    }

    public function all()
    {
        return $this->requests;
    }

    public function remove(RequestInterface $request)
    {
        $this->removeHandle($request);
        foreach ($this->requests as $i => $r) {
            if ($request === $r) {
                unset($this->requests[$i]);
                $this->requests = array_values($this->requests);
                $this->dispatch(self::REMOVE_REQUEST, array('request' => $request));
                return true;
            }
        }

        return false;
    }

    public function reset($hard = false)
    {
        // Remove each request
        if ($this->requests) {
            foreach ($this->requests as $request) {
                $this->remove($request);
            }
        }

        $this->handles = new \SplObjectStorage();
        $this->requests = $this->resourceHash = $this->exceptions = $this->successful = array();
    }

    public function send()
    {
        $this->perform();
        $exceptions = $this->exceptions;
        $successful = $this->successful;
        $this->reset();

        if ($exceptions) {
            $this->throwMultiException($exceptions, $successful);
        }
    }

    public function count()
    {
        return count($this->requests);
    }

    /**
     * Build and throw a MultiTransferException
     *
     * @param array $exceptions Exceptions encountered
     * @param array $successful Successful requests
     * @throws MultiTransferException
     */
    protected function throwMultiException(array $exceptions, array $successful)
    {
        $multiException = new MultiTransferException('Errors during multi transfer');

        while ($e = array_shift($exceptions)) {
            $multiException->add($e['exception']);
            $multiException->addFailedRequest($e['request']);
        }

        // Add successful requests
        foreach ($successful as $request) {
            if (!$multiException->containsRequest($request)) {
                $multiException->addSuccessfulRequest($request);
            }
        }

        throw $multiException;
    }

    /**
     * Prepare for sending
     *
     * @param RequestInterface $request Request to prepare
     * @throws \Exception on error preparing the request
     */
    protected function beforeSend(RequestInterface $request)
    {
        try {
            $state = $request->setState(RequestInterface::STATE_TRANSFER);
            if ($state == RequestInterface::STATE_TRANSFER) {
                // Add the request curl handle to the multi handle
                $this->checkCurlResult(curl_multi_add_handle($this->multiHandle, $this->createCurlHandle($request)->getHandle()));
            } else {
                // Requests might decide they don't need to be sent just before transfer (e.g. CachePlugin)
                $this->remove($request);
                if ($state == RequestInterface::STATE_COMPLETE) {
                    $this->successful[] = $request;
                }
            }
        } catch (\Exception $e) {
            // Queue the exception to be thrown when sent
            $this->removeErroredRequest($request, $e);
        }
    }

    /**
     * Create a curl handle for a request
     *
     * @param RequestInterface $request Request
     *
     * @return CurlHandle
     */
    protected function createCurlHandle(RequestInterface $request)
    {
        $wrapper = CurlHandle::factory($request);
        $this->handles[$request] = $wrapper;
        $this->resourceHash[(int) $wrapper->getHandle()] = $request;

        return $wrapper;
    }

    /**
     * Get the data from the multi handle
     */
    protected function perform()
    {
        if (!$this->requests) {
            return;
        }

        // Initialize the handles with a very quick select timeout
        $active = $mrc = null;
        $this->executeHandles($active, $mrc, 0.001);
        $event = new Event(array('curl_multi' => $this));
        $this->processMessages();

        while ($this->requests) {

            // Notify each request as polling
            $blocking = $total = 0;
            foreach ($this->requests as $request) {
                ++$total;
                $event['request'] = $request;
                $request->getEventDispatcher()->dispatch(self::POLLING_REQUEST, $event);
                // The blocking variable just has to be non-falsey to block the loop
                if ($request->getParams()->hasKey(self::BLOCKING)) {
                    ++$blocking;
                }
            }

            if ($blocking == $total) {
                // Sleep to prevent eating CPU because no requests are actually pending a select call
                usleep(500);
            } else {
                do {
                    $this->executeHandles($active, $mrc, 1);
                } while ($active);
            }
            $this->processMessages();
        }
    }

    /**
     * Process any received curl multi messages
     */
    private function processMessages()
    {
        // Get messages from curl handles
        while ($done = curl_multi_info_read($this->multiHandle)) {
            try {
                $request = $this->resourceHash[(int) $done['handle']];
                $this->processResponse($request, $this->handles[$request], $done);
                $this->successful[] = $request;
            } catch (MultiTransferException $e) {
                $this->removeErroredRequest($request, $e, false);
                throw $e;
            } catch (\Exception $e) {
                $this->removeErroredRequest($request, $e);
            }
        }
    }

    /**
     * Execute and select curl handles until there is activity
     *
     * @param int $active  Active value to update
     * @param int $mrc     Multi result value to update
     * @param int $timeout Select timeout in seconds
     */
    private function executeHandles(&$active, &$mrc, $timeout = 1)
    {
        do {
            $mrc = curl_multi_exec($this->multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM && $active);
        $this->checkCurlResult($mrc);

        // @codeCoverageIgnoreStart
        // Select the curl handles until there is any activity on any of the open file descriptors
        // See https://github.com/php/php-src/blob/master/ext/curl/multi.c#L170
        if ($active && $mrc == CURLM_OK && curl_multi_select($this->multiHandle, $timeout) == -1) {
            // Perform a usleep if a previously executed select returned -1
            // @see https://bugs.php.net/bug.php?id=61141
            usleep(100);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Remove a request that encountered an exception
     *
     * @param RequestInterface $request Request to remove
     * @param \Exception       $e       Exception encountered
     * @param bool             $buffer  Set to false to not buffer the exception
     */
    protected function removeErroredRequest(RequestInterface $request, \Exception $e = null, $buffer = true)
    {
        if ($buffer) {
            $this->exceptions[] = array('request' => $request, 'exception' => $e);
        }

        $this->remove($request);
        $this->dispatch(self::MULTI_EXCEPTION, array('exception' => $e, 'all_exceptions' => $this->exceptions));
    }

    /**
     * Check for errors and fix headers of a request based on a curl response
     *
     * @param RequestInterface $request Request to process
     * @param CurlHandle       $handle  Curl handle object
     * @param array            $curl    Array returned from curl_multi_info_read
     *
     * @throws CurlException on Curl error
     */
    protected function processResponse(RequestInterface $request, CurlHandle $handle, array $curl)
    {
        // Set the transfer stats on the response
        $handle->updateRequestFromTransfer($request);
        // Check if a cURL exception occurred, and if so, notify things
        $curlException = $this->isCurlException($request, $handle, $curl);

        // Always remove completed curl handles.  They can be added back again
        // via events if needed (e.g. ExponentialBackoffPlugin)
        $this->removeHandle($request);

        if (!$curlException) {
            $state = $request->setState(RequestInterface::STATE_COMPLETE, array('handle' => $handle));
            // Only remove the request if it wasn't resent as a result of the state change
            if ($state != RequestInterface::STATE_TRANSFER) {
                $this->remove($request);
            }
        } else {
            // Set the state of the request to an error
            $state = $request->setState(RequestInterface::STATE_ERROR, array('exception' => $curlException));
            // Allow things to ignore the error if possible
            if ($state != RequestInterface::STATE_TRANSFER) {
                $this->remove($request);
            }
            // The error was not handled, so fail
            if ($state == RequestInterface::STATE_ERROR) {
                /** @var CurlException $curlException */
                throw $curlException;
            }
        }
    }

    /**
     * Remove a curl handle from the curl multi object
     *
     * @param RequestInterface $request Request that owns the handle
     */
    protected function removeHandle(RequestInterface $request)
    {
        if (isset($this->handles[$request])) {
            $handle = $this->handles[$request];
            unset($this->handles[$request]);
            unset($this->resourceHash[(int) $handle->getHandle()]);
            curl_multi_remove_handle($this->multiHandle, $handle->getHandle());
            $handle->close();
        }
    }

    /**
     * Check if a cURL transfer resulted in what should be an exception
     *
     * @param RequestInterface $request Request to check
     * @param CurlHandle       $handle  Curl handle object
     * @param array            $curl    Array returned from curl_multi_info_read
     *
     * @return CurlException|bool
     */
    private function isCurlException(RequestInterface $request, CurlHandle $handle, array $curl)
    {
        if (CURLM_OK == $curl['result'] || CURLM_CALL_MULTI_PERFORM == $curl['result']) {
            return false;
        }

        $handle->setErrorNo($curl['result']);
        $e = new CurlException(sprintf('[curl] %s: %s [url] %s',
            $handle->getErrorNo(), $handle->getError(), $handle->getUrl()));
        $e->setCurlHandle($handle)
            ->setRequest($request)
            ->setCurlInfo($handle->getInfo())
            ->setError($handle->getError(), $handle->getErrorNo());

        return $e;
    }

    /**
     * Throw an exception for a cURL multi response if needed
     *
     * @param int $code Curl response code
     * @throws CurlException
     */
    private function checkCurlResult($code)
    {
        if ($code != CURLM_OK && $code != CURLM_CALL_MULTI_PERFORM) {
            throw new CurlException(isset($this->multiErrors[$code])
                ? "cURL error: {$code} ({$this->multiErrors[$code][0]}): cURL message: {$this->multiErrors[$code][1]}"
                : 'Unexpected cURL error: ' . $code
            );
        }
    }
}
