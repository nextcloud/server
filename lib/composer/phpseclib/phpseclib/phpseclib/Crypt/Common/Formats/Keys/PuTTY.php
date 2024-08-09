<?php

/**
 * PuTTY Formatted Key Handler
 *
 * See PuTTY's SSHPUBK.C and https://tartarus.org/~simon/putty-snapshots/htmldoc/AppendixC.html
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Hash;
use phpseclib3\Crypt\Random;
use phpseclib3\Exception\UnsupportedAlgorithmException;

/**
 * PuTTY Formatted Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PuTTY
{
    /**
     * Default comment
     *
     * @var string
     */
    private static $comment = 'phpseclib-generated-key';

    /**
     * Default version
     *
     * @var int
     */
    private static $version = 2;

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
     * Sets the default version
     *
     * @param int $version
     */
    public static function setVersion($version)
    {
        if ($version != 2 && $version != 3) {
            throw new \RuntimeException('Only supported versions are 2 and 3');
        }
        self::$version = $version;
    }

    /**
     * Generate a symmetric key for PuTTY v2 keys
     *
     * @param string $password
     * @param int $length
     * @return string
     */
    private static function generateV2Key($password, $length)
    {
        $symkey = '';
        $sequence = 0;
        while (strlen($symkey) < $length) {
            $temp = pack('Na*', $sequence++, $password);
            $symkey .= Strings::hex2bin(sha1($temp));
        }
        return substr($symkey, 0, $length);
    }

    /**
     * Generate a symmetric key for PuTTY v3 keys
     *
     * @param string $password
     * @param string $flavour
     * @param int $memory
     * @param int $passes
     * @param string $salt
     * @return array
     */
    private static function generateV3Key($password, $flavour, $memory, $passes, $salt)
    {
        if (!function_exists('sodium_crypto_pwhash')) {
            throw new \RuntimeException('sodium_crypto_pwhash needs to exist for Argon2 password hasing');
        }

        switch ($flavour) {
            case 'Argon2i':
                $flavour = SODIUM_CRYPTO_PWHASH_ALG_ARGON2I13;
                break;
            case 'Argon2id':
                $flavour = SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13;
                break;
            default:
                throw new UnsupportedAlgorithmException('Only Argon2i and Argon2id are supported');
        }

        $length = 80; // keylen + ivlen + mac_keylen
        $temp = sodium_crypto_pwhash($length, $password, $salt, $passes, $memory << 10, $flavour);

        $symkey = substr($temp, 0, 32);
        $symiv = substr($temp, 32, 16);
        $hashkey = substr($temp, -32);

        return compact('symkey', 'symiv', 'hashkey');
    }

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password
     * @return array
     */
    public static function load($key, $password)
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        if (strpos($key, 'BEGIN SSH2 PUBLIC KEY') !== false) {
            $lines = preg_split('#[\r\n]+#', $key);
            switch (true) {
                case $lines[0] != '---- BEGIN SSH2 PUBLIC KEY ----':
                    throw new \UnexpectedValueException('Key doesn\'t start with ---- BEGIN SSH2 PUBLIC KEY ----');
                case $lines[count($lines) - 1] != '---- END SSH2 PUBLIC KEY ----':
                    throw new \UnexpectedValueException('Key doesn\'t end with ---- END SSH2 PUBLIC KEY ----');
            }
            $lines = array_splice($lines, 1, -1);
            $lines = array_map(function ($line) {
                return rtrim($line, "\r\n");
            }, $lines);
            $data = $current = '';
            $values = [];
            $in_value = false;
            foreach ($lines as $line) {
                switch (true) {
                    case preg_match('#^(.*?): (.*)#', $line, $match):
                        $in_value = $line[strlen($line) - 1] == '\\';
                        $current = strtolower($match[1]);
                        $values[$current] = $in_value ? substr($match[2], 0, -1) : $match[2];
                        break;
                    case $in_value:
                        $in_value = $line[strlen($line) - 1] == '\\';
                        $values[$current] .= $in_value ? substr($line, 0, -1) : $line;
                        break;
                    default:
                        $data .= $line;
                }
            }

            $components = call_user_func([static::PUBLIC_HANDLER, 'load'], $data);
            if ($components === false) {
                throw new \UnexpectedValueException('Unable to decode public key');
            }
            $components += $values;
            $components['comment'] = str_replace(['\\\\', '\"'], ['\\', '"'], $values['comment']);

            return $components;
        }

        $components = [];

        $key = preg_split('#\r\n|\r|\n#', trim($key));
        if (Strings::shift($key[0], strlen('PuTTY-User-Key-File-')) != 'PuTTY-User-Key-File-') {
            return false;
        }
        $version = (int) Strings::shift($key[0], 3); // should be either "2: " or "3: 0" prior to int casting
        if ($version != 2 && $version != 3) {
            throw new \RuntimeException('Only v2 and v3 PuTTY private keys are supported');
        }
        $components['type'] = $type = rtrim($key[0]);
        if (!in_array($type, static::$types)) {
            $error = count(static::$types) == 1 ?
                'Only ' . static::$types[0] . ' keys are supported. ' :
                '';
            throw new UnsupportedAlgorithmException($error . 'This is an unsupported ' . $type . ' key');
        }
        $encryption = trim(preg_replace('#Encryption: (.+)#', '$1', $key[1]));
        $components['comment'] = trim(preg_replace('#Comment: (.+)#', '$1', $key[2]));

        $publicLength = trim(preg_replace('#Public-Lines: (\d+)#', '$1', $key[3]));
        $public = Strings::base64_decode(implode('', array_map('trim', array_slice($key, 4, $publicLength))));

        $source = Strings::packSSH2('ssss', $type, $encryption, $components['comment'], $public);

        extract(unpack('Nlength', Strings::shift($public, 4)));
        $newtype = Strings::shift($public, $length);
        if ($newtype != $type) {
            throw new \RuntimeException('The binary type does not match the human readable type field');
        }

        $components['public'] = $public;

        switch ($version) {
            case 3:
                $hashkey = '';
                break;
            case 2:
                $hashkey = 'putty-private-key-file-mac-key';
        }

        $offset = $publicLength + 4;
        switch ($encryption) {
            case 'aes256-cbc':
                $crypto = new AES('cbc');
                switch ($version) {
                    case 3:
                        $flavour = trim(preg_replace('#Key-Derivation: (.*)#', '$1', $key[$offset++]));
                        $memory = trim(preg_replace('#Argon2-Memory: (\d+)#', '$1', $key[$offset++]));
                        $passes = trim(preg_replace('#Argon2-Passes: (\d+)#', '$1', $key[$offset++]));
                        $parallelism = trim(preg_replace('#Argon2-Parallelism: (\d+)#', '$1', $key[$offset++]));
                        $salt = Strings::hex2bin(trim(preg_replace('#Argon2-Salt: ([0-9a-f]+)#', '$1', $key[$offset++])));

                        extract(self::generateV3Key($password, $flavour, $memory, $passes, $salt));

                        break;
                    case 2:
                        $symkey = self::generateV2Key($password, 32);
                        $symiv = str_repeat("\0", $crypto->getBlockLength() >> 3);
                        $hashkey .= $password;
                }
        }

        switch ($version) {
            case 3:
                $hash = new Hash('sha256');
                $hash->setKey($hashkey);
                break;
            case 2:
                $hash = new Hash('sha1');
                $hash->setKey(sha1($hashkey, true));
        }

        $privateLength = trim(preg_replace('#Private-Lines: (\d+)#', '$1', $key[$offset++]));
        $private = Strings::base64_decode(implode('', array_map('trim', array_slice($key, $offset, $privateLength))));

        if ($encryption != 'none') {
            $crypto->setKey($symkey);
            $crypto->setIV($symiv);
            $crypto->disablePadding();
            $private = $crypto->decrypt($private);
        }

        $source .= Strings::packSSH2('s', $private);

        $hmac = trim(preg_replace('#Private-MAC: (.+)#', '$1', $key[$offset + $privateLength]));
        $hmac = Strings::hex2bin($hmac);

        if (!hash_equals($hash->hash($source), $hmac)) {
            throw new \UnexpectedValueException('MAC validation error');
        }

        $components['private'] = $private;

        return $components;
    }

    /**
     * Wrap a private key appropriately
     *
     * @param string $public
     * @param string $private
     * @param string $type
     * @param string $password
     * @param array $options optional
     * @return string
     */
    protected static function wrapPrivateKey($public, $private, $type, $password, array $options = [])
    {
        $encryption = (!empty($password) || is_string($password)) ? 'aes256-cbc' : 'none';
        $comment = isset($options['comment']) ? $options['comment'] : self::$comment;
        $version = isset($options['version']) ? $options['version'] : self::$version;

        $key = "PuTTY-User-Key-File-$version: $type\r\n";
        $key .= "Encryption: $encryption\r\n";
        $key .= "Comment: $comment\r\n";

        $public = Strings::packSSH2('s', $type) . $public;

        $source = Strings::packSSH2('ssss', $type, $encryption, $comment, $public);

        $public = Strings::base64_encode($public);
        $key .= "Public-Lines: " . ((strlen($public) + 63) >> 6) . "\r\n";
        $key .= chunk_split($public, 64);

        if (empty($password) && !is_string($password)) {
            $source .= Strings::packSSH2('s', $private);
            switch ($version) {
                case 3:
                    $hash = new Hash('sha256');
                    $hash->setKey('');
                    break;
                case 2:
                    $hash = new Hash('sha1');
                    $hash->setKey(sha1('putty-private-key-file-mac-key', true));
            }
        } else {
            $private .= Random::string(16 - (strlen($private) & 15));
            $source .= Strings::packSSH2('s', $private);
            $crypto = new AES('cbc');

            switch ($version) {
                case 3:
                    $salt = Random::string(16);
                    $key .= "Key-Derivation: Argon2id\r\n";
                    $key .= "Argon2-Memory: 8192\r\n";
                    $key .= "Argon2-Passes: 13\r\n";
                    $key .= "Argon2-Parallelism: 1\r\n";
                    $key .= "Argon2-Salt: " . Strings::bin2hex($salt) . "\r\n";
                    extract(self::generateV3Key($password, 'Argon2id', 8192, 13, $salt));

                    $hash = new Hash('sha256');
                    $hash->setKey($hashkey);

                    break;
                case 2:
                    $symkey = self::generateV2Key($password, 32);
                    $symiv = str_repeat("\0", $crypto->getBlockLength() >> 3);
                    $hashkey = 'putty-private-key-file-mac-key' . $password;

                    $hash = new Hash('sha1');
                    $hash->setKey(sha1($hashkey, true));
            }

            $crypto->setKey($symkey);
            $crypto->setIV($symiv);
            $crypto->disablePadding();
            $private = $crypto->encrypt($private);
            $mac = $hash->hash($source);
        }

        $private = Strings::base64_encode($private);
        $key .= 'Private-Lines: ' . ((strlen($private) + 63) >> 6) . "\r\n";
        $key .= chunk_split($private, 64);
        $key .= 'Private-MAC: ' . Strings::bin2hex($hash->hash($source)) . "\r\n";

        return $key;
    }

    /**
     * Wrap a public key appropriately
     *
     * This is basically the format described in RFC 4716 (https://tools.ietf.org/html/rfc4716)
     *
     * @param string $key
     * @param string $type
     * @return string
     */
    protected static function wrapPublicKey($key, $type)
    {
        $key = pack('Na*a*', strlen($type), $type, $key);
        $key = "---- BEGIN SSH2 PUBLIC KEY ----\r\n" .
               'Comment: "' . str_replace(['\\', '"'], ['\\\\', '\"'], self::$comment) . "\"\r\n" .
               chunk_split(Strings::base64_encode($key), 64) .
               '---- END SSH2 PUBLIC KEY ----';
        return $key;
    }
}
