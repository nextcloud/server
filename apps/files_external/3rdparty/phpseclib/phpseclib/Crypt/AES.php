<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP implementation of AES.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.
 *
 * PHP versions 4 and 5
 *
 * If {@link Crypt_AES::setKeyLength() setKeyLength()} isn't called, it'll be calculated from
 * {@link Crypt_AES::setKey() setKey()}.  ie. if the key is 128-bits, the key length will be 128-bits.  If it's 136-bits
 * it'll be null-padded to 160-bits and 160 bits will be the key length until {@link Crypt_Rijndael::setKey() setKey()}
 * is called, again, at which point, it'll be recalculated.
 *
 * Since Crypt_AES extends Crypt_Rijndael, some functions are available to be called that, in the context of AES, don't
 * make a whole lot of sense.  {@link Crypt_AES::setBlockLength() setBlockLength()}, for instance.  Calling that function,
 * however possible, won't do anything (AES has a fixed block length whereas Rijndael has a variable one).
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include('Crypt/AES.php');
 *
 *    $aes = new Crypt_AES();
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
 * @package    Crypt_AES
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMVIII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id: AES.php,v 1.7 2010/02/09 06:10:25 terrafrost Exp $
 * @link       http://phpseclib.sourceforge.net
 */

/**
 * Include Crypt_Rijndael
 */
if (!class_exists('Crypt_Rijndael')) {
    require_once 'Rijndael.php';
}

/**#@+
 * @access public
 * @see Crypt_AES::encrypt()
 * @see Crypt_AES::decrypt()
 */
/**
 * Encrypt / decrypt using the Counter mode.
 *
 * Set to -1 since that's what Crypt/Random.php uses to index the CTR mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Counter_.28CTR.29
 */
define('CRYPT_AES_MODE_CTR', -1);
/**
 * Encrypt / decrypt using the Electronic Code Book mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Electronic_codebook_.28ECB.29
 */
define('CRYPT_AES_MODE_ECB', 1);
/**
 * Encrypt / decrypt using the Code Book Chaining mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher-block_chaining_.28CBC.29
 */
define('CRYPT_AES_MODE_CBC', 2);
/**
 * Encrypt / decrypt using the Cipher Feedback mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher_feedback_.28CFB.29
 */
define('CRYPT_AES_MODE_CFB', 3);
/**
 * Encrypt / decrypt using the Cipher Feedback mode.
 *
 * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Output_feedback_.28OFB.29
 */
define('CRYPT_AES_MODE_OFB', 4);
/**#@-*/

/**#@+
 * @access private
 * @see Crypt_AES::Crypt_AES()
 */
/**
 * Toggles the internal implementation
 */
define('CRYPT_AES_MODE_INTERNAL', 1);
/**
 * Toggles the mcrypt implementation
 */
define('CRYPT_AES_MODE_MCRYPT', 2);
/**#@-*/

/**
 * Pure-PHP implementation of AES.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.1.0
 * @access  public
 * @package Crypt_AES
 */
class Crypt_AES extends Crypt_Rijndael {
    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see Crypt_AES::encrypt()
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
     * @see Crypt_AES::decrypt()
     * @var String
     * @access private
     */
    var $demcrypt;

    /**
     * mcrypt resource for CFB mode
     *
     * @see Crypt_AES::encrypt()
     * @see Crypt_AES::decrypt()
     * @var String
     * @access private
     */
    var $ecb;

