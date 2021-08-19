<?php

/**
 * Base Class for all \phpseclib\Crypt\* cipher classes
 *
 * PHP version 5
 *
 * Internally for phpseclib developers:
 *  If you plan to add a new cipher class, please note following rules:
 *
 *  - The new \phpseclib\Crypt\* cipher class should extend \phpseclib\Crypt\Base
 *
 *  - Following methods are then required to be overridden/overloaded:
 *
 *    - _encryptBlock()
 *
 *    - _decryptBlock()
 *
 *    - _setupKey()
 *
 *  - All other methods are optional to be overridden/overloaded
 *
 *  - Look at the source code of the current ciphers how they extend \phpseclib\Crypt\Base
 *    and take one of them as a start up for the new cipher class.
 *
 *  - Please read all the other comments/notes/hints here also for each class var/method
 *
 * @category  Crypt
 * @package   Base
 * @author    Jim Wigginton <terrafrost@php.net>
 * @author    Hans-Juergen Petrich <petrich@tronic-media.com>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\Crypt;

/**
 * Base Class for all \phpseclib\Crypt\* cipher classes
 *
 * @package Base
 * @author  Jim Wigginton <terrafrost@php.net>
 * @author  Hans-Juergen Petrich <petrich@tronic-media.com>
 */
abstract class Base
{
    /**#@+
     * @access public
     * @see \phpseclib\Crypt\Base::encrypt()
     * @see \phpseclib\Crypt\Base::decrypt()
     */
    /**
     * Encrypt / decrypt using the Counter mode.
     *
     * Set to -1 since that's what Crypt/Random.php uses to index the CTR mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Counter_.28CTR.29
     */
    const MODE_CTR = -1;
    /**
     * Encrypt / decrypt using the Electronic Code Book mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Electronic_codebook_.28ECB.29
     */
    const MODE_ECB = 1;
    /**
     * Encrypt / decrypt using the Code Book Chaining mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher-block_chaining_.28CBC.29
     */
    const MODE_CBC = 2;
    /**
     * Encrypt / decrypt using the Cipher Feedback mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher_feedback_.28CFB.29
     */
    const MODE_CFB = 3;
    /**
     * Encrypt / decrypt using the Cipher Feedback mode (8bit)
     */
    const MODE_CFB8 = 38;
    /**
     * Encrypt / decrypt using the Output Feedback mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Output_feedback_.28OFB.29
     */
    const MODE_OFB = 4;
    /**
     * Encrypt / decrypt using streaming mode.
     */
    const MODE_STREAM = 5;
    /**#@-*/

    /**
     * Whirlpool available flag
     *
     * @see \phpseclib\Crypt\Base::_hashInlineCryptFunction()
     * @var bool
     * @access private
     */
    static $WHIRLPOOL_AVAILABLE;

    /**#@+
     * @access private
     * @see \phpseclib\Crypt\Base::__construct()
     */
    /**
     * Base value for the internal implementation $engine switch
     */
    const ENGINE_INTERNAL = 1;
    /**
     * Base value for the mcrypt implementation $engine switch
     */
    const ENGINE_MCRYPT = 2;
    /**
     * Base value for the mcrypt implementation $engine switch
     */
    const ENGINE_OPENSSL = 3;
    /**#@-*/

    /**
     * The Encryption Mode
     *
     * @see self::__construct()
     * @var int
     * @access private
     */
    var $mode;

    /**
     * The Block Length of the block cipher
     *
     * @var int
     * @access private
     */
    var $block_size = 16;

    /**
     * The Key
     *
     * @see self::setKey()
     * @var string
     * @access private
     */
    var $key = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * The Initialization Vector
     *
     * @see self::setIV()
     * @var string
     * @access private
     */
    var $iv;

    /**
     * A "sliding" Initialization Vector
     *
     * @see self::enableContinuousBuffer()
     * @see self::_clearBuffers()
     * @var string
     * @access private
     */
    var $encryptIV;

    /**
     * A "sliding" Initialization Vector
     *
     * @see self::enableContinuousBuffer()
     * @see self::_clearBuffers()
     * @var string
     * @access private
     */
    var $decryptIV;

    /**
     * Continuous Buffer status
     *
     * @see self::enableContinuousBuffer()
     * @var bool
     * @access private
     */
    var $continuousBuffer = false;

    /**
     * Encryption buffer for CTR, OFB and CFB modes
     *
     * @see self::encrypt()
     * @see self::_clearBuffers()
     * @var array
     * @access private
     */
    var $enbuffer;

    /**
     * Decryption buffer for CTR, OFB and CFB modes
     *
     * @see self::decrypt()
     * @see self::_clearBuffers()
     * @var array
     * @access private
     */
    var $debuffer;

    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see self::encrypt()
     * @var resource
     * @access private
     */
    var $enmcrypt;

    /**
     * mcrypt resource for decryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see self::decrypt()
     * @var resource
     * @access private
     */
    var $demcrypt;

    /**
     * Does the enmcrypt resource need to be (re)initialized?
     *
     * @see \phpseclib\Crypt\Twofish::setKey()
     * @see \phpseclib\Crypt\Twofish::setIV()
     * @var bool
     * @access private
     */
    var $enchanged = true;

    /**
     * Does the demcrypt resource need to be (re)initialized?
     *
     * @see \phpseclib\Crypt\Twofish::setKey()
     * @see \phpseclib\Crypt\Twofish::setIV()
     * @var bool
     * @access private
     */
    var $dechanged = true;

    /**
     * mcrypt resource for CFB mode
     *
     * mcrypt's CFB mode, in (and only in) buffered context,
     * is broken, so phpseclib implements the CFB mode by it self,
     * even when the mcrypt php extension is available.
     *
     * In order to do the CFB-mode work (fast) phpseclib
     * use a separate ECB-mode mcrypt resource.
     *
     * @link http://phpseclib.sourceforge.net/cfb-demo.phps
     * @see self::encrypt()
     * @see self::decrypt()
     * @see self::_setupMcrypt()
     * @var resource
     * @access private
     */
    var $ecb;

    /**
     * Optimizing value while CFB-encrypting
     *
     * Only relevant if $continuousBuffer enabled
     * and $engine == self::ENGINE_MCRYPT
     *
     * It's faster to re-init $enmcrypt if
     * $buffer bytes > $cfb_init_len than
     * using the $ecb resource furthermore.
     *
     * This value depends of the chosen cipher
     * and the time it would be needed for it's
     * initialization [by mcrypt_generic_init()]
     * which, typically, depends on the complexity
     * on its internaly Key-expanding algorithm.
     *
     * @see self::encrypt()
     * @var int
     * @access private
     */
    var $cfb_init_len = 600;

    /**
     * Does internal cipher state need to be (re)initialized?
     *
     * @see self::setKey()
     * @see self::setIV()
     * @see self::disableContinuousBuffer()
     * @var bool
     * @access private
     */
    var $changed = true;

    /**
     * Padding status
     *
     * @see self::enablePadding()
     * @var bool
     * @access private
     */
    var $padding = true;

    /**
     * Is the mode one that is paddable?
     *
     * @see self::__construct()
     * @var bool
     * @access private
     */
    var $paddable = false;

    /**
     * Holds which crypt engine internaly should be use,
     * which will be determined automatically on __construct()
     *
     * Currently available $engines are:
     * - self::ENGINE_OPENSSL  (very fast, php-extension: openssl, extension_loaded('openssl') required)
     * - self::ENGINE_MCRYPT   (fast, php-extension: mcrypt, extension_loaded('mcrypt') required)
     * - self::ENGINE_INTERNAL (slower, pure php-engine, no php-extension required)
     *
     * @see self::_setEngine()
     * @see self::encrypt()
     * @see self::decrypt()
     * @var int
     * @access private
     */
    var $engine;

    /**
     * Holds the preferred crypt engine
     *
     * @see self::_setEngine()
     * @see self::setPreferredEngine()
     * @var int
     * @access private
     */
    var $preferredEngine;

    /**
     * The mcrypt specific name of the cipher
     *
     * Only used if $engine == self::ENGINE_MCRYPT
     *
     * @link http://www.php.net/mcrypt_module_open
     * @link http://www.php.net/mcrypt_list_algorithms
     * @see self::_setupMcrypt()
     * @var string
     * @access private
     */
    var $cipher_name_mcrypt;

    /**
     * The openssl specific name of the cipher
     *
     * Only used if $engine == self::ENGINE_OPENSSL
     *
     * @link http://www.php.net/openssl-get-cipher-methods
     * @var string
     * @access private
     */
    var $cipher_name_openssl;

    /**
     * The openssl specific name of the cipher in ECB mode
     *
     * If OpenSSL does not support the mode we're trying to use (CTR)
     * it can still be emulated with ECB mode.
     *
     * @link http://www.php.net/openssl-get-cipher-methods
     * @var string
     * @access private
     */
    var $cipher_name_openssl_ecb;

    /**
     * The default salt used by setPassword()
     *
     * @see self::setPassword()
     * @var string
     * @access private
     */
    var $password_default_salt = 'phpseclib/salt';

    /**
     * The name of the performance-optimized callback function
     *
     * Used by encrypt() / decrypt()
     * only if $engine == self::ENGINE_INTERNAL
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @see self::_setupInlineCrypt()
     * @see self::$use_inline_crypt
     * @var Callback
     * @access private
     */
    var $inline_crypt;

