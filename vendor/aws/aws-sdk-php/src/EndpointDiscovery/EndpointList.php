<?php
namespace Aws\EndpointDiscovery;

class EndpointList
{
    private $active;
    private $expired = [];

    public function __construct(array $endpoints)
    {
        $this->active = $endpoints;
        reset($this->active);
    }

    /**
     * Gets an active (unexpired) endpoint. Returns null if none found.
     *
     * @return null|string
     */
    public function getActive()
    {
        if (count($this->active) < 1) {
            return null;
        }
        while (time() > current($this->active)) {
            $key = key($this->active);
            $this->expired[$key] = current($this->active);
            $this->increment($this->active);
            unset($this->active[$key]);
            if (count($this->active) < 1) {
                return null;
            }
        }
        $active = key($this->active);
        $this->increment($this->active);
        return $active;
    }

    /**
     * Gets an active endpoint if possible, then an expired endpoint if possible.
     * Returns null if no endpoints found.
     *
     * @return null|string
     */
    public function getEndpoint()
    {
        if (!empty($active = $this->getActive())) {
            return $active;
        }
        return $this->getExpired();
    }

    /**
     * Removes an endpoint from both lists.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->active[$key]);
        unset($this->expired[$key]);
    }

    /**
     * Get an expired endpoint. Returns null if none found.
     *
     * @return null|string
     */
    private function getExpired()
    {
        if (count($this->expired) < 1) {
            return null;
        }
        $expired = key($this->expired);
        $this->increment($this->expired);
        return $expired;
    }

    private function increment(&$array)
    {
        if (next($array) === false) {
            reset($array);
        }
    }
}
