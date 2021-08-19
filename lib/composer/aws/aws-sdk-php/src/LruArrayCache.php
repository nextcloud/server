<?php
namespace Aws;

/**
 * Simple in-memory LRU cache that limits the number of cached entries.
 *
 * The LRU cache is implemented using PHP's ordered associative array. When
 * accessing an element, the element is removed from the hash and re-added to
 * ensure that recently used items are always at the end of the list while
 * least recently used are at the beginning. When a value is added to the
 * cache, if the number of cached items exceeds the allowed number, the first
 * N number of items are removed from the array.
 */
class LruArrayCache implements CacheInterface, \Countable
{
    /** @var int */
    private $maxItems;

    /** @var array */
    private $items = array();

    /**
     * @param int $maxItems Maximum number of allowed cache items.
     */
    public function __construct($maxItems = 1000)
    {
        $this->maxItems = $maxItems;
    }

    public function get($key)
    {
        if (!isset($this->items[$key])) {
            return null;
        }

        $entry = $this->items[$key];

        // Ensure the item is not expired.
        if (!$entry[1] || time() < $entry[1]) {
            // LRU: remove the item and push it to the end of the array.
            unset($this->items[$key]);
            $this->items[$key] = $entry;
            return $entry[0];
        }

        unset($this->items[$key]);
        return null;
    }

    public function set($key, $value, $ttl = 0)
    {
        // Only call time() if the TTL is not 0/false/null
        $ttl = $ttl ? time() + $ttl : 0;
        $this->items[$key] = [$value, $ttl];

        // Determine if there are more items in the cache than allowed.
        $diff = count($this->items) - $this->maxItems;

        // Clear out least recently used items.
        if ($diff > 0) {
            // Reset to the beginning of the array and begin unsetting.
            reset($this->items);
            for ($i = 0; $i < $diff; $i++) {
                unset($this->items[key($this->items)]);
                next($this->items);
            }
        }
    }

    public function remove($key)
    {
        unset($this->items[$key]);
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }
}
