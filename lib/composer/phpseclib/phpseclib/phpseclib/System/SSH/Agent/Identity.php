<?php

/**
 * Pure-PHP ssh-agent client.
 *
 * {@internal See http://api.libssh.org/rfc/PROTOCOL.agent}
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2009 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\System\SSH\Agent;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\System\SSH\Agent;
use phpseclib3\System\SSH\Common\Traits\ReadBytes;

/**
 * Pure-PHP ssh-agent client identity object
 *
 * Instantiation should only be performed by \phpseclib3\System\SSH\Agent class.
 * This could be thought of as implementing an interface that phpseclib3\Crypt\RSA
 * implements. ie. maybe a Net_SSH_Auth_PublicKey interface or something.
 * The methods in this interface would be getPublicKey and sign since those are the
 * methods phpseclib looks for to perform public key authentication.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @internal
 */
class Identity implements PrivateKey
{
    use ReadBytes;

    // Signature Flags
    // See https://tools.ietf.org/html/draft-miller-ssh-agent-00#section-5.3
    const SSH_AGENT_RSA2_256 = 2;
    const SSH_AGENT_RSA2_512 = 4;

    /**
     * Key Object
     *
     * @var PublicKey
     * @see self::getPublicKey()
     */
    private $key;

    /**
     * Key Blob
     *
     * @var string
     * @see self::sign()
     */
    private $key_blob;

    /**
     * Socket Resource
     *
     * @var resource
     * @see self::sign()
     */
    private $fsock;

    /**
     * Signature flags
     *
     * @var int
     * @see self::sign()
     * @see self::setHash()
     */
    private $flags = 0;

    /**
     * Curve Aliases
     *
     * @var array
     */
    private static $curveAliases = [
        'secp256r1' => 'nistp256',
        'secp384r1' => 'nistp384',
        'secp521r1' => 'nistp521',
        'Ed25519' => 'Ed25519'
    ];

    /**
     * Default Constructor.
     *
     * @param resource $fsock
     */
    public function __construct($fsock)
    {
        $this->fsock = $fsock;
    }

    /**
     * Set Public Key
     *
     * Called by \phpseclib3\System\SSH\Agent::requestIdentities()
     *
     * @param \phpseclib3\Crypt\Common\PublicKey $key
     */
    public function withPublicKey(PublicKey $key)
    {
        if ($key instanceof EC) {
            if (is_array($key->getCurve()) || !isset(self::$curveAliases[$key->getCurve()])) {
                throw new UnsupportedAlgorithmException('The only supported curves are nistp256, nistp384, nistp512 and Ed25519');
            }
        }

        $new = clone $this;
        $new->key = $key;
        return $new;
    }

    /**
     * Set Public Key
     *
     * Called by \phpseclib3\System\SSH\Agent::requestIdentities(). The key blob could be extracted from $this->key
     * but this saves a small amount of computation.
     *
     * @param string $key_blob
     */
    public function withPublicKeyBlob($key_blob)
    {
        $new = clone $this;
        $new->key_blob = $key_blob;
        return $new;
    }

    /**
     * Get Public Key
     *
     * Wrapper for $this->key->getPublicKey()
     *
     * @param string $type optional
     * @return mixed
     */
    public function getPublicKey($type = 'PKCS8')
    {
        return $this->key;
    }

