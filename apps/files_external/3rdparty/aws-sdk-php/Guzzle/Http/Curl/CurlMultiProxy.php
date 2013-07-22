<?php

namespace Guzzle\Http\Curl;

use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\Message\RequestInterface;

/**
 * Proxies requests and connections to a pool of internal curl_multi handles. Each recursive call will add requests
 * to the next available CurlMulti handle.
 */
class CurlMultiProxy extends AbstractHasDispatcher implements CurlMultiInterface
{
    protected $handles = array();
    protected $groups = array();
    protected $queued = array();
    protected $maxHandles;

    /**
     * @param int $maxHandles The maximum number of idle CurlMulti handles to allow to remain open
     */
    public function __construct($maxHandles = 3)
    {
        $this->maxHandles = $maxHandles;
        // You can get some weird "Too many open files" errors when sending a large amount of requests in parallel.
        // These two statements autoload classes before a system runs out of file descriptors so that you can get back
        // valuable error messages if you run out.
        class_exists('Guzzle\Http\Message\Response');
        class_exists('Guzzle\Http\Exception\CurlException');
    }

    public function add(RequestInterface $request)
    {
        $this->queued[] = $request;

        return $this;
    }

    public function all()
    {
        $requests = $this->queued;
        foreach ($this->handles as $handle) {
            $requests = array_merge($requests, $handle->all());
        }

        return $requests;
    }

    public function remove(RequestInterface $request)
    {
        foreach ($this->queued as $i => $r) {
            if ($request === $r) {
                unset($this->queued[$i]);
                return true;
            }
        }

        foreach ($this->handles as $handle) {
            if ($handle->remove($request)) {
                return true;
            }
        }

        return false;
    }

    public function reset($hard = false)
    {
        $this->queued = array();
        $this->groups = array();
        foreach ($this->handles as $handle) {
            $handle->reset();
        }
        if ($hard) {
            $this->handles = array();
        }

        return $this;
    }

    public function send()
    {
        if ($this->queued) {
            $group = $this->getAvailableHandle();
            // Add this handle to a list of handles than is claimed
            $this->groups[] = $group;
            while ($request = array_shift($this->queued)) {
                $group->add($request);
            }
            try {
                $group->send();
                array_pop($this->groups);
                $this->cleanupHandles();
            } catch (\Exception $e) {
                // Remove the group and cleanup if an exception was encountered and no more requests in group
                if (!$group->count()) {
                    array_pop($this->groups);
                    $this->cleanupHandles();
                }
                throw $e;
            }
        }
    }

    public function count()
    {
        return count($this->all());
    }

    /**
     * Get an existing available CurlMulti handle or create a new one
     *
     * @return CurlMulti
     */
    protected function getAvailableHandle()
    {
        // Grab a handle that is not claimed
        foreach ($this->handles as $h) {
            if (!in_array($h, $this->groups, true)) {
                return $h;
            }
        }

        // All are claimed, so create one
        $handle = new CurlMulti();
        $handle->setEventDispatcher($this->getEventDispatcher());
        $this->handles[] = $handle;

        return $handle;
    }

    /**
     * Trims down unused CurlMulti handles to limit the number of open connections
     */
    protected function cleanupHandles()
    {
        if ($diff = max(0, count($this->handles) - $this->maxHandles)) {
            for ($i = count($this->handles) - 1; $i > 0 && $diff > 0; $i--) {
                if (!count($this->handles[$i])) {
                    unset($this->handles[$i]);
                    $diff--;
                }
            }
            $this->handles = array_values($this->handles);
        }
    }
}
