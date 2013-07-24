<?php

namespace Guzzle\Http\Message\Header;

/**
 * Interface for creating headers
 */
interface HeaderFactoryInterface
{
    /**
     * Create a header from a header name and a single value
     *
     * @param string $header Name of the header to create
     * @param string $value  Value to set on the header
     *
     * @return HeaderInterface
     */
    public function createHeader($header, $value = null);
}
