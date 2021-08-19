<?php
namespace Aws\Crypto\Polyfill;

/**
 * Class Key
 *
 * Wraps a string to keep it hidden from stack traces.
 *
 * @package Aws\Crypto\Polyfill
 */
class Key
{
    /**
     * @var string $internalString
     */
    private $internalString;

    /**
     * Hide contents of 
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [];
    }

    /**
     * Key constructor.
     * @param string $str
     */
    public function __construct($str)
    {
        $this->internalString = $str;
    }

    /**
     * Defense in depth:
     *
     * PHP 7.2 includes the Sodium cryptography library, which (among other things)
     * exposes a function called sodium_memzero() that we can use to zero-fill strings
     * to minimize the risk of sensitive cryptographic materials persisting in memory.
     *
     * If this function is not available, we XOR the string in-place with itself as a
     * best-effort attempt.
     */
    public function __destruct()
    {
        if (extension_loaded('sodium') && function_exists('sodium_memzero')) {
            try {
                \sodium_memzero($this->internalString);
            } catch (\SodiumException $ex) {
                // This is a best effort, but does not provide the same guarantees as sodium_memzero():
                $this->internalString ^= $this->internalString;
            }
        }
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->internalString;
    }

    /**
     * @return int
     */
    public function length()
    {
        if (\is_callable('\\mb_strlen')) {
            return (int) \mb_strlen($this->internalString, '8bit');
        }
        return (int) \strlen($this->internalString);
    }
}