    /**
     * Holds whether performance-optimized $inline_crypt() can/should be used.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @see self::inline_crypt
     * @var mixed
     * @access private
     */
    var $use_inline_crypt = true;

    /**
     * If OpenSSL can be used in ECB but not in CTR we can emulate CTR
     *
     * @see self::_openssl_ctr_process()
     * @var bool
     * @access private
     */
    var $openssl_emulate_ctr = false;

    /**
     * Determines what options are passed to openssl_encrypt/decrypt
     *
     * @see self::isValidEngine()
     * @var mixed
     * @access private
     */
    var $openssl_options;

    /**
     * Has the key length explicitly been set or should it be derived from the key, itself?
     *
     * @see self::setKeyLength()
     * @var bool
     * @access private
     */
    var $explicit_key_length = false;

    /**
     * Don't truncate / null pad key
     *
     * @see self::_clearBuffers()
     * @var bool
     * @access private
     */
    var $skip_key_adjustment = false;

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt extension should be used.
     *
     * $mode could be:
     *
     * - self::MODE_ECB
     *
     * - self::MODE_CBC
     *
     * - self::MODE_CTR
     *
     * - self::MODE_CFB
     *
     * - self::MODE_OFB
     *
     * If not explicitly set, self::MODE_CBC will be used.
     *
     * @param int $mode
     * @access public
     */
    function __construct($mode = self::MODE_CBC)
    {
        // $mode dependent settings
        switch ($mode) {
            case self::MODE_ECB:
                $this->paddable = true;
                $this->mode = self::MODE_ECB;
                break;
            case self::MODE_CTR:
            case self::MODE_CFB:
            case self::MODE_CFB8:
            case self::MODE_OFB:
            case self::MODE_STREAM:
                $this->mode = $mode;
                break;
            case self::MODE_CBC:
            default:
                $this->paddable = true;
                $this->mode = self::MODE_CBC;
        }

        $this->_setEngine();
    }

    /**
     * Sets the initialization vector. (optional)
     *
     * SetIV is not required when self::MODE_ECB (or ie for AES: \phpseclib\Crypt\AES::MODE_ECB) is being used.  If not explicitly set, it'll be assumed
     * to be all zero's.
     *
     * @access public
     * @param string $iv
     * @internal Can be overwritten by a sub class, but does not have to be
     */
    function setIV($iv)
    {
        if ($this->mode == self::MODE_ECB) {
            return;
        }

        $this->iv = $iv;
        $this->changed = true;
    }

    /**
     * Sets the key length.
     *
     * Keys with explicitly set lengths need to be treated accordingly
     *
     * @access public
     * @param int $length
     */
    function setKeyLength($length)
    {
        $this->explicit_key_length = true;
        $this->changed = true;
        $this->_setEngine();
    }

    /**
     * Returns the current key length in bits
     *
     * @access public
     * @return int
     */
    function getKeyLength()
    {
        return $this->key_length << 3;
    }

    /**
     * Returns the current block length in bits
     *
     * @access public
     * @return int
     */
    function getBlockLength()
    {
        return $this->block_size << 3;
    }

