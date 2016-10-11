<?php

namespace Guzzle\Iterator;

/**
 * Proxies missing method calls to the innermost iterator
 */
class MethodProxyIterator extends \IteratorIterator
{
    /**
     * Proxy method calls to the wrapped iterator
     *
     * @param string $name Name of the method
     * @param array  $args Arguments to proxy
     *
     * @return mixed
     */
    public function __call($name, array $args)
    {
        $i = $this->getInnerIterator();
        while ($i instanceof \OuterIterator) {
            $i = $i->getInnerIterator();
        }

        return call_user_func_array(array($i, $name), $args);
    }
}
