<?php

namespace OpenCloud\Common\Request;

/**
 * The HttpRequest interface defines methods for wrapping CURL; this allows
 * those methods to be stubbed out for unit testing, thus allowing us to
 * test without actually making live calls.
 */
interface HttpRequestInterface
{
    
    public function SetOption($name, $value);

    public function setheaders($arr);

    public function SetHeader($header, $value);

    public function Execute();

    public function close();

}
