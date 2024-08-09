<?php

/**
 * Pure-PHP (EC)DH implementation
 *
 * PHP version 5
 *
 * Here's an example of how to compute a shared secret with this library:
 * <code>
 * <?php
 * include 'vendor/autoload.php';
 *
 * $ourPrivate = \phpseclib3\Crypt\DH::createKey();
 * $secret = DH::computeSecret($ourPrivate, $theirPublic);
 *
 * ?>
 * </code>
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\DH\Parameters;
use phpseclib3\Crypt\DH\PrivateKey;
use phpseclib3\Crypt\DH\PublicKey;
use phpseclib3\Exception\NoKeyLoadedException;
use phpseclib3\Exception\UnsupportedOperationException;
use phpseclib3\Math\BigInteger;

/**
 * Pure-PHP (EC)DH implementation
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class DH extends AsymmetricKey
{
    /**
     * Algorithm Name
     *
     * @var string
     */
    const ALGORITHM = 'DH';

    /**
     * DH prime
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $prime;

    /**
     * DH Base
     *
     * Prime divisor of p-1
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $base;

    /**
     * Public Key
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $publicKey;

    /**
     * Create DH parameters
     *
     * This method is a bit polymorphic. It can take any of the following:
     *  - two BigInteger's (prime and base)
     *  - an integer representing the size of the prime in bits (the base is assumed to be 2)
     *  - a string (eg. diffie-hellman-group14-sha1)
     *
     * @return Parameters
     */
    public static function createParameters(...$args)
    {
        $class = new \ReflectionClass(static::class);
        if ($class->isFinal()) {
            throw new \RuntimeException('createParameters() should not be called from final classes (' . static::class . ')');
        }

        $params = new Parameters();
        if (count($args) == 2 && $args[0] instanceof BigInteger && $args[1] instanceof BigInteger) {
            //if (!$args[0]->isPrime()) {
            //    throw new \InvalidArgumentException('The first parameter should be a prime number');
            //}
            $params->prime = $args[0];
            $params->base = $args[1];
            return $params;
        } elseif (count($args) == 1 && is_numeric($args[0])) {
            $params->prime = BigInteger::randomPrime($args[0]);
            $params->base = new BigInteger(2);
            return $params;
        } elseif (count($args) != 1 || !is_string($args[0])) {
            throw new \InvalidArgumentException('Valid parameters are either: two BigInteger\'s (prime and base), a single integer (the length of the prime; base is assumed to be 2) or a string');
        }
        switch ($args[0]) {
            // see http://tools.ietf.org/html/rfc2409#section-6.2 and
            // http://tools.ietf.org/html/rfc2412, appendex E
            case 'diffie-hellman-group1-sha1':
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF';
                break;
            // see http://tools.ietf.org/html/rfc3526#section-3
            case 'diffie-hellman-group14-sha1': // 2048-bit MODP Group
            case 'diffie-hellman-group14-sha256':
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                         '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                         '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                         'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                         '3995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF';
                break;
            // see https://tools.ietf.org/html/rfc3526#section-4
            case 'diffie-hellman-group15-sha512': // 3072-bit MODP Group
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                         '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                         '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                         'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                         '3995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33' .
                         'A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7' .
                         'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864' .
                         'D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E2' .
                         '08E24FA074E5AB3143DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF';
                break;
            // see https://tools.ietf.org/html/rfc3526#section-5
            case 'diffie-hellman-group16-sha512': // 4096-bit MODP Group
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                         '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                         '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                         'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                         '3995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33' .
                         'A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7' .
                         'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864' .
                         'D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E2' .
                         '08E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D7' .
                         '88719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8' .
                         'DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2' .
                         '233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA9' .
                         '93B4EA988D8FDDC186FFB7DC90A6C08F4DF435C934063199FFFFFFFFFFFFFFFF';
                break;
            // see https://tools.ietf.org/html/rfc3526#section-6
            case 'diffie-hellman-group17-sha512': // 6144-bit MODP Group
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                         '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                         '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                         'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                         '3995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33' .
                         'A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7' .
                         'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864' .
                         'D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E2' .
                         '08E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D7' .
                         '88719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8' .
                         'DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2' .
                         '233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA9' .
                         '93B4EA988D8FDDC186FFB7DC90A6C08F4DF435C93402849236C3FAB4D27C7026' .
                         'C1D4DCB2602646DEC9751E763DBA37BDF8FF9406AD9E530EE5DB382F413001AE' .
                         'B06A53ED9027D831179727B0865A8918DA3EDBEBCF9B14ED44CE6CBACED4BB1B' .
                         'DB7F1447E6CC254B332051512BD7AF426FB8F401378CD2BF5983CA01C64B92EC' .
                         'F032EA15D1721D03F482D7CE6E74FEF6D55E702F46980C82B5A84031900B1C9E' .
                         '59E7C97FBEC7E8F323A97A7E36CC88BE0F1D45B7FF585AC54BD407B22B4154AA' .
                         'CC8F6D7EBF48E1D814CC5ED20F8037E0A79715EEF29BE32806A1D58BB7C5DA76' .
                         'F550AA3D8A1FBFF0EB19CCB1A313D55CDA56C9EC2EF29632387FE8D76E3C0468' .
                         '043E8F663F4860EE12BF2D5B0B7474D6E694F91E6DCC4024FFFFFFFFFFFFFFFF';
                break;
            // see https://tools.ietf.org/html/rfc3526#section-7
            case 'diffie-hellman-group18-sha512': // 8192-bit MODP Group
                $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                         '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                         '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                         'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                         '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                         '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                         'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                         '3995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33' .
                         'A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7' .
                         'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864' .
                         'D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E2' .
                         '08E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D7' .
                         '88719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8' .
                         'DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2' .
                         '233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA9' .
                         '93B4EA988D8FDDC186FFB7DC90A6C08F4DF435C93402849236C3FAB4D27C7026' .
                         'C1D4DCB2602646DEC9751E763DBA37BDF8FF9406AD9E530EE5DB382F413001AE' .
                         'B06A53ED9027D831179727B0865A8918DA3EDBEBCF9B14ED44CE6CBACED4BB1B' .
                         'DB7F1447E6CC254B332051512BD7AF426FB8F401378CD2BF5983CA01C64B92EC' .
                         'F032EA15D1721D03F482D7CE6E74FEF6D55E702F46980C82B5A84031900B1C9E' .
                         '59E7C97FBEC7E8F323A97A7E36CC88BE0F1D45B7FF585AC54BD407B22B4154AA' .
                         'CC8F6D7EBF48E1D814CC5ED20F8037E0A79715EEF29BE32806A1D58BB7C5DA76' .
                         'F550AA3D8A1FBFF0EB19CCB1A313D55CDA56C9EC2EF29632387FE8D76E3C0468' .
                         '043E8F663F4860EE12BF2D5B0B7474D6E694F91E6DBE115974A3926F12FEE5E4' .
                         '38777CB6A932DF8CD8BEC4D073B931BA3BC832B68D9DD300741FA7BF8AFC47ED' .
                         '2576F6936BA424663AAB639C5AE4F5683423B4742BF1C978238F16CBE39D652D' .
                         'E3FDB8BEFC848AD922222E04A4037C0713EB57A81A23F0C73473FC646CEA306B' .
                         '4BCBC8862F8385DDFA9D4B7FA2C087E879683303ED5BDD3A062B3CF5B3A278A6' .
                         '6D2A13F83F44F82DDF310EE074AB6A364597E899A0255DC164F31CC50846851D' .
                         'F9AB48195DED7EA1B1D510BD7EE74D73FAF36BC31ECFA268359046F4EB879F92' .
                         '4009438B481C6CD7889A002ED5EE382BC9190DA6FC026E479558E4475677E9AA' .
                         '9E3050E2765694DFC81F56E880B96E7160C980DD98EDD3DFFFFFFFFFFFFFFFFF';
                break;
            default:
                throw new \InvalidArgumentException('Invalid named prime provided');
        }

        $params->prime = new BigInteger($prime, 16);
        $params->base = new BigInteger(2);

        return $params;
    }

    /**
     * Create public / private key pair.
     *
     * The rationale for the second parameter is described in http://tools.ietf.org/html/rfc4419#section-6.2 :
     *
     * "To increase the speed of the key exchange, both client and server may
     *  reduce the size of their private exponents.  It should be at least
     *  twice as long as the key material that is generated from the shared
     *  secret.  For more details, see the paper by van Oorschot and Wiener
     *  [VAN-OORSCHOT]."
     *
     * $length is in bits
     *
     * @param Parameters $params
     * @param int $length optional
     * @return DH\PrivateKey
     */
    public static function createKey(Parameters $params, $length = 0)
    {
        $class = new \ReflectionClass(static::class);
        if ($class->isFinal()) {
            throw new \RuntimeException('createKey() should not be called from final classes (' . static::class . ')');
        }

        $one = new BigInteger(1);
        if ($length) {
            $max = $one->bitwise_leftShift($length);
            $max = $max->subtract($one);
        } else {
            $max = $params->prime->subtract($one);
        }

        $key = new PrivateKey();
        $key->prime = $params->prime;
        $key->base = $params->base;
        $key->privateKey = BigInteger::randomRange($one, $max);
        $key->publicKey = $key->base->powMod($key->privateKey, $key->prime);
        return $key;
    }

    /**
     * Compute Shared Secret
     *
     * @param PrivateKey|EC $private
     * @param PublicKey|BigInteger|string $public
     * @return mixed
     */
    public static function computeSecret($private, $public)
    {
        if ($private instanceof PrivateKey) { // DH\PrivateKey
            switch (true) {
                case $public instanceof PublicKey:
                    if (!$private->prime->equals($public->prime) || !$private->base->equals($public->base)) {
                        throw new \InvalidArgumentException('The public and private key do not share the same prime and / or base numbers');
                    }
                    return $public->publicKey->powMod($private->privateKey, $private->prime)->toBytes(true);
                case is_string($public):
                    $public = new BigInteger($public, -256);
                    // fall-through
                case $public instanceof BigInteger:
                    return $public->powMod($private->privateKey, $private->prime)->toBytes(true);
                default:
                    throw new \InvalidArgumentException('$public needs to be an instance of DH\PublicKey, a BigInteger or a string');
            }
        }

        if ($private instanceof EC\PrivateKey) {
            switch (true) {
                case $public instanceof EC\PublicKey:
                    $public = $public->getEncodedCoordinates();
                    // fall-through
                case is_string($public):
                    $point = $private->multiply($public);
                    switch ($private->getCurve()) {
                        case 'Curve25519':
                        case 'Curve448':
                            $secret = $point;
                            break;
                        default:
                            // according to https://www.secg.org/sec1-v2.pdf#page=33 only X is returned
                            $secret = substr($point, 1, (strlen($point) - 1) >> 1);
                    }
                    /*
                    if (($secret[0] & "\x80") === "\x80") {
                        $secret = "\0$secret";
                    }
                    */
                    return $secret;
                default:
                    throw new \InvalidArgumentException('$public needs to be an instance of EC\PublicKey or a string (an encoded coordinate)');
            }
        }
    }

    /**
     * Load the key
     *
     * @param string $key
     * @param string $password optional
     * @return AsymmetricKey
     */
    public static function load($key, $password = false)
    {
        try {
            return EC::load($key, $password);
        } catch (NoKeyLoadedException $e) {
        }

        return parent::load($key, $password);
    }

    /**
     * OnLoad Handler
     *
     * @return bool
     */
    protected static function onLoad(array $components)
    {
        if (!isset($components['privateKey']) && !isset($components['publicKey'])) {
            $new = new Parameters();
        } else {
            $new = isset($components['privateKey']) ?
                new PrivateKey() :
                new PublicKey();
        }

        $new->prime = $components['prime'];
        $new->base = $components['base'];

        if (isset($components['privateKey'])) {
            $new->privateKey = $components['privateKey'];
        }
        if (isset($components['publicKey'])) {
            $new->publicKey = $components['publicKey'];
        }

        return $new;
    }

    /**
     * Determines which hashing function should be used
     *
     * @param string $hash
     */
    public function withHash($hash)
    {
        throw new UnsupportedOperationException('DH does not use a hash algorithm');
    }

    /**
     * Returns the hash algorithm currently being used
     *
     */
    public function getHash()
    {
        throw new UnsupportedOperationException('DH does not use a hash algorithm');
    }

    /**
     * Returns the parameters
     *
     * A public / private key is only returned if the currently loaded "key" contains an x or y
     * value.
     *
     * @see self::getPublicKey()
     * @return mixed
     */
    public function getParameters()
    {
        $type = DH::validatePlugin('Keys', 'PKCS1', 'saveParameters');

        $key = $type::saveParameters($this->prime, $this->base);
        return DH::load($key, 'PKCS1');
    }
}
