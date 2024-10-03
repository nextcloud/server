<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

use Hybridauth\Data;

/**
 * HttpClient\Util home to a number of utility functions.
 */
class Util
{
    /**
     * Redirect handler.
     *
     * @var callable|null
     */
    protected static $redirectHandler;

    /**
     * Exit handler.
     *
     * @var callable|null
     */
    protected static $exitHandler;

    /**
     * Redirect to a given URL.
     *
     * In case your application need to perform certain required actions before Hybridauth redirect users
     * to IDPs websites, the default behaviour can be altered in one of two ways:
     *   If callable $redirectHandler is defined, it will be called instead.
     *   If callable $exitHandler is defined, it will be called instead of exit().
     *
     * @param string $url
     *
     * @return mixed
     */
    public static function redirect($url)
    {
        if (static::$redirectHandler) {
            return call_user_func(static::$redirectHandler, $url);
        }

        header(sprintf('Location: %s', $url));

        if (static::$exitHandler) {
            return call_user_func(static::$exitHandler);
        }

        exit(1);
    }

    /**
     * Redirect handler to which the regular redirect() will yield the action of redirecting users.
     *
     * @param callable $callback
     */
    public static function setRedirectHandler($callback)
    {
        self::$redirectHandler = $callback;
    }

    /**
     * Exit handler will be called instead of regular exit() when calling Util::redirect() method.
     *
     * @param callable $callback
     */
    public static function setExitHandler($callback)
    {
        self::$exitHandler = $callback;
    }

    /**
     * Returns the Current URL.
     *
     * @param bool $requestUri TRUE to use $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
     *
     * @return string
     */
    public static function getCurrentUrl($requestUri = false)
    {
        $collection = new Data\Collection($_SERVER);

        $protocol = 'http://';

        if (($collection->get('HTTPS') && $collection->get('HTTPS') !== 'off') ||
            $collection->get('HTTP_X_FORWARDED_PROTO') === 'https') {
            $protocol = 'https://';
        }

        return $protocol .
            $collection->get('HTTP_HOST') .
            $collection->get($requestUri ? 'REQUEST_URI' : 'PHP_SELF');
    }
}
