<?php

/**
 * OpenSSH Key Handler
 *
 * PHP version 5
 *
 * Place in $HOME/.ssh/authorized_keys
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;
use phpseclib3\Exception\BadDecryptionException;

/**
 * OpenSSH Formatted RSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class OpenSSH
{
    /**
     * Default comment
     *
     * @var string
     */
    protected static $comment = 'phpseclib-generated-key';

    /**
     * Binary key flag
     *
     * @var bool
     */
    protected static $binary = false;

    /**
     * Sets the default comment
     *
     * @param string $comment
     */
    public static function setComment($comment)
    {
        self::$comment = str_replace(["\r", "\n"], '', $comment);
    }

    /**
     * Break a public or private key down into its constituent components
     *
     * $type can be either ssh-dss or ssh-rsa
     *
     * @param string $key
     * @param string $password
     * @return array
     */
    public static function load($key, $password = '')
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        // key format is described here:
        // https://cvsweb.openbsd.org/cgi-bin/cvsweb/src/usr.bin/ssh/PROTOCOL.key?annotate=HEAD

        if (strpos($key, 'BEGIN OPENSSH PRIVATE KEY') !== false) {
            $key = preg_replace('#(?:^-.*?-[\r\n]*$)|\s#ms', '', $key);
            $key = Strings::base64_decode($key);
            $magic = Strings::shift($key, 15);
            if ($magic != "openssh-key-v1\0") {
                throw new \RuntimeException('Expected openssh-key-v1');
            }
            list($ciphername, $kdfname, $kdfoptions, $numKeys) = Strings::unpackSSH2('sssN', $key);
            if ($numKeys != 1) {
                // if we wanted to support multiple keys we could update PublicKeyLoader to preview what the # of keys
                // would be; it'd then call Common\Keys\OpenSSH.php::load() and get the paddedKey. it'd then pass
                // that to the appropriate key loading parser $numKey times or something
                throw new \RuntimeException('Although the OpenSSH private key format supports multiple keys phpseclib does not');
            }
            switch ($ciphername) {
                case 'none':
                    break;
                case 'aes256-ctr':
                    if ($kdfname != 'bcrypt') {
                        throw new \RuntimeException('Only the bcrypt kdf is supported (' . $kdfname . ' encountered)');
                    }
                    list($salt, $rounds) = Strings::unpackSSH2('sN', $kdfoptions);
                    $crypto = new AES('ctr');
                    //$crypto->setKeyLength(256);
                    //$crypto->disablePadding();
                    $crypto->setPassword($password, 'bcrypt', $salt, $rounds, 32);
                    break;
                default:
                    throw new \RuntimeException('The only supported ciphers are: none, aes256-ctr (' . $ciphername . ' is being used)');
            }

            list($publicKey, $paddedKey) = Strings::unpackSSH2('ss', $key);
            list($type) = Strings::unpackSSH2('s', $publicKey);
            if (isset($crypto)) {
                $paddedKey = $crypto->decrypt($paddedKey);
            }
            list($checkint1, $checkint2) = Strings::unpackSSH2('NN', $paddedKey);
            // any leftover bytes in $paddedKey are for padding? but they should be sequential bytes. eg. 1, 2, 3, etc.
            if ($checkint1 != $checkint2) {
                if (isset($crypto)) {
                    throw new BadDecryptionException('Unable to decrypt key - please verify the password you are using');
                }
                throw new \RuntimeException("The two checkints do not match ($checkint1 vs. $checkint2)");
            }
            self::checkType($type);

            return compact('type', 'publicKey', 'paddedKey');
        }

        $parts = explode(' ', $key, 3);

        if (!isset($parts[1])) {
            $key = base64_decode($parts[0]);
            $comment = false;
        } else {
            $asciiType = $parts[0];
            self::checkType($parts[0]);
            $key = base64_decode($parts[1]);
            $comment = isset($parts[2]) ? $parts[2] : false;
        }
        if ($key === false) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        list($type) = Strings::unpackSSH2('s', $key);
        self::checkType($type);
        if (isset($asciiType) && $asciiType != $type) {
            throw new \RuntimeException('Two different types of keys are claimed: ' . $asciiType . ' and ' . $type);
        }
        if (strlen($key) <= 4) {
            throw new \UnexpectedValueException('Key appears to be malformed');
        }

        $publicKey = $key;

        return compact('type', 'publicKey', 'comment');
    }

    /**
     * Toggle between binary and printable keys
     *
     * Printable keys are what are generated by default. These are the ones that go in
     * $HOME/.ssh/authorized_key.
     *
     * @param bool $enabled
     */
    public static function setBinaryOutput($enabled)
    {
        self::$binary = $enabled;
    }

    /**
     * Checks to see if the type is valid
     *
     * @param string $candidate
     */
    private static function checkType($candidate)
    {
        if (!in_array($candidate, static::$types)) {
            throw new \RuntimeException("The key type ($candidate) is not equal to: " . implode(',', static::$types));
        }
    }

    /**
     * Wrap a private key appropriately
     *
     * @param string $publicKey
     * @param string $privateKey
     * @param string $password
     * @param array $options
     * @return string
     */
    protected static function wrapPrivateKey($publicKey, $privateKey, $password, $options)
    {
        list(, $checkint) = unpack('N', Random::string(4));

        $comment = isset($options['comment']) ? $options['comment'] : self::$comment;
        $paddedKey = Strings::packSSH2('NN', $checkint, $checkint) .
                     $privateKey .
                     Strings::packSSH2('s', $comment);

        $usesEncryption = !empty($password) && is_string($password);

        /*
           from http://tools.ietf.org/html/rfc4253#section-6 :

           Note that the length of the concatenation of 'packet_length',
           'padding_length', 'payload', and 'random padding' MUST be a multiple
           of the cipher block size or 8, whichever is larger.
         */
        $blockSize = $usesEncryption ? 16 : 8;
        $paddingLength = (($blockSize - 1) * strlen($paddedKey)) % $blockSize;
        for ($i = 1; $i <= $paddingLength; $i++) {
            $paddedKey .= chr($i);
        }
        if (!$usesEncryption) {
            $key = Strings::packSSH2('sssNss', 'none', 'none', '', 1, $publicKey, $paddedKey);
        } else {
            $rounds = isset($options['rounds']) ? $options['rounds'] : 16;
            $salt = Random::string(16);
            $kdfoptions = Strings::packSSH2('sN', $salt, $rounds);
            $crypto = new AES('ctr');
            $crypto->setPassword($password, 'bcrypt', $salt, $rounds, 32);
            $paddedKey = $crypto->encrypt($paddedKey);
            $key = Strings::packSSH2('sssNss', 'aes256-ctr', 'bcrypt', $kdfoptions, 1, $publicKey, $paddedKey);
        }
        $key = "openssh-key-v1\0$key";

        return "-----BEGIN OPENSSH PRIVATE KEY-----\n" .
               chunk_split(Strings::base64_encode($key), 70, "\n") .
               "-----END OPENSSH PRIVATE KEY-----\n";
    }
}
