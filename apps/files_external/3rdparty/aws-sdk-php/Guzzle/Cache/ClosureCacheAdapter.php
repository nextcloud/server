<?php

namespace Guzzle\Cache;

/**
 * Cache adapter that defers to closures for implementation
 */
class ClosureCacheAdapter implements CacheAdapterInterface
{
    /**
     * @var array Mapping of method names to callables
     */
    protected $callables;

    /**
     * The callables array is an array mapping the actions of the cache adapter to callables.
     * - contains: Callable that accepts an $id and $options argument
     * - delete:   Callable that accepts an $id and $options argument
     * - fetch:    Callable that accepts an $id and $options argument
     * - save:     Callable that accepts an $id, $data, $lifeTime, and $options argument
     *
     * @param array $callables array of action names to callable
     *
     * @throws \InvalidArgumentException if the callable is not callable
     */
    public function __construct(array $callables)
    {
        // Validate each key to ensure it exists and is callable
        foreach (array('contains', 'delete', 'fetch', 'save') as $key) {
            if (!array_key_exists($key, $callables) || !is_callable($callables[$key])) {
                throw new \InvalidArgumentException("callables must contain a callable {$key} key");
            }
        }

        $this->callables = $callables;
    }

    public function contains($id, array $options = null)
    {
        return call_user_func($this->callables['contains'], $id, $options);
    }

    public function delete($id, array $options = null)
    {
        return call_user_func($this->callables['delete'], $id, $options);
    }

    public function fetch($id, array $options = null)
    {
        return call_user_func($this->callables['fetch'], $id, $options);
    }

    public function save($id, $data, $lifeTime = false, array $options = null)
    {
        return call_user_func($this->callables['save'], $id, $data, $lifeTime, $options);
    }
}