    /**
     * Sets the key.
     *
     * The min/max length(s) of the key depends on the cipher which is used.
     * If the key not fits the length(s) of the cipher it will paded with null bytes
     * up to the closest valid key length.  If the key is more than max length,
     * we trim the excess bits.
     *
     * If the key is not explicitly set, it'll be assumed to be all null bytes.
     *
     * @access public
     * @param string $key
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function setKey($key)
    {
        if (!$this->explicit_key_length) {
            $this->setKeyLength(strlen($key) << 3);
            $this->explicit_key_length = false;
        }

        $this->key = $key;
        $this->changed = true;
        $this->_setEngine();
    }

    /**
     * Sets the password.
     *
     * Depending on what $method is set to, setPassword()'s (optional) parameters are as follows:
     *     {@link http://en.wikipedia.org/wiki/PBKDF2 pbkdf2} or pbkdf1:
     *         $hash, $salt, $count, $dkLen
     *
     *         Where $hash (default = sha1) currently supports the following hashes: see: Crypt/Hash.php
     *
     * @see Crypt/Hash.php
     * @param string $password
     * @param string $method
     * @return bool
     * @access public
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function setPassword($password, $method = 'pbkdf2')
    {
        $key = '';

        switch ($method) {
            default: // 'pbkdf2' or 'pbkdf1'
                $func_args = func_get_args();

                // Hash function
                $hash = isset($func_args[2]) ? $func_args[2] : 'sha1';

                // WPA and WPA2 use the SSID as the salt
                $salt = isset($func_args[3]) ? $func_args[3] : $this->password_default_salt;

                // RFC2898#section-4.2 uses 1,000 iterations by default
                // WPA and WPA2 use 4,096.
                $count = isset($func_args[4]) ? $func_args[4] : 1000;

                // Keylength
                if (isset($func_args[5])) {
                    $dkLen = $func_args[5];
                } else {
                    $dkLen = $method == 'pbkdf1' ? 2 * $this->key_length : $this->key_length;
                }

                switch (true) {
                    case $method == 'pbkdf1':
                        $hashObj = new Hash();
                        $hashObj->setHash($hash);
                        if ($dkLen > $hashObj->getLength()) {
                            user_error('Derived key too long');
                            return false;
                        }
                        $t = $password . $salt;
                        for ($i = 0; $i < $count; ++$i) {
                            $t = $hashObj->hash($t);
                        }
                        $key = substr($t, 0, $dkLen);

                        $this->setKey(substr($key, 0, $dkLen >> 1));
                        $this->setIV(substr($key, $dkLen >> 1));

                        return true;
                    // Determining if php[>=5.5.0]'s hash_pbkdf2() function avail- and useable
                    case !function_exists('hash_pbkdf2'):
                    case !function_exists('hash_algos'):
                    case !in_array($hash, hash_algos()):
                        $i = 1;
                        $hmac = new Hash();
                        $hmac->setHash($hash);
                        $hmac->setKey($password);
                        while (strlen($key) < $dkLen) {
                            $f = $u = $hmac->hash($salt . pack('N', $i++));
                            for ($j = 2; $j <= $count; ++$j) {
                                $u = $hmac->hash($u);
                                $f^= $u;
                            }
                            $key.= $f;
                        }
                        $key = substr($key, 0, $dkLen);
                        break;
                    default:
                        $key = hash_pbkdf2($hash, $password, $salt, $count, $dkLen, true);
                }
        }

        $this->setKey($key);

        return true;
    }

    /**
     * Encrypts a message.
     *
     * $plaintext will be padded with additional bytes such that it's length is a multiple of the block size. Other cipher
     * implementations may or may not pad in the same manner.  Other common approaches to padding and the reasons why it's
     * necessary are discussed in the following
     * URL:
     *
     * {@link http://www.di-mgt.com.au/cryptopad.html http://www.di-mgt.com.au/cryptopad.html}
     *
     * An alternative to padding is to, separately, send the length of the file.  This is what SSH, in fact, does.
     * strlen($plaintext) will still need to be a multiple of the block size, however, arbitrary values can be added to make it that
     * length.
     *
     * @see self::decrypt()
     * @access public
     * @param string $plaintext
     * @return string $ciphertext
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function encrypt($plaintext)
    {
        if ($this->paddable) {
            $plaintext = $this->_pad($plaintext);
        }

        if ($this->engine === self::ENGINE_OPENSSL) {
            if ($this->changed) {
                $this->_clearBuffers();
                $this->changed = false;
            }
            switch ($this->mode) {
                case self::MODE_STREAM:
                    return openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, $this->openssl_options);
                case self::MODE_ECB:
                    $result = @openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, $this->openssl_options);
                    return !defined('OPENSSL_RAW_DATA') ? substr($result, 0, -$this->block_size) : $result;
                case self::MODE_CBC:
                    $result = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->encryptIV);
                    if (!defined('OPENSSL_RAW_DATA')) {
                        $result = substr($result, 0, -$this->block_size);
                    }
                    if ($this->continuousBuffer) {
                        $this->encryptIV = substr($result, -$this->block_size);
                    }
                    return $result;
                case self::MODE_CTR:
                    return $this->_openssl_ctr_process($plaintext, $this->encryptIV, $this->enbuffer);
                case self::MODE_CFB:
                    // cfb loosely routines inspired by openssl's:
                    // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                    $ciphertext = '';
                    if ($this->continuousBuffer) {
                        $iv = &$this->encryptIV;
                        $pos = &$this->enbuffer['pos'];
                    } else {
                        $iv = $this->encryptIV;
                        $pos = 0;
                    }
                    $len = strlen($plaintext);
                    $i = 0;
                    if ($pos) {
                        $orig_pos = $pos;
                        $max = $this->block_size - $pos;
                        if ($len >= $max) {
                            $i = $max;
                            $len-= $max;
                            $pos = 0;
                        } else {
                            $i = $len;
                            $pos+= $len;
                            $len = 0;
                        }
                        // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                        $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                        $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                        $plaintext = substr($plaintext, $i);
                    }

                    $overflow = $len % $this->block_size;

                    if ($overflow) {
                        $ciphertext.= openssl_encrypt(substr($plaintext, 0, -$overflow) . str_repeat("\0", $this->block_size), $this->cipher_name_openssl, $this->key, $this->openssl_options, $iv);
                        $iv = $this->_string_pop($ciphertext, $this->block_size);

                        $size = $len - $overflow;
                        $block = $iv ^ substr($plaintext, -$overflow);
                        $iv = substr_replace($iv, $block, 0, $overflow);
                        $ciphertext.= $block;
                        $pos = $overflow;
                    } elseif ($len) {
                        $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $iv);
                        $iv = substr($ciphertext, -$this->block_size);
                    }

                    return $ciphertext;
                case self::MODE_CFB8:
                    $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->encryptIV);
                    if ($this->continuousBuffer) {
                        if (($len = strlen($ciphertext)) >= $this->block_size) {
                            $this->encryptIV = substr($ciphertext, -$this->block_size);
                        } else {
                            $this->encryptIV = substr($this->encryptIV, $len - $this->block_size) . substr($ciphertext, -$len);
                        }
                    }
                    return $ciphertext;
                case self::MODE_OFB:
                    return $this->_openssl_ofb_process($plaintext, $this->encryptIV, $this->enbuffer);
            }
        }

        if ($this->engine === self::ENGINE_MCRYPT) {
            set_error_handler(array($this, 'do_nothing'));

            if ($this->changed) {
                $this->_setupMcrypt();
                $this->changed = false;
            }
            if ($this->enchanged) {
                mcrypt_generic_init($this->enmcrypt, $this->key, $this->encryptIV);
                $this->enchanged = false;
            }

            // re: {@link http://phpseclib.sourceforge.net/cfb-demo.phps}
            // using mcrypt's default handing of CFB the above would output two different things.  using phpseclib's
            // rewritten CFB implementation the above outputs the same thing twice.
            if ($this->mode == self::MODE_CFB && $this->continuousBuffer) {
                $block_size = $this->block_size;
                $iv = &$this->encryptIV;
                $pos = &$this->enbuffer['pos'];
                $len = strlen($plaintext);
                $ciphertext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                    $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                    $this->enbuffer['enmcrypt_init'] = true;
                }
                if ($len >= $block_size) {
                    if ($this->enbuffer['enmcrypt_init'] === false || $len > $this->cfb_init_len) {
                        if ($this->enbuffer['enmcrypt_init'] === true) {
                            mcrypt_generic_init($this->enmcrypt, $this->key, $iv);
                            $this->enbuffer['enmcrypt_init'] = false;
                        }
                        $ciphertext.= mcrypt_generic($this->enmcrypt, substr($plaintext, $i, $len - $len % $block_size));
                        $iv = substr($ciphertext, -$block_size);
                        $len%= $block_size;
                    } else {
                        while ($len >= $block_size) {
                            $iv = mcrypt_generic($this->ecb, $iv) ^ substr($plaintext, $i, $block_size);
                            $ciphertext.= $iv;
                            $len-= $block_size;
                            $i+= $block_size;
                        }
                    }
                }

                if ($len) {
                    $iv = mcrypt_generic($this->ecb, $iv);
                    $block = $iv ^ substr($plaintext, -$len);
                    $iv = substr_replace($iv, $block, 0, $len);
                    $ciphertext.= $block;
                    $pos = $len;
                }

                restore_error_handler();

                return $ciphertext;
            }

            $ciphertext = mcrypt_generic($this->enmcrypt, $plaintext);

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->enmcrypt, $this->key, $this->encryptIV);
            }

            restore_error_handler();

            return $ciphertext;
        }

        if ($this->changed) {
            $this->_setup();
            $this->changed = false;
        }
        if ($this->use_inline_crypt) {
            $inline = $this->inline_crypt;
            return $inline('encrypt', $this, $plaintext);
        }

        $buffer = &$this->enbuffer;
        $block_size = $this->block_size;
        $ciphertext = '';
        switch ($this->mode) {
            case self::MODE_ECB:
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $ciphertext.= $this->_encryptBlock(substr($plaintext, $i, $block_size));
                }
                break;
            case self::MODE_CBC:
                $xor = $this->encryptIV;
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    $block = $this->_encryptBlock($block ^ $xor);
                    $xor = $block;
                    $ciphertext.= $block;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                }
                break;
            case self::MODE_CTR:
                $xor = $this->encryptIV;
                if (strlen($buffer['ciphertext'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['ciphertext'])) {
                            $buffer['ciphertext'].= $this->_encryptBlock($xor);
                        }
                        $this->_increment_str($xor);
                        $key = $this->_string_shift($buffer['ciphertext'], $block_size);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        $key = $this->_encryptBlock($xor);
                        $this->_increment_str($xor);
                        $ciphertext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) % $block_size) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case self::MODE_CFB:
                // cfb loosely routines inspired by openssl's:
                // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                if ($this->continuousBuffer) {
                    $iv = &$this->encryptIV;
                    $pos = &$buffer['pos'];
                } else {
                    $iv = $this->encryptIV;
                    $pos = 0;
                }
                $len = strlen($plaintext);
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                    $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                }
                while ($len >= $block_size) {
                    $iv = $this->_encryptBlock($iv) ^ substr($plaintext, $i, $block_size);
                    $ciphertext.= $iv;
                    $len-= $block_size;
                    $i+= $block_size;
                }
                if ($len) {
                    $iv = $this->_encryptBlock($iv);
                    $block = $iv ^ substr($plaintext, $i);
                    $iv = substr_replace($iv, $block, 0, $len);
                    $ciphertext.= $block;
                    $pos = $len;
                }
                break;
            case self::MODE_CFB8:
                $ciphertext = '';
                $len = strlen($plaintext);
                $iv = $this->encryptIV;

                for ($i = 0; $i < $len; ++$i) {
                    $ciphertext .= ($c = $plaintext[$i] ^ $this->_encryptBlock($iv));
                    $iv = substr($iv, 1) . $c;
                }

                if ($this->continuousBuffer) {
                    if ($len >= $block_size) {
                        $this->encryptIV = substr($ciphertext, -$block_size);
                    } else {
                        $this->encryptIV = substr($this->encryptIV, $len - $block_size) . substr($ciphertext, -$len);
                    }
                }
                break;
            case self::MODE_OFB:
                $xor = $this->encryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['xor'])) {
                            $xor = $this->_encryptBlock($xor);
                            $buffer['xor'].= $xor;
                        }
                        $key = $this->_string_shift($buffer['xor'], $block_size);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $xor = $this->_encryptBlock($xor);
                        $ciphertext.= substr($plaintext, $i, $block_size) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) % $block_size) {
                        $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
                break;
            case self::MODE_STREAM:
                $ciphertext = $this->_encryptBlock($plaintext);
                break;
        }

        return $ciphertext;
    }

    /**
     * Decrypts a message.
     *
     * If strlen($ciphertext) is not a multiple of the block size, null bytes will be added to the end of the string until
     * it is.
     *
     * @see self::encrypt()
     * @access public
     * @param string $ciphertext
     * @return string $plaintext
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function decrypt($ciphertext)
    {
        if ($this->paddable) {
            // we pad with chr(0) since that's what mcrypt_generic does.  to quote from {@link http://www.php.net/function.mcrypt-generic}:
            // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
            $ciphertext = str_pad($ciphertext, strlen($ciphertext) + ($this->block_size - strlen($ciphertext) % $this->block_size) % $this->block_size, chr(0));
        }

        if ($this->engine === self::ENGINE_OPENSSL) {
            if ($this->changed) {
                $this->_clearBuffers();
                $this->changed = false;
            }
            switch ($this->mode) {
                case self::MODE_STREAM:
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, $this->openssl_options);
                    break;
                case self::MODE_ECB:
                    if (!defined('OPENSSL_RAW_DATA')) {
                        $ciphertext.= @openssl_encrypt('', $this->cipher_name_openssl_ecb, $this->key, true);
                    }
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, $this->openssl_options);
                    break;
                case self::MODE_CBC:
                    if (!defined('OPENSSL_RAW_DATA')) {
                        $padding = str_repeat(chr($this->block_size), $this->block_size) ^ substr($ciphertext, -$this->block_size);
                        $ciphertext.= substr(@openssl_encrypt($padding, $this->cipher_name_openssl_ecb, $this->key, true), 0, $this->block_size);
                        $offset = 2 * $this->block_size;
                    } else {
                        $offset = $this->block_size;
                    }
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->decryptIV);
                    if ($this->continuousBuffer) {
                        $this->decryptIV = substr($ciphertext, -$offset, $this->block_size);
                    }
                    break;
                case self::MODE_CTR:
                    $plaintext = $this->_openssl_ctr_process($ciphertext, $this->decryptIV, $this->debuffer);
                    break;
                case self::MODE_CFB:
                    // cfb loosely routines inspired by openssl's:
                    // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                    $plaintext = '';
                    if ($this->continuousBuffer) {
                        $iv = &$this->decryptIV;
                        $pos = &$this->buffer['pos'];
                    } else {
                        $iv = $this->decryptIV;
                        $pos = 0;
                    }
                    $len = strlen($ciphertext);
                    $i = 0;
                    if ($pos) {
                        $orig_pos = $pos;
                        $max = $this->block_size - $pos;
                        if ($len >= $max) {
                            $i = $max;
                            $len-= $max;
                            $pos = 0;
                        } else {
                            $i = $len;
                            $pos+= $len;
                            $len = 0;
                        }
                        // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $this->blocksize
                        $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                        $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                        $ciphertext = substr($ciphertext, $i);
                    }
                    $overflow = $len % $this->block_size;
                    if ($overflow) {
                        $plaintext.= openssl_decrypt(substr($ciphertext, 0, -$overflow), $this->cipher_name_openssl, $this->key, $this->openssl_options, $iv);
                        if ($len - $overflow) {
                            $iv = substr($ciphertext, -$overflow - $this->block_size, -$overflow);
                        }
                        $iv = openssl_encrypt(str_repeat("\0", $this->block_size), $this->cipher_name_openssl, $this->key, $this->openssl_options, $iv);
                        $plaintext.= $iv ^ substr($ciphertext, -$overflow);
                        $iv = substr_replace($iv, substr($ciphertext, -$overflow), 0, $overflow);
                        $pos = $overflow;
                    } elseif ($len) {
                        $plaintext.= openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $iv);
                        $iv = substr($ciphertext, -$this->block_size);
                    }
                    break;
                case self::MODE_CFB8:
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, $this->openssl_options, $this->decryptIV);
                    if ($this->continuousBuffer) {
                        if (($len = strlen($ciphertext)) >= $this->block_size) {
                            $this->decryptIV = substr($ciphertext, -$this->block_size);
                        } else {
                            $this->decryptIV = substr($this->decryptIV, $len - $this->block_size) . substr($ciphertext, -$len);
                        }
                    }
                    break;
                case self::MODE_OFB:
                    $plaintext = $this->_openssl_ofb_process($ciphertext, $this->decryptIV, $this->debuffer);
            }

            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        if ($this->engine === self::ENGINE_MCRYPT) {
            set_error_handler(array($this, 'do_nothing'));
            $block_size = $this->block_size;
            if ($this->changed) {
                $this->_setupMcrypt();
                $this->changed = false;
            }
            if ($this->dechanged) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->decryptIV);
                $this->dechanged = false;
            }

            if ($this->mode == self::MODE_CFB && $this->continuousBuffer) {
                $iv = &$this->decryptIV;
                $pos = &$this->debuffer['pos'];
                $len = strlen($ciphertext);
                $plaintext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                if ($len >= $block_size) {
                    $cb = substr($ciphertext, $i, $len - $len % $block_size);
                    $plaintext.= mcrypt_generic($this->ecb, $iv . $cb) ^ $cb;
                    $iv = substr($cb, -$block_size);
                    $len%= $block_size;
                }
                if ($len) {
                    $iv = mcrypt_generic($this->ecb, $iv);
                    $plaintext.= $iv ^ substr($ciphertext, -$len);
                    $iv = substr_replace($iv, substr($ciphertext, -$len), 0, $len);
                    $pos = $len;
                }

                restore_error_handler();

                return $plaintext;
            }

            $plaintext = mdecrypt_generic($this->demcrypt, $ciphertext);

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->decryptIV);
            }

            restore_error_handler();

            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        if ($this->changed) {
            $this->_setup();
            $this->changed = false;
        }
        if ($this->use_inline_crypt) {
            $inline = $this->inline_crypt;
            return $inline('decrypt', $this, $ciphertext);
        }

        $block_size = $this->block_size;

        $buffer = &$this->debuffer;
        $plaintext = '';
        switch ($this->mode) {
            case self::MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                    $plaintext.= $this->_decryptBlock(substr($ciphertext, $i, $block_size));
                }
                break;
            case self::MODE_CBC:
                $xor = $this->decryptIV;
                for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                    $block = substr($ciphertext, $i, $block_size);
                    $plaintext.= $this->_decryptBlock($block) ^ $xor;
                    $xor = $block;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                }
                break;
            case self::MODE_CTR:
                $xor = $this->decryptIV;
                if (strlen($buffer['ciphertext'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['ciphertext'])) {
                            $buffer['ciphertext'].= $this->_encryptBlock($xor);
                            $this->_increment_str($xor);
                        }
                        $key = $this->_string_shift($buffer['ciphertext'], $block_size);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        $key = $this->_encryptBlock($xor);
                        $this->_increment_str($xor);
                        $plaintext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % $block_size) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case self::MODE_CFB:
                if ($this->continuousBuffer) {
                    $iv = &$this->decryptIV;
                    $pos = &$buffer['pos'];
                } else {
                    $iv = $this->decryptIV;
                    $pos = 0;
                }
                $len = strlen($ciphertext);
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                while ($len >= $block_size) {
                    $iv = $this->_encryptBlock($iv);
                    $cb = substr($ciphertext, $i, $block_size);
                    $plaintext.= $iv ^ $cb;
                    $iv = $cb;
                    $len-= $block_size;
                    $i+= $block_size;
                }
                if ($len) {
                    $iv = $this->_encryptBlock($iv);
                    $plaintext.= $iv ^ substr($ciphertext, $i);
                    $iv = substr_replace($iv, substr($ciphertext, $i), 0, $len);
                    $pos = $len;
                }
                break;
            case self::MODE_CFB8:
                $plaintext = '';
                $len = strlen($ciphertext);
                $iv = $this->decryptIV;

                for ($i = 0; $i < $len; ++$i) {
                    $plaintext .= $ciphertext[$i] ^ $this->_encryptBlock($iv);
                    $iv = substr($iv, 1) . $ciphertext[$i];
                }

                if ($this->continuousBuffer) {
                    if ($len >= $block_size) {
                        $this->decryptIV = substr($ciphertext, -$block_size);
                    } else {
                        $this->decryptIV = substr($this->decryptIV, $len - $block_size) . substr($ciphertext, -$len);
                    }
                }
                break;
            case self::MODE_OFB:
                $xor = $this->decryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['xor'])) {
                            $xor = $this->_encryptBlock($xor);
                            $buffer['xor'].= $xor;
                        }
                        $key = $this->_string_shift($buffer['xor'], $block_size);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $xor = $this->_encryptBlock($xor);
                        $plaintext.= substr($ciphertext, $i, $block_size) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % $block_size) {
                        $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
                break;
            case self::MODE_STREAM:
                $plaintext = $this->_decryptBlock($ciphertext);
                break;
        }
        return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
    }

    /**
     * OpenSSL CTR Processor
     *
     * PHP's OpenSSL bindings do not operate in continuous mode so we'll wrap around it. Since the keystream
     * for CTR is the same for both encrypting and decrypting this function is re-used by both Base::encrypt()
     * and Base::decrypt(). Also, OpenSSL doesn't implement CTR for all of it's symmetric ciphers so this
     * function will emulate CTR with ECB when necessary.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $plaintext
     * @param string $encryptIV
     * @param array $buffer
     * @return string
     * @access private
     */
    function _openssl_ctr_process($plaintext, &$encryptIV, &$buffer)
    {
        $ciphertext = '';

        $block_size = $this->block_size;
        $key = $this->key;

        if ($this->openssl_emulate_ctr) {
            $xor = $encryptIV;
            if (strlen($buffer['ciphertext'])) {
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    if (strlen($block) > strlen($buffer['ciphertext'])) {
                        $result = @openssl_encrypt($xor, $this->cipher_name_openssl_ecb, $key, $this->openssl_options);
                        $result = !defined('OPENSSL_RAW_DATA') ? substr($result, 0, -$this->block_size) : $result;
                        $buffer['ciphertext'].= $result;
                    }
                    $this->_increment_str($xor);
                    $otp = $this->_string_shift($buffer['ciphertext'], $block_size);
                    $ciphertext.= $block ^ $otp;
                }
            } else {
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    $otp = @openssl_encrypt($xor, $this->cipher_name_openssl_ecb, $key, $this->openssl_options);
                    $otp = !defined('OPENSSL_RAW_DATA') ? substr($otp, 0, -$this->block_size) : $otp;
                    $this->_increment_str($xor);
                    $ciphertext.= $block ^ $otp;
                }
            }
            if ($this->continuousBuffer) {
                $encryptIV = $xor;
                if ($start = strlen($plaintext) % $block_size) {
                    $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                }
            }

            return $ciphertext;
        }

        if (strlen($buffer['ciphertext'])) {
            $ciphertext = $plaintext ^ $this->_string_shift($buffer['ciphertext'], strlen($plaintext));
            $plaintext = substr($plaintext, strlen($ciphertext));

            if (!strlen($plaintext)) {
                return $ciphertext;
            }
        }

        $overflow = strlen($plaintext) % $block_size;
        if ($overflow) {
            $plaintext2 = $this->_string_pop($plaintext, $overflow); // ie. trim $plaintext to a multiple of $block_size and put rest of $plaintext in $plaintext2
            $encrypted = openssl_encrypt($plaintext . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, $this->openssl_options, $encryptIV);
            $temp = $this->_string_pop($encrypted, $block_size);
            $ciphertext.= $encrypted . ($plaintext2 ^ $temp);
            if ($this->continuousBuffer) {
                $buffer['ciphertext'] = substr($temp, $overflow);
                $encryptIV = $temp;
            }
        } elseif (!strlen($buffer['ciphertext'])) {
            $ciphertext.= openssl_encrypt($plaintext . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, $this->openssl_options, $encryptIV);
            $temp = $this->_string_pop($ciphertext, $block_size);
            if ($this->continuousBuffer) {
                $encryptIV = $temp;
            }
        }
        if ($this->continuousBuffer) {
            if (!defined('OPENSSL_RAW_DATA')) {
                $encryptIV.= @openssl_encrypt('', $this->cipher_name_openssl_ecb, $key, $this->openssl_options);
            }
            $encryptIV = openssl_decrypt($encryptIV, $this->cipher_name_openssl_ecb, $key, $this->openssl_options);
            if ($overflow) {
                $this->_increment_str($encryptIV);
            }
        }

        return $ciphertext;
    }

    /**
     * OpenSSL OFB Processor
     *
     * PHP's OpenSSL bindings do not operate in continuous mode so we'll wrap around it. Since the keystream
     * for OFB is the same for both encrypting and decrypting this function is re-used by both Base::encrypt()
     * and Base::decrypt().
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $plaintext
     * @param string $encryptIV
     * @param array $buffer
     * @return string
     * @access private
     */
    function _openssl_ofb_process($plaintext, &$encryptIV, &$buffer)
    {
        if (strlen($buffer['xor'])) {
            $ciphertext = $plaintext ^ $buffer['xor'];
            $buffer['xor'] = substr($buffer['xor'], strlen($ciphertext));
            $plaintext = substr($plaintext, strlen($ciphertext));
        } else {
            $ciphertext = '';
        }

        $block_size = $this->block_size;

        $len = strlen($plaintext);
        $key = $this->key;
        $overflow = $len % $block_size;

        if (strlen($plaintext)) {
            if ($overflow) {
                $ciphertext.= openssl_encrypt(substr($plaintext, 0, -$overflow) . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, $this->openssl_options, $encryptIV);
                $xor = $this->_string_pop($ciphertext, $block_size);
                if ($this->continuousBuffer) {
                    $encryptIV = $xor;
                }
                $ciphertext.= $this->_string_shift($xor, $overflow) ^ substr($plaintext, -$overflow);
                if ($this->continuousBuffer) {
                    $buffer['xor'] = $xor;
                }
            } else {
                $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $key, $this->openssl_options, $encryptIV);
                if ($this->continuousBuffer) {
                    $encryptIV = substr($ciphertext, -$block_size) ^ substr($plaintext, -$block_size);
                }
            }
        }

        return $ciphertext;
    }

    /**
     * phpseclib <-> OpenSSL Mode Mapper
     *
     * May need to be overwritten by classes extending this one in some cases
     *
     * @return int
     * @access private
     */
    function _openssl_translate_mode()
    {
        switch ($this->mode) {
            case self::MODE_ECB:
                return 'ecb';
            case self::MODE_CBC:
                return 'cbc';
            case self::MODE_CTR:
                return 'ctr';
            case self::MODE_CFB:
                return 'cfb';
            case self::MODE_CFB8:
                return 'cfb8';
            case self::MODE_OFB:
                return 'ofb';
        }
    }

    /**
     * Pad "packets".
     *
     * Block ciphers working by encrypting between their specified [$this->]block_size at a time
     * If you ever need to encrypt or decrypt something that isn't of the proper length, it becomes necessary to
     * pad the input so that it is of the proper length.
     *
     * Padding is enabled by default.  Sometimes, however, it is undesirable to pad strings.  Such is the case in SSH,
     * where "packets" are padded with random bytes before being encrypted.  Unpad these packets and you risk stripping
     * away characters that shouldn't be stripped away. (SSH knows how many bytes are added because the length is
     * transmitted separately)
     *
     * @see self::disablePadding()
     * @access public
     */
    function enablePadding()
    {
        $this->padding = true;
    }

    /**
     * Do not pad packets.
     *
     * @see self::enablePadding()
     * @access public
     */
    function disablePadding()
    {
        $this->padding = false;
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 32-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $rijndael->encrypt(substr($plaintext,  0, 16));
     *    echo $rijndael->encrypt(substr($plaintext, 16, 16));
     * </code>
     * <code>
     *    echo $rijndael->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $rijndael->encrypt(substr($plaintext, 0, 16));
     *    echo $rijndael->decrypt($rijndael->encrypt(substr($plaintext, 16, 16)));
     * </code>
     * <code>
     *    echo $rijndael->decrypt($rijndael->encrypt(substr($plaintext, 16, 16)));
     * </code>
     *
     * With the continuous buffer disabled, these would yield the same output.  With it enabled, they yield different
     * outputs.  The reason is due to the fact that the initialization vector's change after every encryption /
     * decryption round when the continuous buffer is enabled.  When it's disabled, they remain constant.
     *
     * Put another way, when the continuous buffer is enabled, the state of the \phpseclib\Crypt\*() object changes after each
     * encryption / decryption round, whereas otherwise, it'd remain constant.  For this reason, it's recommended that
     * continuous buffers not be used.  They do offer better security and are, in fact, sometimes required (SSH uses them),
     * however, they are also less intuitive and more likely to cause you problems.
     *
     * @see self::disableContinuousBuffer()
     * @access public
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function enableContinuousBuffer()
    {
        if ($this->mode == self::MODE_ECB) {
            return;
        }

        $this->continuousBuffer = true;

        $this->_setEngine();
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see self::enableContinuousBuffer()
     * @access public
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function disableContinuousBuffer()
    {
        if ($this->mode == self::MODE_ECB) {
            return;
        }
        if (!$this->continuousBuffer) {
            return;
        }

        $this->continuousBuffer = false;
        $this->changed = true;

        $this->_setEngine();
    }

    /**
     * Test for engine validity
     *
     * @see self::__construct()
     * @param int $engine
     * @access public
     * @return bool
     */
    function isValidEngine($engine)
    {
        switch ($engine) {
            case self::ENGINE_OPENSSL:
                if ($this->mode == self::MODE_STREAM && $this->continuousBuffer) {
                    return false;
                }
                $this->openssl_emulate_ctr = false;
                $result = $this->cipher_name_openssl &&
                          extension_loaded('openssl') &&
                          // PHP 5.3.0 - 5.3.2 did not let you set IV's
                          version_compare(PHP_VERSION, '5.3.3', '>=');
                if (!$result) {
                    return false;
                }

                // prior to PHP 5.4.0 OPENSSL_RAW_DATA and OPENSSL_ZERO_PADDING were not defined. instead of expecting an integer
                // $options openssl_encrypt expected a boolean $raw_data.
                if (!defined('OPENSSL_RAW_DATA')) {
                    $this->openssl_options = true;
                } else {
                    $this->openssl_options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
                }

                $methods = openssl_get_cipher_methods();
                if (in_array($this->cipher_name_openssl, $methods)) {
                    return true;
                }
                // not all of openssl's symmetric cipher's support ctr. for those
                // that don't we'll emulate it
                switch ($this->mode) {
                    case self::MODE_CTR:
                        if (in_array($this->cipher_name_openssl_ecb, $methods)) {
                            $this->openssl_emulate_ctr = true;
                            return true;
                        }
                }
                return false;
            case self::ENGINE_MCRYPT:
                set_error_handler(array($this, 'do_nothing'));
                $result = $this->cipher_name_mcrypt &&
                       extension_loaded('mcrypt') &&
                       in_array($this->cipher_name_mcrypt, mcrypt_list_algorithms());
                restore_error_handler();
                return $result;
            case self::ENGINE_INTERNAL:
                return true;
        }

        return false;
    }

    /**
     * Sets the preferred crypt engine
     *
     * Currently, $engine could be:
     *
     * - \phpseclib\Crypt\Base::ENGINE_OPENSSL  [very fast]
     *
     * - \phpseclib\Crypt\Base::ENGINE_MCRYPT   [fast]
     *
     * - \phpseclib\Crypt\Base::ENGINE_INTERNAL [slow]
     *
     * If the preferred crypt engine is not available the fastest available one will be used
     *
     * @see self::__construct()
     * @param int $engine
     * @access public
     */
    function setPreferredEngine($engine)
    {
        switch ($engine) {
            //case self::ENGINE_OPENSSL;
            case self::ENGINE_MCRYPT:
            case self::ENGINE_INTERNAL:
                $this->preferredEngine = $engine;
                break;
            default:
                $this->preferredEngine = self::ENGINE_OPENSSL;
        }

        $this->_setEngine();
    }

    /**
     * Returns the engine currently being utilized
     *
     * @see self::_setEngine()
     * @access public
     */
    function getEngine()
    {
        return $this->engine;
    }

    /**
     * Sets the engine as appropriate
     *
     * @see self::__construct()
     * @access private
     */
    function _setEngine()
    {
        $this->engine = null;

        $candidateEngines = array(
            $this->preferredEngine,
            self::ENGINE_OPENSSL,
            self::ENGINE_MCRYPT
        );
        foreach ($candidateEngines as $engine) {
            if ($this->isValidEngine($engine)) {
                $this->engine = $engine;
                break;
            }
        }
        if (!$this->engine) {
            $this->engine = self::ENGINE_INTERNAL;
        }

        if ($this->engine != self::ENGINE_MCRYPT && $this->enmcrypt) {
            set_error_handler(array($this, 'do_nothing'));
            // Closing the current mcrypt resource(s). _mcryptSetup() will, if needed,
            // (re)open them with the module named in $this->cipher_name_mcrypt
            mcrypt_module_close($this->enmcrypt);
            mcrypt_module_close($this->demcrypt);
            $this->enmcrypt = null;
            $this->demcrypt = null;

            if ($this->ecb) {
                mcrypt_module_close($this->ecb);
                $this->ecb = null;
            }
            restore_error_handler();
        }

        $this->changed = true;
    }

    /**
     * Encrypts a block
     *
     * Note: Must be extended by the child \phpseclib\Crypt\* class
     *
     * @access private
     * @param string $in
     * @return string
     */
    abstract function _encryptBlock($in);

    /**
     * Decrypts a block
     *
     * Note: Must be extended by the child \phpseclib\Crypt\* class
     *
     * @access private
     * @param string $in
     * @return string
     */
    abstract function _decryptBlock($in);

    /**
     * Setup the key (expansion)
     *
     * Only used if $engine == self::ENGINE_INTERNAL
     *
     * Note: Must extend by the child \phpseclib\Crypt\* class
     *
     * @see self::_setup()
     * @access private
     */
    abstract function _setupKey();

    /**
     * Setup the self::ENGINE_INTERNAL $engine
     *
     * (re)init, if necessary, the internal cipher $engine and flush all $buffers
     * Used (only) if $engine == self::ENGINE_INTERNAL
     *
     * _setup() will be called each time if $changed === true
     * typically this happens when using one or more of following public methods:
     *
     * - setKey()
     *
     * - setIV()
     *
     * - disableContinuousBuffer()
     *
     * - First run of encrypt() / decrypt() with no init-settings
     *
     * @see self::setKey()
     * @see self::setIV()
     * @see self::disableContinuousBuffer()
     * @access private
     * @internal _setup() is always called before en/decryption.
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function _setup()
    {
        $this->_clearBuffers();
        $this->_setupKey();

        if ($this->use_inline_crypt) {
            $this->_setupInlineCrypt();
        }
    }

    /**
     * Setup the self::ENGINE_MCRYPT $engine
     *
     * (re)init, if necessary, the (ext)mcrypt resources and flush all $buffers
     * Used (only) if $engine = self::ENGINE_MCRYPT
     *
     * _setupMcrypt() will be called each time if $changed === true
     * typically this happens when using one or more of following public methods:
     *
     * - setKey()
     *
     * - setIV()
     *
     * - disableContinuousBuffer()
     *
     * - First run of encrypt() / decrypt()
     *
     * @see self::setKey()
     * @see self::setIV()
     * @see self::disableContinuousBuffer()
     * @access private
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function _setupMcrypt()
    {
        $this->_clearBuffers();
        $this->enchanged = $this->dechanged = true;

        if (!isset($this->enmcrypt)) {
            static $mcrypt_modes = array(
                self::MODE_CTR    => 'ctr',
                self::MODE_ECB    => MCRYPT_MODE_ECB,
                self::MODE_CBC    => MCRYPT_MODE_CBC,
                self::MODE_CFB    => 'ncfb',
                self::MODE_CFB8   => MCRYPT_MODE_CFB,
                self::MODE_OFB    => MCRYPT_MODE_NOFB,
                self::MODE_STREAM => MCRYPT_MODE_STREAM,
            );

            $this->demcrypt = mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');
            $this->enmcrypt = mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');

            // we need the $ecb mcrypt resource (only) in MODE_CFB with enableContinuousBuffer()
            // to workaround mcrypt's broken ncfb implementation in buffered mode
            // see: {@link http://phpseclib.sourceforge.net/cfb-demo.phps}
            if ($this->mode == self::MODE_CFB) {
                $this->ecb = mcrypt_module_open($this->cipher_name_mcrypt, '', MCRYPT_MODE_ECB, '');
            }
        } // else should mcrypt_generic_deinit be called?

        if ($this->mode == self::MODE_CFB) {
            mcrypt_generic_init($this->ecb, $this->key, str_repeat("\0", $this->block_size));
        }
    }

    /**
     * Pads a string
     *
     * Pads a string using the RSA PKCS padding standards so that its length is a multiple of the blocksize.
     * $this->block_size - (strlen($text) % $this->block_size) bytes are added, each of which is equal to
     * chr($this->block_size - (strlen($text) % $this->block_size)
     *
     * If padding is disabled and $text is not a multiple of the blocksize, the string will be padded regardless
     * and padding will, hence forth, be enabled.
     *
     * @see self::_unpad()
     * @param string $text
     * @access private
     * @return string
     */
    function _pad($text)
    {
        $length = strlen($text);

        if (!$this->padding) {
            if ($length % $this->block_size == 0) {
                return $text;
            } else {
                user_error("The plaintext's length ($length) is not a multiple of the block size ({$this->block_size})");
                $this->padding = true;
            }
        }

        $pad = $this->block_size - ($length % $this->block_size);

        return str_pad($text, $length + $pad, chr($pad));
    }

    /**
     * Unpads a string.
     *
     * If padding is enabled and the reported padding length is invalid the encryption key will be assumed to be wrong
     * and false will be returned.
     *
     * @see self::_pad()
     * @param string $text
     * @access private
     * @return string
     */
    function _unpad($text)
    {
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > $this->block_size) {
            return false;
        }

        return substr($text, 0, -$length);
    }

    /**
     * Clears internal buffers
     *
     * Clearing/resetting the internal buffers is done everytime
     * after disableContinuousBuffer() or on cipher $engine (re)init
     * ie after setKey() or setIV()
     *
     * @access public
     * @internal Could, but not must, extend by the child Crypt_* class
     */
    function _clearBuffers()
    {
        $this->enbuffer = $this->debuffer = array('ciphertext' => '', 'xor' => '', 'pos' => 0, 'enmcrypt_init' => true);

        // mcrypt's handling of invalid's $iv:
        // $this->encryptIV = $this->decryptIV = strlen($this->iv) == $this->block_size ? $this->iv : str_repeat("\0", $this->block_size);
        $this->encryptIV = $this->decryptIV = str_pad(substr($this->iv, 0, $this->block_size), $this->block_size, "\0");

        if (!$this->skip_key_adjustment) {
            $this->key = str_pad(substr($this->key, 0, $this->key_length), $this->key_length, "\0");
        }
    }

    /**
     * String Shift
     *
     * Inspired by array_shift
     *
     * @param string $string
     * @param int $index
     * @access private
     * @return string
     */
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }

    /**
     * String Pop
     *
     * Inspired by array_pop
     *
     * @param string $string
     * @param int $index
     * @access private
     * @return string
     */
    function _string_pop(&$string, $index = 1)
    {
        $substr = substr($string, -$index);
        $string = substr($string, 0, -$index);
        return $substr;
    }

    /**
     * Increment the current string
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @param string $var
     * @access private
     */
    function _increment_str(&$var)
    {
        for ($i = 4; $i <= strlen($var); $i+= 4) {
            $temp = substr($var, -$i, 4);
            switch ($temp) {
                case "\xFF\xFF\xFF\xFF":
                    $var = substr_replace($var, "\x00\x00\x00\x00", -$i, 4);
                    break;
                case "\x7F\xFF\xFF\xFF":
                    $var = substr_replace($var, "\x80\x00\x00\x00", -$i, 4);
                    return;
                default:
                    $temp = unpack('Nnum', $temp);
                    $var = substr_replace($var, pack('N', $temp['num'] + 1), -$i, 4);
                    return;
            }
        }

        $remainder = strlen($var) % 4;

        if ($remainder == 0) {
            return;
        }

        $temp = unpack('Nnum', str_pad(substr($var, 0, $remainder), 4, "\0", STR_PAD_LEFT));
        $temp = substr(pack('N', $temp['num'] + 1), -$remainder);
        $var = substr_replace($var, $temp, 0, $remainder);
    }

    /**
     * Setup the performance-optimized function for de/encrypt()
     *
     * Stores the created (or existing) callback function-name
     * in $this->inline_crypt
     *
     * Internally for phpseclib developers:
     *
     *     _setupInlineCrypt() would be called only if:
     *
     *     - $engine == self::ENGINE_INTERNAL and
     *
     *     - $use_inline_crypt === true
     *
     *     - each time on _setup(), after(!) _setupKey()
     *
     *
     *     This ensures that _setupInlineCrypt() has always a
     *     full ready2go initializated internal cipher $engine state
     *     where, for example, the keys allready expanded,
     *     keys/block_size calculated and such.
     *
     *     It is, each time if called, the responsibility of _setupInlineCrypt():
     *
     *     - to set $this->inline_crypt to a valid and fully working callback function
     *       as a (faster) replacement for encrypt() / decrypt()
     *
     *     - NOT to create unlimited callback functions (for memory reasons!)
     *       no matter how often _setupInlineCrypt() would be called. At some
     *       point of amount they must be generic re-useable.
     *
     *     - the code of _setupInlineCrypt() it self,
     *       and the generated callback code,
     *       must be, in following order:
     *       - 100% safe
     *       - 100% compatible to encrypt()/decrypt()
     *       - using only php5+ features/lang-constructs/php-extensions if
     *         compatibility (down to php4) or fallback is provided
     *       - readable/maintainable/understandable/commented and... not-cryptic-styled-code :-)
     *       - >= 10% faster than encrypt()/decrypt() [which is, by the way,
     *         the reason for the existence of _setupInlineCrypt() :-)]
     *       - memory-nice
     *       - short (as good as possible)
     *
     * Note: - _setupInlineCrypt() is using _createInlineCryptFunction() to create the full callback function code.
     *       - In case of using inline crypting, _setupInlineCrypt() must extend by the child \phpseclib\Crypt\* class.
     *       - The following variable names are reserved:
     *         - $_*  (all variable names prefixed with an underscore)
     *         - $self (object reference to it self. Do not use $this, but $self instead)
     *         - $in (the content of $in has to en/decrypt by the generated code)
     *       - The callback function should not use the 'return' statement, but en/decrypt'ing the content of $in only
     *
     *
     * @see self::_setup()
     * @see self::_createInlineCryptFunction()
     * @see self::encrypt()
     * @see self::decrypt()
     * @access private
     * @internal If a Crypt_* class providing inline crypting it must extend _setupInlineCrypt()
     */
    function _setupInlineCrypt()
    {
        // If, for any reason, an extending \phpseclib\Crypt\Base() \phpseclib\Crypt\* class
        // not using inline crypting then it must be ensured that: $this->use_inline_crypt = false
        // ie in the class var declaration of $use_inline_crypt in general for the \phpseclib\Crypt\* class,
        // in the constructor at object instance-time
        // or, if it's runtime-specific, at runtime

        $this->use_inline_crypt = false;
    }

    /**
     * Creates the performance-optimized function for en/decrypt()
     *
     * Internally for phpseclib developers:
     *
     *    _createInlineCryptFunction():
     *
     *    - merge the $cipher_code [setup'ed by _setupInlineCrypt()]
     *      with the current [$this->]mode of operation code
     *
     *    - create the $inline function, which called by encrypt() / decrypt()
     *      as its replacement to speed up the en/decryption operations.
     *
     *    - return the name of the created $inline callback function
     *
     *    - used to speed up en/decryption
     *
     *
     *
     *    The main reason why can speed up things [up to 50%] this way are:
     *
     *    - using variables more effective then regular.
     *      (ie no use of expensive arrays but integers $k_0, $k_1 ...
     *      or even, for example, the pure $key[] values hardcoded)
     *
     *    - avoiding 1000's of function calls of ie _encryptBlock()
     *      but inlining the crypt operations.
     *      in the mode of operation for() loop.
     *
     *    - full loop unroll the (sometimes key-dependent) rounds
     *      avoiding this way ++$i counters and runtime-if's etc...
     *
     *    The basic code architectur of the generated $inline en/decrypt()
     *    lambda function, in pseudo php, is:
     *
     *    <code>
     *    +----------------------------------------------------------------------------------------------+
     *    | callback $inline = create_function:                                                          |
     *    | lambda_function_0001_crypt_ECB($action, $text)                                               |
     *    | {                                                                                            |
     *    |     INSERT PHP CODE OF:                                                                      |
     *    |     $cipher_code['init_crypt'];                  // general init code.                       |
     *    |                                                  // ie: $sbox'es declarations used for       |
     *    |                                                  //     encrypt and decrypt'ing.             |
     *    |                                                                                              |
     *    |     switch ($action) {                                                                       |
     *    |         case 'encrypt':                                                                      |
     *    |             INSERT PHP CODE OF:                                                              |
     *    |             $cipher_code['init_encrypt'];       // encrypt sepcific init code.               |
     *    |                                                    ie: specified $key or $box                |
     *    |                                                        declarations for encrypt'ing.         |
     *    |                                                                                              |
     *    |             foreach ($ciphertext) {                                                          |
     *    |                 $in = $block_size of $ciphertext;                                            |
     *    |                                                                                              |
     *    |                 INSERT PHP CODE OF:                                                          |
     *    |                 $cipher_code['encrypt_block'];  // encrypt's (string) $in, which is always:  |
     *    |                                                 // strlen($in) == $this->block_size          |
     *    |                                                 // here comes the cipher algorithm in action |
     *    |                                                 // for encryption.                           |
     *    |                                                 // $cipher_code['encrypt_block'] has to      |
     *    |                                                 // encrypt the content of the $in variable   |
     *    |                                                                                              |
     *    |                 $plaintext .= $in;                                                           |
     *    |             }                                                                                |
     *    |             return $plaintext;                                                               |
     *    |                                                                                              |
     *    |         case 'decrypt':                                                                      |
     *    |             INSERT PHP CODE OF:                                                              |
     *    |             $cipher_code['init_decrypt'];       // decrypt sepcific init code                |
     *    |                                                    ie: specified $key or $box                |
     *    |                                                        declarations for decrypt'ing.         |
     *    |             foreach ($plaintext) {                                                           |
     *    |                 $in = $block_size of $plaintext;                                             |
     *    |                                                                                              |
     *    |                 INSERT PHP CODE OF:                                                          |
     *    |                 $cipher_code['decrypt_block'];  // decrypt's (string) $in, which is always   |
     *    |                                                 // strlen($in) == $this->block_size          |
     *    |                                                 // here comes the cipher algorithm in action |
     *    |                                                 // for decryption.                           |
     *    |                                                 // $cipher_code['decrypt_block'] has to      |
     *    |                                                 // decrypt the content of the $in variable   |
     *    |                 $ciphertext .= $in;                                                          |
     *    |             }                                                                                |
     *    |             return $ciphertext;                                                              |
     *    |     }                                                                                        |
     *    | }                                                                                            |
     *    +----------------------------------------------------------------------------------------------+
     *    </code>
     *
     *    See also the \phpseclib\Crypt\*::_setupInlineCrypt()'s for
     *    productive inline $cipher_code's how they works.
     *
     *    Structure of:
     *    <code>
     *    $cipher_code = array(
     *        'init_crypt'    => (string) '', // optional
     *        'init_encrypt'  => (string) '', // optional
     *        'init_decrypt'  => (string) '', // optional
     *        'encrypt_block' => (string) '', // required
     *        'decrypt_block' => (string) ''  // required
     *    );
     *    </code>
     *
     * @see self::_setupInlineCrypt()
     * @see self::encrypt()
     * @see self::decrypt()
     * @param array $cipher_code
     * @access private
     * @return string (the name of the created callback function)
     */
    function _createInlineCryptFunction($cipher_code)
    {
        $block_size = $this->block_size;

        // optional
        $init_crypt    = isset($cipher_code['init_crypt'])    ? $cipher_code['init_crypt']    : '';
        $init_encrypt  = isset($cipher_code['init_encrypt'])  ? $cipher_code['init_encrypt']  : '';
        $init_decrypt  = isset($cipher_code['init_decrypt'])  ? $cipher_code['init_decrypt']  : '';
        // required
        $encrypt_block = $cipher_code['encrypt_block'];
        $decrypt_block = $cipher_code['decrypt_block'];

        // Generating mode of operation inline code,
        // merged with the $cipher_code algorithm
        // for encrypt- and decryption.
        switch ($this->mode) {
            case self::MODE_ECB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);

                    for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.');
                        '.$encrypt_block.'
                        $_ciphertext.= $in;
                    }

                    return $_ciphertext;
                    ';

                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    $_text = str_pad($_text, strlen($_text) + ('.$block_size.' - strlen($_text) % '.$block_size.') % '.$block_size.', chr(0));
                    $_ciphertext_len = strlen($_text);

                    for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.');
                        '.$decrypt_block.'
                        $_plaintext.= $in;
                    }

                    return $self->_unpad($_plaintext);
                    ';
                break;
            case self::MODE_CTR:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);
                    $_xor = $self->encryptIV;
                    $_buffer = &$self->enbuffer;
                    if (strlen($_buffer["ciphertext"])) {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["ciphertext"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $self->_increment_str($_xor);
                                $_buffer["ciphertext"].= $in;
                            }
                            $_key = $self->_string_shift($_buffer["ciphertext"], '.$block_size.');
                            $_ciphertext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            $in = $_xor;
                            '.$encrypt_block.'
                            $self->_increment_str($_xor);
                            $_key = $in;
                            $_ciphertext.= $_block ^ $_key;
                        }
                    }
                    if ($self->continuousBuffer) {
                        $self->encryptIV = $_xor;
                        if ($_start = $_plaintext_len % '.$block_size.') {
                            $_buffer["ciphertext"] = substr($_key, $_start) . $_buffer["ciphertext"];
                        }
                    }

                    return $_ciphertext;
                ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_ciphertext_len = strlen($_text);
                    $_xor = $self->decryptIV;
                    $_buffer = &$self->debuffer;

                    if (strlen($_buffer["ciphertext"])) {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["ciphertext"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $self->_increment_str($_xor);
                                $_buffer["ciphertext"].= $in;
                            }
                            $_key = $self->_string_shift($_buffer["ciphertext"], '.$block_size.');
                            $_plaintext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            $in = $_xor;
                            '.$encrypt_block.'
                            $self->_increment_str($_xor);
                            $_key = $in;
                            $_plaintext.= $_block ^ $_key;
                        }
                    }
                    if ($self->continuousBuffer) {
                        $self->decryptIV = $_xor;
                        if ($_start = $_ciphertext_len % '.$block_size.') {
                            $_buffer["ciphertext"] = substr($_key, $_start) . $_buffer["ciphertext"];
                        }
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_CFB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_buffer = &$self->enbuffer;

                    if ($self->continuousBuffer) {
                        $_iv = &$self->encryptIV;
                        $_pos = &$_buffer["pos"];
                    } else {
                        $_iv = $self->encryptIV;
                        $_pos = 0;
                    }
                    $_len = strlen($_text);
                    $_i = 0;
                    if ($_pos) {
                        $_orig_pos = $_pos;
                        $_max = '.$block_size.' - $_pos;
                        if ($_len >= $_max) {
                            $_i = $_max;
                            $_len-= $_max;
                            $_pos = 0;
                        } else {
                            $_i = $_len;
                            $_pos+= $_len;
                            $_len = 0;
                        }
                        $_ciphertext = substr($_iv, $_orig_pos) ^ $_text;
                        $_iv = substr_replace($_iv, $_ciphertext, $_orig_pos, $_i);
                    }
                    while ($_len >= '.$block_size.') {
                        $in = $_iv;
                        '.$encrypt_block.';
                        $_iv = $in ^ substr($_text, $_i, '.$block_size.');
                        $_ciphertext.= $_iv;
                        $_len-= '.$block_size.';
                        $_i+= '.$block_size.';
                    }
                    if ($_len) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $_block = $_iv ^ substr($_text, $_i);
                        $_iv = substr_replace($_iv, $_block, 0, $_len);
                        $_ciphertext.= $_block;
                        $_pos = $_len;
                    }
                    return $_ciphertext;
                ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_buffer = &$self->debuffer;

                    if ($self->continuousBuffer) {
                        $_iv = &$self->decryptIV;
                        $_pos = &$_buffer["pos"];
                    } else {
                        $_iv = $self->decryptIV;
                        $_pos = 0;
                    }
                    $_len = strlen($_text);
                    $_i = 0;
                    if ($_pos) {
                        $_orig_pos = $_pos;
                        $_max = '.$block_size.' - $_pos;
                        if ($_len >= $_max) {
                            $_i = $_max;
                            $_len-= $_max;
                            $_pos = 0;
                        } else {
                            $_i = $_len;
                            $_pos+= $_len;
                            $_len = 0;
                        }
                        $_plaintext = substr($_iv, $_orig_pos) ^ $_text;
                        $_iv = substr_replace($_iv, substr($_text, 0, $_i), $_orig_pos, $_i);
                    }
                    while ($_len >= '.$block_size.') {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $cb = substr($_text, $_i, '.$block_size.');
                        $_plaintext.= $_iv ^ $cb;
                        $_iv = $cb;
                        $_len-= '.$block_size.';
                        $_i+= '.$block_size.';
                    }
                    if ($_len) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $_plaintext.= $_iv ^ substr($_text, $_i);
                        $_iv = substr_replace($_iv, substr($_text, $_i), 0, $_len);
                        $_pos = $_len;
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_CFB8:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_len = strlen($_text);
                    $_iv = $self->encryptIV;

                    for ($_i = 0; $_i < $_len; ++$_i) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_ciphertext .= ($_c = $_text[$_i] ^ $in);
                        $_iv = substr($_iv, 1) . $_c;
                    }

                    if ($self->continuousBuffer) {
                        if ($_len >= '.$block_size.') {
                            $self->encryptIV = substr($_ciphertext, -'.$block_size.');
                        } else {
                            $self->encryptIV = substr($self->encryptIV, $_len - '.$block_size.') . substr($_ciphertext, -$_len);
                        }
                    }

                    return $_ciphertext;
                    ';
                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_len = strlen($_text);
                    $_iv = $self->decryptIV;

                    for ($_i = 0; $_i < $_len; ++$_i) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_plaintext .= $_text[$_i] ^ $in;
                        $_iv = substr($_iv, 1) . $_text[$_i];
                    }

                    if ($self->continuousBuffer) {
                        if ($_len >= '.$block_size.') {
                            $self->decryptIV = substr($_text, -'.$block_size.');
                        } else {
                            $self->decryptIV = substr($self->decryptIV, $_len - '.$block_size.') . substr($_text, -$_len);
                        }
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_OFB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);
                    $_xor = $self->encryptIV;
                    $_buffer = &$self->enbuffer;

                    if (strlen($_buffer["xor"])) {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["xor"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $_xor = $in;
                                $_buffer["xor"].= $_xor;
                            }
                            $_key = $self->_string_shift($_buffer["xor"], '.$block_size.');
                            $_ciphertext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $in = $_xor;
                            '.$encrypt_block.'
                            $_xor = $in;
                            $_ciphertext.= substr($_text, $_i, '.$block_size.') ^ $_xor;
                        }
                        $_key = $_xor;
                    }
                    if ($self->continuousBuffer) {
                        $self->encryptIV = $_xor;
                        if ($_start = $_plaintext_len % '.$block_size.') {
                             $_buffer["xor"] = substr($_key, $_start) . $_buffer["xor"];
                        }
                    }
                    return $_ciphertext;
                    ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_ciphertext_len = strlen($_text);
                    $_xor = $self->decryptIV;
                    $_buffer = &$self->debuffer;

                    if (strlen($_buffer["xor"])) {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["xor"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $_xor = $in;
                                $_buffer["xor"].= $_xor;
                            }
                            $_key = $self->_string_shift($_buffer["xor"], '.$block_size.');
                            $_plaintext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $in = $_xor;
                            '.$encrypt_block.'
                            $_xor = $in;
                            $_plaintext.= substr($_text, $_i, '.$block_size.') ^ $_xor;
                        }
                        $_key = $_xor;
                    }
                    if ($self->continuousBuffer) {
                        $self->decryptIV = $_xor;
                        if ($_start = $_ciphertext_len % '.$block_size.') {
                             $_buffer["xor"] = substr($_key, $_start) . $_buffer["xor"];
                        }
                    }
                    return $_plaintext;
                    ';
                break;
            case self::MODE_STREAM:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    '.$encrypt_block.'
                    return $_ciphertext;
                    ';
                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    '.$decrypt_block.'
                    return $_plaintext;
                    ';
                break;
            // case self::MODE_CBC:
            default:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);

                    $in = $self->encryptIV;

                    for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.') ^ $in;
                        '.$encrypt_block.'
                        $_ciphertext.= $in;
                    }

                    if ($self->continuousBuffer) {
                        $self->encryptIV = $in;
                    }

                    return $_ciphertext;
                    ';

                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    $_text = str_pad($_text, strlen($_text) + ('.$block_size.' - strlen($_text) % '.$block_size.') % '.$block_size.', chr(0));
                    $_ciphertext_len = strlen($_text);

                    $_iv = $self->decryptIV;

                    for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                        $in = $_block = substr($_text, $_i, '.$block_size.');
                        '.$decrypt_block.'
                        $_plaintext.= $in ^ $_iv;
                        $_iv = $_block;
                    }

                    if ($self->continuousBuffer) {
                        $self->decryptIV = $_iv;
                    }

                    return $self->_unpad($_plaintext);
                    ';
                break;
        }

        // Create the $inline function and return its name as string. Ready to run!
        eval('$func = function ($_action, &$self, $_text) { ' . $init_crypt . 'if ($_action == "encrypt") { ' . $encrypt . ' } else { ' . $decrypt . ' } };');
        return $func;
    }

    /**
     * Holds the lambda_functions table (classwide)
     *
     * Each name of the lambda function, created from
     * _setupInlineCrypt() && _createInlineCryptFunction()
     * is stored, classwide (!), here for reusing.
     *
     * The string-based index of $function is a classwide
     * unique value representing, at least, the $mode of
     * operation (or more... depends of the optimizing level)
     * for which $mode the lambda function was created.
     *
     * @access private
     * @return array &$functions
     */
    function &_getLambdaFunctions()
    {
        static $functions = array();
        return $functions;
    }

    /**
     * Generates a digest from $bytes
     *
     * @see self::_setupInlineCrypt()
     * @access private
     * @param string $bytes
     * @return string
     */
    function _hashInlineCryptFunction($bytes)
    {
        if (!isset(self::$WHIRLPOOL_AVAILABLE)) {
            self::$WHIRLPOOL_AVAILABLE = extension_loaded('hash') && in_array('whirlpool', hash_algos());
        }

        $result = '';
        $hash = $bytes;

        switch (true) {
            case self::$WHIRLPOOL_AVAILABLE:
                foreach (str_split($bytes, 64) as $t) {
                    $hash = hash('whirlpool', $hash, true);
                    $result .= $t ^ $hash;
                }
                return $result . hash('whirlpool', $hash, true);
            default:
                $len = strlen($bytes);
                for ($i = 0; $i < $len; $i+=20) {
                    $t = substr($bytes, $i, 20);
                    $hash = pack('H*', sha1($hash));
                    $result .= $t ^ $hash;
                }
                return $result . pack('H*', sha1($hash));
        }
    }

    /**
     * Convert float to int
     *
     * On ARM CPUs converting floats to ints doesn't always work
     *
     * @access private
     * @param string $x
     * @return int
     */
    function safe_intval($x)
    {
        switch (true) {
            case is_int($x):
            // PHP 5.3, per http://php.net/releases/5_3_0.php, introduced "more consistent float rounding"
            case (php_uname('m') & "\xDF\xDF\xDF") != 'ARM':
                return $x;
        }
        return (fmod($x, 0x80000000) & 0x7FFFFFFF) |
            ((fmod(floor($x / 0x80000000), 2) & 1) << 31);
    }

    /**
     * eval()'able string for in-line float to int
     *
     * @access private
     * @return string
     */
    function safe_intval_inline()
    {
        switch (true) {
            case defined('PHP_INT_SIZE') && PHP_INT_SIZE == 8:
            case (php_uname('m') & "\xDF\xDF\xDF") != 'ARM':
                return '%s';
                break;
            default:
                $safeint = '(is_int($temp = %s) ? $temp : (fmod($temp, 0x80000000) & 0x7FFFFFFF) | ';
                return $safeint . '((fmod(floor($temp / 0x80000000), 2) & 1) << 31))';
        }
    }

    /**
     * Dummy error handler to suppress mcrypt errors
     *
     * @access private
     */
    function do_nothing()
    {
    }
}
