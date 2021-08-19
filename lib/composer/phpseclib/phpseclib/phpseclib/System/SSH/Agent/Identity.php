<?php
/**
 * Pure-PHP ssh-agent client.
 *
 * PHP version 5
 *
 * @category  System
 * @package   SSH\Agent
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2009 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 * @internal  See http://api.libssh.org/rfc/PROTOCOL.agent
 */

namespace phpseclib\System\SSH\Agent;

use phpseclib\System\SSH\Agent;

/**
 * Pure-PHP ssh-agent client identity object
 *
 * Instantiation should only be performed by \phpseclib\System\SSH\Agent class.
 * This could be thought of as implementing an interface that phpseclib\Crypt\RSA
 * implements. ie. maybe a Net_SSH_Auth_PublicKey interface or something.
 * The methods in this interface would be getPublicKey and sign since those are the
 * methods phpseclib looks for to perform public key authentication.
 *
 * @package SSH\Agent
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  internal
 */
class Identity
{
    /**@+
     * Signature Flags
     *
     * See https://tools.ietf.org/html/draft-miller-ssh-agent-00#section-5.3
     *
     * @access private
     */
    const SSH_AGENT_RSA2_256 = 2;
    const SSH_AGENT_RSA2_512 = 4;
    /**#@-*/

    /**
     * Key Object
     *
     * @var \phpseclib\Crypt\RSA
     * @access private
     * @see self::getPublicKey()
     */
    var $key;

    /**
     * Key Blob
     *
     * @var string
     * @access private
     * @see self::sign()
     */
    var $key_blob;

    /**
     * Socket Resource
     *
     * @var resource
     * @access private
     * @see self::sign()
     */
    var $fsock;

    /**
     * Signature flags
     *
     * @var int
     * @access private
     * @see self::sign()
     * @see self::setHash()
     */
    var $flags = 0;

    /**
     * Default Constructor.
     *
     * @param resource $fsock
     * @return \phpseclib\System\SSH\Agent\Identity
     * @access private
     */
    function __construct($fsock)
    {
        $this->fsock = $fsock;
    }

    /**
     * Set Public Key
     *
     * Called by \phpseclib\System\SSH\Agent::requestIdentities()
     *
     * @param \phpseclib\Crypt\RSA $key
     * @access private
     */
    function setPublicKey($key)
    {
        $this->key = $key;
        $this->key->setPublicKey();
    }

    /**
     * Set Public Key
     *
     * Called by \phpseclib\System\SSH\Agent::requestIdentities(). The key blob could be extracted from $this->key
     * but this saves a small amount of computation.
     *
     * @param string $key_blob
     * @access private
     */
    function setPublicKeyBlob($key_blob)
    {
        $this->key_blob = $key_blob;
    }

    /**
     * Get Public Key
     *
     * Wrapper for $this->key->getPublicKey()
     *
     * @param int $format optional
     * @return mixed
     * @access public
     */
    function getPublicKey($format = null)
    {
        return !isset($format) ? $this->key->getPublicKey() : $this->key->getPublicKey($format);
    }

    /**
     * Set Signature Mode
     *
     * Doesn't do anything as ssh-agent doesn't let you pick and choose the signature mode. ie.
     * ssh-agent's only supported mode is \phpseclib\Crypt\RSA::SIGNATURE_PKCS1
     *
     * @param int $mode
     * @access public
     */
    function setSignatureMode($mode)
    {
    }

    /**
     * Set Hash
     *
     * ssh-agent doesn't support using hashes for RSA other than SHA1
     *
     * @param string $hash
     * @access public
     */
    function setHash($hash)
    {
        $this->flags = 0;
        switch ($hash) {
            case 'sha1':
                break;
            case 'sha256':
                $this->flags = self::SSH_AGENT_RSA2_256;
                break;
            case 'sha512':
                $this->flags = self::SSH_AGENT_RSA2_512;
                break;
            default:
                user_error('The only supported hashes for RSA are sha1, sha256 and sha512');
        }
    }

    /**
     * Create a signature
     *
     * See "2.6.2 Protocol 2 private key signature request"
     *
     * @param string $message
     * @return string
     * @access public
     */
    function sign($message)
    {
        // the last parameter (currently 0) is for flags and ssh-agent only defines one flag (for ssh-dss): SSH_AGENT_OLD_SIGNATURE
        $packet = pack('CNa*Na*N', Agent::SSH_AGENTC_SIGN_REQUEST, strlen($this->key_blob), $this->key_blob, strlen($message), $message, $this->flags);
        $packet = pack('Na*', strlen($packet), $packet);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            user_error('Connection closed during signing');
            return false;
        }

        $temp = fread($this->fsock, 4);
        if (strlen($temp) != 4) {
            user_error('Connection closed during signing');
            return false;
        }
        $length = current(unpack('N', $temp));
        $type = ord(fread($this->fsock, 1));
        if ($type != Agent::SSH_AGENT_SIGN_RESPONSE) {
            user_error('Unable to retrieve signature');
            return false;
        }

        $signature_blob = fread($this->fsock, $length - 1);
        if (strlen($signature_blob) != $length - 1) {
            user_error('Connection closed during signing');
            return false;
        }
        $length = current(unpack('N', $this->_string_shift($signature_blob, 4)));
        if ($length != strlen($signature_blob)) {
            user_error('Malformed signature blob');
        }
        $length = current(unpack('N', $this->_string_shift($signature_blob, 4)));
        if ($length > strlen($signature_blob) + 4) {
            user_error('Malformed signature blob');
        }
        $type = $this->_string_shift($signature_blob, $length);
        $this->_string_shift($signature_blob, 4);

        return $signature_blob;
    }

    /**
     * String Shift
     *
     * Inspired by array_shift
     *
     * @param string $string
     * @param int $index
     * @return string
     * @access private
     */
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }
}
