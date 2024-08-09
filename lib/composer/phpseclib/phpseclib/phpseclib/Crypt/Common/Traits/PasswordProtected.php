<?php

/**
 * Password Protected Trait for Private Keys
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Traits;

/**
 * Password Protected Trait for Private Keys
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
trait PasswordProtected
{
    /**
     * Password
     *
     * @var string|bool
     */
    private $password = false;

    /**
     * Sets the password
     *
     * Private keys can be encrypted with a password.  To unset the password, pass in the empty string or false.
     * Or rather, pass in $password such that empty($password) && !is_string($password) is true.
     *
     * @see self::createKey()
     * @see self::load()
     * @param string|bool $password
     */
    public function withPassword($password = false)
    {
        $new = clone $this;
        $new->password = $password;
        return $new;
    }
}
