<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP implementation of RC4.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.
 *
 * PHP versions 4 and 5
 *
 * Useful resources are as follows:
 *
 *  - {@link http://www.mozilla.org/projects/security/pki/nss/draft-kaukonen-cipher-arcfour-03.txt ARCFOUR Algorithm}
 *  - {@link http://en.wikipedia.org/wiki/RC4 - Wikipedia: RC4}
 *
 * RC4 is also known as ARCFOUR or ARC4.  The reason is elaborated upon at Wikipedia.  This class is named RC4 and not
 * ARCFOUR or ARC4 because RC4 is how it is refered to in the SSH1 specification.
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include('Crypt/RC4.php');
 *
 *    $rc4 = new Crypt_RC4();
 *
 *    $rc4->setKey('abcdefgh');
 *
 *    $size = 10 * 1024;
 *    $plaintext = '';
 *    for ($i = 0; $i < $size; $i++) {
 *        $plaintext.= 'a';
 *    }
 *
 *    echo $rc4->decrypt($rc4->encrypt($plaintext));
 * ?>
 * </code>
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Crypt
 * @package    Crypt_RC4
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMVII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id: RC4.php,v 1.8 2009/06/09 04:00:38 terrafrost Exp $
 * @link       http://phpseclib.sourceforge.net
 */

/**#@+
 * @access private
 * @see Crypt_RC4::Crypt_RC4()
 */
/**
 * Toggles the internal implementation
 */
define('CRYPT_RC4_MODE_INTERNAL', 1);
/**
 * Toggles the mcrypt implementation
 */
define('CRYPT_RC4_MODE_MCRYPT', 2);
/**#@-*/

/**#@+
 * @access private
 * @see Crypt_RC4::_crypt()
 */
define('CRYPT_RC4_ENCRYPT', 0);
define('CRYPT_RC4_DECRYPT', 1);
/**#@-*/

/**
 * Pure-PHP implementation of RC4.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.1.0
 * @access  public
 * @package Crypt_RC4
 */
class Crypt_RC4 {
    /**
     * The Key
     *
     * @see Crypt_RC4::setKey()
     * @var String
     * @access private
     */
    var $key = "\0";

    /**
     * The Key Stream for encryption
     *
     * If CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT, this will be equal to the mcrypt object
     *
     * @see Crypt_RC4::setKey()
     * @var Array
     * @access private
     */
    var $encryptStream = false;

    /**
     * The Key Stream for decryption
     *
     * If CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT, this will be equal to the mcrypt object
     *
     * @see Crypt_RC4::setKey()
     * @var Array
     * @access private
     */
    var $decryptStream = false;

    /**
     * The $i and $j indexes for encryption
     *
     * @see Crypt_RC4::_crypt()
     * @var Integer
     * @access private
     */
    var $encryptIndex = 0;

    /**
     * The $i and $j indexes for decryption
     *
     * @see Crypt_RC4::_crypt()
     * @var Integer
     * @access private
     */
    var $decryptIndex = 0;

    /**
     * The Encryption Algorithm
     *
     * Only used if CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT.  Only possible values are MCRYPT_RC4 or MCRYPT_ARCFOUR.
     *
     * @see Crypt_RC4::Crypt_RC4()
     * @var Integer
     * @access private
     */
    var $mode;

    /**
     * Continuous Buffer status
     *
     * @see Crypt_RC4::enableContinuousBuffer()
     * @var Boolean
     * @access private
     */
    var $continuousBuffer = false;

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt extension should be used.
     *
     * @return Crypt_RC4
     * @access public
     */
    function Crypt_RC4()
    {
        if ( !defined('CRYPT_RC4_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && (defined('MCRYPT_ARCFOUR') || defined('MCRYPT_RC4')) && in_array('arcfour', mcrypt_list_algorithms()):
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_RC4_MODE ) {
            case CRYPT_RC4_MODE_MCRYPT:
                switch (true) {
                    case defined('MCRYPT_ARCFOUR'):
                        $this->mode = MCRYPT_ARCFOUR;
                        break;
                    case defined('MCRYPT_RC4');
                        $this->mode = MCRYPT_RC4;
                }
                $this->encryptStream = mcrypt_module_open($this->mode, '', MCRYPT_MODE_STREAM, '');
                $this->decryptStream = mcrypt_module_open($this->mode, '', MCRYPT_MODE_STREAM, '');

        }
    }

    /**
     * Sets the key.
     *
     * Keys can be between 1 and 256 bytes long.  If they are longer then 256 bytes, the first 256 bytes will
     * be used.  If no key is explicitly set, it'll be assumed to be a single null byte.
     *
     * @access public
     * @param String $key
     */
    function setKey($key)
    {
        $this->key = $key;

        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            mcrypt_generic_init($this->encryptStream, $this->key, '');
            mcrypt_generic_init($this->decryptStream, $this->key, '');
            return;
        }

        $keyLength = strlen($key);
        $keyStream = array();
        for ($i = 0; $i < 256; $i++) {
            $keyStream[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $keyStream[$i] + ord($key[$i % $keyLength])) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
        }

        $this->encryptIndex = $this->decryptIndex = array(0, 0);
        $this->encryptStream = $this->decryptStream = $keyStream;
    }

    /**
     * Sets the password.
     *
     * Depending on what $method is set to, setPassword()'s (optional) parameters are as follows:
     *     {@link http://en.wikipedia.org/wiki/PBKDF2 pbkdf2}:
     *         $hash, $salt, $count, $dkLen
     *
     * @param String $password
     * @param optional String $method
     * @access public
     */
    function setPassword($password, $method = 'pbkdf2')
    {
        $key = '';

        switch ($method) {
            default: // 'pbkdf2'
                list(, , $hash, $salt, $count) = func_get_args();
                if (!isset($hash)) {
                    $hash = 'sha1';
                }
                // WPA and WPA2 use the SSID as the salt
                if (!isset($salt)) {
                    $salt = 'phpseclib/salt';
                }
                // RFC2898#section-4.2 uses 1,000 iterations by default
                // WPA and WPA2 use 4,096.
                if (!isset($count)) {
                    $count = 1000;
                }
                if (!isset($dkLen)) {
                    $dkLen = 128;
                }

                if (!class_exists('Crypt_Hash')) {
                    require_once('Crypt/Hash.php');
                }

                $i = 1;
                while (strlen($key) < $dkLen) {
                    //$dk.= $this->_pbkdf($password, $salt, $count, $i++);
                    $hmac = new Crypt_Hash();
                    $hmac->setHash($hash);
                    $hmac->setKey($password);
                    $f = $u = $hmac->hash($salt . pack('N', $i++));
                    for ($j = 2; $j <= $count; $j++) {
                        $u = $hmac->hash($u);
                        $f^= $u;
                    }
                    $key.= $f;
                }
        }

        $this->setKey(substr($key, 0, $dkLen));
    }

    /**
     * Dummy function.
     *
     * Some protocols, such as WEP, prepend an "initialization vector" to the key, effectively creating a new key [1].
     * If you need to use an initialization vector in this manner, feel free to prepend it to the key, yourself, before
     * calling setKey().
     *
     * [1] WEP's initialization vectors (IV's) are used in a somewhat insecure way.  Since, in that protocol,
     * the IV's are relatively easy to predict, an attack described by
     * {@link http://www.drizzle.com/~aboba/IEEE/rc4_ksaproc.pdf Scott Fluhrer, Itsik Mantin, and Adi Shamir}
     * can be used to quickly guess at the rest of the key.  The following links elaborate:
     *
     * {@link http://www.rsa.com/rsalabs/node.asp?id=2009 http://www.rsa.com/rsalabs/node.asp?id=2009}
     * {@link http://en.wikipedia.org/wiki/Related_key_attack http://en.wikipedia.org/wiki/Related_key_attack}
     *
     * @param String $iv
     * @see Crypt_RC4::setKey()
     * @access public
     */
    function setIV($iv)
    {
    }

    /**
     * Encrypts a message.
     *
     * @see Crypt_RC4::_crypt()
     * @access public
     * @param String $plaintext
     */
    function encrypt($plaintext)
    {
        return $this->_crypt($plaintext, CRYPT_RC4_ENCRYPT);
    }

    /**
     * Decrypts a message.
     *
     * $this->decrypt($this->encrypt($plaintext)) == $this->encrypt($this->encrypt($plaintext)).
     * Atleast if the continuous buffer is disabled.
     *
     * @see Crypt_RC4::_crypt()
     * @access public
     * @param String $ciphertext
     */
    function decrypt($ciphertext)
    {
        return $this->_crypt($ciphertext, CRYPT_RC4_DECRYPT);
    }

    /**
     * Encrypts or decrypts a message.
     *
     * @see Crypt_RC4::encrypt()
     * @see Crypt_RC4::decrypt()
     * @access private
     * @param String $text
     * @param Integer $mode
     */
    function _crypt($text, $mode)
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $keyStream = $mode == CRYPT_RC4_ENCRYPT ? 'encryptStream' : 'decryptStream';

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->$keyStream, $this->key, '');
            }

            return mcrypt_generic($this->$keyStream, $text);
        }

        if ($this->encryptStream === false) {
            $this->setKey($this->key);
        }

        switch ($mode) {
            case CRYPT_RC4_ENCRYPT:
                $keyStream = $this->encryptStream;
                list($i, $j) = $this->encryptIndex;
                break;
            case CRYPT_RC4_DECRYPT:
                $keyStream = $this->decryptStream;
                list($i, $j) = $this->decryptIndex;
        }

        $newText = '';
        for ($k = 0; $k < strlen($text); $k++) {
            $i = ($i + 1) & 255;
            $j = ($j + $keyStream[$i]) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
            $temp = $keyStream[($keyStream[$i] + $keyStream[$j]) & 255];
            $newText.= chr(ord($text[$k]) ^ $temp);
        }

        if ($this->continuousBuffer) {
            switch ($mode) {
                case CRYPT_RC4_ENCRYPT:
                    $this->encryptStream = $keyStream;
                    $this->encryptIndex = array($i, $j);
                    break;
                case CRYPT_RC4_DECRYPT:
                    $this->decryptStream = $keyStream;
                    $this->decryptIndex = array($i, $j);
            }
        }

        return $newText;
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 16-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $rc4->encrypt(substr($plaintext, 0, 8));
     *    echo $rc4->encrypt(substr($plaintext, 8, 8));
     * </code>
     * <code>
     *    echo $rc4->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $rc4->encrypt(substr($plaintext, 0, 8));
     *    echo $rc4->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     * <code>
     *    echo $rc4->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     *
     * With the continuous buffer disabled, these would yield the same output.  With it enabled, they yield different
     * outputs.  The reason is due to the fact that the initialization vector's change after every encryption /
     * decryption round when the continuous buffer is enabled.  When it's disabled, they remain constant.
     *
     * Put another way, when the continuous buffer is enabled, the state of the Crypt_DES() object changes after each
     * encryption / decryption round, whereas otherwise, it'd remain constant.  For this reason, it's recommended that
     * continuous buffers not be used.  They do offer better security and are, in fact, sometimes required (SSH uses them),
     * however, they are also less intuitive and more likely to cause you problems.
     *
     * @see Crypt_RC4::disableContinuousBuffer()
     * @access public
     */
    function enableContinuousBuffer()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            mcrypt_generic_init($this->encryptStream, $this->key, '');
            mcrypt_generic_init($this->decryptStream, $this->key, '');
        }

        $this->continuousBuffer = true;
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see Crypt_RC4::enableContinuousBuffer()
     * @access public
     */
    function disableContinuousBuffer()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_INTERNAL ) {
            $this->encryptIndex = $this->decryptIndex = array(0, 0);
            $this->encryptStream = $this->decryptStream = false;
        }

        $this->continuousBuffer = false;
    }

    /**
     * Dummy function.
     *
     * Since RC4 is a stream cipher and not a block cipher, no padding is necessary.  The only reason this function is
     * included is so that you can switch between a block cipher and a stream cipher transparently.
     *
     * @see Crypt_RC4::disablePadding()
     * @access public
     */
    function enablePadding()
    {
    }

    /**
     * Dummy function.
     *
     * @see Crypt_RC4::enablePadding()
     * @access public
     */
    function disablePadding()
    {
    }

    /**
     * Class destructor.
     *
     * Will be called, automatically, if you're using PHP5.  If you're using PHP4, call it yourself.  Only really
     * needs to be called if mcrypt is being used.
     *
     * @access public
     */
    function __destruct()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $this->_closeMCrypt();
        }
    }

    /**
     * Properly close the MCrypt objects.
     *
     * @access prviate
     */
    function _closeMCrypt()
    {
        mcrypt_module_close($this->encryptStream);
        mcrypt_module_close($this->decryptStream);
    }
}

// vim: ts=4:sw=4:et:
// vim6: fdl=1: