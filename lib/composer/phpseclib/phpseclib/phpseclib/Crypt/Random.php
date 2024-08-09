<?php

/**
 * Random Number Generator
 *
 * PHP version 5
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    echo bin2hex(\phpseclib3\Crypt\Random::string(8));
 * ?>
 * </code>
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

/**
 * Pure-PHP Random Number Generator
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Random
{
    /**
     * Generate a random string.
     *
     * Although microoptimizations are generally discouraged as they impair readability this function is ripe with
     * microoptimizations because this function has the potential of being called a huge number of times.
     * eg. for RSA key generation.
     *
     * @param int $length
     * @throws \RuntimeException if a symmetric cipher is needed but not loaded
     * @return string
     */
    public static function string($length)
    {
        if (!$length) {
            return '';
        }

        try {
            return random_bytes($length);
        } catch (\Exception $e) {
            // random_compat will throw an Exception, which in PHP 5 does not implement Throwable
        } catch (\Throwable $e) {
            // If a sufficient source of randomness is unavailable, random_bytes() will throw an
            // object that implements the Throwable interface (Exception, TypeError, Error).
            // We don't actually need to do anything here. The string() method should just continue
            // as normal. Note, however, that if we don't have a sufficient source of randomness for
            // random_bytes(), most of the other calls here will fail too, so we'll end up using
            // the PHP implementation.
        }
        // at this point we have no choice but to use a pure-PHP CSPRNG

        // cascade entropy across multiple PHP instances by fixing the session and collecting all
        // environmental variables, including the previous session data and the current session
        // data.
        //
        // mt_rand seeds itself by looking at the PID and the time, both of which are (relatively)
        // easy to guess at. linux uses mouse clicks, keyboard timings, etc, as entropy sources, but
        // PHP isn't low level to be able to use those as sources and on a web server there's not likely
        // going to be a ton of keyboard or mouse action. web servers do have one thing that we can use
        // however, a ton of people visiting the website. obviously you don't want to base your seeding
        // solely on parameters a potential attacker sends but (1) not everything in $_SERVER is controlled
        // by the user and (2) this isn't just looking at the data sent by the current user - it's based
        // on the data sent by all users. one user requests the page and a hash of their info is saved.
        // another user visits the page and the serialization of their data is utilized along with the
        // server environment stuff and a hash of the previous http request data (which itself utilizes
        // a hash of the session data before that). certainly an attacker should be assumed to have
        // full control over his own http requests. he, however, is not going to have control over
        // everyone's http requests.
        static $crypto = false, $v;
        if ($crypto === false) {
            // save old session data
            $old_session_id = session_id();
            $old_use_cookies = ini_get('session.use_cookies');
            $old_session_cache_limiter = session_cache_limiter();
            $_OLD_SESSION = isset($_SESSION) ? $_SESSION : false;
            if ($old_session_id != '') {
                session_write_close();
            }

            session_id(1);
            ini_set('session.use_cookies', 0);
            session_cache_limiter('');
            session_start();

            $v = (isset($_SERVER) ? self::safe_serialize($_SERVER) : '') .
                 (isset($_POST) ? self::safe_serialize($_POST) : '') .
                 (isset($_GET) ? self::safe_serialize($_GET) : '') .
                 (isset($_COOKIE) ? self::safe_serialize($_COOKIE) : '') .
                 // as of PHP 8.1 $GLOBALS can't be accessed by reference, which eliminates
                 // the need for phpseclib_safe_serialize. see https://wiki.php.net/rfc/restrict_globals_usage
                 // for more info
                 (version_compare(PHP_VERSION, '8.1.0', '>=') ? serialize($GLOBALS) : self::safe_serialize($GLOBALS)) .
                 self::safe_serialize($_SESSION) .
                 self::safe_serialize($_OLD_SESSION);
            $v = $seed = $_SESSION['seed'] = sha1($v, true);
            if (!isset($_SESSION['count'])) {
                $_SESSION['count'] = 0;
            }
            $_SESSION['count']++;

            session_write_close();

            // restore old session data
            if ($old_session_id != '') {
                session_id($old_session_id);
                session_start();
                ini_set('session.use_cookies', $old_use_cookies);
                session_cache_limiter($old_session_cache_limiter);
            } else {
                if ($_OLD_SESSION !== false) {
                    $_SESSION = $_OLD_SESSION;
                    unset($_OLD_SESSION);
                } else {
                    unset($_SESSION);
                }
            }

            // in SSH2 a shared secret and an exchange hash are generated through the key exchange process.
            // the IV client to server is the hash of that "nonce" with the letter A and for the encryption key it's the letter C.
            // if the hash doesn't produce enough a key or an IV that's long enough concat successive hashes of the
            // original hash and the current hash. we'll be emulating that. for more info see the following URL:
            //
            // http://tools.ietf.org/html/rfc4253#section-7.2
            //
            // see the is_string($crypto) part for an example of how to expand the keys
            $key = sha1($seed . 'A', true);
            $iv = sha1($seed . 'C', true);

            // ciphers are used as per the nist.gov link below. also, see this link:
            //
            // http://en.wikipedia.org/wiki/Cryptographically_secure_pseudorandom_number_generator#Designs_based_on_cryptographic_primitives
            switch (true) {
                case class_exists('\phpseclib3\Crypt\AES'):
                    $crypto = new AES('ctr');
                    break;
                case class_exists('\phpseclib3\Crypt\Twofish'):
                    $crypto = new Twofish('ctr');
                    break;
                case class_exists('\phpseclib3\Crypt\Blowfish'):
                    $crypto = new Blowfish('ctr');
                    break;
                case class_exists('\phpseclib3\Crypt\TripleDES'):
                    $crypto = new TripleDES('ctr');
                    break;
                case class_exists('\phpseclib3\Crypt\DES'):
                    $crypto = new DES('ctr');
                    break;
                case class_exists('\phpseclib3\Crypt\RC4'):
                    $crypto = new RC4();
                    break;
                default:
                    throw new \RuntimeException(__CLASS__ . ' requires at least one symmetric cipher be loaded');
            }

            $crypto->setKey(substr($key, 0, $crypto->getKeyLength() >> 3));
            $crypto->setIV(substr($iv, 0, $crypto->getBlockLength() >> 3));
            $crypto->enableContinuousBuffer();
        }

        //return $crypto->encrypt(str_repeat("\0", $length));

        // the following is based off of ANSI X9.31:
        //
        // http://csrc.nist.gov/groups/STM/cavp/documents/rng/931rngext.pdf
        //
        // OpenSSL uses that same standard for it's random numbers:
        //
        // http://www.opensource.apple.com/source/OpenSSL/OpenSSL-38/openssl/fips-1.0/rand/fips_rand.c
        // (do a search for "ANS X9.31 A.2.4")
        $result = '';
        while (strlen($result) < $length) {
            $i = $crypto->encrypt(microtime()); // strlen(microtime()) == 21
            $r = $crypto->encrypt($i ^ $v); // strlen($v) == 20
            $v = $crypto->encrypt($r ^ $i); // strlen($r) == 20
            $result .= $r;
        }

        return substr($result, 0, $length);
    }

    /**
     * Safely serialize variables
     *
     * If a class has a private __sleep() it'll emit a warning
     * @return mixed
     * @param mixed $arr
     */
    private static function safe_serialize(&$arr)
    {
        if (is_object($arr)) {
            return '';
        }
        if (!is_array($arr)) {
            return serialize($arr);
        }
        // prevent circular array recursion
        if (isset($arr['__phpseclib_marker'])) {
            return '';
        }
        $safearr = [];
        $arr['__phpseclib_marker'] = true;
        foreach (array_keys($arr) as $key) {
            // do not recurse on the '__phpseclib_marker' key itself, for smaller memory usage
            if ($key !== '__phpseclib_marker') {
                $safearr[$key] = self::safe_serialize($arr[$key]);
            }
        }
        unset($arr['__phpseclib_marker']);
        return serialize($safearr);
    }
}
