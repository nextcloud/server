<?php

/**
 * Pure-PHP implementation of AES.
 *
 * Uses mcrypt, if available/possible, and an internal implementation, otherwise.
 *
 * PHP version 5
 *
 * NOTE: Since AES.php is (for compatibility and phpseclib-historical reasons) virtually
 * just a wrapper to Rijndael.php you may consider using Rijndael.php instead of
 * to save one include_once().
 *
 * If {@link self::setKeyLength() setKeyLength()} isn't called, it'll be calculated from
 * {@link self::setKey() setKey()}.  ie. if the key is 128-bits, the key length will be 128-bits.  If it's 136-bits
 * it'll be null-padded to 192-bits and 192 bits will be the key length until {@link self::setKey() setKey()}
 * is called, again, at which point, it'll be recalculated.
 *
 * Since \phpseclib3\Crypt\AES extends \phpseclib3\Crypt\Rijndael, some functions are available to be called that, in the context of AES, don't
 * make a whole lot of sense.  {@link self::setBlockLength() setBlockLength()}, for instance.  Calling that function,
 * however possible, won't do anything (AES has a fixed block length whereas Rijndael has a variable one).
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $aes = new \phpseclib3\Crypt\AES('ctr');
 *
 *    $aes->setKey('abcdefghijklmnop');
 *
 *    $size = 10 * 1024;
 *    $plaintext = '';
 *    for ($i = 0; $i < $size; $i++) {
 *        $plaintext.= 'a';
 *    }
 *
 *    echo $aes->decrypt($aes->encrypt($plaintext));
 * ?>
 * </code>
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2008 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

/**
 * Pure-PHP implementation of AES.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class AES extends Rijndael
{
    /**
     * Dummy function
     *
     * Since \phpseclib3\Crypt\AES extends \phpseclib3\Crypt\Rijndael, this function is, technically, available, but it doesn't do anything.
     *
     * @see \phpseclib3\Crypt\Rijndael::setBlockLength()
     * @param int $length
     * @throws \BadMethodCallException anytime it's called
     */
    public function setBlockLength($length)
    {
        throw new \BadMethodCallException('The block length cannot be set for AES.');
    }

    /**
     * Sets the key length
     *
     * Valid key lengths are 128, 192, and 256.  Set the link to bool(false) to disable a fixed key length
     *
     * @see \phpseclib3\Crypt\Rijndael:setKeyLength()
     * @param int $length
     * @throws \LengthException if the key length isn't supported
     */
    public function setKeyLength($length)
    {
        switch ($length) {
            case 128:
            case 192:
            case 256:
                break;
            default:
                throw new \LengthException('Key of size ' . $length . ' not supported by this algorithm. Only keys of sizes 128, 192 or 256 supported');
        }
        parent::setKeyLength($length);
    }

    /**
     * Sets the key.
     *
     * Rijndael supports five different key lengths, AES only supports three.
     *
     * @see \phpseclib3\Crypt\Rijndael:setKey()
     * @see setKeyLength()
     * @param string $key
     * @throws \LengthException if the key length isn't supported
     */
    public function setKey($key)
    {
        switch (strlen($key)) {
            case 16:
            case 24:
            case 32:
                break;
            default:
                throw new \LengthException('Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes 16, 24 or 32 supported');
        }

        parent::setKey($key);
    }
}
