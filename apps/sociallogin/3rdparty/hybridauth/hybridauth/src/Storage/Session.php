<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception\RuntimeException;

/**
 * Hybridauth storage manager
 */
class Session implements StorageInterface
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $storeNamespace = 'HYBRIDAUTH::STORAGE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * Initiate a new session
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (session_id()) {
            return;
        }

        if (headers_sent()) {
            // phpcs:ignore
            throw new RuntimeException('HTTP headers already sent to browser and Hybridauth won\'t be able to start/resume PHP session. To resolve this, session_start() must be called before outputing any data.');
        }

        if (!session_start()) {
            throw new RuntimeException('PHP session failed to start.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            $value = $_SESSION[$this->storeNamespace][$key];

            if (is_array($value) && array_key_exists('lateObject', $value)) {
                $value = unserialize($value['lateObject']);
            }

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (is_object($value)) {
            // We encapsulate as our classes may be defined after session is initialized.
            $value = ['lateObject' => serialize($value)];
        }

        $_SESSION[$this->storeNamespace][$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $_SESSION[$this->storeNamespace] = [];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            $tmp = $_SESSION[$this->storeNamespace];

            unset($tmp[$key]);

            $_SESSION[$this->storeNamespace] = $tmp;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace]) && count($_SESSION[$this->storeNamespace])) {
            $tmp = $_SESSION[$this->storeNamespace];

            foreach ($tmp as $k => $v) {
                if (strstr($k, $key)) {
                    unset($tmp[$k]);
                }
            }

            $_SESSION[$this->storeNamespace] = $tmp;
        }
    }
}
