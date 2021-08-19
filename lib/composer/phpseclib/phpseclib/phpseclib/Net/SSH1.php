<?php

/**
 * Pure-PHP implementation of SSHv1.
 *
 * PHP version 5
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $ssh = new \phpseclib\Net\SSH1('www.domain.tld');
 *    if (!$ssh->login('username', 'password')) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->exec('ls -la');
 * ?>
 * </code>
 *
 * Here's another short example:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $ssh = new \phpseclib\Net\SSH1('www.domain.tld');
 *    if (!$ssh->login('username', 'password')) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->read('username@username:~$');
 *    $ssh->write("ls -la\n");
 *    echo $ssh->read('username@username:~$');
 * ?>
 * </code>
 *
 * More information on the SSHv1 specification can be found by reading
 * {@link http://www.snailbook.com/docs/protocol-1.5.txt protocol-1.5.txt}.
 *
 * @category  Net
 * @package   SSH1
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\Net;

use phpseclib\Crypt\DES;
use phpseclib\Crypt\Random;
use phpseclib\Crypt\TripleDES;
use phpseclib\Math\BigInteger;

/**
 * Pure-PHP implementation of SSHv1.
 *
 * @package SSH1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class SSH1
{
    /**#@+
     * Encryption Methods
     *
     * @see \phpseclib\Net\SSH1::getSupportedCiphers()
     * @access public
     */
    /**
     * No encryption
     *
     * Not supported.
     */
    const CIPHER_NONE = 0;
    /**
     * IDEA in CFB mode
     *
     * Not supported.
     */
    const CIPHER_IDEA = 1;
    /**
     * DES in CBC mode
     */
    const CIPHER_DES = 2;
    /**
     * Triple-DES in CBC mode
     *
     * All implementations are required to support this
     */
    const CIPHER_3DES = 3;
    /**
     * TRI's Simple Stream encryption CBC
     *
     * Not supported nor is it defined in the official SSH1 specs.  OpenSSH, however, does define it (see cipher.h),
     * although it doesn't use it (see cipher.c)
     */
    const CIPHER_BROKEN_TSS = 4;
    /**
     * RC4
     *
     * Not supported.
     *
     * @internal According to the SSH1 specs:
     *
     *        "The first 16 bytes of the session key are used as the key for
     *         the server to client direction.  The remaining 16 bytes are used
     *         as the key for the client to server direction.  This gives
     *         independent 128-bit keys for each direction."
     *
     *     This library currently only supports encryption when the same key is being used for both directions.  This is
     *     because there's only one $crypto object.  Two could be added ($encrypt and $decrypt, perhaps).
     */
    const CIPHER_RC4 = 5;
    /**
     * Blowfish
     *
     * Not supported nor is it defined in the official SSH1 specs.  OpenSSH, however, defines it (see cipher.h) and
     * uses it (see cipher.c)
     */
    const CIPHER_BLOWFISH = 6;
    /**#@-*/

    /**#@+
     * Authentication Methods
     *
     * @see \phpseclib\Net\SSH1::getSupportedAuthentications()
     * @access public
    */
    /**
     * .rhosts or /etc/hosts.equiv
     */
    const AUTH_RHOSTS = 1;
    /**
     * pure RSA authentication
     */
    const AUTH_RSA = 2;
    /**
     * password authentication
     *
     * This is the only method that is supported by this library.
     */
    const AUTH_PASSWORD = 3;
    /**
     * .rhosts with RSA host authentication
     */
    const AUTH_RHOSTS_RSA = 4;
    /**#@-*/

    /**#@+
     * Terminal Modes
     *
     * @link http://3sp.com/content/developer/maverick-net/docs/Maverick.SSH.PseudoTerminalModesMembers.html
     * @access private
    */
    const TTY_OP_END = 0;
    /**#@-*/

    /**
     * The Response Type
     *
     * @see \phpseclib\Net\SSH1::_get_binary_packet()
     * @access private
     */
    const RESPONSE_TYPE = 1;

    /**
     * The Response Data
     *
     * @see \phpseclib\Net\SSH1::_get_binary_packet()
     * @access private
     */
    const RESPONSE_DATA = 2;

    /**#@+
     * Execution Bitmap Masks
     *
     * @see \phpseclib\Net\SSH1::bitmap
     * @access private
    */
    const MASK_CONSTRUCTOR = 0x00000001;
    const MASK_CONNECTED   = 0x00000002;
    const MASK_LOGIN       = 0x00000004;
    const MASK_SHELL       = 0x00000008;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Net\SSH1::getLog()
    */
    /**
     * Returns the message numbers
     */
    const LOG_SIMPLE = 1;
    /**
     * Returns the message content
     */
    const LOG_COMPLEX = 2;
    /**
     * Outputs the content real-time
     */
    const LOG_REALTIME = 3;
    /**
     * Dumps the content real-time to a file
     */
    const LOG_REALTIME_FILE = 4;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Net\SSH1::read()
    */
    /**
     * Returns when a string matching $expect exactly is found
     */
    const READ_SIMPLE = 1;
    /**
     * Returns when a string matching the regular expression $expect is found
     */
    const READ_REGEX = 2;
    /**#@-*/

    /**
     * The SSH identifier
     *
     * @var string
     * @access private
     */
    var $identifier = 'SSH-1.5-phpseclib';

    /**
     * The Socket Object
     *
     * @var object
     * @access private
     */
    var $fsock;

    /**
     * The cryptography object
     *
     * @var object
     * @access private
     */
    var $crypto = false;

    /**
     * Execution Bitmap
     *
     * The bits that are set represent functions that have been called already.  This is used to determine
     * if a requisite function has been successfully executed.  If not, an error should be thrown.
     *
     * @var int
     * @access private
     */
    var $bitmap = 0;

    /**
     * The Server Key Public Exponent
     *
     * Logged for debug purposes
     *
     * @see self::getServerKeyPublicExponent()
     * @var string
     * @access private
     */
    var $server_key_public_exponent;

    /**
     * The Server Key Public Modulus
     *
     * Logged for debug purposes
     *
     * @see self::getServerKeyPublicModulus()
     * @var string
     * @access private
     */
    var $server_key_public_modulus;

    /**
     * The Host Key Public Exponent
     *
     * Logged for debug purposes
     *
     * @see self::getHostKeyPublicExponent()
     * @var string
     * @access private
     */
    var $host_key_public_exponent;

    /**
     * The Host Key Public Modulus
     *
     * Logged for debug purposes
     *
     * @see self::getHostKeyPublicModulus()
     * @var string
     * @access private
     */
    var $host_key_public_modulus;

    /**
     * Supported Ciphers
     *
     * Logged for debug purposes
     *
     * @see self::getSupportedCiphers()
     * @var array
     * @access private
     */
    var $supported_ciphers = array(
        self::CIPHER_NONE       => 'No encryption',
        self::CIPHER_IDEA       => 'IDEA in CFB mode',
        self::CIPHER_DES        => 'DES in CBC mode',
        self::CIPHER_3DES       => 'Triple-DES in CBC mode',
        self::CIPHER_BROKEN_TSS => 'TRI\'s Simple Stream encryption CBC',
        self::CIPHER_RC4        => 'RC4',
        self::CIPHER_BLOWFISH   => 'Blowfish'
    );

    /**
     * Supported Authentications
     *
     * Logged for debug purposes
     *
     * @see self::getSupportedAuthentications()
     * @var array
     * @access private
     */
    var $supported_authentications = array(
        self::AUTH_RHOSTS     => '.rhosts or /etc/hosts.equiv',
        self::AUTH_RSA        => 'pure RSA authentication',
        self::AUTH_PASSWORD   => 'password authentication',
        self::AUTH_RHOSTS_RSA => '.rhosts with RSA host authentication'
    );

    /**
     * Server Identification
     *
     * @see self::getServerIdentification()
     * @var string
     * @access private
     */
    var $server_identification = '';

    /**
     * Protocol Flags
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $protocol_flags = array();

    /**
     * Protocol Flag Log
     *
     * @see self::getLog()
     * @var array
     * @access private
     */
    var $protocol_flag_log = array();

    /**
     * Message Log
     *
     * @see self::getLog()
     * @var array
     * @access private
     */
    var $message_log = array();

    /**
     * Real-time log file pointer
     *
     * @see self::_append_log()
     * @var resource
     * @access private
     */
    var $realtime_log_file;

    /**
     * Real-time log file size
     *
     * @see self::_append_log()
     * @var int
     * @access private
     */
    var $realtime_log_size;

    /**
     * Real-time log file wrap boolean
     *
     * @see self::_append_log()
     * @var bool
     * @access private
     */
    var $realtime_log_wrap;

    /**
     * Interactive Buffer
     *
     * @see self::read()
     * @var array
     * @access private
     */
    var $interactiveBuffer = '';

    /**
     * Timeout
     *
     * @see self::setTimeout()
     * @access private
     */
    var $timeout;

    /**
     * Current Timeout
     *
     * @see self::_get_channel_packet()
     * @access private
     */
    var $curTimeout;

    /**
     * Log Boundary
     *
     * @see self::_format_log()
     * @access private
     */
    var $log_boundary = ':';

    /**
     * Log Long Width
     *
     * @see self::_format_log()
     * @access private
     */
    var $log_long_width = 65;

    /**
     * Log Short Width
     *
     * @see self::_format_log()
     * @access private
     */
    var $log_short_width = 16;

    /**
     * Hostname
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var string
     * @access private
     */
    var $host;

    /**
     * Port Number
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var int
     * @access private
     */
    var $port;

    /**
     * Timeout for initial connection
     *
     * Set by the constructor call. Calling setTimeout() is optional. If it's not called functions like
     * exec() won't timeout unless some PHP setting forces it too. The timeout specified in the constructor,
     * however, is non-optional. There will be a timeout, whether or not you set it. If you don't it'll be
     * 10 seconds. It is used by fsockopen() in that function.
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var int
     * @access private
     */
    var $connectionTimeout;

    /**
     * Default cipher
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var int
     * @access private
     */
    var $cipher;

    /**
     * Default Constructor.
     *
     * Connects to an SSHv1 server
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param int $cipher
     * @return \phpseclib\Net\SSH1
     * @access public
     */
    function __construct($host, $port = 22, $timeout = 10, $cipher = self::CIPHER_3DES)
    {
        $this->protocol_flags = array(
            1  => 'NET_SSH1_MSG_DISCONNECT',
            2  => 'NET_SSH1_SMSG_PUBLIC_KEY',
            3  => 'NET_SSH1_CMSG_SESSION_KEY',
            4  => 'NET_SSH1_CMSG_USER',
            9  => 'NET_SSH1_CMSG_AUTH_PASSWORD',
            10 => 'NET_SSH1_CMSG_REQUEST_PTY',
            12 => 'NET_SSH1_CMSG_EXEC_SHELL',
            13 => 'NET_SSH1_CMSG_EXEC_CMD',
            14 => 'NET_SSH1_SMSG_SUCCESS',
            15 => 'NET_SSH1_SMSG_FAILURE',
            16 => 'NET_SSH1_CMSG_STDIN_DATA',
            17 => 'NET_SSH1_SMSG_STDOUT_DATA',
            18 => 'NET_SSH1_SMSG_STDERR_DATA',
            19 => 'NET_SSH1_CMSG_EOF',
            20 => 'NET_SSH1_SMSG_EXITSTATUS',
            33 => 'NET_SSH1_CMSG_EXIT_CONFIRMATION'
        );

        $this->_define_array($this->protocol_flags);

        $this->host = $host;
        $this->port = $port;
        $this->connectionTimeout = $timeout;
        $this->cipher = $cipher;
    }

    /**
     * Connect to an SSHv1 server
     *
     * @return bool
     * @access private
     */
    function _connect()
    {
        $this->fsock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
        if (!$this->fsock) {
            user_error(rtrim("Cannot connect to {$this->host}:{$this->port}. Error $errno. $errstr"));
            return false;
        }

        $this->server_identification = $init_line = fgets($this->fsock, 255);

        if (defined('NET_SSH1_LOGGING')) {
            $this->_append_log('<-', $this->server_identification);
            $this->_append_log('->', $this->identifier . "\r\n");
        }

        if (!preg_match('#SSH-([0-9\.]+)-(.+)#', $init_line, $parts)) {
            user_error('Can only connect to SSH servers');
            return false;
        }
        if ($parts[1][0] != 1) {
            user_error("Cannot connect to SSH $parts[1] servers");
            return false;
        }

        fputs($this->fsock, $this->identifier."\r\n");

        $response = $this->_get_binary_packet();
        if ($response[self::RESPONSE_TYPE] != NET_SSH1_SMSG_PUBLIC_KEY) {
            user_error('Expected SSH_SMSG_PUBLIC_KEY');
            return false;
        }

        $anti_spoofing_cookie = $this->_string_shift($response[self::RESPONSE_DATA], 8);

        $this->_string_shift($response[self::RESPONSE_DATA], 4);

        if (strlen($response[self::RESPONSE_DATA]) < 2) {
            return false;
        }
        $temp = unpack('nlen', $this->_string_shift($response[self::RESPONSE_DATA], 2));
        $server_key_public_exponent = new BigInteger($this->_string_shift($response[self::RESPONSE_DATA], ceil($temp['len'] / 8)), 256);
        $this->server_key_public_exponent = $server_key_public_exponent;

        if (strlen($response[self::RESPONSE_DATA]) < 2) {
            return false;
        }
        $temp = unpack('nlen', $this->_string_shift($response[self::RESPONSE_DATA], 2));
        $server_key_public_modulus = new BigInteger($this->_string_shift($response[self::RESPONSE_DATA], ceil($temp['len'] / 8)), 256);

        $this->server_key_public_modulus = $server_key_public_modulus;

        $this->_string_shift($response[self::RESPONSE_DATA], 4);

        if (strlen($response[self::RESPONSE_DATA]) < 2) {
            return false;
        }
        $temp = unpack('nlen', $this->_string_shift($response[self::RESPONSE_DATA], 2));
        $host_key_public_exponent = new BigInteger($this->_string_shift($response[self::RESPONSE_DATA], ceil($temp['len'] / 8)), 256);
        $this->host_key_public_exponent = $host_key_public_exponent;

        if (strlen($response[self::RESPONSE_DATA]) < 2) {
            return false;
        }
        $temp = unpack('nlen', $this->_string_shift($response[self::RESPONSE_DATA], 2));
        $host_key_public_modulus = new BigInteger($this->_string_shift($response[self::RESPONSE_DATA], ceil($temp['len'] / 8)), 256);

        $this->host_key_public_modulus = $host_key_public_modulus;

        $this->_string_shift($response[self::RESPONSE_DATA], 4);

        // get a list of the supported ciphers
        if (strlen($response[self::RESPONSE_DATA]) < 4) {
            return false;
        }
        extract(unpack('Nsupported_ciphers_mask', $this->_string_shift($response[self::RESPONSE_DATA], 4)));

        foreach ($this->supported_ciphers as $mask => $name) {
            if (($supported_ciphers_mask & (1 << $mask)) == 0) {
                unset($this->supported_ciphers[$mask]);
            }
        }

        // get a list of the supported authentications
        if (strlen($response[self::RESPONSE_DATA]) < 4) {
            return false;
        }
        extract(unpack('Nsupported_authentications_mask', $this->_string_shift($response[self::RESPONSE_DATA], 4)));
        foreach ($this->supported_authentications as $mask => $name) {
            if (($supported_authentications_mask & (1 << $mask)) == 0) {
                unset($this->supported_authentications[$mask]);
            }
        }

        $session_id = pack('H*', md5($host_key_public_modulus->toBytes() . $server_key_public_modulus->toBytes() . $anti_spoofing_cookie));

        $session_key = Random::string(32);
        $double_encrypted_session_key = $session_key ^ str_pad($session_id, 32, chr(0));

        if ($server_key_public_modulus->compare($host_key_public_modulus) < 0) {
            $double_encrypted_session_key = $this->_rsa_crypt(
                $double_encrypted_session_key,
                array(
                    $server_key_public_exponent,
                    $server_key_public_modulus
                )
            );
            $double_encrypted_session_key = $this->_rsa_crypt(
                $double_encrypted_session_key,
                array(
                    $host_key_public_exponent,
                    $host_key_public_modulus
                )
            );
        } else {
            $double_encrypted_session_key = $this->_rsa_crypt(
                $double_encrypted_session_key,
                array(
                    $host_key_public_exponent,
                    $host_key_public_modulus
                )
            );
            $double_encrypted_session_key = $this->_rsa_crypt(
                $double_encrypted_session_key,
                array(
                    $server_key_public_exponent,
                    $server_key_public_modulus
                )
            );
        }

        $cipher = isset($this->supported_ciphers[$this->cipher]) ? $this->cipher : self::CIPHER_3DES;
        $data = pack('C2a*na*N', NET_SSH1_CMSG_SESSION_KEY, $cipher, $anti_spoofing_cookie, 8 * strlen($double_encrypted_session_key), $double_encrypted_session_key, 0);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_SESSION_KEY');
            return false;
        }

        switch ($cipher) {
            //case self::CIPHER_NONE:
            //    $this->crypto = new \phpseclib\Crypt\Null();
            //    break;
            case self::CIPHER_DES:
                $this->crypto = new DES();
                $this->crypto->disablePadding();
                $this->crypto->enableContinuousBuffer();
                $this->crypto->setKey(substr($session_key, 0,  8));
                break;
            case self::CIPHER_3DES:
                $this->crypto = new TripleDES(TripleDES::MODE_3CBC);
                $this->crypto->disablePadding();
                $this->crypto->enableContinuousBuffer();
                $this->crypto->setKey(substr($session_key, 0, 24));
                break;
            //case self::CIPHER_RC4:
            //    $this->crypto = new RC4();
            //    $this->crypto->enableContinuousBuffer();
            //    $this->crypto->setKey(substr($session_key, 0,  16));
            //    break;
        }

        $response = $this->_get_binary_packet();

        if ($response[self::RESPONSE_TYPE] != NET_SSH1_SMSG_SUCCESS) {
            user_error('Expected SSH_SMSG_SUCCESS');
            return false;
        }

        $this->bitmap = self::MASK_CONNECTED;

        return true;
    }

    /**
     * Login
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @access public
     */
    function login($username, $password = '')
    {
        if (!($this->bitmap & self::MASK_CONSTRUCTOR)) {
            $this->bitmap |= self::MASK_CONSTRUCTOR;
            if (!$this->_connect()) {
                return false;
            }
        }

        if (!($this->bitmap & self::MASK_CONNECTED)) {
            return false;
        }

        $data = pack('CNa*', NET_SSH1_CMSG_USER, strlen($username), $username);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_USER');
            return false;
        }

        $response = $this->_get_binary_packet();

        if ($response === true) {
            return false;
        }
        if ($response[self::RESPONSE_TYPE] == NET_SSH1_SMSG_SUCCESS) {
            $this->bitmap |= self::MASK_LOGIN;
            return true;
        } elseif ($response[self::RESPONSE_TYPE] != NET_SSH1_SMSG_FAILURE) {
            user_error('Expected SSH_SMSG_SUCCESS or SSH_SMSG_FAILURE');
            return false;
        }

        $data = pack('CNa*', NET_SSH1_CMSG_AUTH_PASSWORD, strlen($password), $password);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_AUTH_PASSWORD');
            return false;
        }

        // remove the username and password from the last logged packet
        if (defined('NET_SSH1_LOGGING') && NET_SSH1_LOGGING == self::LOG_COMPLEX) {
            $data = pack('CNa*', NET_SSH1_CMSG_AUTH_PASSWORD, strlen('password'), 'password');
            $this->message_log[count($this->message_log) - 1] = $data;
        }

        $response = $this->_get_binary_packet();

        if ($response === true) {
            return false;
        }
        if ($response[self::RESPONSE_TYPE] == NET_SSH1_SMSG_SUCCESS) {
            $this->bitmap |= self::MASK_LOGIN;
            return true;
        } elseif ($response[self::RESPONSE_TYPE] == NET_SSH1_SMSG_FAILURE) {
            return false;
        } else {
            user_error('Expected SSH_SMSG_SUCCESS or SSH_SMSG_FAILURE');
            return false;
        }
    }

    /**
     * Set Timeout
     *
     * $ssh->exec('ping 127.0.0.1'); on a Linux host will never return and will run indefinitely.  setTimeout() makes it so it'll timeout.
     * Setting $timeout to false or 0 will mean there is no timeout.
     *
     * @param mixed $timeout
     */
    function setTimeout($timeout)
    {
        $this->timeout = $this->curTimeout = $timeout;
    }

    /**
     * Executes a command on a non-interactive shell, returns the output, and quits.
     *
     * An SSH1 server will close the connection after a command has been executed on a non-interactive shell.  SSH2
     * servers don't, however, this isn't an SSH2 client.  The way this works, on the server, is by initiating a
     * shell with the -s option, as discussed in the following links:
     *
     * {@link http://www.faqs.org/docs/bashman/bashref_65.html http://www.faqs.org/docs/bashman/bashref_65.html}
     * {@link http://www.faqs.org/docs/bashman/bashref_62.html http://www.faqs.org/docs/bashman/bashref_62.html}
     *
     * To execute further commands, a new \phpseclib\Net\SSH1 object will need to be created.
     *
     * Returns false on failure and the output, otherwise.
     *
     * @see self::interactiveRead()
     * @see self::interactiveWrite()
     * @param string $cmd
     * @param bool $block
     * @return mixed
     * @access public
     */
    function exec($cmd, $block = true)
    {
        if (!($this->bitmap & self::MASK_LOGIN)) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        $data = pack('CNa*', NET_SSH1_CMSG_EXEC_CMD, strlen($cmd), $cmd);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_EXEC_CMD');
            return false;
        }

        if (!$block) {
            return true;
        }

        $output = '';
        $response = $this->_get_binary_packet();

        if ($response !== false) {
            do {
                $output.= substr($response[self::RESPONSE_DATA], 4);
                $response = $this->_get_binary_packet();
            } while (is_array($response) && $response[self::RESPONSE_TYPE] != NET_SSH1_SMSG_EXITSTATUS);
        }

        $data = pack('C', NET_SSH1_CMSG_EXIT_CONFIRMATION);

        // i don't think it's really all that important if this packet gets sent or not.
        $this->_send_binary_packet($data);

        fclose($this->fsock);

        // reset the execution bitmap - a new \phpseclib\Net\SSH1 object needs to be created.
        $this->bitmap = 0;

        return $output;
    }

    /**
     * Creates an interactive shell
     *
     * @see self::interactiveRead()
     * @see self::interactiveWrite()
     * @return bool
     * @access private
     */
    function _initShell()
    {
        // connect using the sample parameters in protocol-1.5.txt.
        // according to wikipedia.org's entry on text terminals, "the fundamental type of application running on a text
        // terminal is a command line interpreter or shell".  thus, opening a terminal session to run the shell.
        $data = pack('CNa*N4C', NET_SSH1_CMSG_REQUEST_PTY, strlen('vt100'), 'vt100', 24, 80, 0, 0, self::TTY_OP_END);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_REQUEST_PTY');
            return false;
        }

        $response = $this->_get_binary_packet();

        if ($response === true) {
            return false;
        }
        if ($response[self::RESPONSE_TYPE] != NET_SSH1_SMSG_SUCCESS) {
            user_error('Expected SSH_SMSG_SUCCESS');
            return false;
        }

        $data = pack('C', NET_SSH1_CMSG_EXEC_SHELL);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_EXEC_SHELL');
            return false;
        }

        $this->bitmap |= self::MASK_SHELL;

        //stream_set_blocking($this->fsock, 0);

        return true;
    }

    /**
     * Inputs a command into an interactive shell.
     *
     * @see self::interactiveWrite()
     * @param string $cmd
     * @return bool
     * @access public
     */
    function write($cmd)
    {
        return $this->interactiveWrite($cmd);
    }

    /**
     * Returns the output of an interactive shell when there's a match for $expect
     *
     * $expect can take the form of a string literal or, if $mode == self::READ_REGEX,
     * a regular expression.
     *
     * @see self::write()
     * @param string $expect
     * @param int $mode
     * @return bool
     * @access public
     */
    function read($expect, $mode = self::READ_SIMPLE)
    {
        if (!($this->bitmap & self::MASK_LOGIN)) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        if (!($this->bitmap & self::MASK_SHELL) && !$this->_initShell()) {
            user_error('Unable to initiate an interactive shell session');
            return false;
        }

        $match = $expect;
        while (true) {
            if ($mode == self::READ_REGEX) {
                preg_match($expect, $this->interactiveBuffer, $matches);
                $match = isset($matches[0]) ? $matches[0] : '';
            }
            $pos = strlen($match) ? strpos($this->interactiveBuffer, $match) : false;
            if ($pos !== false) {
                return $this->_string_shift($this->interactiveBuffer, $pos + strlen($match));
            }
            $response = $this->_get_binary_packet();

            if ($response === true) {
                return $this->_string_shift($this->interactiveBuffer, strlen($this->interactiveBuffer));
            }
            $this->interactiveBuffer.= substr($response[self::RESPONSE_DATA], 4);
        }
    }

    /**
     * Inputs a command into an interactive shell.
     *
     * @see self::interactiveRead()
     * @param string $cmd
     * @return bool
     * @access public
     */
    function interactiveWrite($cmd)
    {
        if (!($this->bitmap & self::MASK_LOGIN)) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        if (!($this->bitmap & self::MASK_SHELL) && !$this->_initShell()) {
            user_error('Unable to initiate an interactive shell session');
            return false;
        }

        $data = pack('CNa*', NET_SSH1_CMSG_STDIN_DATA, strlen($cmd), $cmd);

        if (!$this->_send_binary_packet($data)) {
            user_error('Error sending SSH_CMSG_STDIN');
            return false;
        }

        return true;
    }

    /**
     * Returns the output of an interactive shell when no more output is available.
     *
     * Requires PHP 4.3.0 or later due to the use of the stream_select() function.  If you see stuff like
     * "^[[00m", you're seeing ANSI escape codes.  According to
     * {@link http://support.microsoft.com/kb/101875 How to Enable ANSI.SYS in a Command Window}, "Windows NT
     * does not support ANSI escape sequences in Win32 Console applications", so if you're a Windows user,
     * there's not going to be much recourse.
     *
     * @see self::interactiveRead()
     * @return string
     * @access public
     */
    function interactiveRead()
    {
        if (!($this->bitmap & self::MASK_LOGIN)) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        if (!($this->bitmap & self::MASK_SHELL) && !$this->_initShell()) {
            user_error('Unable to initiate an interactive shell session');
            return false;
        }

        $read = array($this->fsock);
        $write = $except = null;
        if (stream_select($read, $write, $except, 0)) {
            $response = $this->_get_binary_packet();
            return substr($response[self::RESPONSE_DATA], 4);
        } else {
            return '';
        }
    }

    /**
     * Disconnect
     *
     * @access public
     */
    function disconnect()
    {
        $this->_disconnect();
    }

    /**
     * Destructor.
     *
     * Will be called, automatically, if you're supporting just PHP5.  If you're supporting PHP4, you'll need to call
     * disconnect().
     *
     * @access public
     */
    function __destruct()
    {
        $this->_disconnect();
    }

    /**
     * Disconnect
     *
     * @param string $msg
     * @access private
     */
    function _disconnect($msg = 'Client Quit')
    {
        if ($this->bitmap) {
            $data = pack('C', NET_SSH1_CMSG_EOF);
            $this->_send_binary_packet($data);
            /*
            $response = $this->_get_binary_packet();
            if ($response === true) {
                $response = array(self::RESPONSE_TYPE => -1);
            }
            switch ($response[self::RESPONSE_TYPE]) {
                case NET_SSH1_SMSG_EXITSTATUS:
                    $data = pack('C', NET_SSH1_CMSG_EXIT_CONFIRMATION);
                    break;
                default:
                    $data = pack('CNa*', NET_SSH1_MSG_DISCONNECT, strlen($msg), $msg);
            }
            */
            $data = pack('CNa*', NET_SSH1_MSG_DISCONNECT, strlen($msg), $msg);

            $this->_send_binary_packet($data);
            fclose($this->fsock);
            $this->bitmap = 0;
        }
    }

    /**
     * Gets Binary Packets
     *
     * See 'The Binary Packet Protocol' of protocol-1.5.txt for more info.
     *
     * Also, this function could be improved upon by adding detection for the following exploit:
     * http://www.securiteam.com/securitynews/5LP042K3FY.html
     *
     * @see self::_send_binary_packet()
     * @return array
     * @access private
     */
    function _get_binary_packet()
    {
        if (feof($this->fsock)) {
            //user_error('connection closed prematurely');
            return false;
        }

        if ($this->curTimeout) {
            $read = array($this->fsock);
            $write = $except = null;

            $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
            $sec = floor($this->curTimeout);
            $usec = 1000000 * ($this->curTimeout - $sec);
            // on windows this returns a "Warning: Invalid CRT parameters detected" error
            if (!@stream_select($read, $write, $except, $sec, $usec) && !count($read)) {
                //$this->_disconnect('Timeout');
                return true;
            }
            $elapsed = strtok(microtime(), ' ') + strtok('') - $start;
            $this->curTimeout-= $elapsed;
        }

        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
        $data = fread($this->fsock, 4);
        if (strlen($data) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $data);

        $padding_length = 8 - ($temp['length'] & 7);
        $length = $temp['length'] + $padding_length;
        $raw = '';

        while ($length > 0) {
            $temp = fread($this->fsock, $length);
            if (strlen($temp) != $length) {
                return false;
            }
            $raw.= $temp;
            $length-= strlen($temp);
        }
        $stop = strtok(microtime(), ' ') + strtok('');

        if (strlen($raw) && $this->crypto !== false) {
            $raw = $this->crypto->decrypt($raw);
        }

        $padding = substr($raw, 0, $padding_length);
        $type = $raw[$padding_length];
        $data = substr($raw, $padding_length + 1, -4);

        if (strlen($raw) < 4) {
            return false;
        }
        $temp = unpack('Ncrc', substr($raw, -4));

        //if ( $temp['crc'] != $this->_crc($padding . $type . $data) ) {
        //    user_error('Bad CRC in packet from server');
        //    return false;
        //}

        $type = ord($type);

        if (defined('NET_SSH1_LOGGING')) {
            $temp = isset($this->protocol_flags[$type]) ? $this->protocol_flags[$type] : 'UNKNOWN';
            $temp = '<- ' . $temp .
                    ' (' . round($stop - $start, 4) . 's)';
            $this->_append_log($temp, $data);
        }

        return array(
            self::RESPONSE_TYPE => $type,
            self::RESPONSE_DATA => $data
        );
    }

    /**
     * Sends Binary Packets
     *
     * Returns true on success, false on failure.
     *
     * @see self::_get_binary_packet()
     * @param string $data
     * @return bool
     * @access private
     */
    function _send_binary_packet($data)
    {
        if (feof($this->fsock)) {
            //user_error('connection closed prematurely');
            return false;
        }

        $length = strlen($data) + 4;

        $padding = Random::string(8 - ($length & 7));

        $orig = $data;
        $data = $padding . $data;
        $data.= pack('N', $this->_crc($data));

        if ($this->crypto !== false) {
            $data = $this->crypto->encrypt($data);
        }

        $packet = pack('Na*', $length, $data);

        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
        $result = strlen($packet) == fputs($this->fsock, $packet);
        $stop = strtok(microtime(), ' ') + strtok('');

        if (defined('NET_SSH1_LOGGING')) {
            $temp = isset($this->protocol_flags[ord($orig[0])]) ? $this->protocol_flags[ord($orig[0])] : 'UNKNOWN';
            $temp = '-> ' . $temp .
                    ' (' . round($stop - $start, 4) . 's)';
            $this->_append_log($temp, $orig);
        }

        return $result;
    }

    /**
     * Cyclic Redundancy Check (CRC)
     *
     * PHP's crc32 function is implemented slightly differently than the one that SSH v1 uses, so
     * we've reimplemented it. A more detailed discussion of the differences can be found after
     * $crc_lookup_table's initialization.
     *
     * @see self::_get_binary_packet()
     * @see self::_send_binary_packet()
     * @param string $data
     * @return int
     * @access private
     */
    function _crc($data)
    {
        static $crc_lookup_table = array(
            0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA,
            0x076DC419, 0x706AF48F, 0xE963A535, 0x9E6495A3,
            0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988,
            0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91,
            0x1DB71064, 0x6AB020F2, 0xF3B97148, 0x84BE41DE,
            0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7,
            0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC,
            0x14015C4F, 0x63066CD9, 0xFA0F3D63, 0x8D080DF5,
            0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172,
            0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B,
            0x35B5A8FA, 0x42B2986C, 0xDBBBC9D6, 0xACBCF940,
            0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59,
            0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116,
            0x21B4F4B5, 0x56B3C423, 0xCFBA9599, 0xB8BDA50F,
            0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924,
            0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D,
            0x76DC4190, 0x01DB7106, 0x98D220BC, 0xEFD5102A,
            0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433,
            0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818,
            0x7F6A0DBB, 0x086D3D2D, 0x91646C97, 0xE6635C01,
            0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E,
            0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457,
            0x65B0D9C6, 0x12B7E950, 0x8BBEB8EA, 0xFCB9887C,
            0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65,
            0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2,
            0x4ADFA541, 0x3DD895D7, 0xA4D1C46D, 0xD3D6F4FB,
            0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0,
            0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9,
            0x5005713C, 0x270241AA, 0xBE0B1010, 0xC90C2086,
            0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F,
            0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4,
            0x59B33D17, 0x2EB40D81, 0xB7BD5C3B, 0xC0BA6CAD,
            0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A,
            0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683,
            0xE3630B12, 0x94643B84, 0x0D6D6A3E, 0x7A6A5AA8,
            0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1,
            0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE,
            0xF762575D, 0x806567CB, 0x196C3671, 0x6E6B06E7,
            0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC,
            0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5,
            0xD6D6A3E8, 0xA1D1937E, 0x38D8C2C4, 0x4FDFF252,
            0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B,
            0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60,
            0xDF60EFC3, 0xA867DF55, 0x316E8EEF, 0x4669BE79,
            0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236,
            0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F,
            0xC5BA3BBE, 0xB2BD0B28, 0x2BB45A92, 0x5CB36A04,
            0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D,
            0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A,
            0x9C0906A9, 0xEB0E363F, 0x72076785, 0x05005713,
            0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38,
            0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21,
            0x86D3D2D4, 0xF1D4E242, 0x68DDB3F8, 0x1FDA836E,
            0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777,
            0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C,
            0x8F659EFF, 0xF862AE69, 0x616BFFD3, 0x166CCF45,
            0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2,
            0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB,
            0xAED16A4A, 0xD9D65ADC, 0x40DF0B66, 0x37D83BF0,
            0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9,
            0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6,
            0xBAD03605, 0xCDD70693, 0x54DE5729, 0x23D967BF,
            0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94,
            0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D
        );

        // For this function to yield the same output as PHP's crc32 function, $crc would have to be
        // set to 0xFFFFFFFF, initially - not 0x00000000 as it currently is.
        $crc = 0x00000000;
        $length = strlen($data);

        for ($i=0; $i<$length; $i++) {
            // We AND $crc >> 8 with 0x00FFFFFF because we want the eight newly added bits to all
            // be zero.  PHP, unfortunately, doesn't always do this.  0x80000000 >> 8, as an example,
            // yields 0xFF800000 - not 0x00800000.  The following link elaborates:
            // http://www.php.net/manual/en/language.operators.bitwise.php#57281
            $crc = (($crc >> 8) & 0x00FFFFFF) ^ $crc_lookup_table[($crc & 0xFF) ^ ord($data[$i])];
        }

        // In addition to having to set $crc to 0xFFFFFFFF, initially, the return value must be XOR'd with
        // 0xFFFFFFFF for this function to return the same thing that PHP's crc32 function would.
        return $crc;
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

    /**
     * RSA Encrypt
     *
     * Returns mod(pow($m, $e), $n), where $n should be the product of two (large) primes $p and $q and where $e
     * should be a number with the property that gcd($e, ($p - 1) * ($q - 1)) == 1.  Could just make anything that
     * calls this call modexp, instead, but I think this makes things clearer, maybe...
     *
     * @see self::__construct()
     * @param BigInteger $m
     * @param array $key
     * @return BigInteger
     * @access private
     */
    function _rsa_crypt($m, $key)
    {
        /*
        $rsa = new RSA();
        $rsa->loadKey($key, RSA::PUBLIC_FORMAT_RAW);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        return $rsa->encrypt($m);
        */

        // To quote from protocol-1.5.txt:
        // The most significant byte (which is only partial as the value must be
        // less than the public modulus, which is never a power of two) is zero.
        //
        // The next byte contains the value 2 (which stands for public-key
        // encrypted data in the PKCS standard [PKCS#1]).  Then, there are non-
        // zero random bytes to fill any unused space, a zero byte, and the data
        // to be encrypted in the least significant bytes, the last byte of the
        // data in the least significant byte.

        // Presumably the part of PKCS#1 they're refering to is "Section 7.2.1 Encryption Operation",
        // under "7.2 RSAES-PKCS1-v1.5" and "7 Encryption schemes" of the following URL:
        // ftp://ftp.rsasecurity.com/pub/pkcs/pkcs-1/pkcs-1v2-1.pdf
        $modulus = $key[1]->toBytes();
        $length = strlen($modulus) - strlen($m) - 3;
        $random = '';
        while (strlen($random) != $length) {
            $block = Random::string($length - strlen($random));
            $block = str_replace("\x00", '', $block);
            $random.= $block;
        }
        $temp = chr(0) . chr(2) . $random . chr(0) . $m;

        $m = new BigInteger($temp, 256);
        $m = $m->modPow($key[0], $key[1]);

        return $m->toBytes();
    }

    /**
     * Define Array
     *
     * Takes any number of arrays whose indices are integers and whose values are strings and defines a bunch of
     * named constants from it, using the value as the name of the constant and the index as the value of the constant.
     * If any of the constants that would be defined already exists, none of the constants will be defined.
     *
     * @access private
     */
    function _define_array()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            foreach ($arg as $key => $value) {
                if (!defined($value)) {
                    define($value, $key);
                } else {
                    break 2;
                }
            }
        }
    }

    /**
     * Returns a log of the packets that have been sent and received.
     *
     * Returns a string if NET_SSH1_LOGGING == self::LOG_COMPLEX, an array if NET_SSH1_LOGGING == self::LOG_SIMPLE and false if !defined('NET_SSH1_LOGGING')
     *
     * @access public
     * @return array|false|string
     */
    function getLog()
    {
        if (!defined('NET_SSH1_LOGGING')) {
            return false;
        }

        switch (NET_SSH1_LOGGING) {
            case self::LOG_SIMPLE:
                return $this->message_number_log;
                break;
            case self::LOG_COMPLEX:
                return $this->_format_log($this->message_log, $this->protocol_flags_log);
                break;
            default:
                return false;
        }
    }

    /**
     * Formats a log for printing
     *
     * @param array $message_log
     * @param array $message_number_log
     * @access private
     * @return string
     */
    function _format_log($message_log, $message_number_log)
    {
        $output = '';
        for ($i = 0; $i < count($message_log); $i++) {
            $output.= $message_number_log[$i] . "\r\n";
            $current_log = $message_log[$i];
            $j = 0;
            do {
                if (strlen($current_log)) {
                    $output.= str_pad(dechex($j), 7, '0', STR_PAD_LEFT) . '0  ';
                }
                $fragment = $this->_string_shift($current_log, $this->log_short_width);
                $hex = substr(preg_replace_callback('#.#s', array($this, '_format_log_helper'), $fragment), strlen($this->log_boundary));
                // replace non ASCII printable characters with dots
                // http://en.wikipedia.org/wiki/ASCII#ASCII_printable_characters
                // also replace < with a . since < messes up the output on web browsers
                $raw = preg_replace('#[^\x20-\x7E]|<#', '.', $fragment);
                $output.= str_pad($hex, $this->log_long_width - $this->log_short_width, ' ') . $raw . "\r\n";
                $j++;
            } while (strlen($current_log));
            $output.= "\r\n";
        }

        return $output;
    }

    /**
     * Helper function for _format_log
     *
     * For use with preg_replace_callback()
     *
     * @param array $matches
     * @access private
     * @return string
     */
    function _format_log_helper($matches)
    {
        return $this->log_boundary . str_pad(dechex(ord($matches[0])), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Return the server key public exponent
     *
     * Returns, by default, the base-10 representation.  If $raw_output is set to true, returns, instead,
     * the raw bytes.  This behavior is similar to PHP's md5() function.
     *
     * @param bool $raw_output
     * @return string
     * @access public
     */
    function getServerKeyPublicExponent($raw_output = false)
    {
        return $raw_output ? $this->server_key_public_exponent->toBytes() : $this->server_key_public_exponent->toString();
    }

    /**
     * Return the server key public modulus
     *
     * Returns, by default, the base-10 representation.  If $raw_output is set to true, returns, instead,
     * the raw bytes.  This behavior is similar to PHP's md5() function.
     *
     * @param bool $raw_output
     * @return string
     * @access public
     */
    function getServerKeyPublicModulus($raw_output = false)
    {
        return $raw_output ? $this->server_key_public_modulus->toBytes() : $this->server_key_public_modulus->toString();
    }

    /**
     * Return the host key public exponent
     *
     * Returns, by default, the base-10 representation.  If $raw_output is set to true, returns, instead,
     * the raw bytes.  This behavior is similar to PHP's md5() function.
     *
     * @param bool $raw_output
     * @return string
     * @access public
     */
    function getHostKeyPublicExponent($raw_output = false)
    {
        return $raw_output ? $this->host_key_public_exponent->toBytes() : $this->host_key_public_exponent->toString();
    }

    /**
     * Return the host key public modulus
     *
     * Returns, by default, the base-10 representation.  If $raw_output is set to true, returns, instead,
     * the raw bytes.  This behavior is similar to PHP's md5() function.
     *
     * @param bool $raw_output
     * @return string
     * @access public
     */
    function getHostKeyPublicModulus($raw_output = false)
    {
        return $raw_output ? $this->host_key_public_modulus->toBytes() : $this->host_key_public_modulus->toString();
    }

    /**
     * Return a list of ciphers supported by SSH1 server.
     *
     * Just because a cipher is supported by an SSH1 server doesn't mean it's supported by this library. If $raw_output
     * is set to true, returns, instead, an array of constants.  ie. instead of array('Triple-DES in CBC mode'), you'll
     * get array(self::CIPHER_3DES).
     *
     * @param bool $raw_output
     * @return array
     * @access public
     */
    function getSupportedCiphers($raw_output = false)
    {
        return $raw_output ? array_keys($this->supported_ciphers) : array_values($this->supported_ciphers);
    }

    /**
     * Return a list of authentications supported by SSH1 server.
     *
     * Just because a cipher is supported by an SSH1 server doesn't mean it's supported by this library. If $raw_output
     * is set to true, returns, instead, an array of constants.  ie. instead of array('password authentication'), you'll
     * get array(self::AUTH_PASSWORD).
     *
     * @param bool $raw_output
     * @return array
     * @access public
     */
    function getSupportedAuthentications($raw_output = false)
    {
        return $raw_output ? array_keys($this->supported_authentications) : array_values($this->supported_authentications);
    }

    /**
     * Return the server identification.
     *
     * @return string
     * @access public
     */
    function getServerIdentification()
    {
        return rtrim($this->server_identification);
    }

    /**
     * Logs data packets
     *
     * Makes sure that only the last 1MB worth of packets will be logged
     *
     * @param int $protocol_flags
     * @param string $message
     * @access private
     */
    function _append_log($protocol_flags, $message)
    {
        switch (NET_SSH1_LOGGING) {
            // useful for benchmarks
            case self::LOG_SIMPLE:
                $this->protocol_flags_log[] = $protocol_flags;
                break;
            // the most useful log for SSH1
            case self::LOG_COMPLEX:
                $this->protocol_flags_log[] = $protocol_flags;
                $this->_string_shift($message);
                $this->log_size+= strlen($message);
                $this->message_log[] = $message;
                while ($this->log_size > self::LOG_MAX_SIZE) {
                    $this->log_size-= strlen(array_shift($this->message_log));
                    array_shift($this->protocol_flags_log);
                }
                break;
            // dump the output out realtime; packets may be interspersed with non packets,
            // passwords won't be filtered out and select other packets may not be correctly
            // identified
            case self::LOG_REALTIME:
                echo "<pre>\r\n" . $this->_format_log(array($message), array($protocol_flags)) . "\r\n</pre>\r\n";
                @flush();
                @ob_flush();
                break;
            // basically the same thing as self::LOG_REALTIME with the caveat that self::LOG_REALTIME_FILE
            // needs to be defined and that the resultant log file will be capped out at self::LOG_MAX_SIZE.
            // the earliest part of the log file is denoted by the first <<< START >>> and is not going to necessarily
            // at the beginning of the file
            case self::LOG_REALTIME_FILE:
                if (!isset($this->realtime_log_file)) {
                    // PHP doesn't seem to like using constants in fopen()
                    $filename = self::LOG_REALTIME_FILE;
                    $fp = fopen($filename, 'w');
                    $this->realtime_log_file = $fp;
                }
                if (!is_resource($this->realtime_log_file)) {
                    break;
                }
                $entry = $this->_format_log(array($message), array($protocol_flags));
                if ($this->realtime_log_wrap) {
                    $temp = "<<< START >>>\r\n";
                    $entry.= $temp;
                    fseek($this->realtime_log_file, ftell($this->realtime_log_file) - strlen($temp));
                }
                $this->realtime_log_size+= strlen($entry);
                if ($this->realtime_log_size > self::LOG_MAX_SIZE) {
                    fseek($this->realtime_log_file, 0);
                    $this->realtime_log_size = strlen($entry);
                    $this->realtime_log_wrap = true;
                }
                fputs($this->realtime_log_file, $entry);
        }
    }
}