    /**
     * The SubByte S-Box
     *
     * @see Crypt_AES::_encryptBlock()
     * @var Array
     * @access intern
     */
    var $sbox = array(
        0x63, 0x7C, 0x77, 0x7B, 0xF2, 0x6B, 0x6F, 0xC5, 0x30, 0x01, 0x67, 0x2B, 0xFE, 0xD7, 0xAB, 0x76,
        0xCA, 0x82, 0xC9, 0x7D, 0xFA, 0x59, 0x47, 0xF0, 0xAD, 0xD4, 0xA2, 0xAF, 0x9C, 0xA4, 0x72, 0xC0,
        0xB7, 0xFD, 0x93, 0x26, 0x36, 0x3F, 0xF7, 0xCC, 0x34, 0xA5, 0xE5, 0xF1, 0x71, 0xD8, 0x31, 0x15,
        0x04, 0xC7, 0x23, 0xC3, 0x18, 0x96, 0x05, 0x9A, 0x07, 0x12, 0x80, 0xE2, 0xEB, 0x27, 0xB2, 0x75,
        0x09, 0x83, 0x2C, 0x1A, 0x1B, 0x6E, 0x5A, 0xA0, 0x52, 0x3B, 0xD6, 0xB3, 0x29, 0xE3, 0x2F, 0x84,
        0x53, 0xD1, 0x00, 0xED, 0x20, 0xFC, 0xB1, 0x5B, 0x6A, 0xCB, 0xBE, 0x39, 0x4A, 0x4C, 0x58, 0xCF,
        0xD0, 0xEF, 0xAA, 0xFB, 0x43, 0x4D, 0x33, 0x85, 0x45, 0xF9, 0x02, 0x7F, 0x50, 0x3C, 0x9F, 0xA8,
        0x51, 0xA3, 0x40, 0x8F, 0x92, 0x9D, 0x38, 0xF5, 0xBC, 0xB6, 0xDA, 0x21, 0x10, 0xFF, 0xF3, 0xD2,
        0xCD, 0x0C, 0x13, 0xEC, 0x5F, 0x97, 0x44, 0x17, 0xC4, 0xA7, 0x7E, 0x3D, 0x64, 0x5D, 0x19, 0x73,
        0x60, 0x81, 0x4F, 0xDC, 0x22, 0x2A, 0x90, 0x88, 0x46, 0xEE, 0xB8, 0x14, 0xDE, 0x5E, 0x0B, 0xDB,
        0xE0, 0x32, 0x3A, 0x0A, 0x49, 0x06, 0x24, 0x5C, 0xC2, 0xD3, 0xAC, 0x62, 0x91, 0x95, 0xE4, 0x79,
        0xE7, 0xC8, 0x37, 0x6D, 0x8D, 0xD5, 0x4E, 0xA9, 0x6C, 0x56, 0xF4, 0xEA, 0x65, 0x7A, 0xAE, 0x08,
        0xBA, 0x78, 0x25, 0x2E, 0x1C, 0xA6, 0xB4, 0xC6, 0xE8, 0xDD, 0x74, 0x1F, 0x4B, 0xBD, 0x8B, 0x8A,
        0x70, 0x3E, 0xB5, 0x66, 0x48, 0x03, 0xF6, 0x0E, 0x61, 0x35, 0x57, 0xB9, 0x86, 0xC1, 0x1D, 0x9E,
        0xE1, 0xF8, 0x98, 0x11, 0x69, 0xD9, 0x8E, 0x94, 0x9B, 0x1E, 0x87, 0xE9, 0xCE, 0x55, 0x28, 0xDF,
        0x8C, 0xA1, 0x89, 0x0D, 0xBF, 0xE6, 0x42, 0x68, 0x41, 0x99, 0x2D, 0x0F, 0xB0, 0x54, 0xBB, 0x16
    );