    /**
     * Sets the hash
     *
     * @param string $hash
     */
    public function withHash($hash)
    {
        $new = clone $this;

        $hash = strtolower($hash);

        if ($this->key instanceof RSA) {
            $new->flags = 0;
            switch ($hash) {
                case 'sha1':
                    break;
                case 'sha256':
                    $new->flags = self::SSH_AGENT_RSA2_256;
                    break;
                case 'sha512':
                    $new->flags = self::SSH_AGENT_RSA2_512;
                    break;
                default:
                    throw new UnsupportedAlgorithmException('The only supported hashes for RSA are sha1, sha256 and sha512');
            }
        }
        if ($this->key instanceof EC) {
            switch ($this->key->getCurve()) {
                case 'secp256r1':
                    $expectedHash = 'sha256';
                    break;
                case 'secp384r1':
                    $expectedHash = 'sha384';
                    break;
                //case 'secp521r1':
                //case 'Ed25519':
                default:
                    $expectedHash = 'sha512';
            }
            if ($hash != $expectedHash) {
                throw new UnsupportedAlgorithmException('The only supported hash for ' . self::$curveAliases[$this->key->getCurve()] . ' is ' . $expectedHash);
            }
        }
        if ($this->key instanceof DSA) {
            if ($hash != 'sha1') {
                throw new UnsupportedAlgorithmException('The only supported hash for DSA is sha1');
            }
        }
        return $new;
    }

    /**
     * Sets the padding
     *
     * Only PKCS1 padding is supported
     *
     * @param string $padding
     */
    public function withPadding($padding)
    {
        if (!$this->key instanceof RSA) {
            throw new UnsupportedAlgorithmException('Only RSA keys support padding');
        }
        if ($padding != RSA::SIGNATURE_PKCS1 && $padding != RSA::SIGNATURE_RELAXED_PKCS1) {
            throw new UnsupportedAlgorithmException('ssh-agent can only create PKCS1 signatures');
        }
        return $this;
    }

    /**
     * Determines the signature padding mode
     *
     * Valid values are: ASN1, SSH2, Raw
     *
     * @param string $format
     */
    public function withSignatureFormat($format)
    {
        if ($this->key instanceof RSA) {
            throw new UnsupportedAlgorithmException('Only DSA and EC keys support signature format setting');
        }
        if ($format != 'SSH2') {
            throw new UnsupportedAlgorithmException('Only SSH2-formatted signatures are currently supported');
        }

        return $this;
    }

    /**
     * Returns the curve
     *
     * Returns a string if it's a named curve, an array if not
     *
     * @return string|array
     */
    public function getCurve()
    {
        if (!$this->key instanceof EC) {
            throw new UnsupportedAlgorithmException('Only EC keys have curves');
        }

        return $this->key->getCurve();
    }

    /**
     * Create a signature
     *
     * See "2.6.2 Protocol 2 private key signature request"
     *
     * @param string $message
     * @return string
     * @throws \RuntimeException on connection errors
     * @throws \phpseclib3\Exception\UnsupportedAlgorithmException if the algorithm is unsupported
     */
    public function sign($message)
    {
        // the last parameter (currently 0) is for flags and ssh-agent only defines one flag (for ssh-dss): SSH_AGENT_OLD_SIGNATURE
        $packet = Strings::packSSH2(
            'CssN',
            Agent::SSH_AGENTC_SIGN_REQUEST,
            $this->key_blob,
            $message,
            $this->flags
        );
        $packet = Strings::packSSH2('s', $packet);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            throw new \RuntimeException('Connection closed during signing');
        }

        $length = current(unpack('N', $this->readBytes(4)));
        $packet = $this->readBytes($length);

        list($type, $signature_blob) = Strings::unpackSSH2('Cs', $packet);
        if ($type != Agent::SSH_AGENT_SIGN_RESPONSE) {
            throw new \RuntimeException('Unable to retrieve signature');
        }

        if (!$this->key instanceof RSA) {
            return $signature_blob;
        }

        list($type, $signature_blob) = Strings::unpackSSH2('ss', $signature_blob);

        return $signature_blob;
    }

    /**
     * Returns the private key
     *
     * @param string $type
     * @param array $options optional
     * @return string
     */
    public function toString($type, array $options = [])
    {
        throw new \RuntimeException('ssh-agent does not provide a mechanism to get the private key');
    }

    /**
     * Sets the password
     *
     * @param string|bool $password
     * @return never
     */
    public function withPassword($password = false)
    {
        throw new \RuntimeException('ssh-agent does not provide a mechanism to get the private key');
    }
}
