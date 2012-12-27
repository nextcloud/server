<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP implementation of Triple DES.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.  Operates in the EDE3 mode (encrypt-decrypt-encrypt).
 *
 * PHP versions 4 and 5
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include('Crypt/TripleDES.php');
 *
 *    $des = new Crypt_TripleDES();
 *
 *    $des->setKey('abcdefghijklmnopqrstuvwx');
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
 * @package    Crypt_TripleDES
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMVII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id: TripleDES.php,v 1.13 2010/02/26 03:40:25 terrafrost Exp $
 * @link       http://phpseclib.sourceforge.net
 */

/**
 * Include Crypt_DES
 */
if (!class_exists('Crypt_DES')) {
    require_once('DES.php');
}

/**
 * Encrypt / decrypt using inner chaining
 *
 * Inner chaining is used by SSH-1 and is generally considered to be less secure then outer chaining (CRYPT_DES_MODE_CBC3).
 */
define('CRYPT_DES_MODE_3CBC', -2);

/**
 * Encrypt / decrypt using outer chaining
 *
 * Outer chaining is used by SSH-2 and when the mode is set to CRYPT_DES_MODE_CBC.
 */
define('CRYPT_DES_MODE_CBC3', CRYPT_DES_MODE_CBC);

/**
 * Pure-PHP implementation of Triple DES.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.1.0
 * @access  public
 * @package Crypt_TerraDES
 */
class Crypt_TripleDES {
    /**
     * The Three Keys
     *
     * @see Crypt_TripleDES::setKey()
     * @var String
     * @access private
     */
    var $key = "\0\0\0\0\0\0\0\0";

    /**
     * The Encryption Mode
     *
     * @see Crypt_TripleDES::Crypt_TripleDES()
     * @var Integer
     * @access private
     */
    var $mode = CRYPT_DES_MODE_CBC;

    /**
     * Continuous Buffer status
     *
     * @see Crypt_TripleDES::enableContinuousBuffer()
     * @var Boolean
     * @access private
     */
    var $continuousBuffer = false;

    /**
     * Padding status
     *
     * @see Crypt_TripleDES::enablePadding()
     * @var Boolean
     * @access private
     */
    var $padding = true;

    /**
     * The Initialization Vector
     *
     * @see Crypt_TripleDES::setIV()
     * @var String
     * @access private
     */
    var $iv = "\0\0\0\0\0\0\0\0";

    /**
     * A "sliding" Initialization Vector
     *
     * @see Crypt_TripleDES::enableContinuousBuffer()
     * @var String
     * @access private
     */
    var $encryptIV = "\0\0\0\0\0\0\0\0";

    /**
     * A "sliding" Initialization Vector
     *
     * @see Crypt_TripleDES::enableContinuousBuffer()
     * @var String
     * @access private
     */
    var $decryptIV = "\0\0\0\0\0\0\0\0";

    /**
     * The Crypt_DES objects
     *
     * @var Array
     * @access private
     */
    var $des;

    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see Crypt_TripleDES::encrypt()
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
     * @see Crypt_TripleDES::decrypt()
     * @var String
     * @access private
     */
    var $demcrypt;

    /**
     * Does the enmcrypt resource need to be (re)initialized?
     *
     * @see Crypt_TripleDES::setKey()
     * @see Crypt_TripleDES::setIV()
     * @var Boolean
     * @access private
     */
    var $enchanged = true;

    /**
     * Does the demcrypt resource need to be (re)initialized?
     *
     * @see Crypt_TripleDES::setKey()
     * @see Crypt_TripleDES::setIV()
     * @var Boolean
     * @access private
     */
    var $dechanged = true;

    /**
     * Is the mode one that is paddable?
     *
     * @see Crypt_TripleDES::Crypt_TripleDES()
     * @var Boolean
     * @access private
     */
    var $paddable = false;

    /**
     * Encryption buffer for CTR, OFB and CFB modes
     *
     * @see Crypt_TripleDES::encrypt()
     * @var String
     * @access private
     */
    var $enbuffer = '';

    /**
     * Decryption buffer for CTR, OFB and CFB modes
     *
     * @see Crypt_TripleDES::decrypt()
     * @var String
     * @access private
     */
    var $debuffer = '';

    /**
     * mcrypt resource for CFB mode
     *
     * @see Crypt_TripleDES::encrypt()
     * @see Crypt_TripleDES::decrypt()
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
     * @return Crypt_TripleDES
     * @access public
     */
    function Crypt_TripleDES($mode = CRYPT_DES_MODE_CBC)
    {
        if ( !defined('CRYPT_DES_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && in_array('tripledes', mcrypt_list_algorithms()):
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_INTERNAL);
            }
        }

        if ( $mode == CRYPT_DES_MODE_3CBC ) {
            $this->mode = CRYPT_DES_MODE_3CBC;
            $this->des = array(
                new Crypt_DES(CRYPT_DES_MODE_CBC),
                new Crypt_DES(CRYPT_DES_MODE_CBC),
                new Crypt_DES(CRYPT_DES_MODE_CBC)
            );
            $this->paddable = true;

            // we're going to be doing the padding, ourselves, so disable it in the Crypt_DES objects
            $this->des[0]->disablePadding();
            $this->des[1]->disablePadding();
            $this->des[2]->disablePadding();

            return;
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
                        break;
                    case CRYPT_DES_MODE_CFB:
                        $this->mode = 'ncfb';
                        break;
                    case CRYPT_DES_MODE_OFB:
                        $this->mode = MCRYPT_MODE_NOFB;
                        break;
                    case CRYPT_DES_MODE_CBC:
                    default:
                        $this->paddable = true;
                        $this->mode = MCRYPT_MODE_CBC;
                }

                break;
            default:
                $this->des = array(
                    new Crypt_DES(CRYPT_DES_MODE_ECB),
                    new Crypt_DES(CRYPT_DES_MODE_ECB),
                    new Crypt_DES(CRYPT_DES_MODE_ECB)
                );
 
                // we're going to be doing the padding, ourselves, so disable it in the Crypt_DES objects
                $this->des[0]->disablePadding();
                $this->des[1]->disablePadding();
                $this->des[2]->disablePadding();

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
     * Keys can be of any length.  Triple DES, itself, can use 128-bit (eg. strlen($key) == 16) or
     * 192-bit (eg. strlen($key) == 24) keys.  This function pads and truncates $key as appropriate.
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
        $length = strlen($key);
        if ($length > 8) {
            $key = str_pad($key, 24, chr(0));
            // if $key is between 64 and 128-bits, use the first 64-bits as the last, per this:
            // http://php.net/function.mcrypt-encrypt#47973
            //$key = $length <= 16 ? substr_replace($key, substr($key, 0, 8), 16) : substr($key, 0, 24);
        } else {
            $key = str_pad($key, 8, chr(0));
        }
        $this->key = $key;
        switch (true) {
            case CRYPT_DES_MODE == CRYPT_DES_MODE_INTERNAL:
            case $this->mode == CRYPT_DES_MODE_3CBC:
                $this->des[0]->setKey(substr($key,  0, 8));
                $this->des[1]->setKey(substr($key,  8, 8));
                $this->des[2]->setKey(substr($key, 16, 8));
        }
        $this->enchanged = $this->dechanged = true;
    }

    /**
     * Sets the password.
     *
     * Depending on what $method is set to, setPassword()'s (optional) parameters are as follows:
     *     {@link http://en.wikipedia.org/wiki/PBKDF2 pbkdf2}:
     *         $hash, $salt, $method
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
                // WPA and WPA use the SSID as the salt
                if (!isset($salt)) {
                    $salt = 'phpseclib';
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
                while (strlen($key) < 24) { // $dkLen == 24
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
        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->setIV($iv);
            $this->des[1]->setIV($iv);
            $this->des[2]->setIV($iv);
        }
        $this->enchanged = $this->dechanged = true;
    }

    /**
     * Generate CTR XOR encryption key
     *
     * Encrypt the output of this and XOR it against the ciphertext / plaintext to get the
     * plaintext / ciphertext in CTR mode.
     *
     * @see Crypt_TripleDES::decrypt()
     * @see Crypt_TripleDES::encrypt()
     * @access private
     * @param Integer $length
     * @param String $iv
     */
    function _generate_xor($length, &$iv)
    {
        $xor = '';
        $num_blocks = ($length + 7) >> 3;
        for ($i = 0; $i < $num_blocks; $i++) {
            $xor.= $iv;
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
        }

        return $xor;
    }

    /**
     * Encrypts a message.
     *
     * @access public
     * @param String $plaintext
     */
    function encrypt($plaintext)
    {
        if ($this->paddable) {
            $plaintext = $this->_pad($plaintext);
        }

        // if the key is smaller then 8, do what we'd normally do
        if ($this->mode == CRYPT_DES_MODE_3CBC && strlen($this->key) > 8) {
            $ciphertext = $this->des[2]->encrypt($this->des[1]->decrypt($this->des[0]->encrypt($plaintext)));

            return $ciphertext;
        }

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            if ($this->enchanged) {
                if (!isset($this->enmcrypt)) {
                    $this->enmcrypt = mcrypt_module_open(MCRYPT_3DES, '', $this->mode, '');
                }
                mcrypt_generic_init($this->enmcrypt, $this->key, $this->encryptIV);
                if ($this->mode != 'ncfb') {
                    $this->enchanged = false;
                }
            }

            if ($this->mode != 'ncfb') {
                $ciphertext = mcrypt_generic($this->enmcrypt, $plaintext);
            } else {
                if ($this->enchanged) {
                    $this->ecb = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
                    mcrypt_generic_init($this->ecb, $this->key, "\0\0\0\0\0\0\0\0");
                    $this->enchanged = false;
                }

                if (strlen($this->enbuffer)) {
                    $ciphertext = $plaintext ^ substr($this->encryptIV, strlen($this->enbuffer));
                    $this->enbuffer.= $ciphertext;
                    if (strlen($this->enbuffer) == 8) {
                        $this->encryptIV = $this->enbuffer;
                        $this->enbuffer = '';
                        mcrypt_generic_init($this->enmcrypt, $this->key, $this->encryptIV);
                    }
                    $plaintext = substr($plaintext, strlen($ciphertext));
                } else {
                    $ciphertext = '';
                }

                $last_pos = strlen($plaintext) & 0xFFFFFFF8;
                $ciphertext.= $last_pos ? mcrypt_generic($this->enmcrypt, substr($plaintext, 0, $last_pos)) : '';

                if (strlen($plaintext) & 0x7) {
                    if (strlen($ciphertext)) {
                        $this->encryptIV = substr($ciphertext, -8);
                    }
                    $this->encryptIV = mcrypt_generic($this->ecb, $this->encryptIV);
                    $this->enbuffer = substr($plaintext, $last_pos) ^ $this->encryptIV;
                    $ciphertext.= $this->enbuffer;
                }
            }

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->enmcrypt, $this->key, $this->encryptIV);
            }

            return $ciphertext;
        }

        if (strlen($this->key) <= 8) {
            $this->des[0]->mode = $this->mode;

            return $this->des[0]->encrypt($plaintext);
        }

        $des = $this->des;

        $buffer = &$this->enbuffer;
        $continuousBuffer = $this->continuousBuffer;
        $ciphertext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8);
                    // all of these _processBlock calls could, in theory, be put in a function - say Crypt_TripleDES::_ede_encrypt() or something.
                    // only problem with that: it would slow encryption and decryption down.  $this->des would have to be called every time that
                    // function is called, instead of once for the whole string of text that's being encrypted, which would, in turn, make 
                    // encryption and decryption take more time, per this:
                    //
                    // http://blog.libssh2.org/index.php?/archives/21-Compiled-Variables.html
                    $block = $des[0]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $des[1]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $des[2]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $ciphertext.= $block;
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->encryptIV;
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8) ^ $xor;
                    $block = $des[0]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $des[1]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $des[2]->_processBlock($block, CRYPT_DES_ENCRYPT);
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
                        $key = $this->_generate_xor(8, $xor);
                        $key = $des[0]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $key = $des[1]->_processBlock($key, CRYPT_DES_DECRYPT);
                        $key = $des[2]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $buffer['encrypted'].= $key;
                        $key = $this->_string_shift($buffer['encrypted'], 8);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $block = substr($plaintext, $i, 8);
                        $key = $this->_generate_xor(8, $xor);
                        $key = $des[0]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $key = $des[1]->_processBlock($key, CRYPT_DES_DECRYPT);
                        $key = $des[2]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $ciphertext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) & 7) {
                        $buffer['encrypted'] = substr($key, $start) . $buffer;
                    }
                }
                break;
            case CRYPT_DES_MODE_CFB:
                if (!empty($buffer['xor'])) {
                    $ciphertext = $plaintext ^ $buffer['xor'];
                    $iv = $buffer['encrypted'] . $ciphertext;
                    $start = strlen($ciphertext);
                    $buffer['encrypted'].= $ciphertext;
                    $buffer['xor'] = substr($buffer['xor'], strlen($ciphertext));
                } else {
                    $ciphertext = '';
                    $iv = $this->encryptIV;
                    $start = 0;
                }

                for ($i = $start; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8);
                    $iv = $des[0]->_processBlock($iv, CRYPT_DES_ENCRYPT);
                    $iv = $des[1]->_processBlock($iv, CRYPT_DES_DECRYPT);
                    $xor= $des[2]->_processBlock($iv, CRYPT_DES_ENCRYPT);

                    $iv = $block ^ $xor;
                    if ($continuousBuffer && strlen($iv) != 8) {
                        $buffer = array(
                            'encrypted' => $iv,
                            'xor' => substr($xor, strlen($iv))
                        );
                    }
                    $ciphertext.= $iv;
                }

                if ($this->continuousBuffer) {
                    $this->encryptIV = $iv;
                }
                break;
            case CRYPT_DES_MODE_OFB:
                $xor = $this->encryptIV;
                if (strlen($buffer)) {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $xor = $des[0]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $buffer.= $xor;
                        $key = $this->_string_shift($buffer, 8);
                        $ciphertext.= substr($plaintext, $i, 8) ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=8) {
                        $xor = $des[0]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $ciphertext.= substr($plaintext, $i, 8) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) & 7) {
                         $buffer = substr($key, $start) . $buffer;
                    }
                }
        }

        return $ciphertext;
    }

    /**
     * Decrypts a message.
     *
     * @access public
     * @param String $ciphertext
     */
    function decrypt($ciphertext)
    {
        if ($this->mode == CRYPT_DES_MODE_3CBC && strlen($this->key) > 8) {
            $plaintext = $this->des[0]->decrypt($this->des[1]->encrypt($this->des[2]->decrypt($ciphertext)));

            return $this->_unpad($plaintext);
        }

        if ($this->paddable) {
            // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
            // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
            $ciphertext = str_pad($ciphertext, (strlen($ciphertext) + 7) & 0xFFFFFFF8, chr(0));
        }

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            if ($this->dechanged) {
                if (!isset($this->demcrypt)) {
                    $this->demcrypt = mcrypt_module_open(MCRYPT_3DES, '', $this->mode, '');
                }
                mcrypt_generic_init($this->demcrypt, $this->key, $this->decryptIV);
                if ($this->mode != 'ncfb') {
                    $this->dechanged = false;
                }
            }

            if ($this->mode != 'ncfb') {
                $plaintext = mdecrypt_generic($this->demcrypt, $ciphertext);
            } else {
                if ($this->dechanged) {
                    $this->ecb = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
                    mcrypt_generic_init($this->ecb, $this->key, "\0\0\0\0\0\0\0\0");
                    $this->dechanged = false;
                }

                if (strlen($this->debuffer)) {
                    $plaintext = $ciphertext ^ substr($this->decryptIV, strlen($this->debuffer));

                    $this->debuffer.= substr($ciphertext, 0, strlen($plaintext));
                    if (strlen($this->debuffer) == 8) {
                        $this->decryptIV = $this->debuffer;
                        $this->debuffer = '';
                        mcrypt_generic_init($this->demcrypt, $this->key, $this->decryptIV);
                    }
                    $ciphertext = substr($ciphertext, strlen($plaintext));
                } else {
                    $plaintext = '';
                }

                $last_pos = strlen($ciphertext) & 0xFFFFFFF8;
                $plaintext.= $last_pos ? mdecrypt_generic($this->demcrypt, substr($ciphertext, 0, $last_pos)) : '';

                if (strlen($ciphertext) & 0x7) {
                    if (strlen($plaintext)) {
                        $this->decryptIV = substr($ciphertext, $last_pos - 8, 8);
                    }
                    $this->decryptIV = mcrypt_generic($this->ecb, $this->decryptIV);
                    $this->debuffer = substr($ciphertext, $last_pos);
                    $plaintext.= $this->debuffer ^ $this->decryptIV;
                }

                return $plaintext;
            }

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->decryptIV);
            }

            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        if (strlen($this->key) <= 8) {
            $this->des[0]->mode = $this->mode;
            $plaintext = $this->des[0]->decrypt($ciphertext);
            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        $des = $this->des;

        $buffer = &$this->enbuffer;
        $continuousBuffer = $this->continuousBuffer;
        $plaintext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $block = substr($ciphertext, $i, 8);
                    $block = $des[2]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $des[1]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $des[0]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $plaintext.= $block;
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->decryptIV;
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $orig = $block = substr($ciphertext, $i, 8);
                    $block = $des[2]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $des[1]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $des[0]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $plaintext.= $block ^ $xor;
                    $xor = $orig;
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
                        $key = $this->_generate_xor(8, $xor);
                        $key = $des[0]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $key = $des[1]->_processBlock($key, CRYPT_DES_DECRYPT);
                        $key = $des[2]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $buffer['ciphertext'].= $key;
                        $key = $this->_string_shift($buffer['ciphertext'], 8);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $block = substr($ciphertext, $i, 8);
                        $key = $this->_generate_xor(8, $xor);
                        $key = $des[0]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $key = $des[1]->_processBlock($key, CRYPT_DES_DECRYPT);
                        $key = $des[2]->_processBlock($key, CRYPT_DES_ENCRYPT);
                        $plaintext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($plaintext) & 7) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case CRYPT_DES_MODE_CFB:
                if (!empty($buffer['ciphertext'])) {
                    $plaintext = $ciphertext ^ substr($this->decryptIV, strlen($buffer['ciphertext']));
                    $buffer['ciphertext'].= substr($ciphertext, 0, strlen($plaintext));
                    if (strlen($buffer['ciphertext']) == 8) {
                        $xor = $des[0]->_processBlock($buffer['ciphertext'], CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $buffer['ciphertext'] = '';
                    }
                    $start = strlen($plaintext);
                    $block = $this->decryptIV;
                } else {
                    $plaintext = '';
                    $xor = $des[0]->_processBlock($this->decryptIV, CRYPT_DES_ENCRYPT);
                    $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                    $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                    $start = 0;
                }

                for ($i = $start; $i < strlen($ciphertext); $i+=8) {
                    $block = substr($ciphertext, $i, 8);
                    $plaintext.= $block ^ $xor;
                    if ($continuousBuffer && strlen($block) != 8) {
                        $buffer['ciphertext'].= $block;
                        $block = $xor;
                    } else if (strlen($block) == 8) {
                        $xor = $des[0]->_processBlock($block, CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                    }
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $block;
                }
                break;
            case CRYPT_DES_MODE_OFB:
                $xor = $this->decryptIV;
                if (strlen($buffer)) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $xor = $des[0]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $buffer.= $xor;
                        $key = $this->_string_shift($buffer, 8);
                        $plaintext.= substr($ciphertext, $i, 8) ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                        $xor = $des[0]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $xor = $des[1]->_processBlock($xor, CRYPT_DES_DECRYPT);
                        $xor = $des[2]->_processBlock($xor, CRYPT_DES_ENCRYPT);
                        $plaintext.= substr($ciphertext, $i, 8) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) & 7) {
                         $buffer = substr($key, $start) . $buffer;
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
     * @see Crypt_TripleDES::disableContinuousBuffer()
     * @access public
     */
    function enableContinuousBuffer()
    {
        $this->continuousBuffer = true;
        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->enableContinuousBuffer();
            $this->des[1]->enableContinuousBuffer();
            $this->des[2]->enableContinuousBuffer();
        }
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see Crypt_TripleDES::enableContinuousBuffer()
     * @access public
     */
    function disableContinuousBuffer()
    {
        $this->continuousBuffer = false;
        $this->encryptIV = $this->iv;
        $this->decryptIV = $this->iv;

        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->disableContinuousBuffer();
            $this->des[1]->disableContinuousBuffer();
            $this->des[2]->disableContinuousBuffer();
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
     * @see Crypt_TripleDES::disablePadding()
     * @access public
     */
    function enablePadding()
    {
        $this->padding = true;
    }

    /**
     * Do not pad packets.
     *
     * @see Crypt_TripleDES::enablePadding()
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
     * @see Crypt_TripleDES::_unpad()
     * @access private
     */
    function _pad($text)
    {
        $length = strlen($text);

        if (!$this->padding) {
            if (($length & 7) == 0) {
                return $text;
            } else {
                user_error("The plaintext's length ($length) is not a multiple of the block size (8)", E_USER_NOTICE);
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
     * @see Crypt_TripleDES::_pad()
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