    /**
     * The inverse SubByte S-Box
     *
     * @see Crypt_AES::_decryptBlock()
     * @var Array
     * @access intern
     */
    var $isbox = array(
        0x52, 0x09, 0x6A, 0xD5, 0x30, 0x36, 0xA5, 0x38, 0xBF, 0x40, 0xA3, 0x9E, 0x81, 0xF3, 0xD7, 0xFB,
        0x7C, 0xE3, 0x39, 0x82, 0x9B, 0x2F, 0xFF, 0x87, 0x34, 0x8E, 0x43, 0x44, 0xC4, 0xDE, 0xE9, 0xCB,
        0x54, 0x7B, 0x94, 0x32, 0xA6, 0xC2, 0x23, 0x3D, 0xEE, 0x4C, 0x95, 0x0B, 0x42, 0xFA, 0xC3, 0x4E,
        0x08, 0x2E, 0xA1, 0x66, 0x28, 0xD9, 0x24, 0xB2, 0x76, 0x5B, 0xA2, 0x49, 0x6D, 0x8B, 0xD1, 0x25,
        0x72, 0xF8, 0xF6, 0x64, 0x86, 0x68, 0x98, 0x16, 0xD4, 0xA4, 0x5C, 0xCC, 0x5D, 0x65, 0xB6, 0x92,
        0x6C, 0x70, 0x48, 0x50, 0xFD, 0xED, 0xB9, 0xDA, 0x5E, 0x15, 0x46, 0x57, 0xA7, 0x8D, 0x9D, 0x84,
        0x90, 0xD8, 0xAB, 0x00, 0x8C, 0xBC, 0xD3, 0x0A, 0xF7, 0xE4, 0x58, 0x05, 0xB8, 0xB3, 0x45, 0x06,
        0xD0, 0x2C, 0x1E, 0x8F, 0xCA, 0x3F, 0x0F, 0x02, 0xC1, 0xAF, 0xBD, 0x03, 0x01, 0x13, 0x8A, 0x6B,
        0x3A, 0x91, 0x11, 0x41, 0x4F, 0x67, 0xDC, 0xEA, 0x97, 0xF2, 0xCF, 0xCE, 0xF0, 0xB4, 0xE6, 0x73,
        0x96, 0xAC, 0x74, 0x22, 0xE7, 0xAD, 0x35, 0x85, 0xE2, 0xF9, 0x37, 0xE8, 0x1C, 0x75, 0xDF, 0x6E,
        0x47, 0xF1, 0x1A, 0x71, 0x1D, 0x29, 0xC5, 0x89, 0x6F, 0xB7, 0x62, 0x0E, 0xAA, 0x18, 0xBE, 0x1B,
        0xFC, 0x56, 0x3E, 0x4B, 0xC6, 0xD2, 0x79, 0x20, 0x9A, 0xDB, 0xC0, 0xFE, 0x78, 0xCD, 0x5A, 0xF4,
        0x1F, 0xDD, 0xA8, 0x33, 0x88, 0x07, 0xC7, 0x31, 0xB1, 0x12, 0x10, 0x59, 0x27, 0x80, 0xEC, 0x5F,
        0x60, 0x51, 0x7F, 0xA9, 0x19, 0xB5, 0x4A, 0x0D, 0x2D, 0xE5, 0x7A, 0x9F, 0x93, 0xC9, 0x9C, 0xEF,
        0xA0, 0xE0, 0x3B, 0x4D, 0xAE, 0x2A, 0xF5, 0xB0, 0xC8, 0xEB, 0xBB, 0x3C, 0x83, 0x53, 0x99, 0x61,
        0x17, 0x2B, 0x04, 0x7E, 0xBA, 0x77, 0xD6, 0x26, 0xE1, 0x69, 0x14, 0x63, 0x55, 0x21, 0x0C, 0x7D
    );

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt extension should be used.  $mode should only, at present, be
     * CRYPT_AES_MODE_ECB or CRYPT_AES_MODE_CBC.  If not explictly set, CRYPT_AES_MODE_CBC will be used.
     *
     * @param optional Integer $mode
     * @return Crypt_AES
     * @access public
     */
    function Crypt_AES($mode = CRYPT_AES_MODE_CBC)
    {
        if ( !defined('CRYPT_AES_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && in_array('rijndael-128', mcrypt_list_algorithms()):
                    define('CRYPT_AES_MODE', CRYPT_AES_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_AES_MODE', CRYPT_AES_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_AES_MODE ) {
            case CRYPT_AES_MODE_MCRYPT:
                switch ($mode) {
                    case CRYPT_AES_MODE_ECB:
                        $this->paddable = true;
                        $this->mode = MCRYPT_MODE_ECB;
                        break;
                    case CRYPT_AES_MODE_CTR:
                        // ctr doesn't have a constant associated with it even though it appears to be fairly widely
                        // supported.  in lieu of knowing just how widely supported it is, i've, for now, opted not to
                        // include a compatibility layer.  the layer has been implemented but, for now, is commented out.
                        $this->mode = 'ctr';
                        //$this->mode = in_array('ctr', mcrypt_list_modes()) ? 'ctr' : CRYPT_AES_MODE_CTR;
                        break;
                    case CRYPT_AES_MODE_CFB:
                        $this->mode = 'ncfb';
                        break;
                    case CRYPT_AES_MODE_OFB:
                        $this->mode = MCRYPT_MODE_NOFB;
                        break;
                    case CRYPT_AES_MODE_CBC:
                    default:
                        $this->paddable = true;
                        $this->mode = MCRYPT_MODE_CBC;
                }

                break;
            default:
                switch ($mode) {
                    case CRYPT_AES_MODE_ECB:
                        $this->paddable = true;
                        $this->mode = CRYPT_RIJNDAEL_MODE_ECB;
                        break;
                    case CRYPT_AES_MODE_CTR:
                        $this->mode = CRYPT_RIJNDAEL_MODE_CTR;
                        break;
                    case CRYPT_AES_MODE_CFB:
                        $this->mode = CRYPT_RIJNDAEL_MODE_CFB;
                        break;
                    case CRYPT_AES_MODE_OFB:
                        $this->mode = CRYPT_RIJNDAEL_MODE_OFB;
                        break;
                    case CRYPT_AES_MODE_CBC:
                    default:
                        $this->paddable = true;
                        $this->mode = CRYPT_RIJNDAEL_MODE_CBC;
                }
        }

        if (CRYPT_AES_MODE == CRYPT_AES_MODE_INTERNAL) {
            parent::Crypt_Rijndael($this->mode);
        }
    }

    /**
     * Extended Crypt_Rijndael::_setup() 
     *
     * Optimizing the key schedule arrays ($w, $dw) for _encryptBlock() and _decryptBlock() after Crypt_Rijndael::_setup()
     *
     * @see Crypt_Rijndael::_setup()
     * @access private
     */
    function _setup() 
    {
        if (!$this->changed) {
            return;
        }

        $this->w = $this->dw = array();
        parent::_setup();

        $this->dw = array_reverse($this->dw);
        $w  = array_pop($this->w);
        $dw = array_pop($this->dw);
        foreach ($this->w as $r => $wr) {
            foreach ($wr as $c => $wc) {
                $w[]  = $wc;
                $dw[] = $this->dw[$r][$c];
            }
        }
        $this->w  = $w;
        $this->dw = $dw;
    }

    /**
     * Dummy function
     *
     * Since Crypt_AES extends Crypt_Rijndael, this function is, technically, available, but it doesn't do anything.
     *
     * @access public
     * @param Integer $length
     */
    function setBlockLength($length)
    {
        return;
    }

    /**
     * Sets the initialization vector. (optional)
     *
     * SetIV is not required when CRYPT_RIJNDAEL_MODE_ECB is being used.  If not explictly set, it'll be assumed
     * to be all zero's.
     *
     * @access public
     * @param String $iv
     */
    function setIV($iv)
    {
        parent::setIV($iv);
        if ( CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT ) {
            $this->changed = true;
        }
    }

    /**
     * Encrypts a message.
     *
     * $plaintext will be padded with up to 16 additional bytes.  Other AES implementations may or may not pad in the
     * same manner.  Other common approaches to padding and the reasons why it's necessary are discussed in the following
     * URL:
     *
     * {@link http://www.di-mgt.com.au/cryptopad.html http://www.di-mgt.com.au/cryptopad.html}
     *
     * An alternative to padding is to, separately, send the length of the file.  This is what SSH, in fact, does.
     * strlen($plaintext) will still need to be a multiple of 16, however, arbitrary values can be added to make it that
     * length.
     *
     * @see Crypt_AES::decrypt()
     * @access public
     * @param String $plaintext
     */
    function encrypt($plaintext)
    {
        if ( CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT ) {
            $this->_mcryptSetup();

            // re: http://phpseclib.sourceforge.net/cfb-demo.phps
            // using mcrypt's default handing of CFB the above would output two different things.  using phpseclib's
            // rewritten CFB implementation the above outputs the same thing twice.
            if ($this->mode == 'ncfb' && $this->continuousBuffer) {
                $iv = &$this->encryptIV;
                $pos = &$this->enbuffer['pos'];
                $len = strlen($plaintext);
                $ciphertext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = 16 - $pos;
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
                if ($len >= 16) {
                    if ($this->enbuffer['enmcrypt_init'] === false || $len > 280) {
                        if ($this->enbuffer['enmcrypt_init'] === true) {
                            mcrypt_generic_init($this->enmcrypt, $this->key, $iv);
                            $this->enbuffer['enmcrypt_init'] = false;
                        }
                        $ciphertext.= mcrypt_generic($this->enmcrypt, substr($plaintext, $i, $len - $len % 16));
                        $iv = substr($ciphertext, -16);
                        $len%= 16;
                    } else {
                        while ($len >= 16) {
                            $iv = mcrypt_generic($this->ecb, $iv) ^ substr($plaintext, $i, 16);
                            $ciphertext.= $iv;
                            $len-= 16;
                            $i+= 16;
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

            if ($this->paddable) {
                $plaintext = $this->_pad($plaintext);
            }

            $ciphertext = mcrypt_generic($this->enmcrypt, $plaintext);

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->enmcrypt, $this->key, $this->iv);
            }

            return $ciphertext;
        }

        return parent::encrypt($plaintext);
    }

    /**
     * Decrypts a message.
     *
     * If strlen($ciphertext) is not a multiple of 16, null bytes will be added to the end of the string until it is.
     *
     * @see Crypt_AES::encrypt()
     * @access public
     * @param String $ciphertext
     */
    function decrypt($ciphertext)
    {
        if ( CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT ) {
            $this->_mcryptSetup();

            if ($this->mode == 'ncfb' && $this->continuousBuffer) {
                $iv = &$this->decryptIV;
                $pos = &$this->debuffer['pos'];
                $len = strlen($ciphertext);
                $plaintext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = 16 - $pos;
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
                if ($len >= 16) {
                    $cb = substr($ciphertext, $i, $len - $len % 16);
                    $plaintext.= mcrypt_generic($this->ecb, $iv . $cb) ^ $cb;
                    $iv = substr($cb, -16);
                    $len%= 16;
                }
                if ($len) {
                    $iv = mcrypt_generic($this->ecb, $iv);
                    $plaintext.= $iv ^ substr($ciphertext, -$len);
                    $iv = substr_replace($iv, substr($ciphertext, -$len), 0, $len);
                    $pos = $len;
                }

                return $plaintext;
            }

            if ($this->paddable) {
                // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
                // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
                $ciphertext = str_pad($ciphertext, (strlen($ciphertext) + 15) & 0xFFFFFFF0, chr(0));
            }

            $plaintext = mdecrypt_generic($this->demcrypt, $ciphertext);

            if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->demcrypt, $this->key, $this->iv);
            }

            return $this->paddable ? $this->_unpad($plaintext) : $plaintext;
        }

        return parent::decrypt($ciphertext);
    }

    /**
     * Setup mcrypt
     *
     * Validates all the variables.
     *
     * @access private
     */
    function _mcryptSetup()
    {
        if (!$this->changed) {
            return;
        }

        if (!$this->explicit_key_length) {
            // this just copied from Crypt_Rijndael::_setup()
            $length = strlen($this->key) >> 2;
            if ($length > 8) {
                $length = 8;
            } else if ($length < 4) {
                $length = 4;
            }
            $this->Nk = $length;
            $this->key_size = $length << 2;
        }

        switch ($this->Nk) {
            case 4: // 128
                $this->key_size = 16;
                break;
            case 5: // 160
            case 6: // 192
                $this->key_size = 24;
                break;
            case 7: // 224
            case 8: // 256
                $this->key_size = 32;
        }

        $this->key = str_pad(substr($this->key, 0, $this->key_size), $this->key_size, chr(0));
        $this->encryptIV = $this->decryptIV = $this->iv = str_pad(substr($this->iv, 0, 16), 16, chr(0));

        if (!isset($this->enmcrypt)) {
            $mode = $this->mode;
            //$mode = $this->mode == CRYPT_AES_MODE_CTR ? MCRYPT_MODE_ECB : $this->mode;

            $this->demcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', $mode, '');
            $this->enmcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', $mode, '');

            if ($mode == 'ncfb') {
                $this->ecb = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
            }

        } // else should mcrypt_generic_deinit be called?

        mcrypt_generic_init($this->demcrypt, $this->key, $this->iv);
        mcrypt_generic_init($this->enmcrypt, $this->key, $this->iv);

        if ($this->mode == 'ncfb') {
            mcrypt_generic_init($this->ecb, $this->key, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
        }

        $this->changed = false;
    }

    /**
     * Encrypts a block
     *
     * Optimized over Crypt_Rijndael's implementation by means of loop unrolling.
     *
     * @see Crypt_Rijndael::_encryptBlock()
     * @access private
     * @param String $in
     * @return String
     */
    function _encryptBlock($in)
    {
        $state = unpack('N*', $in);

        $sbox = $this->sbox;
        $w  = $this->w;
        $t0 = $this->t0;
        $t1 = $this->t1;
        $t2 = $this->t2;
        $t3 = $this->t3;

        // addRoundKey
        $s0 = $state[1] ^ $w[4];
        $s1 = $state[2] ^ $w[5];
        $s2 = $state[3] ^ $w[6];
        $s3 = $state[4] ^ $w[7];

        // shiftRows + subWord + mixColumns + addRoundKey
        $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[8];
        $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[9];
        $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[10];
        $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[11];

        $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[12];
        $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[13];
        $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[14];
        $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[15];

        $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[16];
        $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[17];
        $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[18];
        $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[19];

        $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[20];
        $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[21];
        $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[22];
        $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[23];

        $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[24];
        $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[25];
        $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[26];
        $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[27];

        $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[28];
        $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[29];
        $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[30];
        $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[31];

        $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[32];
        $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[33];
        $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[34];
        $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[35];

        $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[36];
        $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[37];
        $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[38];
        $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[39];

        $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[40];
        $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[41];
        $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[42];
        $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[43];

        switch ($this->Nr) {
            case 10:
                break;

            case 14:
                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[44];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[45];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[46];
                $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[47];

                $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[48];
                $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[49];
                $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[50];
                $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[51];

                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[52];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[53];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[54];
                $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[55];

                $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[56];
                $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[57];
                $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[58];
                $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[59];
                break;

            case 12:
                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[44];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[45];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[46];
                $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[47];

                $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[48];
                $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[49];
                $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[50];
                $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[51];
                break;

            case 13:
                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[44];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[45];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[46];
                $s3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[47];

                $e0 = $t0[($s0 >> 24) & 0xff] ^ $t1[($s1 >> 16) & 0xff] ^ $t2[($s2 >> 8) & 0xff] ^ $t3[$s3 & 0xff] ^ $w[48];
                $e1 = $t0[($s1 >> 24) & 0xff] ^ $t1[($s2 >> 16) & 0xff] ^ $t2[($s3 >> 8) & 0xff] ^ $t3[$s0 & 0xff] ^ $w[49];
                $e2 = $t0[($s2 >> 24) & 0xff] ^ $t1[($s3 >> 16) & 0xff] ^ $t2[($s0 >> 8) & 0xff] ^ $t3[$s1 & 0xff] ^ $w[50];
                $e3 = $t0[($s3 >> 24) & 0xff] ^ $t1[($s0 >> 16) & 0xff] ^ $t2[($s1 >> 8) & 0xff] ^ $t3[$s2 & 0xff] ^ $w[51];

                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[52];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[53];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[54];
                $e3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[55];
                // Note: Here we skip $s3 but using $e3

                $e0 = $s0;
                $e1 = $s1;
                $e2 = $s2;
                // $e3 = $s3;
                break;

            default: // 11
                $s0 = $t0[($e0 >> 24) & 0xff] ^ $t1[($e1 >> 16) & 0xff] ^ $t2[($e2 >> 8) & 0xff] ^ $t3[$e3 & 0xff] ^ $w[44];
                $s1 = $t0[($e1 >> 24) & 0xff] ^ $t1[($e2 >> 16) & 0xff] ^ $t2[($e3 >> 8) & 0xff] ^ $t3[$e0 & 0xff] ^ $w[45];
                $s2 = $t0[($e2 >> 24) & 0xff] ^ $t1[($e3 >> 16) & 0xff] ^ $t2[($e0 >> 8) & 0xff] ^ $t3[$e1 & 0xff] ^ $w[46];
                $e3 = $t0[($e3 >> 24) & 0xff] ^ $t1[($e0 >> 16) & 0xff] ^ $t2[($e1 >> 8) & 0xff] ^ $t3[$e2 & 0xff] ^ $w[47];
                // Note: Here we skip $s3 but using $e3

                $e0 = $s0;
                $e1 = $s1;
                $e2 = $s2;
                // $e3 = $s3;
        }

        // subWord
        $e0 = $sbox[$e0 & 0xff] | ($sbox[($e0 >> 8) & 0xff] << 8) | ($sbox[($e0 >> 16) & 0xff] << 16) | ($sbox[($e0 >> 24) & 0xff] << 24);
        $e1 = $sbox[$e1 & 0xff] | ($sbox[($e1 >> 8) & 0xff] << 8) | ($sbox[($e1 >> 16) & 0xff] << 16) | ($sbox[($e1 >> 24) & 0xff] << 24);
        $e2 = $sbox[$e2 & 0xff] | ($sbox[($e2 >> 8) & 0xff] << 8) | ($sbox[($e2 >> 16) & 0xff] << 16) | ($sbox[($e2 >> 24) & 0xff] << 24);
        $e3 = $sbox[$e3 & 0xff] | ($sbox[($e3 >> 8) & 0xff] << 8) | ($sbox[($e3 >> 16) & 0xff] << 16) | ($sbox[($e3 >> 24) & 0xff] << 24);

        // shiftRows + addRoundKey
        return pack('N*',
            ($e0 & 0xFF000000) ^ ($e1 & 0x00FF0000) ^ ($e2 & 0x0000FF00) ^ ($e3 & 0x000000FF) ^ $w[0],
            ($e1 & 0xFF000000) ^ ($e2 & 0x00FF0000) ^ ($e3 & 0x0000FF00) ^ ($e0 & 0x000000FF) ^ $w[1],
            ($e2 & 0xFF000000) ^ ($e3 & 0x00FF0000) ^ ($e0 & 0x0000FF00) ^ ($e1 & 0x000000FF) ^ $w[2],
            ($e3 & 0xFF000000) ^ ($e0 & 0x00FF0000) ^ ($e1 & 0x0000FF00) ^ ($e2 & 0x000000FF) ^ $w[3]
        );
    }

    /**
     * Decrypts a block
     *
     * Optimized over Crypt_Rijndael's implementation by means of loop unrolling.
     *
     * @see Crypt_Rijndael::_decryptBlock()
     * @access private
     * @param String $in
     * @return String
     */
    function _decryptBlock($in)
    {
        $state = unpack('N*', $in);

        $sbox = $this->isbox;
        $dw  = $this->dw;
        $dt0 = $this->dt0;
        $dt1 = $this->dt1;
        $dt2 = $this->dt2;
        $dt3 = $this->dt3;

        // addRoundKey
        $s0 = $state[1] ^ $dw[4];
        $s1 = $state[2] ^ $dw[5];
        $s2 = $state[3] ^ $dw[6];
        $s3 = $state[4] ^ $dw[7];

        // invShiftRows + invSubBytes + invMixColumns + addRoundKey
        $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[8];
        $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[9];
        $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[10];
        $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[11];

        $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[12];
        $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[13];
        $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[14];
        $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[15];

        $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[16];
        $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[17];
        $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[18];
        $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[19];

        $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[20];
        $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[21];
        $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[22];
        $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[23];

        $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[24];
        $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[25];
        $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[26];
        $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[27];

        $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[28];
        $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[29];
        $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[30];
        $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[31];

        $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[32];
        $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[33];
        $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[34];
        $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[35];

        $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[36];
        $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[37];
        $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[38];
        $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[39];

        $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[40];
        $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[41];
        $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[42];
        $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[43];

        switch ($this->Nr) {
            case 10:
                break;

            case 14:
                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[44];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[45];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[46];
                $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[47];

                $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[48];
                $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[49];
                $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[50];
                $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[51];

                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[52];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[53];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[54];
                $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[55];

                $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[56];
                $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[57];
                $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[58];
                $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[59];
                break;

            case 12:
                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[44];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[45];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[46];
                $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[47];

                $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[48];
                $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[49];
                $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[50];
                $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[51];
                break;

            case 13:
                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[44];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[45];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[46];
                $s3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[47];

                $e0 = $dt0[($s0 >> 24) & 0xff] ^ $dt1[($s3 >> 16) & 0xff] ^ $dt2[($s2 >> 8) & 0xff] ^ $dt3[$s1 & 0xff] ^ $dw[48];
                $e1 = $dt0[($s1 >> 24) & 0xff] ^ $dt1[($s0 >> 16) & 0xff] ^ $dt2[($s3 >> 8) & 0xff] ^ $dt3[$s2 & 0xff] ^ $dw[49];
                $e2 = $dt0[($s2 >> 24) & 0xff] ^ $dt1[($s1 >> 16) & 0xff] ^ $dt2[($s0 >> 8) & 0xff] ^ $dt3[$s3 & 0xff] ^ $dw[50];
                $e3 = $dt0[($s3 >> 24) & 0xff] ^ $dt1[($s2 >> 16) & 0xff] ^ $dt2[($s1 >> 8) & 0xff] ^ $dt3[$s0 & 0xff] ^ $dw[51];

                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[52];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[53];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[54];
                $e3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[55];
                // Note: Here we skip $s3 but using $e3

                $e0 = $s0;
                $e1 = $s1;
                $e2 = $s2;
                // $e3 = $s3;
                break;

            default: // 11
                $s0 = $dt0[($e0 >> 24) & 0xff] ^ $dt1[($e3 >> 16) & 0xff] ^ $dt2[($e2 >> 8) & 0xff] ^ $dt3[$e1 & 0xff] ^ $dw[44];
                $s1 = $dt0[($e1 >> 24) & 0xff] ^ $dt1[($e0 >> 16) & 0xff] ^ $dt2[($e3 >> 8) & 0xff] ^ $dt3[$e2 & 0xff] ^ $dw[45];
                $s2 = $dt0[($e2 >> 24) & 0xff] ^ $dt1[($e1 >> 16) & 0xff] ^ $dt2[($e0 >> 8) & 0xff] ^ $dt3[$e3 & 0xff] ^ $dw[46];
                $e3 = $dt0[($e3 >> 24) & 0xff] ^ $dt1[($e2 >> 16) & 0xff] ^ $dt2[($e1 >> 8) & 0xff] ^ $dt3[$e0 & 0xff] ^ $dw[47];
                // Note: Here we skip $s3 but using $e3

                $e0 = $s0;
                $e1 = $s1;
                $e2 = $s2;
                // $e3 = $s3;
        }

        // invSubWord
        $e0 = $sbox[$e0 & 0xff] | ($sbox[($e0 >> 8) & 0xff] << 8) | ($sbox[($e0 >> 16) & 0xff] << 16) | ($sbox[($e0 >> 24) & 0xff] << 24);
        $e1 = $sbox[$e1 & 0xff] | ($sbox[($e1 >> 8) & 0xff] << 8) | ($sbox[($e1 >> 16) & 0xff] << 16) | ($sbox[($e1 >> 24) & 0xff] << 24);
        $e2 = $sbox[$e2 & 0xff] | ($sbox[($e2 >> 8) & 0xff] << 8) | ($sbox[($e2 >> 16) & 0xff] << 16) | ($sbox[($e2 >> 24) & 0xff] << 24);
        $e3 = $sbox[$e3 & 0xff] | ($sbox[($e3 >> 8) & 0xff] << 8) | ($sbox[($e3 >> 16) & 0xff] << 16) | ($sbox[($e3 >> 24) & 0xff] << 24);

        // invShiftRows + addRoundKey
        return pack('N*',
            ($e0 & 0xFF000000) ^ ($e3 & 0x00FF0000) ^ ($e2 & 0x0000FF00) ^ ($e1 & 0x000000FF) ^ $dw[0],
            ($e1 & 0xFF000000) ^ ($e0 & 0x00FF0000) ^ ($e3 & 0x0000FF00) ^ ($e2 & 0x000000FF) ^ $dw[1],
            ($e2 & 0xFF000000) ^ ($e1 & 0x00FF0000) ^ ($e0 & 0x0000FF00) ^ ($e3 & 0x000000FF) ^ $dw[2],
            ($e3 & 0xFF000000) ^ ($e2 & 0x00FF0000) ^ ($e1 & 0x0000FF00) ^ ($e0 & 0x000000FF) ^ $dw[3]
        );
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * The default behavior.
     *
     * @see Crypt_Rijndael::disableContinuousBuffer()
     * @access public
     */
    function enableContinuousBuffer()
    {
        parent::enableContinuousBuffer();

        if (CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT) {
            $this->enbuffer['enmcrypt_init'] = true;
            $this->debuffer['demcrypt_init'] = true;
        }
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see Crypt_Rijndael::enableContinuousBuffer()
     * @access public
     */
    function disableContinuousBuffer()
    {
        parent::disableContinuousBuffer();

        if (CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT) {
            mcrypt_generic_init($this->enmcrypt, $this->key, $this->iv);
            mcrypt_generic_init($this->demcrypt, $this->key, $this->iv);
        }
    }
}

// vim: ts=4:sw=4:et:
// vim6: fdl=1:
