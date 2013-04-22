<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP implementation of DES.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.
 *
 * PHP versions 4 and 5
 *
 * Useful resources are as follows:
 *
 *  - {@link http://en.wikipedia.org/wiki/DES_supplementary_material Wikipedia: DES supplementary material}
 *  - {@link http://www.itl.nist.gov/fipspubs/fip46-2.htm FIPS 46-2 - (DES), Data Encryption Standard}
 *  - {@link http://www.cs.eku.edu/faculty/styer/460/Encrypt/JS-DES.html JavaScript DES Example}
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include('Crypt/DES.php');
 *
 *    $des = new Crypt_DES();
 *
 *    $des->setKey('abcdefgh');
 *
 *    $size = 10 * 1024;
 *    $plaintext = '';
 *    for ($i = 0; $i < $size; $i++) {
 *        $plaintext.= 'a';
 *    }
 *
 *    echo $des->decrypt($des->encrypt($plaintext));
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
 * @package    Crypt_DES
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMVII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id: DES.php,v 1.12 2010/02/09 06:10:26 terrafrost Exp $
 * @link       http://phpseclib.sourceforge.net
 */

/**#@+
 * @access private
 * @see Crypt_DES::_prepareKey()
 * @see Crypt_DES::_processBlock()
 */
/**
 * Contains array_reverse($keys[CRYPT_DES_DECRYPT])
 */
define('CRYPT_DES_ENCRYPT', 0);
/**
 * Contains array_reverse($keys[CRYPT_DES_ENCRYPT])
 */
define('CRYPT_DES_DECRYPT', 1);
/**#@-*/

/**#@+
 * @access public
 * @see Crypt_DES::encrypt()
 * @see Crypt_DES::decrypt()
 */
/**
 * Encrypt / decrypt using the Counter mode.
 *
 * Set to -1 since that's what Crypt/Random.php uses to index the CTR mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Counter_.28CTR.29
 */
define('CRYPT_DES_MODE_CTR', -1);
/**
 * Encrypt / decrypt using the Electronic Code Book mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Electronic_codebook_.28ECB.29
 */
define('CRYPT_DES_MODE_ECB', 1);
/**
 * Encrypt / decrypt using the Code Book Chaining mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher-block_chaining_.28CBC.29
 */
define('CRYPT_DES_MODE_CBC', 2);
/**
 * Encrypt / decrypt using the Cipher Feedback mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher_feedback_.28CFB.29
 */
define('CRYPT_DES_MODE_CFB', 3);
/**
 * Encrypt / decrypt using the Cipher Feedback mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Output_feedback_.28OFB.29
 */
define('CRYPT_DES_MODE_OFB', 4);
/**#@-*/

/**#@+
 * @access private
 * @see Crypt_DES::Crypt_DES()
 */
/**
 * Toggles the internal implementation
 */
define('CRYPT_DES_MODE_INTERNAL', 1);
/**
 * Toggles the mcrypt implementation
 */
define('CRYPT_DES_MODE_MCRYPT', 2);
/**#@-*/

/**
 * Pure-PHP implementation of DES.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.1.0
 * @access  public
 * @package Crypt_DES
 */
class Crypt_DES {
    /**
     * The Key Schedule
     *
     * @see Crypt_DES::setKey()
     * @var Array
     * @access private
     */
    var $keys = "\0\0\0\0\0\0\0\0";

    /**
     * The Encryption Mode
     *
     * @see Crypt_DES::Crypt_DES()
     * @var Integer
     * @access private
     */
    var $mode;

    /**
     * Continuous Buffer status
     *
     * @see Crypt_DES::enableContinuousBuffer()
     * @var Boolean
     * @access private
     */
    var $continuousBuffer = false;

    /**
     * Padding status
     *
     * @see Crypt_DES::enablePadding()
     * @var Boolean
     * @access private
     */
    var $padding = true;

    /**
     * The Initialization Vector
     *
     * @see Crypt_DES::setIV()
     * @var String
     * @access private
     */
    var $iv = "\0\0\0\0\0\0\0\0";

    /**
     * A "sliding" Initialization Vector
     *
     * @see Crypt_DES::enableContinuousBuffer()
     * @var String
     * @access private
     */
    var $encryptIV = "\0\0\0\0\0\0\0\0";

    /**
     * A "sliding" Initialization Vector
     *
     * @see Crypt_DES::enableContinuousBuffer()
     * @var String
     * @access private
     */
    var $decryptIV = "\0\0\0\0\0\0\0\0";

    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see Crypt_DES::encrypt()
     * @var String
     * @access private
     */
    var $enmcrypt;

    /**
     * mcrypt resource for decryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see Crypt_DES::decrypt()
     * @var String
     * @access private
     */
    var $demcrypt;

    /**
     * Does the enmcrypt resource need to be (re)initialized?
     *
     * @see Crypt_DES::setKey()
     * @see Crypt_DES::setIV()
     * @var Boolean
     * @access private
     */
    var $enchanged = true;

    /**
     * Does the demcrypt resource need to be (re)initialized?
     *
     * @see Crypt_DES::setKey()
     * @see Crypt_DES::setIV()
     * @var Boolean
     * @access private
     */
    var $dechanged = true;

    /**
     * Is the mode one that is paddable?
     *
     * @see Crypt_DES::Crypt_DES()
     * @var Boolean
     * @access private
     */
    var $paddable = false;

    /**
     * Encryption buffer for CTR, OFB and CFB modes
     *
     * @see Crypt_DES::encrypt()
     * @var Array
     * @access private
     */
    var $enbuffer = array('encrypted' => '', 'xor' => '', 'pos' => 0, 'enmcrypt_init' => true);

    /**
     * Decryption buffer for CTR, OFB and CFB modes
     *
     * @see Crypt_DES::decrypt()
     * @var Array
     * @access private
     */
    var $debuffer = array('ciphertext' => '', 'xor' => '', 'pos' => 0, 'demcrypt_init' => true);

    /**
     * mcrypt resource for CFB mode
     *
     * @see Crypt_DES::encrypt()
     * @see Crypt_DES::decrypt()
     * @var String
     * @access private
     */
    var $ecb;

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt extension should be used.  $mode should only, at present, be
     * CRYPT_DES_MODE_ECB or CRYPT_DES_MODE_CBC.  If not explictly set, CRYPT_DES_MODE_CBC will be used.
     *
     * @param optional Integer $mode
     * @return Crypt_DES
     * @access public
     */
    function Crypt_DES($mode = CRYPT_DES_MODE_CBC)
    {
        if ( !defined('CRYPT_DES_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && in_array('des', mcrypt_list_algorithms()):
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_DES_MODE ) {
            case CRYPT_DES_MODE_MCRYPT:
                switch ($mode) {
                    case CRYPT_DES_MODE_ECB:
                        $this->paddable = true;
                        $this->mode = MCRYPT_MODE_ECB;
                        break;
                    case CRYPT_DES_MODE_CTR:
                        $this->mode = 'ctr';
                        //$this->mode = in_array('ctr', mcrypt_list_modes()) ? 'ctr' : CRYPT_DES_MODE_CTR;
                        break;
                    case CRYPT_DES_MODE_CFB:
                        $this->mode = 'ncfb';
                        $this->ecb = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
                        break;
                    case CRYPT_DES_MODE_OFB:
                        $this->mode = MCRYPT_MODE_NOFB;
                        break;
                    case CRYPT_DES_MODE_CBC:
                    default:
                        $this->paddable = true;
                        $this->mode = MCRYPT_MODE_CBC;
                }
                $this->enmcrypt = mcrypt_module_open(MCRYPT_DES, '', $this->mode, '');
                $this->demcrypt = mcrypt_module_open(MCRYPT_DES, '', $this->mode, '');

                break;
            default:
                switch ($mode) {
                    case CRYPT_DES_MODE_ECB:
                    case CRYPT_DES_MODE_CBC:
                        $this->paddable = true;
                        $this->mode = $mode;
                        break;
                    case CRYPT_DES_MODE_CTR:
                    case CRYPT_DES_MODE_CFB:
                    case CRYPT_DES_MODE_OFB:
                        $this->mode = $mode;
                        break;
                    default:
                        $this->paddable = true;
                        $this->mode = CRYPT_DES_MODE_CBC;
                }
        }
    }

    /**
     * Sets the key.
     *
     * Keys can be of any length.  DES, itself, uses 64-bit keys (eg. strlen($key) == 8), however, we
     * only use the first eight, if $key has more then eight characters in it, and pad $key with the
     * null byte if it is less then eight characters long.
     *
     * DES also requires that every eighth bit be a parity bit, however, we'll ignore that.
     *
     * If the key is not explicitly set, it'll be assumed to be all zero's.
     *
     * @access public
     * @param String $key
     */
    function setKey($key)
    {
        $this->keys = ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) ? str_pad(substr($key, 0, 8), 8, chr(0)) : $this->_prepareKey($key);
        $this->enchanged = true;
        $this->dechanged = true;
    }

    /**
     * Sets the password.
     *
     * Depending on what $method is set to, setPassword()'s (optional) parameters are as follows:
     *     {@link http://en.wikipedia.org/wiki/PBKDF2 pbkdf2}:
     *         $hash, $salt, $count
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

                if (!class_exists('Crypt_Hash')) {
                    require_once('Crypt/Hash.php');
                }

                $i = 1;
                while (strlen($key) < 8) { // $dkLen == 8
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

        $this->setKey($key);
    }

    /**
     * Sets the initialization vector. (optional)
     *
     * SetIV is not required when CRYPT_DES_MODE_ECB is being used.  If not explictly set, it'll be assumed
     * to be all zero's.
     *
     * @access public
     * @param String $iv
     */
    function setIV($iv)
    {
        $this->encryptIV = $this->decryptIV = $this->iv = str_pad(substr($iv, 0, 8), 8, chr(0));
        $this->enchanged = true;
        $this->dechanged = true;
    }

    /**
     * Generate CTR XOR encryption key
     *
     * Encrypt the output of this and XOR it against the ciphertext / plaintext to get the
     * plaintext / ciphertext in CTR mode.
     *
     * @see Crypt_DES::decrypt()
     * @see Crypt_DES::encrypt()
     * @access public
     * @param String $iv
     */
    function _generate_xor(&$iv)
    {
        $xor = $iv;
        for ($j = 4; $j <= 8; $j+=4) {
            $temp = substr($iv, -$j, 4);
            switch ($temp) {
                case "\xFF\xFF\xFF\xFF":
                    $iv = substr_replace($iv, "\x00\x00\x00\x00", -$j, 4);
                    break;
                case "\x7F\xFF\xFF\xFF":
                    $iv = substr_replace($iv, "\x80\x00\x00\x00", -$j, 4);
                    break 2;
                default:
                    extract(unpack('Ncount', $temp));
                    $iv = substr_replace($iv, pack('N', $count + 1), -$j, 4);
                    break 2;
            }
        }

        return $xor;
    }

    /**
     * Encrypts a message.
     *
     * $plaintext will be padded with up to 8 additional bytes.  Other DES implementations may or may not pad in the
     * same manner.  Other common approaches to padding and the reasons why it's necessary are discussed in the following
     * URL:
     *
     * {@link http://www.di-mgt.com.au/cryptopad.html http://www.di-mgt.com.au/cryptopad.html}
     *
     * An alternative to padding is to, separately, send the length of the file.  This is what SSH, in fact, does.
     * strlen($plaintext) will still need to be a multiple of 8, however, arbitrary values can be added to make it that
     * length.
     *
     * @see Crypt_DES::decrypt()
     * @access public
     * @param String $plaintext
     */
    function encrypt($plaintext)
    {
        if ($this->paddable) {
            $plaintext = $this->_pad($plaintext);
        }

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            if ($this->enchanged) {
                mcrypt_generic_init($this->enmcrypt, $this->keys, $this->encryptIV);
                if ($this->mode == 'ncfb') {
                    mcrypt_generic_init($this->ecb, $this->keys, "\0\0\0\0\0\0\0\0");
                }
                $this->enchanged = false;
            }

            if ($this->mode != 'ncfb' || !$this->continuousBuffer) {
                $ciphertext = mcrypt_generic($this->enmcrypt, $plaintext);
            } else {
                $iv = &$this->encryptIV;
                $pos = &$this->enbuffer['pos'];
                $len = strlen($plaintext);
                $ciphertext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = 8 - $pos;
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
                if ($len >= 8) {
                    if ($this->enbuffer['enmcrypt_init'] === false || $len > 600) {
                        if ($this->enbuffer['enmcrypt_init'] === true) {
                            mcrypt_generic_init($this->enmcrypt, $this->keys, $iv);
                            $this->enbuffer['enmcrypt_init'] = false;
                        }
                        $ciphertext.= mcrypt_generic($this->enmcrypt, substr($plaintext, $i, $len - $len % 8));
                        $iv = substr($ciphertext, -8);
                        $len%= 8;
                    } else {
                        while ($len >= 8) {
                            $iv = mcrypt_generic($this->ecb, $iv) ^ substr($plaintext, $i, 8);
                            $ciphertext.= $iv;
                            $len-= 8;
                            $i+= 8;
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
                return $ciphertext;
            }

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->enmcrypt, $this->keys, $this->encryptIV);
            }

            return $ciphertext;
        }

        if (!is_array($this->keys)) {
            $this->keys = $this->_prepareKey("\0\0\0\0\0\0\0\0");
        }

        $buffer = &$this->enbuffer;
        $continuousBuffer = $this->continuousBuffer;
        $ciphertext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $ciphertext.= $this->_processBlock(substr($plaintext, $i, 8), CRYPT_DES_ENCRYPT);
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->encryptIV;
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8);
                    $block = $this->_processBlock($block ^ $xor, CRYPT_DES_ENCRYPT);
                    $xor = $block;
                    $ciphertext.= $block;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                }
                break;
            case CRYPT_DES_MODE_CTR:
                $xor = $this->encryptIV;
                if (strlen($buffer['encrypted'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $block = substr($plaintext, $i, 8);
                        $buffer['encrypted'].= $this->_processBlock($this->_generate_xor($xor), CRYPT_DES_ENCRYPT);
                        $key = $this->_string_shift($buffer['encrypted'], 8);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $block = substr($plaintext, $i, 8);
                        $key = $this->_processBlock($this->_generate_xor($xor), CRYPT_DES_ENCRYPT);
                        $ciphertext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) & 7) {
                        $buffer['encrypted'] = substr($key, $start) . $buffer['encrypted'];
                    }
                }
                break;
            case CRYPT_DES_MODE_CFB:
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
                    $max = 8 - $pos;
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
                }
                while ($len >= 8) {
                    $iv = $this->_processBlock($iv, CRYPT_DES_ENCRYPT) ^ substr($plaintext, $i, 8);
                    $ciphertext.= $iv;
                    $len-= 8;
                    $i+= 8;
                }
                if ($len) {
                    $iv = $this->_processBlock($iv, CRYPT_DES_ENCRYPT);
                    $block = $iv ^ substr($plaintext, $i);
                    $iv = substr_replace($iv, $block, 0, $len);
                    $ciphertext.= $block;
                    $pos = $len;
                }
                return $ciphertext;
            case CRYPT_DES_MODE_OFB:
                $xor = $this->encryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $xor = $this->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $buffer['xor'].= $xor;
                        $key = $this->_string_shift($buffer['xor'], 8);
                        $ciphertext.= substr($plaintext, $i, 8) ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $xor = $this->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $ciphertext.= substr($plaintext, $i, 8) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) & 7) {
                         $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
        }

        return $ciphertext;
    }

    /**
     * Decrypts a message.
     *
     * If strlen($ciphertext) is not a multiple of 8, null bytes will be added to the end of the string until it is.
     *
     * @see Crypt_DES::encrypt()
     * @access public
     * @param String $ciphertext
     */
    function decrypt($ciphertext)
    {
        if ($this->paddable) {
            // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
            // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
            $ciphertext = str_pad($ciphertext, (strlen($ciphertext) + 7) & 0xFFFFFFF8, chr(0));
        }

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            if ($this->dechanged) {
                mcrypt_generic_init($this->demcrypt, $this->keys, $this->decryptIV);
                if ($this->mode == 'ncfb') {
                    mcrypt_generic_init($this->ecb, $this->keys, "\0\0\0\0\0\0\0\0");
                }
                $this->dechanged = false;
            }

            if ($this->mode != 'ncfb' || !$this->continuousBuffer) {
                $plaintext = mdecrypt_generic($this->demcrypt, $ciphertext);
            } else {
                $iv = &$this->decryptIV;
                $pos = &$this->debuffer['pos'];
                $len = strlen($ciphertext);
                $plaintext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = 8 - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                if ($len >= 8) {
                    $cb = substr($ciphertext, $i, $len - $len % 8);
                    $plaintext.= mcrypt_generic($this->ecb, $iv . $cb) ^ $cb;
                    $iv = substr($cb, -8);
                    $len%= 8;
                }
                if ($len) {
                    $iv = mcrypt_generic($this->ecb, $iv);
                    $plaintext.= $iv ^ substr($ciphertext, -$len);
                    $iv = substr_replace($iv, substr($ciphertext, -$len), 0, $len);
                    $pos = $len;
                }
                return $plaintext;
            }

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->demcrypt, $this->keys, $this->decryptIV);
            }

            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        if (!is_array($this->keys)) {
            $this->keys = $this->_prepareKey("\0\0\0\0\0\0\0\0");
        }

        $buffer = &$this->debuffer;
        $continuousBuffer = $this->continuousBuffer;
        $plaintext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $plaintext.= $this->_processBlock(substr($ciphertext, $i, 8), CRYPT_DES_DECRYPT);
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->decryptIV;
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $block = substr($ciphertext, $i, 8);
                    $plaintext.= $this->_processBlock($block, CRYPT_DES_DECRYPT) ^ $xor;
                    $xor = $block;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                }
                break;
            case CRYPT_DES_MODE_CTR:
                $xor = $this->decryptIV;
                if (strlen($buffer['ciphertext'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $block = substr($ciphertext, $i, 8);
                        $buffer['ciphertext'].= $this->_processBlock($this->_generate_xor($xor), CRYPT_DES_ENCRYPT);
                        $key = $this->_string_shift($buffer['ciphertext'], 8);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $block = substr($ciphertext, $i, 8);
                        $key = $this->_processBlock($this->_generate_xor($xor), CRYPT_DES_ENCRYPT);
                        $plaintext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % 8) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case CRYPT_DES_MODE_CFB:
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
                    $max = 8 - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                while ($len >= 8) {
                    $iv = $this->_processBlock($iv, CRYPT_DES_ENCRYPT);
                    $cb = substr($ciphertext, $i, 8);
                    $plaintext.= $iv ^ $cb;
                    $iv = $cb;
                    $len-= 8;
                    $i+= 8;
                }
                if ($len) {
                    $iv = $this->_processBlock($iv, CRYPT_DES_ENCRYPT);
                    $plaintext.= $iv ^ substr($ciphertext, $i);
                    $iv = substr_replace($iv, substr($ciphertext, $i), 0, $len);
                    $pos = $len;
                }
                return $plaintext;
            case CRYPT_DES_MODE_OFB:
                $xor = $this->decryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $xor = $this->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $buffer['xor'].= $xor;
                        $key = $this->_string_shift($buffer['xor'], 8);
                        $plaintext.= substr($ciphertext, $i, 8) ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $xor = $this->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $plaintext.= substr($ciphertext, $i, 8) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % 8) {
                         $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
        }

        return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 16-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $des->encrypt(substr($plaintext, 0, 8));
     *    echo $des->encrypt(substr($plaintext, 8, 8));
     * </code>
     * <code>
     *    echo $des->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $des->encrypt(substr($plaintext, 0, 8));
     *    echo $des->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     * <code>
     *    echo $des->decrypt($des->encrypt(substr($plaintext, 8, 8)));
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
     * @see Crypt_DES::disableContinuousBuffer()
     * @access public
     */
    function enableContinuousBuffer()
    {
        $this->continuousBuffer = true;
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see Crypt_DES::enableContinuousBuffer()
     * @access public
     */
    function disableContinuousBuffer()
    {
        $this->continuousBuffer = false;
        $this->encryptIV = $this->iv;
        $this->decryptIV = $this->iv;
        $this->enbuffer = array('encrypted' => '', 'xor' => '', 'pos' => 0, 'enmcrypt_init' => true);
        $this->debuffer = array('ciphertext' => '', 'xor' => '', 'pos' => 0, 'demcrypt_init' => true);

        if (CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT) {
            mcrypt_generic_init($this->enmcrypt, $this->keys, $this->iv);
            mcrypt_generic_init($this->demcrypt, $this->keys, $this->iv);
        }
    }

    /**
     * Pad "packets".
     *
     * DES works by encrypting eight bytes at a time.  If you ever need to encrypt or decrypt something that's not
     * a multiple of eight, it becomes necessary to pad the input so that it's length is a multiple of eight.
     *
     * Padding is enabled by default.  Sometimes, however, it is undesirable to pad strings.  Such is the case in SSH1,
     * where "packets" are padded with random bytes before being encrypted.  Unpad these packets and you risk stripping
     * away characters that shouldn't be stripped away. (SSH knows how many bytes are added because the length is
     * transmitted separately)
     *
     * @see Crypt_DES::disablePadding()
     * @access public
     */
    function enablePadding()
    {
        $this->padding = true;
    }

    /**
     * Do not pad packets.
     *
     * @see Crypt_DES::enablePadding()
     * @access public
     */
    function disablePadding()
    {
        $this->padding = false;
    }

    /**
     * Pads a string
     *
     * Pads a string using the RSA PKCS padding standards so that its length is a multiple of the blocksize (8).
     * 8 - (strlen($text) & 7) bytes are added, each of which is equal to chr(8 - (strlen($text) & 7)
     *
     * If padding is disabled and $text is not a multiple of the blocksize, the string will be padded regardless
     * and padding will, hence forth, be enabled.
     *
     * @see Crypt_DES::_unpad()
     * @access private
     */
    function _pad($text)
    {
        $length = strlen($text);

        if (!$this->padding) {
            if (($length & 7) == 0) {
                return $text;
            } else {
                user_error("The plaintext's length ($length) is not a multiple of the block size (8)");
                $this->padding = true;
            }
        }

        $pad = 8 - ($length & 7);
        return str_pad($text, $length + $pad, chr($pad));
    }

    /**
     * Unpads a string
     *
     * If padding is enabled and the reported padding length is invalid the encryption key will be assumed to be wrong
     * and false will be returned.
     *
     * @see Crypt_DES::_pad()
     * @access private
     */
    function _unpad($text)
    {
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > 8) {
            return false;
        }

        return substr($text, 0, -$length);
    }

    /**
     * Encrypts or decrypts a 64-bit block
     *
     * $mode should be either CRYPT_DES_ENCRYPT or CRYPT_DES_DECRYPT.  See
     * {@link http://en.wikipedia.org/wiki/Image:Feistel.png Feistel.png} to get a general
     * idea of what this function does.
     *
     * @access private
     * @param String $block
     * @param Integer $mode
     * @return String
     */
    function _processBlock($block, $mode)
    {
        // s-boxes.  in the official DES docs, they're described as being matrices that
        // one accesses by using the first and last bits to determine the row and the
        // middle four bits to determine the column.  in this implementation, they've
        // been converted to vectors
        static $sbox = array(
            array(
                14,  0,  4, 15, 13,  7,  1,  4,  2, 14, 15,  2, 11, 13,  8,  1,
                 3, 10 ,10,  6,  6, 12, 12, 11,  5,  9,  9,  5,  0,  3,  7,  8,
                 4, 15,  1, 12, 14,  8,  8,  2, 13,  4,  6,  9,  2,  1, 11,  7,
                15,  5, 12, 11,  9,  3,  7, 14,  3, 10, 10,  0,  5,  6,  0, 13
            ),
            array(
                15,  3,  1, 13,  8,  4, 14,  7,  6, 15, 11,  2,  3,  8,  4, 14,
                 9, 12,  7,  0,  2,  1, 13, 10, 12,  6,  0,  9,  5, 11, 10,  5,
                 0, 13, 14,  8,  7, 10, 11,  1, 10,  3,  4, 15, 13,  4,  1,  2,
                 5, 11,  8,  6, 12,  7,  6, 12,  9,  0,  3,  5,  2, 14, 15,  9
            ),
            array(
                10, 13,  0,  7,  9,  0, 14,  9,  6,  3,  3,  4, 15,  6,  5, 10,
                 1,  2, 13,  8, 12,  5,  7, 14, 11, 12,  4, 11,  2, 15,  8,  1,
                13,  1,  6, 10,  4, 13,  9,  0,  8,  6, 15,  9,  3,  8,  0,  7,
                11,  4,  1, 15,  2, 14, 12,  3,  5, 11, 10,  5, 14,  2,  7, 12
            ),
            array(
                 7, 13, 13,  8, 14, 11,  3,  5,  0,  6,  6, 15,  9,  0, 10,  3,
                 1,  4,  2,  7,  8,  2,  5, 12, 11,  1, 12, 10,  4, 14, 15,  9,
                10,  3,  6, 15,  9,  0,  0,  6, 12, 10, 11,  1,  7, 13, 13,  8,
                15,  9,  1,  4,  3,  5, 14, 11,  5, 12,  2,  7,  8,  2,  4, 14
            ),
            array(
                 2, 14, 12, 11,  4,  2,  1, 12,  7,  4, 10,  7, 11, 13,  6,  1,
                 8,  5,  5,  0,  3, 15, 15, 10, 13,  3,  0,  9, 14,  8,  9,  6,
                 4, 11,  2,  8,  1, 12, 11,  7, 10,  1, 13, 14,  7,  2,  8, 13,
                15,  6,  9, 15, 12,  0,  5,  9,  6, 10,  3,  4,  0,  5, 14,  3
            ),
            array(
                12, 10,  1, 15, 10,  4, 15,  2,  9,  7,  2, 12,  6,  9,  8,  5,
                 0,  6, 13,  1,  3, 13,  4, 14, 14,  0,  7, 11,  5,  3, 11,  8,
                 9,  4, 14,  3, 15,  2,  5, 12,  2,  9,  8,  5, 12, 15,  3, 10,
                 7, 11,  0, 14,  4,  1, 10,  7,  1,  6, 13,  0, 11,  8,  6, 13
            ),
            array(
                 4, 13, 11,  0,  2, 11, 14,  7, 15,  4,  0,  9,  8,  1, 13, 10,
                 3, 14, 12,  3,  9,  5,  7, 12,  5,  2, 10, 15,  6,  8,  1,  6,
                 1,  6,  4, 11, 11, 13, 13,  8, 12,  1,  3,  4,  7, 10, 14,  7,
                10,  9, 15,  5,  6,  0,  8, 15,  0, 14,  5,  2,  9,  3,  2, 12
            ),
            array(
                13,  1,  2, 15,  8, 13,  4,  8,  6, 10, 15,  3, 11,  7,  1,  4,
                10, 12,  9,  5,  3,  6, 14, 11,  5,  0,  0, 14, 12,  9,  7,  2,
                 7,  2, 11,  1,  4, 14,  1,  7,  9,  4, 12, 10, 14,  8,  2, 13,
                 0, 15,  6, 12, 10,  9, 13,  0, 15,  3,  3,  5,  5,  6,  8, 11
            )
        );

        $keys = $this->keys;

        $temp = unpack('Na/Nb', $block);
        $block = array($temp['a'], $temp['b']);

        // because php does arithmetic right shifts, if the most significant bits are set, right
        // shifting those into the correct position will add 1's - not 0's.  this will intefere
        // with the | operation unless a second & is done.  so we isolate these bits and left shift
        // them into place.  we then & each block with 0x7FFFFFFF to prevennt 1's from being added
        // for any other shifts.
        $msb = array(
            ($block[0] >> 31) & 1,
            ($block[1] >> 31) & 1
        );
        $block[0] &= 0x7FFFFFFF;
        $block[1] &= 0x7FFFFFFF;

        // we isolate the appropriate bit in the appropriate integer and shift as appropriate.  in
        // some cases, there are going to be multiple bits in the same integer that need to be shifted
        // in the same way.  we combine those into one shift operation.
        $block = array(
            (($block[1] & 0x00000040) << 25) | (($block[1] & 0x00004000) << 16) |
            (($block[1] & 0x00400001) <<  7) | (($block[1] & 0x40000100) >>  2) |
            (($block[0] & 0x00000040) << 21) | (($block[0] & 0x00004000) << 12) |
            (($block[0] & 0x00400001) <<  3) | (($block[0] & 0x40000100) >>  6) |
            (($block[1] & 0x00000010) << 19) | (($block[1] & 0x00001000) << 10) |
            (($block[1] & 0x00100000) <<  1) | (($block[1] & 0x10000000) >>  8) |
            (($block[0] & 0x00000010) << 15) | (($block[0] & 0x00001000) <<  6) |
            (($block[0] & 0x00100000) >>  3) | (($block[0] & 0x10000000) >> 12) |
            (($block[1] & 0x00000004) << 13) | (($block[1] & 0x00000400) <<  4) |
            (($block[1] & 0x00040000) >>  5) | (($block[1] & 0x04000000) >> 14) |
            (($block[0] & 0x00000004) <<  9) | ( $block[0] & 0x00000400       ) |
            (($block[0] & 0x00040000) >>  9) | (($block[0] & 0x04000000) >> 18) |
            (($block[1] & 0x00010000) >> 11) | (($block[1] & 0x01000000) >> 20) |
            (($block[0] & 0x00010000) >> 15) | (($block[0] & 0x01000000) >> 24)
        ,
            (($block[1] & 0x00000080) << 24) | (($block[1] & 0x00008000) << 15) |
            (($block[1] & 0x00800002) <<  6) | (($block[0] & 0x00000080) << 20) |
            (($block[0] & 0x00008000) << 11) | (($block[0] & 0x00800002) <<  2) |
            (($block[1] & 0x00000020) << 18) | (($block[1] & 0x00002000) <<  9) |
            ( $block[1] & 0x00200000       ) | (($block[1] & 0x20000000) >>  9) |
            (($block[0] & 0x00000020) << 14) | (($block[0] & 0x00002000) <<  5) |
            (($block[0] & 0x00200000) >>  4) | (($block[0] & 0x20000000) >> 13) |
            (($block[1] & 0x00000008) << 12) | (($block[1] & 0x00000800) <<  3) |
            (($block[1] & 0x00080000) >>  6) | (($block[1] & 0x08000000) >> 15) |
            (($block[0] & 0x00000008) <<  8) | (($block[0] & 0x00000800) >>  1) |
            (($block[0] & 0x00080000) >> 10) | (($block[0] & 0x08000000) >> 19) |
            (($block[1] & 0x00000200) >>  3) | (($block[0] & 0x00000200) >>  7) |
            (($block[1] & 0x00020000) >> 12) | (($block[1] & 0x02000000) >> 21) |
            (($block[0] & 0x00020000) >> 16) | (($block[0] & 0x02000000) >> 25) |
            ($msb[1] << 28) | ($msb[0] << 24)
        );

        for ($i = 0; $i < 16; $i++) {
            // start of "the Feistel (F) function" - see the following URL:
            // http://en.wikipedia.org/wiki/Image:Data_Encryption_Standard_InfoBox_Diagram.png
            $temp = (($sbox[0][((($block[1] >> 27) & 0x1F) | (($block[1] & 1) << 5)) ^ $keys[$mode][$i][0]]) << 28)
                  | (($sbox[1][(($block[1] & 0x1F800000) >> 23) ^ $keys[$mode][$i][1]]) << 24)
                  | (($sbox[2][(($block[1] & 0x01F80000) >> 19) ^ $keys[$mode][$i][2]]) << 20)
                  | (($sbox[3][(($block[1] & 0x001F8000) >> 15) ^ $keys[$mode][$i][3]]) << 16)
                  | (($sbox[4][(($block[1] & 0x0001F800) >> 11) ^ $keys[$mode][$i][4]]) << 12)
                  | (($sbox[5][(($block[1] & 0x00001F80) >>  7) ^ $keys[$mode][$i][5]]) <<  8)
                  | (($sbox[6][(($block[1] & 0x000001F8) >>  3) ^ $keys[$mode][$i][6]]) <<  4)
                  | ( $sbox[7][((($block[1] & 0x1F) << 1) | (($block[1] >> 31) & 1)) ^ $keys[$mode][$i][7]]);

            $msb = ($temp >> 31) & 1;
            $temp &= 0x7FFFFFFF;
            $newBlock = (($temp & 0x00010000) << 15) | (($temp & 0x02020120) <<  5)
                      | (($temp & 0x00001800) << 17) | (($temp & 0x01000000) >> 10)
                      | (($temp & 0x00000008) << 24) | (($temp & 0x00100000) <<  6)
                      | (($temp & 0x00000010) << 21) | (($temp & 0x00008000) <<  9)
                      | (($temp & 0x00000200) << 12) | (($temp & 0x10000000) >> 27)
                      | (($temp & 0x00000040) << 14) | (($temp & 0x08000000) >>  8)
                      | (($temp & 0x00004000) <<  4) | (($temp & 0x00000002) << 16)
                      | (($temp & 0x00442000) >>  6) | (($temp & 0x40800000) >> 15)
                      | (($temp & 0x00000001) << 11) | (($temp & 0x20000000) >> 20)
                      | (($temp & 0x00080000) >> 13) | (($temp & 0x00000004) <<  3)
                      | (($temp & 0x04000000) >> 22) | (($temp & 0x00000480) >>  7)
                      | (($temp & 0x00200000) >> 19) | ($msb << 23);
            // end of "the Feistel (F) function" - $newBlock is F's output

            $temp = $block[1];
            $block[1] = $block[0] ^ $newBlock;
            $block[0] = $temp;
        }

        $msb = array(
            ($block[0] >> 31) & 1,
            ($block[1] >> 31) & 1
        );
        $block[0] &= 0x7FFFFFFF;
        $block[1] &= 0x7FFFFFFF;

        $block = array(
            (($block[0] & 0x01000004) <<  7) | (($block[1] & 0x01000004) <<  6) |
            (($block[0] & 0x00010000) << 13) | (($block[1] & 0x00010000) << 12) |
            (($block[0] & 0x00000100) << 19) | (($block[1] & 0x00000100) << 18) |
            (($block[0] & 0x00000001) << 25) | (($block[1] & 0x00000001) << 24) |
            (($block[0] & 0x02000008) >>  2) | (($block[1] & 0x02000008) >>  3) |
            (($block[0] & 0x00020000) <<  4) | (($block[1] & 0x00020000) <<  3) |
            (($block[0] & 0x00000200) << 10) | (($block[1] & 0x00000200) <<  9) |
            (($block[0] & 0x00000002) << 16) | (($block[1] & 0x00000002) << 15) |
            (($block[0] & 0x04000000) >> 11) | (($block[1] & 0x04000000) >> 12) |
            (($block[0] & 0x00040000) >>  5) | (($block[1] & 0x00040000) >>  6) |
            (($block[0] & 0x00000400) <<  1) | ( $block[1] & 0x00000400       ) |
            (($block[0] & 0x08000000) >> 20) | (($block[1] & 0x08000000) >> 21) |
            (($block[0] & 0x00080000) >> 14) | (($block[1] & 0x00080000) >> 15) |
            (($block[0] & 0x00000800) >>  8) | (($block[1] & 0x00000800) >>  9)
        ,
            (($block[0] & 0x10000040) <<  3) | (($block[1] & 0x10000040) <<  2) |
            (($block[0] & 0x00100000) <<  9) | (($block[1] & 0x00100000) <<  8) |
            (($block[0] & 0x00001000) << 15) | (($block[1] & 0x00001000) << 14) |
            (($block[0] & 0x00000010) << 21) | (($block[1] & 0x00000010) << 20) |
            (($block[0] & 0x20000080) >>  6) | (($block[1] & 0x20000080) >>  7) |
            ( $block[0] & 0x00200000       ) | (($block[1] & 0x00200000) >>  1) |
            (($block[0] & 0x00002000) <<  6) | (($block[1] & 0x00002000) <<  5) |
            (($block[0] & 0x00000020) << 12) | (($block[1] & 0x00000020) << 11) |
            (($block[0] & 0x40000000) >> 15) | (($block[1] & 0x40000000) >> 16) |
            (($block[0] & 0x00400000) >>  9) | (($block[1] & 0x00400000) >> 10) |
            (($block[0] & 0x00004000) >>  3) | (($block[1] & 0x00004000) >>  4) |
            (($block[0] & 0x00800000) >> 18) | (($block[1] & 0x00800000) >> 19) |
            (($block[0] & 0x00008000) >> 12) | (($block[1] & 0x00008000) >> 13) |
            ($msb[0] <<  7) | ($msb[1] <<  6)
        );

        return pack('NN', $block[0], $block[1]);
    }

    /**
     * Creates the key schedule.
     *
     * @access private
     * @param String $key
     * @return Array
     */
    function _prepareKey($key)
    {
        static $shifts = array( // number of key bits shifted per round
            1, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1
        );

        // pad the key and remove extra characters as appropriate.
        $key = str_pad(substr($key, 0, 8), 8, chr(0));

        $temp = unpack('Na/Nb', $key);
        $key = array($temp['a'], $temp['b']);
        $msb = array(
            ($key[0] >> 31) & 1,
            ($key[1] >> 31) & 1
        );
        $key[0] &= 0x7FFFFFFF;
        $key[1] &= 0x7FFFFFFF;

        $key = array(
            (($key[1] & 0x00000002) << 26) | (($key[1] & 0x00000204) << 17) |
            (($key[1] & 0x00020408) <<  8) | (($key[1] & 0x02040800) >>  1) |
            (($key[0] & 0x00000002) << 22) | (($key[0] & 0x00000204) << 13) |
            (($key[0] & 0x00020408) <<  4) | (($key[0] & 0x02040800) >>  5) |
            (($key[1] & 0x04080000) >> 10) | (($key[0] & 0x04080000) >> 14) |
            (($key[1] & 0x08000000) >> 19) | (($key[0] & 0x08000000) >> 23) |
            (($key[0] & 0x00000010) >>  1) | (($key[0] & 0x00001000) >> 10) |
            (($key[0] & 0x00100000) >> 19) | (($key[0] & 0x10000000) >> 28)
        ,
            (($key[1] & 0x00000080) << 20) | (($key[1] & 0x00008000) << 11) |
            (($key[1] & 0x00800000) <<  2) | (($key[0] & 0x00000080) << 16) |
            (($key[0] & 0x00008000) <<  7) | (($key[0] & 0x00800000) >>  2) |
            (($key[1] & 0x00000040) << 13) | (($key[1] & 0x00004000) <<  4) |
            (($key[1] & 0x00400000) >>  5) | (($key[1] & 0x40000000) >> 14) |
            (($key[0] & 0x00000040) <<  9) | ( $key[0] & 0x00004000       ) |
            (($key[0] & 0x00400000) >>  9) | (($key[0] & 0x40000000) >> 18) |
            (($key[1] & 0x00000020) <<  6) | (($key[1] & 0x00002000) >>  3) |
            (($key[1] & 0x00200000) >> 12) | (($key[1] & 0x20000000) >> 21) |
            (($key[0] & 0x00000020) <<  2) | (($key[0] & 0x00002000) >>  7) |
            (($key[0] & 0x00200000) >> 16) | (($key[0] & 0x20000000) >> 25) |
            (($key[1] & 0x00000010) >>  1) | (($key[1] & 0x00001000) >> 10) |
            (($key[1] & 0x00100000) >> 19) | (($key[1] & 0x10000000) >> 28) |
            ($msb[1] << 24) | ($msb[0] << 20)
        ); 

        $keys = array();
        for ($i = 0; $i < 16; $i++) {
            $key[0] <<= $shifts[$i];
            $temp = ($key[0] & 0xF0000000) >> 28;
            $key[0] = ($key[0] | $temp) & 0x0FFFFFFF;

            $key[1] <<= $shifts[$i];
            $temp = ($key[1] & 0xF0000000) >> 28;
            $key[1] = ($key[1] | $temp) & 0x0FFFFFFF;

            $temp = array(
                (($key[1] & 0x00004000) >>  9) | (($key[1] & 0x00000800) >>  7) |
                (($key[1] & 0x00020000) >> 14) | (($key[1] & 0x00000010) >>  2) |
                (($key[1] & 0x08000000) >> 26) | (($key[1] & 0x00800000) >> 23)
            ,
                (($key[1] & 0x02400000) >> 20) | (($key[1] & 0x00000001) <<  4) |
                (($key[1] & 0x00002000) >> 10) | (($key[1] & 0x00040000) >> 18) |
                (($key[1] & 0x00000080) >>  6)
            ,
                ( $key[1] & 0x00000020       ) | (($key[1] & 0x00000200) >>  5) |
                (($key[1] & 0x00010000) >> 13) | (($key[1] & 0x01000000) >> 22) |
                (($key[1] & 0x00000004) >>  1) | (($key[1] & 0x00100000) >> 20)
            ,
                (($key[1] & 0x00001000) >>  7) | (($key[1] & 0x00200000) >> 17) |
                (($key[1] & 0x00000002) <<  2) | (($key[1] & 0x00000100) >>  6) |
                (($key[1] & 0x00008000) >> 14) | (($key[1] & 0x04000000) >> 26)
            ,
                (($key[0] & 0x00008000) >> 10) | ( $key[0] & 0x00000010       ) |
                (($key[0] & 0x02000000) >> 22) | (($key[0] & 0x00080000) >> 17) |
                (($key[0] & 0x00000200) >>  8) | (($key[0] & 0x00000002) >>  1)
            ,
                (($key[0] & 0x04000000) >> 21) | (($key[0] & 0x00010000) >> 12) |
                (($key[0] & 0x00000020) >>  2) | (($key[0] & 0x00000800) >>  9) |
                (($key[0] & 0x00800000) >> 22) | (($key[0] & 0x00000100) >>  8)
            ,
                (($key[0] & 0x00001000) >>  7) | (($key[0] & 0x00000088) >>  3) |
                (($key[0] & 0x00020000) >> 14) | (($key[0] & 0x00000001) <<  2) |
                (($key[0] & 0x00400000) >> 21)
            ,
                (($key[0] & 0x00000400) >>  5) | (($key[0] & 0x00004000) >> 10) |
                (($key[0] & 0x00000040) >>  3) | (($key[0] & 0x00100000) >> 18) |
                (($key[0] & 0x08000000) >> 26) | (($key[0] & 0x01000000) >> 24)
            );

            $keys[] = $temp;
        }

        $temp = array(
            CRYPT_DES_ENCRYPT => $keys,
            CRYPT_DES_DECRYPT => array_reverse($keys)
        );

        return $temp;
    }

    /**
     * String Shift
     *
     * Inspired by array_shift
     *
     * @param String $string
     * @param optional Integer $index
     * @return String
     * @access private
     */
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }
}

// vim: ts=4:sw=4:et:
// vim6: fdl=1:
