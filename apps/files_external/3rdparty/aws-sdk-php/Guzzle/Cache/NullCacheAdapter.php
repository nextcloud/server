<?php

namespace Guzzle\Cache;

/**
 * Null cache adapter
 */
class NullCacheAdapter extends AbstractCacheAdapter
{
    public function __construct() {}

    public function contains($id, array $options = null)
    {
        return false;
    }

    public function delete($id, array $options = null)
    {
        return true;
    }

    public function fetch($id, array $options = null)
    {
        return false;
    }

    public function save($id, $data, $lifeTime = false, array $options = null)
    {
        return true;
    }
}
