<?php

/**
 * Pure-PHP implementation of SSHv2.
 *
 * PHP version 5
 *
 * Here are some examples of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $ssh = new \phpseclib\Net\SSH2('www.domain.tld');
 *    if (!$ssh->login('username', 'password')) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->exec('pwd');
 *    echo $ssh->exec('ls -la');
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $key = new \phpseclib\Crypt\RSA();
 *    //$key->setPassword('whatever');
 *    $key->loadKey(file_get_contents('privatekey'));
 *
 *    $ssh = new \phpseclib\Net\SSH2('www.domain.tld');
 *    if (!$ssh->login('username', $key)) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->read('username@username:~$');
 *    $ssh->write("ls -la\n");
 *    echo $ssh->read('username@username:~$');
 * ?>
 * </code>
 *
 * @category  Net
 * @package   SSH2
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\Net;

use phpseclib\Crypt\Base;
use phpseclib\Crypt\Blowfish;
use phpseclib\Crypt\Hash;
use phpseclib\Crypt\Random;
use phpseclib\Crypt\RC4;
use phpseclib\Crypt\Rijndael;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\TripleDES;
use phpseclib\Crypt\Twofish;
use phpseclib\Math\BigInteger; // Used to do Diffie-Hellman key exchange and DSA/RSA signature verification.
use phpseclib\System\SSH\Agent;

/**
 * Pure-PHP implementation of SSHv2.
 *
 * @package SSH2
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class SSH2
{
    /**#@+
     * Execution Bitmap Masks
     *
     * @see \phpseclib\Net\SSH2::bitmap
     * @access private
     */
    const MASK_CONSTRUCTOR   = 0x00000001;
    const MASK_CONNECTED     = 0x00000002;
    const MASK_LOGIN_REQ     = 0x00000004;
    const MASK_LOGIN         = 0x00000008;
    const MASK_SHELL         = 0x00000010;
    const MASK_WINDOW_ADJUST = 0x00000020;
    /**#@-*/

    /**#@+
     * Channel constants
     *
     * RFC4254 refers not to client and server channels but rather to sender and recipient channels.  we don't refer
     * to them in that way because RFC4254 toggles the meaning. the client sends a SSH_MSG_CHANNEL_OPEN message with
     * a sender channel and the server sends a SSH_MSG_CHANNEL_OPEN_CONFIRMATION in response, with a sender and a
     * recepient channel.  at first glance, you might conclude that SSH_MSG_CHANNEL_OPEN_CONFIRMATION's sender channel
     * would be the same thing as SSH_MSG_CHANNEL_OPEN's sender channel, but it's not, per this snipet:
     *     The 'recipient channel' is the channel number given in the original
     *     open request, and 'sender channel' is the channel number allocated by
     *     the other side.
     *
     * @see \phpseclib\Net\SSH2::_send_channel_packet()
     * @see \phpseclib\Net\SSH2::_get_channel_packet()
     * @access private
    */
    const CHANNEL_EXEC          = 1; // PuTTy uses 0x100
    const CHANNEL_SHELL         = 2;
    const CHANNEL_SUBSYSTEM     = 3;
    const CHANNEL_AGENT_FORWARD = 4;
    const CHANNEL_KEEP_ALIVE    = 5;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Net\SSH2::getLog()
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
    /**
     * Make sure that the log never gets larger than this
     */
    const LOG_MAX_SIZE = 1048576; // 1024 * 1024
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Net\SSH2::read()
    */
    /**
     * Returns when a string matching $expect exactly is found
     */
    const READ_SIMPLE = 1;
    /**
     * Returns when a string matching the regular expression $expect is found
     */
    const READ_REGEX = 2;
    /**
     * Returns whenever a data packet is received.
     *
     * Some data packets may only contain a single character so it may be necessary
     * to call read() multiple times when using this option
     */
    const READ_NEXT = 3;
    /**#@-*/

    /**
     * The SSH identifier
     *
     * @var string
     * @access private
     */
    var $identifier;

    /**
     * The Socket Object
     *
     * @var object
     * @access private
     */
    var $fsock;

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
     * Error information
     *
     * @see self::getErrors()
     * @see self::getLastError()
     * @var string
     * @access private
     */
    var $errors = array();

    /**
     * Server Identifier
     *
     * @see self::getServerIdentification()
     * @var array|false
     * @access private
     */
    var $server_identifier = false;

    /**
     * Key Exchange Algorithms
     *
     * @see self::getKexAlgorithims()
     * @var array|false
     * @access private
     */
    var $kex_algorithms = false;

    /**
     * Key Exchange Algorithm
     *
     * @see self::getMethodsNegotiated()
     * @var string|false
     * @access private
     */
    var $kex_algorithm = false;

    /**
     * Minimum Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     * @access private
     */
    var $kex_dh_group_size_min = 1536;

    /**
     * Preferred Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     * @access private
     */
    var $kex_dh_group_size_preferred = 2048;

    /**
     * Maximum Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     * @access private
     */
    var $kex_dh_group_size_max = 4096;

    /**
     * Server Host Key Algorithms
     *
     * @see self::getServerHostKeyAlgorithms()
     * @var array|false
     * @access private
     */
    var $server_host_key_algorithms = false;

    /**
     * Encryption Algorithms: Client to Server
     *
     * @see self::getEncryptionAlgorithmsClient2Server()
     * @var array|false
     * @access private
     */
    var $encryption_algorithms_client_to_server = false;

    /**
     * Encryption Algorithms: Server to Client
     *
     * @see self::getEncryptionAlgorithmsServer2Client()
     * @var array|false
     * @access private
     */
    var $encryption_algorithms_server_to_client = false;

    /**
     * MAC Algorithms: Client to Server
     *
     * @see self::getMACAlgorithmsClient2Server()
     * @var array|false
     * @access private
     */
    var $mac_algorithms_client_to_server = false;

    /**
     * MAC Algorithms: Server to Client
     *
     * @see self::getMACAlgorithmsServer2Client()
     * @var array|false
     * @access private
     */
    var $mac_algorithms_server_to_client = false;

    /**
     * Compression Algorithms: Client to Server
     *
     * @see self::getCompressionAlgorithmsClient2Server()
     * @var array|false
     * @access private
     */
    var $compression_algorithms_client_to_server = false;

    /**
     * Compression Algorithms: Server to Client
     *
     * @see self::getCompressionAlgorithmsServer2Client()
     * @var array|false
     * @access private
     */
    var $compression_algorithms_server_to_client = false;

    /**
     * Languages: Server to Client
     *
     * @see self::getLanguagesServer2Client()
     * @var array|false
     * @access private
     */
    var $languages_server_to_client = false;

    /**
     * Languages: Client to Server
     *
     * @see self::getLanguagesClient2Server()
     * @var array|false
     * @access private
     */
    var $languages_client_to_server = false;

    /**
     * Preferred Algorithms
     *
     * @see self::setPreferredAlgorithms()
     * @var array
     * @access private
     */
    var $preferred = array();

    /**
     * Block Size for Server to Client Encryption
     *
     * "Note that the length of the concatenation of 'packet_length',
     *  'padding_length', 'payload', and 'random padding' MUST be a multiple
     *  of the cipher block size or 8, whichever is larger.  This constraint
     *  MUST be enforced, even when using stream ciphers."
     *
     *  -- http://tools.ietf.org/html/rfc4253#section-6
     *
     * @see self::__construct()
     * @see self::_send_binary_packet()
     * @var int
     * @access private
     */
    var $encrypt_block_size = 8;

    /**
     * Block Size for Client to Server Encryption
     *
     * @see self::__construct()
     * @see self::_get_binary_packet()
     * @var int
     * @access private
     */
    var $decrypt_block_size = 8;

    /**
     * Server to Client Encryption Object
     *
     * @see self::_get_binary_packet()
     * @var object
     * @access private
     */
    var $decrypt = false;

    /**
     * Client to Server Encryption Object
     *
     * @see self::_send_binary_packet()
     * @var object
     * @access private
     */
    var $encrypt = false;

    /**
     * Client to Server HMAC Object
     *
     * @see self::_send_binary_packet()
     * @var object
     * @access private
     */
    var $hmac_create = false;

    /**
     * Server to Client HMAC Object
     *
     * @see self::_get_binary_packet()
     * @var object
     * @access private
     */
    var $hmac_check = false;

    /**
     * Size of server to client HMAC
     *
     * We need to know how big the HMAC will be for the server to client direction so that we know how many bytes to read.
     * For the client to server side, the HMAC object will make the HMAC as long as it needs to be.  All we need to do is
     * append it.
     *
     * @see self::_get_binary_packet()
     * @var int
     * @access private
     */
    var $hmac_size = false;

    /**
     * Server Public Host Key
     *
     * @see self::getServerPublicHostKey()
     * @var string
     * @access private
     */
    var $server_public_host_key;

    /**
     * Session identifier
     *
     * "The exchange hash H from the first key exchange is additionally
     *  used as the session identifier, which is a unique identifier for
     *  this connection."
     *
     *  -- http://tools.ietf.org/html/rfc4253#section-7.2
     *
     * @see self::_key_exchange()
     * @var string
     * @access private
     */
    var $session_id = false;

    /**
     * Exchange hash
     *
     * The current exchange hash
     *
     * @see self::_key_exchange()
     * @var string
     * @access private
     */
    var $exchange_hash = false;

    /**
     * Message Numbers
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $message_numbers = array();

    /**
     * Disconnection Message 'reason codes' defined in RFC4253
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $disconnect_reasons = array();

    /**
     * SSH_MSG_CHANNEL_OPEN_FAILURE 'reason codes', defined in RFC4254
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $channel_open_failure_reasons = array();

    /**
     * Terminal Modes
     *
     * @link http://tools.ietf.org/html/rfc4254#section-8
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $terminal_modes = array();

    /**
     * SSH_MSG_CHANNEL_EXTENDED_DATA's data_type_codes
     *
     * @link http://tools.ietf.org/html/rfc4254#section-5.2
     * @see self::__construct()
     * @var array
     * @access private
     */
    var $channel_extended_data_type_codes = array();

    /**
     * Send Sequence Number
     *
     * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
     *
     * @see self::_send_binary_packet()
     * @var int
     * @access private
     */
    var $send_seq_no = 0;

    /**
     * Get Sequence Number
     *
     * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
     *
     * @see self::_get_binary_packet()
     * @var int
     * @access private
     */
    var $get_seq_no = 0;

    /**
     * Server Channels
     *
     * Maps client channels to server channels
     *
     * @see self::_get_channel_packet()
     * @see self::exec()
     * @var array
     * @access private
     */
    var $server_channels = array();

    /**
     * Channel Buffers
     *
     * If a client requests a packet from one channel but receives two packets from another those packets should
     * be placed in a buffer
     *
     * @see self::_get_channel_packet()
     * @see self::exec()
     * @var array
     * @access private
     */
    var $channel_buffers = array();

    /**
     * Channel Status
     *
     * Contains the type of the last sent message
     *
     * @see self::_get_channel_packet()
     * @var array
     * @access private
     */
    var $channel_status = array();

    /**
     * Packet Size
     *
     * Maximum packet size indexed by channel
     *
     * @see self::_send_channel_packet()
     * @var array
     * @access private
     */
    var $packet_size_client_to_server = array();

    /**
     * Message Number Log
     *
     * @see self::getLog()
     * @var array
     * @access private
     */
    var $message_number_log = array();

    /**
     * Message Log
     *
     * @see self::getLog()
     * @var array
     * @access private
     */
    var $message_log = array();

    /**
     * The Window Size
     *
     * Bytes the other party can send before it must wait for the window to be adjusted (0x7FFFFFFF = 2GB)
     *
     * @var int
     * @see self::_send_channel_packet()
     * @see self::exec()
     * @access private
     */
    var $window_size = 0x7FFFFFFF;

    /**
     * What we resize the window to
     *
     * When PuTTY resizes the window it doesn't add an additional 0x7FFFFFFF bytes - it adds 0x40000000 bytes.
     * Some SFTP clients (GoAnywhere) don't support adding 0x7FFFFFFF to the window size after the fact so
     * we'll just do what PuTTY does
     *
     * @var int
     * @see self::_send_channel_packet()
     * @see self::exec()
     * @access private
     */
    var $window_resize = 0x40000000;

    /**
     * Window size, server to client
     *
     * Window size indexed by channel
     *
     * @see self::_send_channel_packet()
     * @var array
     * @access private
     */
    var $window_size_server_to_client = array();

    /**
     * Window size, client to server
     *
     * Window size indexed by channel
     *
     * @see self::_get_channel_packet()
     * @var array
     * @access private
     */
    var $window_size_client_to_server = array();

    /**
     * Server signature
     *
     * Verified against $this->session_id
     *
     * @see self::getServerPublicHostKey()
     * @var string
     * @access private
     */
    var $signature = '';

    /**
     * Server signature format
     *
     * ssh-rsa or ssh-dss.
     *
     * @see self::getServerPublicHostKey()
     * @var string
     * @access private
     */
    var $signature_format = '';

    /**
     * Interactive Buffer
     *
     * @see self::read()
     * @var array
     * @access private
     */
    var $interactiveBuffer = '';

    /**
     * Current log size
     *
     * Should never exceed self::LOG_MAX_SIZE
     *
     * @see self::_send_binary_packet()
     * @see self::_get_binary_packet()
     * @var int
     * @access private
     */
    var $log_size;

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
     * Keep Alive Interval
     *
     * @see self::setKeepAlive()
     * @access private
     */
    var $keepAlive;

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
     * Has the signature been validated?
     *
     * @see self::getServerPublicHostKey()
     * @var bool
     * @access private
     */
    var $signature_validated = false;

    /**
     * Real-time log file wrap boolean
     *
     * @see self::_append_log()
     * @access private
     */
    var $realtime_log_wrap;

    /**
     * Flag to suppress stderr from output
     *
     * @see self::enableQuietMode()
     * @access private
     */
    var $quiet_mode = false;

    /**
     * Time of first network activity
     *
     * @var int
     * @access private
     */
    var $last_packet;

    /**
     * Exit status returned from ssh if any
     *
     * @var int
     * @access private
     */
    var $exit_status;

    /**
     * Flag to request a PTY when using exec()
     *
     * @var bool
     * @see self::enablePTY()
     * @access private
     */
    var $request_pty = false;

    /**
     * Flag set while exec() is running when using enablePTY()
     *
     * @var bool
     * @access private
     */
    var $in_request_pty_exec = false;

    /**
     * Flag set after startSubsystem() is called
     *
     * @var bool
     * @access private
     */
    var $in_subsystem;

    /**
     * Contents of stdError
     *
     * @var string
     * @access private
     */
    var $stdErrorLog;

    /**
     * The Last Interactive Response
     *
     * @see self::_keyboard_interactive_process()
     * @var string
     * @access private
     */
    var $last_interactive_response = '';

    /**
     * Keyboard Interactive Request / Responses
     *
     * @see self::_keyboard_interactive_process()
     * @var array
     * @access private
     */
    var $keyboard_requests_responses = array();

    /**
     * Banner Message
     *
     * Quoting from the RFC, "in some jurisdictions, sending a warning message before
     * authentication may be relevant for getting legal protection."
     *
     * @see self::_filter()
     * @see self::getBannerMessage()
     * @var string
     * @access private
     */
    var $banner_message = '';

    /**
     * Did read() timeout or return normally?
     *
     * @see self::isTimeout()
     * @var bool
     * @access private
     */
    var $is_timeout = false;

    /**
     * Log Boundary
     *
     * @see self::_format_log()
     * @var string
     * @access private
     */
    var $log_boundary = ':';

    /**
     * Log Long Width
     *
     * @see self::_format_log()
     * @var int
     * @access private
     */
    var $log_long_width = 65;

    /**
     * Log Short Width
     *
     * @see self::_format_log()
     * @var int
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
     * Number of columns for terminal window size
     *
     * @see self::getWindowColumns()
     * @see self::setWindowColumns()
     * @see self::setWindowSize()
     * @var int
     * @access private
     */
    var $windowColumns = 80;

    /**
     * Number of columns for terminal window size
     *
     * @see self::getWindowRows()
     * @see self::setWindowRows()
     * @see self::setWindowSize()
     * @var int
     * @access private
     */
    var $windowRows = 24;

    /**
     * Crypto Engine
     *
     * @see self::setCryptoEngine()
     * @see self::_key_exchange()
     * @var int
     * @access private
     */
    var $crypto_engine = false;

    /**
     * A System_SSH_Agent for use in the SSH2 Agent Forwarding scenario
     *
     * @var System_SSH_Agent
     * @access private
     */
    var $agent;

    /**
     * Send the identification string first?
     *
     * @var bool
     * @access private
     */
    var $send_id_string_first = true;

    /**
     * Send the key exchange initiation packet first?
     *
     * @var bool
     * @access private
     */
    var $send_kex_first = true;

    /**
     * Some versions of OpenSSH incorrectly calculate the key size
     *
     * @var bool
     * @access private
     */
    var $bad_key_size_fix = false;

    /**
     * Should we try to re-connect to re-establish keys?
     *
     * @var bool
     * @access private
     */
    var $retry_connect = false;

    /**
     * Binary Packet Buffer
     *
     * @var string|false
     * @access private
     */
    var $binary_packet_buffer = false;

    /**
     * Preferred Signature Format
     *
     * @var string|false
     * @access private
     */
    var $preferred_signature_format = false;

    /**
     * Authentication Credentials
     *
     * @var array
     * @access private
     */
    var $auth = array();

    /**
     * The authentication methods that may productively continue authentication.
     *
     * @see https://tools.ietf.org/html/rfc4252#section-5.1
     * @var array|null
     */
    private $auth_methods_to_continue = null;

    /**
     * Default Constructor.
     *
     * $host can either be a string, representing the host, or a stream resource.
     *
     * @param mixed $host
     * @param int $port
     * @param int $timeout
     * @see self::login()
     * @return \phpseclib\Net\SSH2
     * @access public
     */
    function __construct($host, $port = 22, $timeout = 10)
    {
        $this->message_numbers = array(
            1 => 'NET_SSH2_MSG_DISCONNECT',
            2 => 'NET_SSH2_MSG_IGNORE',
            3 => 'NET_SSH2_MSG_UNIMPLEMENTED',
            4 => 'NET_SSH2_MSG_DEBUG',
            5 => 'NET_SSH2_MSG_SERVICE_REQUEST',
            6 => 'NET_SSH2_MSG_SERVICE_ACCEPT',
            20 => 'NET_SSH2_MSG_KEXINIT',
            21 => 'NET_SSH2_MSG_NEWKEYS',
            30 => 'NET_SSH2_MSG_KEXDH_INIT',
            31 => 'NET_SSH2_MSG_KEXDH_REPLY',
            50 => 'NET_SSH2_MSG_USERAUTH_REQUEST',
            51 => 'NET_SSH2_MSG_USERAUTH_FAILURE',
            52 => 'NET_SSH2_MSG_USERAUTH_SUCCESS',
            53 => 'NET_SSH2_MSG_USERAUTH_BANNER',

            80 => 'NET_SSH2_MSG_GLOBAL_REQUEST',
            81 => 'NET_SSH2_MSG_REQUEST_SUCCESS',
            82 => 'NET_SSH2_MSG_REQUEST_FAILURE',
            90 => 'NET_SSH2_MSG_CHANNEL_OPEN',
            91 => 'NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION',
            92 => 'NET_SSH2_MSG_CHANNEL_OPEN_FAILURE',
            93 => 'NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST',
            94 => 'NET_SSH2_MSG_CHANNEL_DATA',
            95 => 'NET_SSH2_MSG_CHANNEL_EXTENDED_DATA',
            96 => 'NET_SSH2_MSG_CHANNEL_EOF',
            97 => 'NET_SSH2_MSG_CHANNEL_CLOSE',
            98 => 'NET_SSH2_MSG_CHANNEL_REQUEST',
            99 => 'NET_SSH2_MSG_CHANNEL_SUCCESS',
            100 => 'NET_SSH2_MSG_CHANNEL_FAILURE'
        );
        $this->disconnect_reasons = array(
            1 => 'NET_SSH2_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT',
            2 => 'NET_SSH2_DISCONNECT_PROTOCOL_ERROR',
            3 => 'NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED',
            4 => 'NET_SSH2_DISCONNECT_RESERVED',
            5 => 'NET_SSH2_DISCONNECT_MAC_ERROR',
            6 => 'NET_SSH2_DISCONNECT_COMPRESSION_ERROR',
            7 => 'NET_SSH2_DISCONNECT_SERVICE_NOT_AVAILABLE',
            8 => 'NET_SSH2_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED',
            9 => 'NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE',
            10 => 'NET_SSH2_DISCONNECT_CONNECTION_LOST',
            11 => 'NET_SSH2_DISCONNECT_BY_APPLICATION',
            12 => 'NET_SSH2_DISCONNECT_TOO_MANY_CONNECTIONS',
            13 => 'NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER',
            14 => 'NET_SSH2_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE',
            15 => 'NET_SSH2_DISCONNECT_ILLEGAL_USER_NAME'
        );
        $this->channel_open_failure_reasons = array(
            1 => 'NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED'
        );
        $this->terminal_modes = array(
            0 => 'NET_SSH2_TTY_OP_END'
        );
        $this->channel_extended_data_type_codes = array(
            1 => 'NET_SSH2_EXTENDED_DATA_STDERR'
        );

        $this->_define_array(
            $this->message_numbers,
            $this->disconnect_reasons,
            $this->channel_open_failure_reasons,
            $this->terminal_modes,
            $this->channel_extended_data_type_codes,
            array(60 => 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ'),
            array(60 => 'NET_SSH2_MSG_USERAUTH_PK_OK'),
            array(60 => 'NET_SSH2_MSG_USERAUTH_INFO_REQUEST',
                  61 => 'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE'),
            // RFC 4419 - diffie-hellman-group-exchange-sha{1,256}
            array(30 => 'NET_SSH2_MSG_KEXDH_GEX_REQUEST_OLD',
                  31 => 'NET_SSH2_MSG_KEXDH_GEX_GROUP',
                  32 => 'NET_SSH2_MSG_KEXDH_GEX_INIT',
                  33 => 'NET_SSH2_MSG_KEXDH_GEX_REPLY',
                  34 => 'NET_SSH2_MSG_KEXDH_GEX_REQUEST'),
            // RFC 5656 - Elliptic Curves (for curve25519-sha256@libssh.org)
            array(30 => 'NET_SSH2_MSG_KEX_ECDH_INIT',
                  31 => 'NET_SSH2_MSG_KEX_ECDH_REPLY')
        );

        if (is_resource($host)) {
            $this->fsock = $host;
            return;
        }

        if (is_string($host)) {
            $this->host = $host;
            $this->port = $port;
            $this->timeout = $timeout;
        }
    }

    /**
     * Set Crypto Engine Mode
     *
     * Possible $engine values:
     * CRYPT_MODE_INTERNAL, CRYPT_MODE_MCRYPT
     *
     * @param int $engine
     * @access public
     */
    function setCryptoEngine($engine)
    {
        $this->crypto_engine = $engine;
    }

    /**
     * Send Identification String First
     *
     * https://tools.ietf.org/html/rfc4253#section-4.2 says "when the connection has been established,
     * both sides MUST send an identification string". It does not say which side sends it first. In
     * theory it shouldn't matter but it is a fact of life that some SSH servers are simply buggy
     *
     * @access public
     */
    function sendIdentificationStringFirst()
    {
        $this->send_id_string_first = true;
    }

    /**
     * Send Identification String Last
     *
     * https://tools.ietf.org/html/rfc4253#section-4.2 says "when the connection has been established,
     * both sides MUST send an identification string". It does not say which side sends it first. In
     * theory it shouldn't matter but it is a fact of life that some SSH servers are simply buggy
     *
     * @access public
     */
    function sendIdentificationStringLast()
    {
        $this->send_id_string_first = false;
    }

    /**
     * Send SSH_MSG_KEXINIT First
     *
     * https://tools.ietf.org/html/rfc4253#section-7.1 says "key exchange begins by each sending
     * sending the [SSH_MSG_KEXINIT] packet". It does not say which side sends it first. In theory
     * it shouldn't matter but it is a fact of life that some SSH servers are simply buggy
     *
     * @access public
     */
    function sendKEXINITFirst()
    {
        $this->send_kex_first = true;
    }

    /**
     * Send SSH_MSG_KEXINIT Last
     *
     * https://tools.ietf.org/html/rfc4253#section-7.1 says "key exchange begins by each sending
     * sending the [SSH_MSG_KEXINIT] packet". It does not say which side sends it first. In theory
     * it shouldn't matter but it is a fact of life that some SSH servers are simply buggy
     *
     * @access public
     */
    function sendKEXINITLast()
    {
        $this->send_kex_first = false;
    }

    /**
     * Connect to an SSHv2 server
     *
     * @return bool
     * @access private
     */
    function _connect()
    {
        if ($this->bitmap & self::MASK_CONSTRUCTOR) {
            return false;
        }

        $this->bitmap |= self::MASK_CONSTRUCTOR;

        $this->curTimeout = $this->timeout;

        $this->last_packet = microtime(true);

        if (!is_resource($this->fsock)) {
            $start = microtime(true);
            // with stream_select a timeout of 0 means that no timeout takes place;
            // with fsockopen a timeout of 0 means that you instantly timeout
            // to resolve this incompatibility a timeout of 100,000 will be used for fsockopen if timeout is 0
            $this->fsock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->curTimeout == 0 ? 100000 : $this->curTimeout);
            if (!$this->fsock) {
                $host = $this->host . ':' . $this->port;
                user_error(rtrim("Cannot connect to $host. Error $errno. $errstr"));
                return false;
            }
            $elapsed = microtime(true) - $start;

            if ($this->curTimeout) {
                $this->curTimeout-= $elapsed;
                if ($this->curTimeout < 0) {
                    $this->is_timeout = true;
                    return false;
                }
            }
        }

        $this->identifier = $this->_generate_identifier();

        if ($this->send_id_string_first) {
            fputs($this->fsock, $this->identifier . "\r\n");
        }

        /* According to the SSH2 specs,

          "The server MAY send other lines of data before sending the version
           string.  Each line SHOULD be terminated by a Carriage Return and Line
           Feed.  Such lines MUST NOT begin with "SSH-", and SHOULD be encoded
           in ISO-10646 UTF-8 [RFC3629] (language is not specified).  Clients
           MUST be able to process such lines." */
        $data = '';
        while (!feof($this->fsock) && !preg_match('#(.*)^(SSH-(\d\.\d+).*)#ms', $data, $matches)) {
            $line = '';
            while (true) {
                if ($this->curTimeout) {
                    if ($this->curTimeout < 0) {
                        $this->is_timeout = true;
                        return false;
                    }
                    $read = array($this->fsock);
                    $write = $except = null;
                    $start = microtime(true);
                    $sec = floor($this->curTimeout);
                    $usec = 1000000 * ($this->curTimeout - $sec);
                    // on windows this returns a "Warning: Invalid CRT parameters detected" error
                    // the !count() is done as a workaround for <https://bugs.php.net/42682>
                    if (!@stream_select($read, $write, $except, $sec, $usec) && !count($read)) {
                        $this->is_timeout = true;
                        return false;
                    }
                    $elapsed = microtime(true) - $start;
                    $this->curTimeout-= $elapsed;
                }

                $temp = stream_get_line($this->fsock, 255, "\n");
                if (strlen($temp) == 255) {
                    continue;
                }
                if ($temp === false) {
                    return false;
                }

                $line.= "$temp\n";

                // quoting RFC4253, "Implementers who wish to maintain
                // compatibility with older, undocumented versions of this protocol may
                // want to process the identification string without expecting the
                // presence of the carriage return character for reasons described in
                // Section 5 of this document."

                //if (substr($line, -2) == "\r\n") {
                //    break;
                //}

                break;
            }

            $data.= $line;
        }

        if (feof($this->fsock)) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }

        $extra = $matches[1];

        if (defined('NET_SSH2_LOGGING')) {
            $this->_append_log('<-', $matches[0]);
            $this->_append_log('->', $this->identifier . "\r\n");
        }

        $this->server_identifier = trim($temp, "\r\n");
        if (strlen($extra)) {
            $this->errors[] = $data;
        }

        if (version_compare($matches[3], '1.99', '<')) {
            user_error("Cannot connect to SSH $matches[3] servers");
            return false;
        }

        if (!$this->send_id_string_first) {
            fputs($this->fsock, $this->identifier . "\r\n");
        }

        if (!$this->send_kex_first) {
            $response = $this->_get_binary_packet();
            if ($response === false) {
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }

            if (!strlen($response) || ord($response[0]) != NET_SSH2_MSG_KEXINIT) {
                user_error('Expected SSH_MSG_KEXINIT');
                return false;
            }

            if (!$this->_key_exchange($response)) {
                return false;
            }
        }

        if ($this->send_kex_first && !$this->_key_exchange()) {
            return false;
        }

        $this->bitmap|= self::MASK_CONNECTED;

        return true;
    }

    /**
     * Generates the SSH identifier
     *
     * You should overwrite this method in your own class if you want to use another identifier
     *
     * @access protected
     * @return string
     */
    function _generate_identifier()
    {
        $identifier = 'SSH-2.0-phpseclib_2.0';

        $ext = array();
        if (function_exists('sodium_crypto_box_publickey_from_secretkey')) {
            $ext[] = 'libsodium';
        }

        if (extension_loaded('openssl')) {
            $ext[] = 'openssl';
        } elseif (extension_loaded('mcrypt')) {
            $ext[] = 'mcrypt';
        }

        if (extension_loaded('gmp')) {
            $ext[] = 'gmp';
        } elseif (extension_loaded('bcmath')) {
            $ext[] = 'bcmath';
        }

        if (!empty($ext)) {
            $identifier .= ' (' . implode(', ', $ext) . ')';
        }

        return $identifier;
    }

    /**
     * Key Exchange
     *
     * @param string $kexinit_payload_server optional
     * @access private
     */
    function _key_exchange($kexinit_payload_server = false)
    {
        $preferred = $this->preferred;
        $send_kex = true;

        $kex_algorithms = isset($preferred['kex']) ?
            $preferred['kex'] :
            $this->getSupportedKEXAlgorithms();
        $server_host_key_algorithms = isset($preferred['hostkey']) ?
            $preferred['hostkey'] :
            $this->getSupportedHostKeyAlgorithms();
        $s2c_encryption_algorithms = isset($preferred['server_to_client']['crypt']) ?
            $preferred['server_to_client']['crypt'] :
            $this->getSupportedEncryptionAlgorithms();
        $c2s_encryption_algorithms = isset($preferred['client_to_server']['crypt']) ?
            $preferred['client_to_server']['crypt'] :
            $this->getSupportedEncryptionAlgorithms();
        $s2c_mac_algorithms = isset($preferred['server_to_client']['mac']) ?
            $preferred['server_to_client']['mac'] :
            $this->getSupportedMACAlgorithms();
        $c2s_mac_algorithms = isset($preferred['client_to_server']['mac']) ?
            $preferred['client_to_server']['mac'] :
            $this->getSupportedMACAlgorithms();
        $s2c_compression_algorithms = isset($preferred['server_to_client']['comp']) ?
            $preferred['server_to_client']['comp'] :
            $this->getSupportedCompressionAlgorithms();
        $c2s_compression_algorithms = isset($preferred['client_to_server']['comp']) ?
            $preferred['client_to_server']['comp'] :
            $this->getSupportedCompressionAlgorithms();

        // some SSH servers have buggy implementations of some of the above algorithms
        switch (true) {
            case $this->server_identifier == 'SSH-2.0-SSHD':
            case substr($this->server_identifier, 0, 13) == 'SSH-2.0-DLINK':
                if (!isset($preferred['server_to_client']['mac'])) {
                    $s2c_mac_algorithms = array_values(array_diff(
                        $s2c_mac_algorithms,
                        array('hmac-sha1-96', 'hmac-md5-96')
                    ));
                }
                if (!isset($preferred['client_to_server']['mac'])) {
                    $c2s_mac_algorithms = array_values(array_diff(
                        $c2s_mac_algorithms,
                        array('hmac-sha1-96', 'hmac-md5-96')
                    ));
                }
        }

        $str_kex_algorithms = implode(',', $kex_algorithms);
        $str_server_host_key_algorithms = implode(',', $server_host_key_algorithms);
        $encryption_algorithms_server_to_client = implode(',', $s2c_encryption_algorithms);
        $encryption_algorithms_client_to_server = implode(',', $c2s_encryption_algorithms);
        $mac_algorithms_server_to_client = implode(',', $s2c_mac_algorithms);
        $mac_algorithms_client_to_server = implode(',', $c2s_mac_algorithms);
        $compression_algorithms_server_to_client = implode(',', $s2c_compression_algorithms);
        $compression_algorithms_client_to_server = implode(',', $c2s_compression_algorithms);

        $client_cookie = Random::string(16);

        $kexinit_payload_client = pack(
            'Ca*Na*Na*Na*Na*Na*Na*Na*Na*Na*Na*CN',
            NET_SSH2_MSG_KEXINIT,
            $client_cookie,
            strlen($str_kex_algorithms),
            $str_kex_algorithms,
            strlen($str_server_host_key_algorithms),
            $str_server_host_key_algorithms,
            strlen($encryption_algorithms_client_to_server),
            $encryption_algorithms_client_to_server,
            strlen($encryption_algorithms_server_to_client),
            $encryption_algorithms_server_to_client,
            strlen($mac_algorithms_client_to_server),
            $mac_algorithms_client_to_server,
            strlen($mac_algorithms_server_to_client),
            $mac_algorithms_server_to_client,
            strlen($compression_algorithms_client_to_server),
            $compression_algorithms_client_to_server,
            strlen($compression_algorithms_server_to_client),
            $compression_algorithms_server_to_client,
            0,
            '',
            0,
            '',
            0,
            0
        );

        if ($kexinit_payload_server === false) {
            if (!$this->_send_binary_packet($kexinit_payload_client)) {
                return false;
            }

            $kexinit_payload_server = $this->_get_binary_packet();
            if ($kexinit_payload_server === false) {
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }

            if (!strlen($kexinit_payload_server) || ord($kexinit_payload_server[0]) != NET_SSH2_MSG_KEXINIT) {
                user_error('Expected SSH_MSG_KEXINIT');
                return false;
            }

            $send_kex = false;
        }

        $response = $kexinit_payload_server;
        $this->_string_shift($response, 1); // skip past the message number (it should be SSH_MSG_KEXINIT)
        $server_cookie = $this->_string_shift($response, 16);

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->kex_algorithms = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->server_host_key_algorithms = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->encryption_algorithms_client_to_server = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->encryption_algorithms_server_to_client = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->mac_algorithms_client_to_server = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->mac_algorithms_server_to_client = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->compression_algorithms_client_to_server = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->compression_algorithms_server_to_client = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->languages_client_to_server = explode(',', $this->_string_shift($response, $temp['length']));

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->languages_server_to_client = explode(',', $this->_string_shift($response, $temp['length']));

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Cfirst_kex_packet_follows', $this->_string_shift($response, 1)));
        $first_kex_packet_follows = $first_kex_packet_follows != 0;

        if ($send_kex && !$this->_send_binary_packet($kexinit_payload_client)) {
            return false;
        }

        // we need to decide upon the symmetric encryption algorithms before we do the diffie-hellman key exchange
        // we don't initialize any crypto-objects, yet - we do that, later. for now, we need the lengths to make the
        // diffie-hellman key exchange as fast as possible
        $decrypt = $this->_array_intersect_first($s2c_encryption_algorithms, $this->encryption_algorithms_server_to_client);
        $decryptKeyLength = $this->_encryption_algorithm_to_key_size($decrypt);
        if ($decryptKeyLength === null) {
            user_error('No compatible server to client encryption algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        $encrypt = $this->_array_intersect_first($c2s_encryption_algorithms, $this->encryption_algorithms_client_to_server);
        $encryptKeyLength = $this->_encryption_algorithm_to_key_size($encrypt);
        if ($encryptKeyLength === null) {
            user_error('No compatible client to server encryption algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        // through diffie-hellman key exchange a symmetric key is obtained
        $this->kex_algorithm = $kex_algorithm = $this->_array_intersect_first($kex_algorithms, $this->kex_algorithms);
        if ($kex_algorithm === false) {
            user_error('No compatible key exchange algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        $server_host_key_algorithm = $this->_array_intersect_first($server_host_key_algorithms, $this->server_host_key_algorithms);
        if ($server_host_key_algorithm === false) {
            user_error('No compatible server host key algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        $mac_algorithm_in = $this->_array_intersect_first($s2c_mac_algorithms, $this->mac_algorithms_server_to_client);
        if ($mac_algorithm_in === false) {
            user_error('No compatible server to client message authentication algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        $compression_algorithm_out = $this->_array_intersect_first($c2s_compression_algorithms, $this->compression_algorithms_client_to_server);
        if ($compression_algorithm_out === false) {
            user_error('No compatible client to server compression algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }
        //$this->decompress = $compression_algorithm_out == 'zlib';

        $compression_algorithm_in = $this->_array_intersect_first($s2c_compression_algorithms, $this->compression_algorithms_client_to_server);
        if ($compression_algorithm_in === false) {
            user_error('No compatible server to client compression algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }
        //$this->compress = $compression_algorithm_in == 'zlib';

        // Only relevant in diffie-hellman-group-exchange-sha{1,256}, otherwise empty.
        $exchange_hash_rfc4419 = '';

        if ($kex_algorithm === 'curve25519-sha256@libssh.org') {
            $x = Random::string(32);
            $eBytes = sodium_crypto_box_publickey_from_secretkey($x);
            $clientKexInitMessage = 'NET_SSH2_MSG_KEX_ECDH_INIT';
            $serverKexReplyMessage = 'NET_SSH2_MSG_KEX_ECDH_REPLY';
            $kexHash = new Hash('sha256');
        } else {
            if (strpos($kex_algorithm, 'diffie-hellman-group-exchange') === 0) {
                $dh_group_sizes_packed = pack(
                    'NNN',
                    $this->kex_dh_group_size_min,
                    $this->kex_dh_group_size_preferred,
                    $this->kex_dh_group_size_max
                );
                $packet = pack(
                    'Ca*',
                    NET_SSH2_MSG_KEXDH_GEX_REQUEST,
                    $dh_group_sizes_packed
                );
                if (!$this->_send_binary_packet($packet)) {
                    return false;
                }
                $this->_updateLogHistory('UNKNOWN (34)', 'NET_SSH2_MSG_KEXDH_GEX_REQUEST');

                $response = $this->_get_binary_packet();
                if ($response === false) {
                    $this->bitmap = 0;
                    user_error('Connection closed by server');
                    return false;
                }
                extract(unpack('Ctype', $this->_string_shift($response, 1)));
                if ($type != NET_SSH2_MSG_KEXDH_GEX_GROUP) {
                    user_error('Expected SSH_MSG_KEX_DH_GEX_GROUP');
                    return false;
                }
                $this->_updateLogHistory('NET_SSH2_MSG_KEXDH_REPLY', 'NET_SSH2_MSG_KEXDH_GEX_GROUP');

                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('NprimeLength', $this->_string_shift($response, 4)));
                $primeBytes = $this->_string_shift($response, $primeLength);
                $prime = new BigInteger($primeBytes, -256);

                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('NgLength', $this->_string_shift($response, 4)));
                $gBytes = $this->_string_shift($response, $gLength);
                $g = new BigInteger($gBytes, -256);

                $exchange_hash_rfc4419 = pack(
                    'a*Na*Na*',
                    $dh_group_sizes_packed,
                    $primeLength,
                    $primeBytes,
                    $gLength,
                    $gBytes
                );

                $clientKexInitMessage = 'NET_SSH2_MSG_KEXDH_GEX_INIT';
                $serverKexReplyMessage = 'NET_SSH2_MSG_KEXDH_GEX_REPLY';
            } else {
                switch ($kex_algorithm) {
                    // see http://tools.ietf.org/html/rfc2409#section-6.2 and
                    // http://tools.ietf.org/html/rfc2412, appendex E
                    case 'diffie-hellman-group1-sha1':
                        $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                                '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                                '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                                'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF';
                        break;
                    // see http://tools.ietf.org/html/rfc3526#section-3
                    case 'diffie-hellman-group14-sha1':
                        $prime = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                                '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                                '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                                'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                                '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                                '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                                'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                                '3995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF';
                        break;
                }
                // For both diffie-hellman-group1-sha1 and diffie-hellman-group14-sha1
                // the generator field element is 2 (decimal) and the hash function is sha1.
                $g = new BigInteger(2);
                $prime = new BigInteger($prime, 16);
                $clientKexInitMessage = 'NET_SSH2_MSG_KEXDH_INIT';
                $serverKexReplyMessage = 'NET_SSH2_MSG_KEXDH_REPLY';
            }

            switch ($kex_algorithm) {
                case 'diffie-hellman-group-exchange-sha256':
                    $kexHash = new Hash('sha256');
                    break;
                default:
                    $kexHash = new Hash('sha1');
            }

            /* To increase the speed of the key exchange, both client and server may
            reduce the size of their private exponents.  It should be at least
            twice as long as the key material that is generated from the shared
            secret.  For more details, see the paper by van Oorschot and Wiener
            [VAN-OORSCHOT].

            -- http://tools.ietf.org/html/rfc4419#section-6.2 */
            $one = new BigInteger(1);
            $keyLength = min($kexHash->getLength(), max($encryptKeyLength, $decryptKeyLength));
            $max = $one->bitwise_leftShift(16 * $keyLength); // 2 * 8 * $keyLength
            $max = $max->subtract($one);

            $x = $one->random($one, $max);
            $e = $g->modPow($x, $prime);

            $eBytes = $e->toBytes(true);
        }
        $data = pack('CNa*', constant($clientKexInitMessage), strlen($eBytes), $eBytes);

        if (!$this->_send_binary_packet($data)) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }
        switch ($clientKexInitMessage) {
            case 'NET_SSH2_MSG_KEX_ECDH_INIT':
                $this->_updateLogHistory('NET_SSH2_MSG_KEXDH_INIT', 'NET_SSH2_MSG_KEX_ECDH_INIT');
                break;
            case 'NET_SSH2_MSG_KEXDH_GEX_INIT':
                $this->_updateLogHistory('UNKNOWN (32)', 'NET_SSH2_MSG_KEXDH_GEX_INIT');
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }
        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        if ($type != constant($serverKexReplyMessage)) {
            user_error("Expected $serverKexReplyMessage");
            return false;
        }
        switch ($serverKexReplyMessage) {
            case 'NET_SSH2_MSG_KEX_ECDH_REPLY':
                $this->_updateLogHistory('NET_SSH2_MSG_KEXDH_REPLY', 'NET_SSH2_MSG_KEX_ECDH_REPLY');
                break;
            case 'NET_SSH2_MSG_KEXDH_GEX_REPLY':
                $this->_updateLogHistory('UNKNOWN (33)', 'NET_SSH2_MSG_KEXDH_GEX_REPLY');
        }

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->server_public_host_key = $server_public_host_key = $this->_string_shift($response, $temp['length']);

        if (strlen($server_public_host_key) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
        $public_key_format = $this->_string_shift($server_public_host_key, $temp['length']);

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $fBytes = $this->_string_shift($response, $temp['length']);

        if (strlen($response) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($response, 4));
        $this->signature = $this->_string_shift($response, $temp['length']);

        if (strlen($this->signature) < 4) {
            return false;
        }
        $temp = unpack('Nlength', $this->_string_shift($this->signature, 4));
        $this->signature_format = $this->_string_shift($this->signature, $temp['length']);

        if ($kex_algorithm === 'curve25519-sha256@libssh.org') {
            if (strlen($fBytes) !== 32) {
                user_error('Received curve25519 public key of invalid length.');
                return false;
            }
            $key = new BigInteger(sodium_crypto_scalarmult($x, $fBytes), 256);
            // sodium_compat doesn't emulate sodium_memzero
            // also, with v1 of libsodium API the extension identifies itself as
            // libsodium whereas v2 of the libsodium API (what PHP 7.2+ includes)
            // identifies itself as sodium. sodium_compat uses the v1 API to
            // emulate the v2 API if it's the v1 API that's available
            if (extension_loaded('sodium') || extension_loaded('libsodium')) {
                sodium_memzero($x);
            }
        } else {
            $f = new BigInteger($fBytes, -256);
            $key = $f->modPow($x, $prime);
        }
        $keyBytes = $key->toBytes(true);

        $this->exchange_hash = pack(
            'Na*Na*Na*Na*Na*a*Na*Na*Na*',
            strlen($this->identifier),
            $this->identifier,
            strlen($this->server_identifier),
            $this->server_identifier,
            strlen($kexinit_payload_client),
            $kexinit_payload_client,
            strlen($kexinit_payload_server),
            $kexinit_payload_server,
            strlen($this->server_public_host_key),
            $this->server_public_host_key,
            $exchange_hash_rfc4419,
            strlen($eBytes),
            $eBytes,
            strlen($fBytes),
            $fBytes,
            strlen($keyBytes),
            $keyBytes
        );

        $this->exchange_hash = $kexHash->hash($this->exchange_hash);

        if ($this->session_id === false) {
            $this->session_id = $this->exchange_hash;
        }

        switch ($server_host_key_algorithm) {
            case 'ssh-dss':
                $expected_key_format = 'ssh-dss';
                break;
            //case 'rsa-sha2-256':
            //case 'rsa-sha2-512':
            //case 'ssh-rsa':
            default:
                $expected_key_format = 'ssh-rsa';
        }

        if ($public_key_format != $expected_key_format || $this->signature_format != $server_host_key_algorithm) {
            switch (true) {
                case $this->signature_format == $server_host_key_algorithm:
                case $server_host_key_algorithm != 'rsa-sha2-256' && $server_host_key_algorithm != 'rsa-sha2-512':
                case $this->signature_format != 'ssh-rsa':
                    user_error('Server Host Key Algorithm Mismatch');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            }
        }

        $packet = pack(
            'C',
            NET_SSH2_MSG_NEWKEYS
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $response = $this->_get_binary_packet();

        if ($response === false) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        if ($type != NET_SSH2_MSG_NEWKEYS) {
            user_error('Expected SSH_MSG_NEWKEYS');
            return false;
        }

        $keyBytes = pack('Na*', strlen($keyBytes), $keyBytes);

        $this->encrypt = $this->_encryption_algorithm_to_crypt_instance($encrypt);
        if ($this->encrypt) {
            if ($this->crypto_engine) {
                $this->encrypt->setPreferredEngine($this->crypto_engine);
            }
            if ($this->encrypt->block_size) {
                $this->encrypt_block_size = $this->encrypt->block_size;
            }
            $this->encrypt->enableContinuousBuffer();
            $this->encrypt->disablePadding();

            if ($this->encrypt->getBlockLength()) {
                $this->encrypt_block_size = $this->encrypt->getBlockLength() >> 3;
            }

            $iv = $kexHash->hash($keyBytes . $this->exchange_hash . 'A' . $this->session_id);
            while ($this->encrypt_block_size > strlen($iv)) {
                $iv.= $kexHash->hash($keyBytes . $this->exchange_hash . $iv);
            }
            $this->encrypt->setIV(substr($iv, 0, $this->encrypt_block_size));

            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'C' . $this->session_id);
            while ($encryptKeyLength > strlen($key)) {
                $key.= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            $this->encrypt->setKey(substr($key, 0, $encryptKeyLength));

            $this->encrypt->name = $decrypt;
        }

        $this->decrypt = $this->_encryption_algorithm_to_crypt_instance($decrypt);
        if ($this->decrypt) {
            if ($this->crypto_engine) {
                $this->decrypt->setPreferredEngine($this->crypto_engine);
            }
            if ($this->decrypt->block_size) {
                $this->decrypt_block_size = $this->decrypt->block_size;
            }
            $this->decrypt->enableContinuousBuffer();
            $this->decrypt->disablePadding();

            if ($this->decrypt->getBlockLength()) {
                $this->decrypt_block_size = $this->decrypt->getBlockLength() >> 3;
            }

            $iv = $kexHash->hash($keyBytes . $this->exchange_hash . 'B' . $this->session_id);
            while ($this->decrypt_block_size > strlen($iv)) {
                $iv.= $kexHash->hash($keyBytes . $this->exchange_hash . $iv);
            }
            $this->decrypt->setIV(substr($iv, 0, $this->decrypt_block_size));

            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'D' . $this->session_id);
            while ($decryptKeyLength > strlen($key)) {
                $key.= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            $this->decrypt->setKey(substr($key, 0, $decryptKeyLength));

            $this->decrypt->name = $decrypt;
        }

        /* The "arcfour128" algorithm is the RC4 cipher, as described in
           [SCHNEIER], using a 128-bit key.  The first 1536 bytes of keystream
           generated by the cipher MUST be discarded, and the first byte of the
           first encrypted packet MUST be encrypted using the 1537th byte of
           keystream.

           -- http://tools.ietf.org/html/rfc4345#section-4 */
        if ($encrypt == 'arcfour128' || $encrypt == 'arcfour256') {
            $this->encrypt->encrypt(str_repeat("\0", 1536));
        }
        if ($decrypt == 'arcfour128' || $decrypt == 'arcfour256') {
            $this->decrypt->decrypt(str_repeat("\0", 1536));
        }

        $mac_algorithm_out = $this->_array_intersect_first($c2s_mac_algorithms, $this->mac_algorithms_client_to_server);
        if ($mac_algorithm_out === false) {
            user_error('No compatible client to server message authentication algorithms found');
            return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
        }

        $createKeyLength = 0; // ie. $mac_algorithm == 'none'
        switch ($mac_algorithm_out) {
            case 'hmac-sha2-256':
                $this->hmac_create = new Hash('sha256');
                $createKeyLength = 32;
                break;
            case 'hmac-sha1':
                $this->hmac_create = new Hash('sha1');
                $createKeyLength = 20;
                break;
            case 'hmac-sha1-96':
                $this->hmac_create = new Hash('sha1-96');
                $createKeyLength = 20;
                break;
            case 'hmac-md5':
                $this->hmac_create = new Hash('md5');
                $createKeyLength = 16;
                break;
            case 'hmac-md5-96':
                $this->hmac_create = new Hash('md5-96');
                $createKeyLength = 16;
        }
        $this->hmac_create->name = $mac_algorithm_out;

        $checkKeyLength = 0;
        $this->hmac_size = 0;
        switch ($mac_algorithm_in) {
            case 'hmac-sha2-256':
                $this->hmac_check = new Hash('sha256');
                $checkKeyLength = 32;
                $this->hmac_size = 32;
                break;
            case 'hmac-sha1':
                $this->hmac_check = new Hash('sha1');
                $checkKeyLength = 20;
                $this->hmac_size = 20;
                break;
            case 'hmac-sha1-96':
                $this->hmac_check = new Hash('sha1-96');
                $checkKeyLength = 20;
                $this->hmac_size = 12;
                break;
            case 'hmac-md5':
                $this->hmac_check = new Hash('md5');
                $checkKeyLength = 16;
                $this->hmac_size = 16;
                break;
            case 'hmac-md5-96':
                $this->hmac_check = new Hash('md5-96');
                $checkKeyLength = 16;
                $this->hmac_size = 12;
        }
        $this->hmac_check->name = $mac_algorithm_in;

        $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'E' . $this->session_id);
        while ($createKeyLength > strlen($key)) {
            $key.= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
        }
        $this->hmac_create->setKey(substr($key, 0, $createKeyLength));

        $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'F' . $this->session_id);
        while ($checkKeyLength > strlen($key)) {
            $key.= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
        }
        $this->hmac_check->setKey(substr($key, 0, $checkKeyLength));

        return true;
    }

    /**
     * Maps an encryption algorithm name to the number of key bytes.
     *
     * @param string $algorithm Name of the encryption algorithm
     * @return int|null Number of bytes as an integer or null for unknown
     * @access private
     */
    function _encryption_algorithm_to_key_size($algorithm)
    {
        if ($this->bad_key_size_fix && $this->_bad_algorithm_candidate($algorithm)) {
            return 16;
        }

        switch ($algorithm) {
            case 'none':
                return 0;
            case 'aes128-cbc':
            case 'aes128-ctr':
            case 'arcfour':
            case 'arcfour128':
            case 'blowfish-cbc':
            case 'blowfish-ctr':
            case 'twofish128-cbc':
            case 'twofish128-ctr':
                return 16;
            case '3des-cbc':
            case '3des-ctr':
            case 'aes192-cbc':
            case 'aes192-ctr':
            case 'twofish192-cbc':
            case 'twofish192-ctr':
                return 24;
            case 'aes256-cbc':
            case 'aes256-ctr':
            case 'arcfour256':
            case 'twofish-cbc':
            case 'twofish256-cbc':
            case 'twofish256-ctr':
                return 32;
        }
        return null;
    }

    /**
     * Maps an encryption algorithm name to an instance of a subclass of
     * \phpseclib\Crypt\Base.
     *
     * @param string $algorithm Name of the encryption algorithm
     * @return mixed Instance of \phpseclib\Crypt\Base or null for unknown
     * @access private
     */
    function _encryption_algorithm_to_crypt_instance($algorithm)
    {
        switch ($algorithm) {
            case '3des-cbc':
                return new TripleDES();
            case '3des-ctr':
                return new TripleDES(Base::MODE_CTR);
            case 'aes256-cbc':
            case 'aes192-cbc':
            case 'aes128-cbc':
                return new Rijndael();
            case 'aes256-ctr':
            case 'aes192-ctr':
            case 'aes128-ctr':
                return new Rijndael(Base::MODE_CTR);
            case 'blowfish-cbc':
                return new Blowfish();
            case 'blowfish-ctr':
                return new Blowfish(Base::MODE_CTR);
            case 'twofish128-cbc':
            case 'twofish192-cbc':
            case 'twofish256-cbc':
            case 'twofish-cbc':
                return new Twofish();
            case 'twofish128-ctr':
            case 'twofish192-ctr':
            case 'twofish256-ctr':
                return new Twofish(Base::MODE_CTR);
            case 'arcfour':
            case 'arcfour128':
            case 'arcfour256':
                return new RC4();
        }
        return null;
    }

    /**
     * Tests whether or not proposed algorithm has a potential for issues
     *
     * @link https://www.chiark.greenend.org.uk/~sgtatham/putty/wishlist/ssh2-aesctr-openssh.html
     * @link https://bugzilla.mindrot.org/show_bug.cgi?id=1291
     * @param string $algorithm Name of the encryption algorithm
     * @return bool
     * @access private
     */
    function _bad_algorithm_candidate($algorithm)
    {
        switch ($algorithm) {
            case 'arcfour256':
            case 'aes192-ctr':
            case 'aes256-ctr':
                return true;
        }

        return false;
    }

    /**
     * Login
     *
     * The $password parameter can be a plaintext password, a \phpseclib\Crypt\RSA object or an array
     *
     * @param string $username
     * @return bool
     * @see self::_login()
     * @access public
     */
    function login($username)
    {
        $args = func_get_args();
        $this->auth[] = $args;

        // try logging with 'none' as an authentication method first since that's what
        // PuTTY does
        if (substr($this->server_identifier, 0, 13) != 'SSH-2.0-CoreFTP' && $this->auth_methods_to_continue === null) {
            if ($this->_login($username)) {
                return true;
            }
            if (count($args) == 1) {
                return false;
            }
        }
        return call_user_func_array(array(&$this, '_login'), $args);
    }

    /**
     * Login Helper
     *
     * @param string $username
     * @return bool
     * @see self::_login_helper()
     * @access private
     */
    function _login($username)
    {
        if (!($this->bitmap & self::MASK_CONSTRUCTOR)) {
            if (!$this->_connect()) {
                return false;
            }
        }

        $args = array_slice(func_get_args(), 1);
        if (empty($args)) {
            return $this->_login_helper($username);
        }

        foreach ($args as $arg) {
            if ($this->_login_helper($username, $arg)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Login Helper
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @access private
     * @internal It might be worthwhile, at some point, to protect against {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
     *           by sending dummy SSH_MSG_IGNORE messages.
     */
    function _login_helper($username, $password = null)
    {
        if (!($this->bitmap & self::MASK_CONNECTED)) {
            return false;
        }

        if (!($this->bitmap & self::MASK_LOGIN_REQ)) {
            $packet = pack(
                'CNa*',
                NET_SSH2_MSG_SERVICE_REQUEST,
                strlen('ssh-userauth'),
                'ssh-userauth'
            );

            if (!$this->_send_binary_packet($packet)) {
                return false;
            }

            $response = $this->_get_binary_packet();
            if ($response === false) {
                if ($this->retry_connect) {
                    $this->retry_connect = false;
                    if (!$this->_connect()) {
                        return false;
                    }
                    return $this->_login_helper($username, $password);
                }
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }

            if (strlen($response) < 4) {
                return false;
            }
            extract(unpack('Ctype', $this->_string_shift($response, 1)));

            if ($type != NET_SSH2_MSG_SERVICE_ACCEPT) {
                user_error('Expected SSH_MSG_SERVICE_ACCEPT');
                return false;
            }
            $this->bitmap |= self::MASK_LOGIN_REQ;
        }

        if (strlen($this->last_interactive_response)) {
            return !is_string($password) && !is_array($password) ? false : $this->_keyboard_interactive_process($password);
        }

        if ($password instanceof RSA) {
            return $this->_privatekey_login($username, $password);
        } elseif ($password instanceof Agent) {
            return $this->_ssh_agent_login($username, $password);
        }

        if (is_array($password)) {
            if ($this->_keyboard_interactive_login($username, $password)) {
                $this->bitmap |= self::MASK_LOGIN;
                return true;
            }
            return false;
        }

        if (!isset($password)) {
            $packet = pack(
                'CNa*Na*Na*',
                NET_SSH2_MSG_USERAUTH_REQUEST,
                strlen($username),
                $username,
                strlen('ssh-connection'),
                'ssh-connection',
                strlen('none'),
                'none'
            );

            if (!$this->_send_binary_packet($packet)) {
                return false;
            }

            $response = $this->_get_binary_packet();
            if ($response === false) {
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }

            if (!strlen($response)) {
                return false;
            }
            extract(unpack('Ctype', $this->_string_shift($response, 1)));

            switch ($type) {
                case NET_SSH2_MSG_USERAUTH_SUCCESS:
                    $this->bitmap |= self::MASK_LOGIN;
                    return true;
                case NET_SSH2_MSG_USERAUTH_FAILURE:
                    extract(unpack('Nmethodlistlen', $this->_string_shift($response, 4)));
                    $this->auth_methods_to_continue = explode(',', $this->_string_shift($response, $methodlistlen));
                default:
                    return false;
            }
        }

        $packet = pack(
            'CNa*Na*Na*CNa*',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            strlen($username),
            $username,
            strlen('ssh-connection'),
            'ssh-connection',
            strlen('password'),
            'password',
            0,
            strlen($password),
            $password
        );

        // remove the username and password from the logged packet
        if (!defined('NET_SSH2_LOGGING')) {
            $logged = null;
        } else {
            $logged = pack(
                'CNa*Na*Na*CNa*',
                NET_SSH2_MSG_USERAUTH_REQUEST,
                strlen('username'),
                'username',
                strlen('ssh-connection'),
                'ssh-connection',
                strlen('password'),
                'password',
                0,
                strlen('password'),
                'password'
            );
        }

        if (!$this->_send_binary_packet($packet, $logged)) {
            return false;
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ: // in theory, the password can be changed
                $this->_updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ');
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->errors[] = 'SSH_MSG_USERAUTH_PASSWD_CHANGEREQ: ' . $this->_string_shift($response, $length);
                return $this->_disconnect(NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER);
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                // can we use keyboard-interactive authentication?  if not then either the login is bad or the server employees
                // multi-factor authentication
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $auth_methods = explode(',', $this->_string_shift($response, $length));
                $this->auth_methods_to_continue = $auth_methods;
                if (!strlen($response)) {
                    return false;
                }
                extract(unpack('Cpartial_success', $this->_string_shift($response, 1)));
                $partial_success = $partial_success != 0;

                if (!$partial_success && in_array('keyboard-interactive', $auth_methods)) {
                    if ($this->_keyboard_interactive_login($username, $password)) {
                        $this->bitmap |= self::MASK_LOGIN;
                        return true;
                    }
                    return false;
                }
                return false;
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                $this->bitmap |= self::MASK_LOGIN;
                return true;
        }

        return false;
    }

    /**
     * Login via keyboard-interactive authentication
     *
     * See {@link http://tools.ietf.org/html/rfc4256 RFC4256} for details.  This is not a full-featured keyboard-interactive authenticator.
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @access private
     */
    function _keyboard_interactive_login($username, $password)
    {
        $packet = pack(
            'CNa*Na*Na*Na*Na*',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            strlen($username),
            $username,
            strlen('ssh-connection'),
            'ssh-connection',
            strlen('keyboard-interactive'),
            'keyboard-interactive',
            0,
            '',
            0,
            ''
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        return $this->_keyboard_interactive_process($password);
    }

    /**
     * Handle the keyboard-interactive requests / responses.
     *
     * @return bool
     * @access private
     */
    function _keyboard_interactive_process()
    {
        $responses = func_get_args();

        if (strlen($this->last_interactive_response)) {
            $response = $this->last_interactive_response;
        } else {
            $orig = $response = $this->_get_binary_packet();
            if ($response === false) {
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }
        }

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_INFO_REQUEST:
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->_string_shift($response, $length); // name; may be empty
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->_string_shift($response, $length); // instruction; may be empty
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->_string_shift($response, $length); // language tag; may be empty
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nnum_prompts', $this->_string_shift($response, 4)));

                for ($i = 0; $i < count($responses); $i++) {
                    if (is_array($responses[$i])) {
                        foreach ($responses[$i] as $key => $value) {
                            $this->keyboard_requests_responses[$key] = $value;
                        }
                        unset($responses[$i]);
                    }
                }
                $responses = array_values($responses);

                if (isset($this->keyboard_requests_responses)) {
                    for ($i = 0; $i < $num_prompts; $i++) {
                        if (strlen($response) < 4) {
                            return false;
                        }
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        // prompt - ie. "Password: "; must not be empty
                        $prompt = $this->_string_shift($response, $length);
                        //$echo = $this->_string_shift($response) != chr(0);
                        foreach ($this->keyboard_requests_responses as $key => $value) {
                            if (substr($prompt, 0, strlen($key)) == $key) {
                                $responses[] = $value;
                                break;
                            }
                        }
                    }
                }

                // see http://tools.ietf.org/html/rfc4256#section-3.2
                if (strlen($this->last_interactive_response)) {
                    $this->last_interactive_response = '';
                } else {
                    $this->_updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_INFO_REQUEST');
                }

                if (!count($responses) && $num_prompts) {
                    $this->last_interactive_response = $orig;
                    return false;
                }

                /*
                   After obtaining the requested information from the user, the client
                   MUST respond with an SSH_MSG_USERAUTH_INFO_RESPONSE message.
                */
                // see http://tools.ietf.org/html/rfc4256#section-3.4
                $packet = $logged = pack('CN', NET_SSH2_MSG_USERAUTH_INFO_RESPONSE, count($responses));
                for ($i = 0; $i < count($responses); $i++) {
                    $packet.= pack('Na*', strlen($responses[$i]), $responses[$i]);
                    $logged.= pack('Na*', strlen('dummy-answer'), 'dummy-answer');
                }

                if (!$this->_send_binary_packet($packet, $logged)) {
                    return false;
                }

                $this->_updateLogHistory('UNKNOWN (61)', 'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE');

                /*
                   After receiving the response, the server MUST send either an
                   SSH_MSG_USERAUTH_SUCCESS, SSH_MSG_USERAUTH_FAILURE, or another
                   SSH_MSG_USERAUTH_INFO_REQUEST message.
                */
                // maybe phpseclib should force close the connection after x request / responses?  unless something like that is done
                // there could be an infinite loop of request / responses.
                return $this->_keyboard_interactive_process();
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                return true;
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                extract(unpack('Nmethodlistlen', $this->_string_shift($response, 4)));
                $this->auth_methods_to_continue = explode(',', $this->_string_shift($response, $methodlistlen));
                return false;
        }

        return false;
    }

    /**
     * Login with an ssh-agent provided key
     *
     * @param string $username
     * @param \phpseclib\System\SSH\Agent $agent
     * @return bool
     * @access private
     */
    function _ssh_agent_login($username, $agent)
    {
        $this->agent = $agent;
        $keys = $agent->requestIdentities();
        foreach ($keys as $key) {
            if ($this->_privatekey_login($username, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Login with an RSA private key
     *
     * @param string $username
     * @param \phpseclib\Crypt\RSA $privatekey
     * @return bool
     * @access private
     * @internal It might be worthwhile, at some point, to protect against {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
     *           by sending dummy SSH_MSG_IGNORE messages.
     */
    function _privatekey_login($username, $privatekey)
    {
        // see http://tools.ietf.org/html/rfc4253#page-15
        $publickey = $privatekey->getPublicKey(RSA::PUBLIC_FORMAT_RAW);
        if ($publickey === false) {
            return false;
        }

        $publickey = array(
            'e' => $publickey['e']->toBytes(true),
            'n' => $publickey['n']->toBytes(true)
        );
        $publickey = pack(
            'Na*Na*Na*',
            strlen('ssh-rsa'),
            'ssh-rsa',
            strlen($publickey['e']),
            $publickey['e'],
            strlen($publickey['n']),
            $publickey['n']
        );

        switch ($this->signature_format) {
            case 'rsa-sha2-512':
                $hash = 'sha512';
                $signatureType = 'rsa-sha2-512';
                break;
            case 'rsa-sha2-256':
                $hash = 'sha256';
                $signatureType = 'rsa-sha2-256';
                break;
            //case 'ssh-rsa':
            default:
                $hash = 'sha1';
                $signatureType = 'ssh-rsa';
        }

        $part1 = pack(
            'CNa*Na*Na*',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            strlen($username),
            $username,
            strlen('ssh-connection'),
            'ssh-connection',
            strlen('publickey'),
            'publickey'
        );
        $part2 = pack('Na*Na*', strlen($signatureType), $signatureType, strlen($publickey), $publickey);

        $packet = $part1 . chr(0) . $part2;
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                if (strlen($response) < 4) {
                    return false;
                }
                extract(unpack('Nmethodlistlen', $this->_string_shift($response, 4)));
                $this->auth_methods_to_continue = explode(',', $this->_string_shift($response, $methodlistlen));
                $this->errors[] = 'SSH_MSG_USERAUTH_FAILURE';
                return false;
            case NET_SSH2_MSG_USERAUTH_PK_OK:
                // we'll just take it on faith that the public key blob and the public key algorithm name are as
                // they should be
                $this->_updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_PK_OK');
                break;
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                $this->bitmap |= self::MASK_LOGIN;
                return true;
            default:
                user_error('Unexpected response to publickey authentication pt 1');
                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
        }

        $packet = $part1 . chr(1) . $part2;
        $privatekey->setSignatureMode(RSA::SIGNATURE_PKCS1);
        $privatekey->setHash($hash);
        $signature = $privatekey->sign(pack('Na*a*', strlen($this->session_id), $this->session_id, $packet));
        $signature = pack('Na*Na*', strlen($signatureType), $signatureType, strlen($signature), $signature);
        $packet.= pack('Na*', strlen($signature), $signature);

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            $this->bitmap = 0;
            user_error('Connection closed by server');
            return false;
        }

        if (!strlen($response)) {
            return false;
        }
        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                // either the login is bad or the server employs multi-factor authentication
                extract(unpack('Nmethodlistlen', $this->_string_shift($response, 4)));
                $this->auth_methods_to_continue = explode(',', $this->_string_shift($response, $methodlistlen));
                return false;
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                $this->bitmap |= self::MASK_LOGIN;
                return true;
        }

        user_error('Unexpected response to publickey authentication pt 2');
        return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
    }

    /**
     * Set Timeout
     *
     * $ssh->exec('ping 127.0.0.1'); on a Linux host will never return and will run indefinitely.  setTimeout() makes it so it'll timeout.
     * Setting $timeout to false or 0 will mean there is no timeout.
     *
     * @param mixed $timeout
     * @access public
     */
    function setTimeout($timeout)
    {
        $this->timeout = $this->curTimeout = $timeout;
    }

    /**
     * Set Keep Alive
     *
     * Sends an SSH2_MSG_IGNORE message every x seconds, if x is a positive non-zero number.
     *
     * @param int $interval
     * @access public
     */
    function setKeepAlive($interval)
    {
        $this->keepAlive = $interval;
    }

    /**
     * Get the output from stdError
     *
     * @access public
     */
    function getStdError()
    {
        return $this->stdErrorLog;
    }

    /**
     * Execute Command
     *
     * If $callback is set to false then \phpseclib\Net\SSH2::_get_channel_packet(self::CHANNEL_EXEC) will need to be called manually.
     * In all likelihood, this is not a feature you want to be taking advantage of.
     *
     * @param string $command
     * @param Callback $callback
     * @return string
     * @access public
     */
    function exec($command, $callback = null)
    {
        $this->curTimeout = $this->timeout;
        $this->is_timeout = false;
        $this->stdErrorLog = '';

        if (!$this->isAuthenticated()) {
            return false;
        }

        if ($this->in_request_pty_exec) {
            user_error('If you want to run multiple exec()\'s you will need to disable (and re-enable if appropriate) a PTY for each one.');
            return false;
        }

        // RFC4254 defines the (client) window size as "bytes the other party can send before it must wait for the window to
        // be adjusted".  0x7FFFFFFF is, at 2GB, the max size.  technically, it should probably be decremented, but,
        // honestly, if you're transferring more than 2GB, you probably shouldn't be using phpseclib, anyway.
        // see http://tools.ietf.org/html/rfc4254#section-5.2 for more info
        $this->window_size_server_to_client[self::CHANNEL_EXEC] = $this->window_size;
        // 0x8000 is the maximum max packet size, per http://tools.ietf.org/html/rfc4253#section-6.1, although since PuTTy
        // uses 0x4000, that's what will be used here, as well.
        $packet_size = 0x4000;

        $packet = pack(
            'CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN,
            strlen('session'),
            'session',
            self::CHANNEL_EXEC,
            $this->window_size_server_to_client[self::CHANNEL_EXEC],
            $packet_size
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_OPEN;

        $response = $this->_get_channel_packet(self::CHANNEL_EXEC);
        if ($response === false) {
            return false;
        }

        if ($this->request_pty === true) {
            $terminal_modes = pack('C', NET_SSH2_TTY_OP_END);
            $packet = pack(
                'CNNa*CNa*N5a*',
                NET_SSH2_MSG_CHANNEL_REQUEST,
                $this->server_channels[self::CHANNEL_EXEC],
                strlen('pty-req'),
                'pty-req',
                1,
                strlen('vt100'),
                'vt100',
                $this->windowColumns,
                $this->windowRows,
                0,
                0,
                strlen($terminal_modes),
                $terminal_modes
            );

            if (!$this->_send_binary_packet($packet)) {
                return false;
            }

            $response = $this->_get_binary_packet();
            if ($response === false) {
                $this->bitmap = 0;
                user_error('Connection closed by server');
                return false;
            }

            if (!strlen($response)) {
                return false;
            }
            list(, $type) = unpack('C', $this->_string_shift($response, 1));

            switch ($type) {
                case NET_SSH2_MSG_CHANNEL_SUCCESS:
                    break;
                case NET_SSH2_MSG_CHANNEL_FAILURE:
                default:
                    user_error('Unable to request pseudo-terminal');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
            }
            $this->in_request_pty_exec = true;
        }

        // sending a pty-req SSH_MSG_CHANNEL_REQUEST message is unnecessary and, in fact, in most cases, slows things
        // down.  the one place where it might be desirable is if you're doing something like \phpseclib\Net\SSH2::exec('ping localhost &').
        // with a pty-req SSH_MSG_CHANNEL_REQUEST, exec() will return immediately and the ping process will then
        // then immediately terminate.  without such a request exec() will loop indefinitely.  the ping process won't end but
        // neither will your script.

        // although, in theory, the size of SSH_MSG_CHANNEL_REQUEST could exceed the maximum packet size established by
        // SSH_MSG_CHANNEL_OPEN_CONFIRMATION, RFC4254#section-5.1 states that the "maximum packet size" refers to the
        // "maximum size of an individual data packet". ie. SSH_MSG_CHANNEL_DATA.  RFC4254#section-5.2 corroborates.
        $packet = pack(
            'CNNa*CNa*',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_EXEC],
            strlen('exec'),
            'exec',
            1,
            strlen($command),
            $command
        );
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_REQUEST;

        $response = $this->_get_channel_packet(self::CHANNEL_EXEC);
        if ($response === false) {
            return false;
        }

        $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_DATA;

        if ($callback === false || $this->in_request_pty_exec) {
            return true;
        }

        $output = '';
        while (true) {
            $temp = $this->_get_channel_packet(self::CHANNEL_EXEC);
            switch (true) {
                case $temp === true:
                    return is_callable($callback) ? true : $output;
                case $temp === false:
                    return false;
                default:
                    if (is_callable($callback)) {
                        if (call_user_func($callback, $temp) === true) {
                            $this->_close_channel(self::CHANNEL_EXEC);
                            return true;
                        }
                    } else {
                        $output.= $temp;
                    }
            }
        }
    }

    /**
     * Creates an interactive shell
     *
     * @see self::read()
     * @see self::write()
     * @return bool
     * @access private
     */
    function _initShell()
    {
        if ($this->in_request_pty_exec === true) {
            return true;
        }

        $this->window_size_server_to_client[self::CHANNEL_SHELL] = $this->window_size;
        $packet_size = 0x4000;

        $packet = pack(
            'CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN,
            strlen('session'),
            'session',
            self::CHANNEL_SHELL,
            $this->window_size_server_to_client[self::CHANNEL_SHELL],
            $packet_size
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SHELL] = NET_SSH2_MSG_CHANNEL_OPEN;

        $response = $this->_get_channel_packet(self::CHANNEL_SHELL);
        if ($response === false) {
            return false;
        }

        $terminal_modes = pack('C', NET_SSH2_TTY_OP_END);
        $packet = pack(
            'CNNa*CNa*N5a*',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SHELL],
            strlen('pty-req'),
            'pty-req',
            1,
            strlen('vt100'),
            'vt100',
            $this->windowColumns,
            $this->windowRows,
            0,
            0,
            strlen($terminal_modes),
            $terminal_modes
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $packet = pack(
            'CNNa*C',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SHELL],
            strlen('shell'),
            'shell',
            1
        );
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SHELL] = NET_SSH2_MSG_IGNORE;

        $this->bitmap |= self::MASK_SHELL;

        return true;
    }

    /**
     * Return the channel to be used with read() / write()
     *
     * @see self::read()
     * @see self::write()
     * @return int
     * @access public
     */
    function _get_interactive_channel()
    {
        switch (true) {
            case $this->in_subsystem:
                return self::CHANNEL_SUBSYSTEM;
            case $this->in_request_pty_exec:
                return self::CHANNEL_EXEC;
            default:
                return self::CHANNEL_SHELL;
        }
    }

    /**
     * Return an available open channel
     *
     * @return int
     * @access public
     */
    function _get_open_channel()
    {
        $channel = self::CHANNEL_EXEC;
        do {
            if (isset($this->channel_status[$channel]) && $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_OPEN) {
                return $channel;
            }
        } while ($channel++ < self::CHANNEL_SUBSYSTEM);

        return false;
    }

    /**
     * Returns the output of an interactive shell
     *
     * Returns when there's a match for $expect, which can take the form of a string literal or,
     * if $mode == self::READ_REGEX, a regular expression.
     *
     * @see self::write()
     * @param string $expect
     * @param int $mode
     * @return string|bool
     * @access public
     */
    function read($expect = '', $mode = self::READ_SIMPLE)
    {
        $this->curTimeout = $this->timeout;
        $this->is_timeout = false;

        if (!$this->isAuthenticated()) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        if (!($this->bitmap & self::MASK_SHELL) && !$this->_initShell()) {
            user_error('Unable to initiate an interactive shell session');
            return false;
        }

        $channel = $this->_get_interactive_channel();

        if ($mode == self::READ_NEXT) {
            return $this->_get_channel_packet($channel);
        }

        $match = $expect;
        while (true) {
            if ($mode == self::READ_REGEX) {
                preg_match($expect, substr($this->interactiveBuffer, -1024), $matches);
                $match = isset($matches[0]) ? $matches[0] : '';
            }
            $pos = strlen($match) ? strpos($this->interactiveBuffer, $match) : false;
            if ($pos !== false) {
                return $this->_string_shift($this->interactiveBuffer, $pos + strlen($match));
            }
            $response = $this->_get_channel_packet($channel);
            if (is_bool($response)) {
                $this->in_request_pty_exec = false;
                return $response ? $this->_string_shift($this->interactiveBuffer, strlen($this->interactiveBuffer)) : false;
            }

            $this->interactiveBuffer.= $response;
        }
    }

    /**
     * Inputs a command into an interactive shell.
     *
     * @see self::read()
     * @param string $cmd
     * @return bool
     * @access public
     */
    function write($cmd)
    {
        if (!$this->isAuthenticated()) {
            user_error('Operation disallowed prior to login()');
            return false;
        }

        if (!($this->bitmap & self::MASK_SHELL) && !$this->_initShell()) {
            user_error('Unable to initiate an interactive shell session');
            return false;
        }

        return $this->_send_channel_packet($this->_get_interactive_channel(), $cmd);
    }

    /**
     * Start a subsystem.
     *
     * Right now only one subsystem at a time is supported. To support multiple subsystem's stopSubsystem() could accept
     * a string that contained the name of the subsystem, but at that point, only one subsystem of each type could be opened.
     * To support multiple subsystem's of the same name maybe it'd be best if startSubsystem() generated a new channel id and
     * returns that and then that that was passed into stopSubsystem() but that'll be saved for a future date and implemented
     * if there's sufficient demand for such a feature.
     *
     * @see self::stopSubsystem()
     * @param string $subsystem
     * @return bool
     * @access public
     */
    function startSubsystem($subsystem)
    {
        $this->window_size_server_to_client[self::CHANNEL_SUBSYSTEM] = $this->window_size;

        $packet = pack(
            'CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN,
            strlen('session'),
            'session',
            self::CHANNEL_SUBSYSTEM,
            $this->window_size,
            0x4000
        );

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SUBSYSTEM] = NET_SSH2_MSG_CHANNEL_OPEN;

        $response = $this->_get_channel_packet(self::CHANNEL_SUBSYSTEM);
        if ($response === false) {
            return false;
        }

        $packet = pack(
            'CNNa*CNa*',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SUBSYSTEM],
            strlen('subsystem'),
            'subsystem',
            1,
            strlen($subsystem),
            $subsystem
        );
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SUBSYSTEM] = NET_SSH2_MSG_CHANNEL_REQUEST;

        $response = $this->_get_channel_packet(self::CHANNEL_SUBSYSTEM);

        if ($response === false) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SUBSYSTEM] = NET_SSH2_MSG_CHANNEL_DATA;

        $this->bitmap |= self::MASK_SHELL;
        $this->in_subsystem = true;

        return true;
    }

    /**
     * Stops a subsystem.
     *
     * @see self::startSubsystem()
     * @return bool
     * @access public
     */
    function stopSubsystem()
    {
        $this->in_subsystem = false;
        $this->_close_channel(self::CHANNEL_SUBSYSTEM);
        return true;
    }

    /**
     * Closes a channel
     *
     * If read() timed out you might want to just close the channel and have it auto-restart on the next read() call
     *
     * @access public
     */
    function reset()
    {
        $this->_close_channel($this->_get_interactive_channel());
    }

    /**
     * Is timeout?
     *
     * Did exec() or read() return because they timed out or because they encountered the end?
     *
     * @access public
     */
    function isTimeout()
    {
        return $this->is_timeout;
    }

    /**
     * Disconnect
     *
     * @access public
     */
    function disconnect()
    {
        $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
        if (isset($this->realtime_log_file) && is_resource($this->realtime_log_file)) {
            fclose($this->realtime_log_file);
        }
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
        $this->disconnect();
    }

    /**
     * Is the connection still active?
     *
     * @return bool
     * @access public
     */
    function isConnected()
    {
        return (bool) ($this->bitmap & self::MASK_CONNECTED);
    }

    /**
     * Have you successfully been logged in?
     *
     * @return bool
     * @access public
     */
    function isAuthenticated()
    {
        return (bool) ($this->bitmap & self::MASK_LOGIN);
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * Inspired by http://php.net/manual/en/mysqli.ping.php
     *
     * @return bool
     * @access public
     */
    function ping()
    {
        if (!$this->isAuthenticated()) {
            if (!empty($this->auth)) {
                return $this->_reconnect();
            }
            return false;
        }

        $this->window_size_server_to_client[self::CHANNEL_KEEP_ALIVE] = $this->window_size;
        $packet_size = 0x4000;
        $packet = pack(
            'CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN,
            strlen('session'),
            'session',
            self::CHANNEL_KEEP_ALIVE,
            $this->window_size_server_to_client[self::CHANNEL_KEEP_ALIVE],
            $packet_size
        );

        if (!@$this->_send_binary_packet($packet)) {
            return $this->_reconnect();
        }

        $this->channel_status[self::CHANNEL_KEEP_ALIVE] = NET_SSH2_MSG_CHANNEL_OPEN;

        $response = @$this->_get_channel_packet(self::CHANNEL_KEEP_ALIVE);
        if ($response !== false) {
            $this->_close_channel(self::CHANNEL_KEEP_ALIVE);
            return true;
        }

        return $this->_reconnect();
    }

    /**
     * In situ reconnect method
     *
     * @return boolean
     * @access private
     */
    function _reconnect()
    {
        $this->_reset_connection(NET_SSH2_DISCONNECT_CONNECTION_LOST);
        $this->retry_connect = true;
        if (!$this->_connect()) {
            return false;
        }
        foreach ($this->auth as $auth) {
            $result = call_user_func_array(array(&$this, 'login'), $auth);
        }
        return $result;
    }

    /**
     * Resets a connection for re-use
     *
     * @param int $reason
     * @access private
     */
    function _reset_connection($reason)
    {
        $this->_disconnect($reason);
        $this->decrypt = $this->encrypt = false;
        $this->decrypt_block_size = $this->encrypt_block_size = 8;
        $this->hmac_check = $this->hmac_create = false;
        $this->hmac_size = false;
        $this->session_id = false;
        $this->retry_connect = true;
        $this->get_seq_no = $this->send_seq_no = 0;
    }

    /**
     * Gets Binary Packets
     *
     * See '6. Binary Packet Protocol' of rfc4253 for more info.
     *
     * @see self::_send_binary_packet()
     * @return string
     * @access private
     */
    function _get_binary_packet($skip_channel_filter = false)
    {
        if ($skip_channel_filter) {
            $read = array($this->fsock);
            $write = $except = null;

            if (!$this->curTimeout) {
                if ($this->keepAlive <= 0) {
                    @stream_select($read, $write, $except, null);
                } else {
                    if (!@stream_select($read, $write, $except, $this->keepAlive) && !count($read)) {
                        $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_IGNORE, 0));
                        return $this->_get_binary_packet(true);
                    }
                }
            } else {
                if ($this->curTimeout < 0) {
                    $this->is_timeout = true;
                    return true;
                }

                $read = array($this->fsock);
                $write = $except = null;

                $start = microtime(true);

                if ($this->keepAlive > 0 && $this->keepAlive < $this->curTimeout) {
                    if (!@stream_select($read, $write, $except, $this->keepAlive) && !count($read)) {
                        $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_IGNORE, 0));
                        $elapsed = microtime(true) - $start;
                        $this->curTimeout-= $elapsed;
                        return $this->_get_binary_packet(true);
                    }
                    $elapsed = microtime(true) - $start;
                    $this->curTimeout-= $elapsed;
                }

                $sec = floor($this->curTimeout);
                $usec = 1000000 * ($this->curTimeout - $sec);

                // on windows this returns a "Warning: Invalid CRT parameters detected" error
                if (!@stream_select($read, $write, $except, $sec, $usec) && !count($read)) {
                    $this->is_timeout = true;
                    return true;
                }
                $elapsed = microtime(true) - $start;
                $this->curTimeout-= $elapsed;
            }
        }

        if (!is_resource($this->fsock) || feof($this->fsock)) {
            $this->bitmap = 0;
            user_error('Connection closed prematurely');
            return false;
        }

        $start = microtime(true);
        $raw = stream_get_contents($this->fsock, $this->decrypt_block_size);

        if (!strlen($raw)) {
            return '';
        }

        if ($this->decrypt !== false) {
            $raw = $this->decrypt->decrypt($raw);
        }
        if ($raw === false) {
            user_error('Unable to decrypt content');
            return false;
        }

        if (strlen($raw) < 5) {
            return false;
        }
        extract(unpack('Npacket_length/Cpadding_length', $this->_string_shift($raw, 5)));

        $remaining_length = $packet_length + 4 - $this->decrypt_block_size;

        // quoting <http://tools.ietf.org/html/rfc4253#section-6.1>,
        // "implementations SHOULD check that the packet length is reasonable"
        // PuTTY uses 0x9000 as the actual max packet size and so to shall we
        if ($remaining_length < -$this->decrypt_block_size || $remaining_length > 0x9000 || $remaining_length % $this->decrypt_block_size != 0) {
            if (!$this->bad_key_size_fix && $this->_bad_algorithm_candidate($this->decrypt->name) && !($this->bitmap & SSH2::MASK_LOGIN)) {
                $this->bad_key_size_fix = true;
                $this->_reset_connection(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
                return false;
            }
            user_error('Invalid size');
            return false;
        }

        $buffer = '';
        while ($remaining_length > 0) {
            $temp = stream_get_contents($this->fsock, $remaining_length);
            if ($temp === false || feof($this->fsock)) {
                $this->bitmap = 0;
                user_error('Error reading from socket');
                return false;
            }
            $buffer.= $temp;
            $remaining_length-= strlen($temp);
        }

        $stop = microtime(true);
        if (strlen($buffer)) {
            $raw.= $this->decrypt !== false ? $this->decrypt->decrypt($buffer) : $buffer;
        }

        $payload = $this->_string_shift($raw, $packet_length - $padding_length - 1);
        $padding = $this->_string_shift($raw, $padding_length); // should leave $raw empty

        if ($this->hmac_check !== false) {
            $hmac = stream_get_contents($this->fsock, $this->hmac_size);
            if ($hmac === false || strlen($hmac) != $this->hmac_size) {
                $this->bitmap = 0;
                user_error('Error reading socket');
                return false;
            } elseif ($hmac != $this->hmac_check->hash(pack('NNCa*', $this->get_seq_no, $packet_length, $padding_length, $payload . $padding))) {
                user_error('Invalid HMAC');
                return false;
            }
        }

        //if ($this->decompress) {
        //    $payload = gzinflate(substr($payload, 2));
        //}

        $this->get_seq_no++;

        if (defined('NET_SSH2_LOGGING')) {
            $current = microtime(true);
            $message_number = isset($this->message_numbers[ord($payload[0])]) ? $this->message_numbers[ord($payload[0])] : 'UNKNOWN (' . ord($payload[0]) . ')';
            $message_number = '<- ' . $message_number .
                              ' (since last: ' . round($current - $this->last_packet, 4) . ', network: ' . round($stop - $start, 4) . 's)';
            $this->_append_log($message_number, $payload);
            $this->last_packet = $current;
        }

        return $this->_filter($payload, $skip_channel_filter);
    }

    /**
     * Filter Binary Packets
     *
     * Because some binary packets need to be ignored...
     *
     * @see self::_get_binary_packet()
     * @return string
     * @access private
     */
    function _filter($payload, $skip_channel_filter)
    {
        switch (ord($payload[0])) {
            case NET_SSH2_MSG_DISCONNECT:
                $this->_string_shift($payload, 1);
                if (strlen($payload) < 8) {
                    return false;
                }
                extract(unpack('Nreason_code/Nlength', $this->_string_shift($payload, 8)));
                $this->errors[] = 'SSH_MSG_DISCONNECT: ' . $this->disconnect_reasons[$reason_code] . "\r\n" . $this->_string_shift($payload, $length);
                $this->bitmap = 0;
                return false;
            case NET_SSH2_MSG_IGNORE:
                $payload = $this->_get_binary_packet($skip_channel_filter);
                break;
            case NET_SSH2_MSG_DEBUG:
                $this->_string_shift($payload, 2);
                if (strlen($payload) < 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($payload, 4)));
                $this->errors[] = 'SSH_MSG_DEBUG: ' . $this->_string_shift($payload, $length);
                $payload = $this->_get_binary_packet($skip_channel_filter);
                break;
            case NET_SSH2_MSG_UNIMPLEMENTED:
                return false;
            case NET_SSH2_MSG_KEXINIT:
                if ($this->session_id !== false) {
                    $this->send_kex_first = false;
                    if (!$this->_key_exchange($payload)) {
                        $this->bitmap = 0;
                        return false;
                    }
                    $payload = $this->_get_binary_packet($skip_channel_filter);
                }
        }

        // see http://tools.ietf.org/html/rfc4252#section-5.4; only called when the encryption has been activated and when we haven't already logged in
        if (($this->bitmap & self::MASK_CONNECTED) && !$this->isAuthenticated() && ord($payload[0]) == NET_SSH2_MSG_USERAUTH_BANNER) {
            $this->_string_shift($payload, 1);
            if (strlen($payload) < 4) {
                return false;
            }
            extract(unpack('Nlength', $this->_string_shift($payload, 4)));
            $this->banner_message = $this->_string_shift($payload, $length);
            $payload = $this->_get_binary_packet();
        }

        // only called when we've already logged in
        if (($this->bitmap & self::MASK_CONNECTED) && $this->isAuthenticated()) {
            if (is_bool($payload)) {
                return $payload;
            }

            switch (ord($payload[0])) {
                case NET_SSH2_MSG_CHANNEL_REQUEST:
                    if (strlen($payload) == 31) {
                        extract(unpack('cpacket_type/Nchannel/Nlength', $payload));
                        if (substr($payload, 9, $length) == 'keepalive@openssh.com' && isset($this->server_channels[$channel])) {
                            if (ord(substr($payload, 9 + $length))) { // want reply
                                $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_SUCCESS, $this->server_channels[$channel]));
                            }
                            $payload = $this->_get_binary_packet($skip_channel_filter);
                        }
                    }
                    break;
                case NET_SSH2_MSG_CHANNEL_DATA:
                case NET_SSH2_MSG_CHANNEL_EXTENDED_DATA:
                case NET_SSH2_MSG_CHANNEL_CLOSE:
                case NET_SSH2_MSG_CHANNEL_EOF:
                    if (!$skip_channel_filter && !empty($this->server_channels)) {
                        $this->binary_packet_buffer = $payload;
                        $this->_get_channel_packet(true);
                        $payload = $this->_get_binary_packet();
                    }
                    break;
                case NET_SSH2_MSG_GLOBAL_REQUEST: // see http://tools.ietf.org/html/rfc4254#section-4
                    if (strlen($payload) < 4) {
                        return false;
                    }
                    extract(unpack('Nlength', $this->_string_shift($payload, 4)));
                    $this->errors[] = 'SSH_MSG_GLOBAL_REQUEST: ' . $this->_string_shift($payload, $length);

                    if (!$this->_send_binary_packet(pack('C', NET_SSH2_MSG_REQUEST_FAILURE))) {
                        return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
                    }

                    $payload = $this->_get_binary_packet($skip_channel_filter);
                    break;
                case NET_SSH2_MSG_CHANNEL_OPEN: // see http://tools.ietf.org/html/rfc4254#section-5.1
                    $this->_string_shift($payload, 1);
                    if (strlen($payload) < 4) {
                        return false;
                    }
                    extract(unpack('Nlength', $this->_string_shift($payload, 4)));
                    $data = $this->_string_shift($payload, $length);
                    if (strlen($payload) < 4) {
                        return false;
                    }
                    extract(unpack('Nserver_channel', $this->_string_shift($payload, 4)));
                    switch ($data) {
                        case 'auth-agent':
                        case 'auth-agent@openssh.com':
                            if (isset($this->agent)) {
                                $new_channel = self::CHANNEL_AGENT_FORWARD;

                                if (strlen($payload) < 8) {
                                    return false;
                                }
                                extract(unpack('Nremote_window_size', $this->_string_shift($payload, 4)));
                                extract(unpack('Nremote_maximum_packet_size', $this->_string_shift($payload, 4)));

                                $this->packet_size_client_to_server[$new_channel] = $remote_window_size;
                                $this->window_size_server_to_client[$new_channel] = $remote_maximum_packet_size;
                                $this->window_size_client_to_server[$new_channel] = $this->window_size;

                                $packet_size = 0x4000;

                                $packet = pack(
                                    'CN4',
                                    NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION,
                                    $server_channel,
                                    $new_channel,
                                    $packet_size,
                                    $packet_size
                                );

                                $this->server_channels[$new_channel] = $server_channel;
                                $this->channel_status[$new_channel] = NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION;
                                if (!$this->_send_binary_packet($packet)) {
                                    return false;
                                }
                            }
                            break;
                        default:
                            $packet = pack(
                                'CN3a*Na*',
                                NET_SSH2_MSG_REQUEST_FAILURE,
                                $server_channel,
                                NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED,
                                0,
                                '',
                                0,
                                ''
                            );

                            if (!$this->_send_binary_packet($packet)) {
                                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
                            }
                    }
                    $payload = $this->_get_binary_packet($skip_channel_filter);
                    break;
                case NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST:
                    $this->_string_shift($payload, 1);
                    if (strlen($payload) < 8) {
                        return false;
                    }
                    extract(unpack('Nchannel', $this->_string_shift($payload, 4)));
                    extract(unpack('Nwindow_size', $this->_string_shift($payload, 4)));
                    $this->window_size_client_to_server[$channel]+= $window_size;

                    $payload = ($this->bitmap & self::MASK_WINDOW_ADJUST) ? true : $this->_get_binary_packet($skip_channel_filter);
            }
        }

        return $payload;
    }

    /**
     * Enable Quiet Mode
     *
     * Suppress stderr from output
     *
     * @access public
     */
    function enableQuietMode()
    {
        $this->quiet_mode = true;
    }

    /**
     * Disable Quiet Mode
     *
     * Show stderr in output
     *
     * @access public
     */
    function disableQuietMode()
    {
        $this->quiet_mode = false;
    }

    /**
     * Returns whether Quiet Mode is enabled or not
     *
     * @see self::enableQuietMode()
     * @see self::disableQuietMode()
     * @access public
     * @return bool
     */
    function isQuietModeEnabled()
    {
        return $this->quiet_mode;
    }

    /**
     * Enable request-pty when using exec()
     *
     * @access public
     */
    function enablePTY()
    {
        $this->request_pty = true;
    }

    /**
     * Disable request-pty when using exec()
     *
     * @access public
     */
    function disablePTY()
    {
        if ($this->in_request_pty_exec) {
            $this->_close_channel(self::CHANNEL_EXEC);
            $this->in_request_pty_exec = false;
        }
        $this->request_pty = false;
    }

    /**
     * Returns whether request-pty is enabled or not
     *
     * @see self::enablePTY()
     * @see self::disablePTY()
     * @access public
     * @return bool
     */
    function isPTYEnabled()
    {
        return $this->request_pty;
    }

    /**
     * Gets channel data
     *
     * Returns the data as a string if it's available and false if not.
     *
     * @param int $client_channel
     * @param bool $skip_extended
     * @return mixed|bool
     * @access private
     */
    function _get_channel_packet($client_channel, $skip_extended = false)
    {
        if (!empty($this->channel_buffers[$client_channel])) {
            return array_shift($this->channel_buffers[$client_channel]);
        }

        while (true) {
            if ($this->binary_packet_buffer !== false) {
                $response = $this->binary_packet_buffer;
                $this->binary_packet_buffer = false;
            } else {
                $response = $this->_get_binary_packet(true);
                if ($response === true && $this->is_timeout) {
                    if ($client_channel == self::CHANNEL_EXEC && !$this->request_pty) {
                        $this->_close_channel($client_channel);
                    }
                    return true;
                }
                if ($response === false) {
                    $this->bitmap = 0;
                    user_error('Connection closed by server');
                    return false;
                }
            }

            if ($client_channel == -1 && $response === true) {
                return true;
            }
            if (!strlen($response)) {
                return false;
            }
            extract(unpack('Ctype', $this->_string_shift($response, 1)));

            if (strlen($response) < 4) {
                return false;
            }
            if ($type == NET_SSH2_MSG_CHANNEL_OPEN) {
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
            } else {
                extract(unpack('Nchannel', $this->_string_shift($response, 4)));
            }

            // will not be setup yet on incoming channel open request
            if (isset($channel) && isset($this->channel_status[$channel]) && isset($this->window_size_server_to_client[$channel])) {
                $this->window_size_server_to_client[$channel]-= strlen($response);

                // resize the window, if appropriate
                if ($this->window_size_server_to_client[$channel] < 0) {
                // PuTTY does something more analogous to the following:
                //if ($this->window_size_server_to_client[$channel] < 0x3FFFFFFF) {
                    $packet = pack('CNN', NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST, $this->server_channels[$channel], $this->window_resize);
                    if (!$this->_send_binary_packet($packet)) {
                        return false;
                    }
                    $this->window_size_server_to_client[$channel]+= $this->window_resize;
                }

                switch ($type) {
                    case NET_SSH2_MSG_CHANNEL_EXTENDED_DATA:
                        /*
                        if ($client_channel == self::CHANNEL_EXEC) {
                            $this->_send_channel_packet($client_channel, chr(0));
                        }
                        */
                        // currently, there's only one possible value for $data_type_code: NET_SSH2_EXTENDED_DATA_STDERR
                        if (strlen($response) < 8) {
                            return false;
                        }
                        extract(unpack('Ndata_type_code/Nlength', $this->_string_shift($response, 8)));
                        $data = $this->_string_shift($response, $length);
                        $this->stdErrorLog.= $data;
                        if ($skip_extended || $this->quiet_mode) {
                            continue 2;
                        }
                        if ($client_channel == $channel && $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_DATA) {
                            return $data;
                        }
                        if (!isset($this->channel_buffers[$channel])) {
                            $this->channel_buffers[$channel] = array();
                        }
                        $this->channel_buffers[$channel][] = $data;

                        continue 2;
                    case NET_SSH2_MSG_CHANNEL_REQUEST:
                        if ($this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_CLOSE) {
                            continue 2;
                        }
                        if (strlen($response) < 4) {
                            return false;
                        }
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $value = $this->_string_shift($response, $length);
                        switch ($value) {
                            case 'exit-signal':
                                $this->_string_shift($response, 1);
                                if (strlen($response) < 4) {
                                    return false;
                                }
                                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                                $this->errors[] = 'SSH_MSG_CHANNEL_REQUEST (exit-signal): ' . $this->_string_shift($response, $length);
                                $this->_string_shift($response, 1);
                                if (strlen($response) < 4) {
                                    return false;
                                }
                                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                                if ($length) {
                                    $this->errors[count($this->errors)].= "\r\n" . $this->_string_shift($response, $length);
                                }

                                $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_EOF, $this->server_channels[$client_channel]));
                                $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));

                                $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_EOF;

                                continue 3;
                            case 'exit-status':
                                if (strlen($response) < 5) {
                                    return false;
                                }
                                extract(unpack('Cfalse/Nexit_status', $this->_string_shift($response, 5)));
                                $this->exit_status = $exit_status;

                                // "The client MAY ignore these messages."
                                // -- http://tools.ietf.org/html/rfc4254#section-6.10

                                continue 3;
                            default:
                                // "Some systems may not implement signals, in which case they SHOULD ignore this message."
                                //  -- http://tools.ietf.org/html/rfc4254#section-6.9
                                continue 3;
                        }
                }

                switch ($this->channel_status[$channel]) {
                    case NET_SSH2_MSG_CHANNEL_OPEN:
                        switch ($type) {
                            case NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION:
                                if (strlen($response) < 4) {
                                    return false;
                                }
                                extract(unpack('Nserver_channel', $this->_string_shift($response, 4)));
                                $this->server_channels[$channel] = $server_channel;
                                if (strlen($response) < 4) {
                                    return false;
                                }
                                extract(unpack('Nwindow_size', $this->_string_shift($response, 4)));
                                if ($window_size < 0) {
                                    $window_size&= 0x7FFFFFFF;
                                    $window_size+= 0x80000000;
                                }
                                $this->window_size_client_to_server[$channel] = $window_size;
                                if (strlen($response) < 4) {
                                     return false;
                                }
                                $temp = unpack('Npacket_size_client_to_server', $this->_string_shift($response, 4));
                                $this->packet_size_client_to_server[$channel] = $temp['packet_size_client_to_server'];
                                $result = $client_channel == $channel ? true : $this->_get_channel_packet($client_channel, $skip_extended);
                                $this->_on_channel_open();
                                return $result;
                            //case NET_SSH2_MSG_CHANNEL_OPEN_FAILURE:
                            default:
                                user_error('Unable to open channel');
                                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
                        }
                        break;
                    case NET_SSH2_MSG_IGNORE:
                        switch ($type) {
                            case NET_SSH2_MSG_CHANNEL_SUCCESS:
                                //$this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_DATA;
                                continue 3;
                            case NET_SSH2_MSG_CHANNEL_FAILURE:
                                user_error('Error opening channel');
                                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
                        }
                        break;
                    case NET_SSH2_MSG_CHANNEL_REQUEST:
                        switch ($type) {
                            case NET_SSH2_MSG_CHANNEL_SUCCESS:
                                return true;
                            case NET_SSH2_MSG_CHANNEL_FAILURE:
                                return false;
                            default:
                                user_error('Unable to fulfill channel request');
                                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
                        }
                    case NET_SSH2_MSG_CHANNEL_CLOSE:
                        return $type == NET_SSH2_MSG_CHANNEL_CLOSE ? true : $this->_get_channel_packet($client_channel, $skip_extended);
                }
            }

            // ie. $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_DATA

            switch ($type) {
                case NET_SSH2_MSG_CHANNEL_DATA:
                    //if ($this->channel_status[$channel] == NET_SSH2_MSG_IGNORE) {
                    //    $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_DATA;
                    //}

                    /*
                    if ($channel == self::CHANNEL_EXEC) {
                        // SCP requires null packets, such as this, be sent.  further, in the case of the ssh.com SSH server
                        // this actually seems to make things twice as fast.  more to the point, the message right after
                        // SSH_MSG_CHANNEL_DATA (usually SSH_MSG_IGNORE) won't block for as long as it would have otherwise.
                        // in OpenSSH it slows things down but only by a couple thousandths of a second.
                        $this->_send_channel_packet($channel, chr(0));
                    }
                    */
                    if (strlen($response) < 4) {
                        return false;
                    }
                    extract(unpack('Nlength', $this->_string_shift($response, 4)));
                    $data = $this->_string_shift($response, $length);

                    if ($channel == self::CHANNEL_AGENT_FORWARD) {
                        $agent_response = $this->agent->_forward_data($data);
                        if (!is_bool($agent_response)) {
                            $this->_send_channel_packet($channel, $agent_response);
                        }
                        break;
                    }

                    if ($client_channel == $channel) {
                        return $data;
                    }
                    if (!isset($this->channel_buffers[$channel])) {
                        $this->channel_buffers[$channel] = array();
                    }
                    $this->channel_buffers[$channel][] = $data;
                    break;
                case NET_SSH2_MSG_CHANNEL_CLOSE:
                    $this->curTimeout = 5;

                    if ($this->bitmap & self::MASK_SHELL) {
                        $this->bitmap&= ~self::MASK_SHELL;
                    }
                    if ($this->channel_status[$channel] != NET_SSH2_MSG_CHANNEL_EOF) {
                        $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));
                    }

                    $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_CLOSE;
                    if ($client_channel == $channel) {
                        return true;
                    }
                case NET_SSH2_MSG_CHANNEL_EOF:
                    break;
                default:
                    user_error('Error reading channel data');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
            }
        }
    }

    /**
     * Sends Binary Packets
     *
     * See '6. Binary Packet Protocol' of rfc4253 for more info.
     *
     * @param string $data
     * @param string $logged
     * @see self::_get_binary_packet()
     * @return bool
     * @access private
     */
    function _send_binary_packet($data, $logged = null)
    {
        if (!is_resource($this->fsock) || feof($this->fsock)) {
            $this->bitmap = 0;
            user_error('Connection closed prematurely');
            return false;
        }

        //if ($this->compress) {
        //    // the -4 removes the checksum:
        //    // http://php.net/function.gzcompress#57710
        //    $data = substr(gzcompress($data), 0, -4);
        //}

        // 4 (packet length) + 1 (padding length) + 4 (minimal padding amount) == 9
        $packet_length = strlen($data) + 9;
        // round up to the nearest $this->encrypt_block_size
        $packet_length+= (($this->encrypt_block_size - 1) * $packet_length) % $this->encrypt_block_size;
        // subtracting strlen($data) is obvious - subtracting 5 is necessary because of packet_length and padding_length
        $padding_length = $packet_length - strlen($data) - 5;
        $padding = Random::string($padding_length);

        // we subtract 4 from packet_length because the packet_length field isn't supposed to include itself
        $packet = pack('NCa*', $packet_length - 4, $padding_length, $data . $padding);

        $hmac = $this->hmac_create !== false ? $this->hmac_create->hash(pack('Na*', $this->send_seq_no, $packet)) : '';
        $this->send_seq_no++;

        if ($this->encrypt !== false) {
            $packet = $this->encrypt->encrypt($packet);
        }

        $packet.= $hmac;

        $start = microtime(true);
        $result = strlen($packet) == @fputs($this->fsock, $packet);
        $stop = microtime(true);

        if (defined('NET_SSH2_LOGGING')) {
            $current = microtime(true);
            $message_number = isset($this->message_numbers[ord($data[0])]) ? $this->message_numbers[ord($data[0])] : 'UNKNOWN (' . ord($data[0]) . ')';
            $message_number = '-> ' . $message_number .
                              ' (since last: ' . round($current - $this->last_packet, 4) . ', network: ' . round($stop - $start, 4) . 's)';
            $this->_append_log($message_number, isset($logged) ? $logged : $data);
            $this->last_packet = $current;
        }

        return $result;
    }

    /**
     * Logs data packets
     *
     * Makes sure that only the last 1MB worth of packets will be logged
     *
     * @param string $message_number
     * @param string $message
     * @access private
     */
    function _append_log($message_number, $message)
    {
        // remove the byte identifying the message type from all but the first two messages (ie. the identification strings)
        if (strlen($message_number) > 2) {
            $this->_string_shift($message);
        }

        switch (NET_SSH2_LOGGING) {
            // useful for benchmarks
            case self::LOG_SIMPLE:
                $this->message_number_log[] = $message_number;
                break;
            // the most useful log for SSH2
            case self::LOG_COMPLEX:
                $this->message_number_log[] = $message_number;
                $this->log_size+= strlen($message);
                $this->message_log[] = $message;
                while ($this->log_size > self::LOG_MAX_SIZE) {
                    $this->log_size-= strlen(array_shift($this->message_log));
                    array_shift($this->message_number_log);
                }
                break;
            // dump the output out realtime; packets may be interspersed with non packets,
            // passwords won't be filtered out and select other packets may not be correctly
            // identified
            case self::LOG_REALTIME:
                switch (PHP_SAPI) {
                    case 'cli':
                        $start = $stop = "\r\n";
                        break;
                    default:
                        $start = '<pre>';
                        $stop = '</pre>';
                }
                echo $start . $this->_format_log(array($message), array($message_number)) . $stop;
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
                    $filename = self::LOG_REALTIME_FILENAME;
                    $fp = fopen($filename, 'w');
                    $this->realtime_log_file = $fp;
                }
                if (!is_resource($this->realtime_log_file)) {
                    break;
                }
                $entry = $this->_format_log(array($message), array($message_number));
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

    /**
     * Sends channel data
     *
     * Spans multiple SSH_MSG_CHANNEL_DATAs if appropriate
     *
     * @param int $client_channel
     * @param string $data
     * @return bool
     * @access private
     */
    function _send_channel_packet($client_channel, $data)
    {
        while (strlen($data)) {
            if (!$this->window_size_client_to_server[$client_channel]) {
                $this->bitmap^= self::MASK_WINDOW_ADJUST;
                // using an invalid channel will let the buffers be built up for the valid channels
                $this->_get_channel_packet(-1);
                $this->bitmap^= self::MASK_WINDOW_ADJUST;
            }

            /* The maximum amount of data allowed is determined by the maximum
               packet size for the channel, and the current window size, whichever
               is smaller.
                 -- http://tools.ietf.org/html/rfc4254#section-5.2 */
            $max_size = min(
                $this->packet_size_client_to_server[$client_channel],
                $this->window_size_client_to_server[$client_channel]
            );

            $temp = $this->_string_shift($data, $max_size);
            $packet = pack(
                'CN2a*',
                NET_SSH2_MSG_CHANNEL_DATA,
                $this->server_channels[$client_channel],
                strlen($temp),
                $temp
            );
            $this->window_size_client_to_server[$client_channel]-= strlen($temp);
            if (!$this->_send_binary_packet($packet)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Closes and flushes a channel
     *
     * \phpseclib\Net\SSH2 doesn't properly close most channels.  For exec() channels are normally closed by the server
     * and for SFTP channels are presumably closed when the client disconnects.  This functions is intended
     * for SCP more than anything.
     *
     * @param int $client_channel
     * @param bool $want_reply
     * @return bool
     * @access private
     */
    function _close_channel($client_channel, $want_reply = false)
    {
        // see http://tools.ietf.org/html/rfc4254#section-5.3

        $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_EOF, $this->server_channels[$client_channel]));

        if (!$want_reply) {
            $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$client_channel]));
        }

        $this->channel_status[$client_channel] = NET_SSH2_MSG_CHANNEL_CLOSE;

        $this->curTimeout = 5;

        while (!is_bool($this->_get_channel_packet($client_channel))) {
        }

        if ($this->is_timeout) {
            $this->disconnect();
        }

        if ($want_reply) {
            $this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$client_channel]));
        }

        if ($this->bitmap & self::MASK_SHELL) {
            $this->bitmap&= ~self::MASK_SHELL;
        }
    }

    /**
     * Disconnect
     *
     * @param int $reason
     * @return bool
     * @access private
     */
    function _disconnect($reason)
    {
        if ($this->bitmap & self::MASK_CONNECTED) {
            $data = pack('CNNa*Na*', NET_SSH2_MSG_DISCONNECT, $reason, 0, '', 0, '');
            $this->_send_binary_packet($data);
        }

        $this->bitmap = 0;
        if (is_resource($this->fsock) && get_resource_type($this->fsock) == 'stream') {
            fclose($this->fsock);
        }

        return false;
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
     * Returns a string if NET_SSH2_LOGGING == self::LOG_COMPLEX, an array if NET_SSH2_LOGGING == self::LOG_SIMPLE and false if !defined('NET_SSH2_LOGGING')
     *
     * @access public
     * @return array|false|string
     */
    function getLog()
    {
        if (!defined('NET_SSH2_LOGGING')) {
            return false;
        }

        switch (NET_SSH2_LOGGING) {
            case self::LOG_SIMPLE:
                return $this->message_number_log;
            case self::LOG_COMPLEX:
                $log = $this->_format_log($this->message_log, $this->message_number_log);
                return PHP_SAPI == 'cli' ? $log : '<pre>' . $log . '</pre>';
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
     * Helper function for agent->_on_channel_open()
     *
     * Used when channels are created to inform agent
     * of said channel opening. Must be called after
     * channel open confirmation received
     *
     * @access private
     */
    function _on_channel_open()
    {
        if (isset($this->agent)) {
            $this->agent->_on_channel_open($this);
        }
    }

    /**
     * Returns the first value of the intersection of two arrays or false if
     * the intersection is empty. The order is defined by the first parameter.
     *
     * @param array $array1
     * @param array $array2
     * @return mixed False if intersection is empty, else intersected value.
     * @access private
     */
    function _array_intersect_first($array1, $array2)
    {
        foreach ($array1 as $value) {
            if (in_array($value, $array2)) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Returns all errors
     *
     * @return string[]
     * @access public
     */
    function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns the last error
     *
     * @return string
     * @access public
     */
    function getLastError()
    {
        $count = count($this->errors);

        if ($count > 0) {
            return $this->errors[$count - 1];
        }
    }

    /**
     * Return the server identification.
     *
     * @return string
     * @access public
     */
    function getServerIdentification()
    {
        $this->_connect();

        return $this->server_identifier;
    }

    /**
     * Return a list of the key exchange algorithms the server supports.
     *
     * @return array
     * @access public
     */
    function getKexAlgorithms()
    {
        $this->_connect();

        return $this->kex_algorithms;
    }

    /**
     * Return a list of the host key (public key) algorithms the server supports.
     *
     * @return array
     * @access public
     */
    function getServerHostKeyAlgorithms()
    {
        $this->_connect();

        return $this->server_host_key_algorithms;
    }

    /**
     * Return a list of the (symmetric key) encryption algorithms the server supports, when receiving stuff from the client.
     *
     * @return array
     * @access public
     */
    function getEncryptionAlgorithmsClient2Server()
    {
        $this->_connect();

        return $this->encryption_algorithms_client_to_server;
    }

    /**
     * Return a list of the (symmetric key) encryption algorithms the server supports, when sending stuff to the client.
     *
     * @return array
     * @access public
     */
    function getEncryptionAlgorithmsServer2Client()
    {
        $this->_connect();

        return $this->encryption_algorithms_server_to_client;
    }

    /**
     * Return a list of the MAC algorithms the server supports, when receiving stuff from the client.
     *
     * @return array
     * @access public
     */
    function getMACAlgorithmsClient2Server()
    {
        $this->_connect();

        return $this->mac_algorithms_client_to_server;
    }

    /**
     * Return a list of the MAC algorithms the server supports, when sending stuff to the client.
     *
     * @return array
     * @access public
     */
    function getMACAlgorithmsServer2Client()
    {
        $this->_connect();

        return $this->mac_algorithms_server_to_client;
    }

    /**
     * Return a list of the compression algorithms the server supports, when receiving stuff from the client.
     *
     * @return array
     * @access public
     */
    function getCompressionAlgorithmsClient2Server()
    {
        $this->_connect();

        return $this->compression_algorithms_client_to_server;
    }

    /**
     * Return a list of the compression algorithms the server supports, when sending stuff to the client.
     *
     * @return array
     * @access public
     */
    function getCompressionAlgorithmsServer2Client()
    {
        $this->_connect();

        return $this->compression_algorithms_server_to_client;
    }

    /**
     * Return a list of the languages the server supports, when sending stuff to the client.
     *
     * @return array
     * @access public
     */
    function getLanguagesServer2Client()
    {
        $this->_connect();

        return $this->languages_server_to_client;
    }

    /**
     * Return a list of the languages the server supports, when receiving stuff from the client.
     *
     * @return array
     * @access public
     */
    function getLanguagesClient2Server()
    {
        $this->_connect();

        return $this->languages_client_to_server;
    }

    /**
     * Returns a list of algorithms the server supports
     *
     * @return array
     * @access public
     */
    function getServerAlgorithms()
    {
        $this->_connect();

        return array(
            'kex' => $this->kex_algorithms,
            'hostkey' => $this->server_host_key_algorithms,
            'client_to_server' => array(
                'crypt' => $this->encryption_algorithms_client_to_server,
                'mac' => $this->mac_algorithms_client_to_server,
                'comp' => $this->compression_algorithms_client_to_server,
                'lang' => $this->languages_client_to_server
            ),
            'server_to_client' => array(
                'crypt' => $this->encryption_algorithms_server_to_client,
                'mac' => $this->mac_algorithms_server_to_client,
                'comp' => $this->compression_algorithms_server_to_client,
                'lang' => $this->languages_server_to_client
            )
        );
    }

    /**
     * Returns a list of KEX algorithms that phpseclib supports
     *
     * @return array
     * @access public
     */
    function getSupportedKEXAlgorithms()
    {
        $kex_algorithms = array(
            // Elliptic Curve Diffie-Hellman Key Agreement (ECDH) using
            // Curve25519. See doc/curve25519-sha256@libssh.org.txt in the
            // libssh repository for more information.
            'curve25519-sha256@libssh.org',

            'diffie-hellman-group-exchange-sha256',// RFC 4419
            'diffie-hellman-group-exchange-sha1',  // RFC 4419

            // Diffie-Hellman Key Agreement (DH) using integer modulo prime
            // groups.
            'diffie-hellman-group14-sha1', // REQUIRED
            'diffie-hellman-group1-sha1', // REQUIRED
        );

        if (!function_exists('sodium_crypto_box_publickey_from_secretkey')) {
            $kex_algorithms = array_diff(
                $kex_algorithms,
                array('curve25519-sha256@libssh.org')
            );
        }

        return $kex_algorithms;
    }

    /**
     * Returns a list of host key algorithms that phpseclib supports
     *
     * @return array
     * @access public
     */
    function getSupportedHostKeyAlgorithms()
    {
        return array(
            'rsa-sha2-256', // RFC 8332
            'rsa-sha2-512', // RFC 8332
            'ssh-rsa', // RECOMMENDED  sign   Raw RSA Key
            'ssh-dss'  // REQUIRED     sign   Raw DSS Key
        );
    }

    /**
     * Returns a list of symmetric key algorithms that phpseclib supports
     *
     * @return array
     * @access public
     */
    function getSupportedEncryptionAlgorithms()
    {
        $algos = array(
            // from <http://tools.ietf.org/html/rfc4345#section-4>:
            'arcfour256',
            'arcfour128',

            //'arcfour',      // OPTIONAL          the ARCFOUR stream cipher with a 128-bit key

            // CTR modes from <http://tools.ietf.org/html/rfc4344#section-4>:
            'aes128-ctr',     // RECOMMENDED       AES (Rijndael) in SDCTR mode, with 128-bit key
            'aes192-ctr',     // RECOMMENDED       AES with 192-bit key
            'aes256-ctr',     // RECOMMENDED       AES with 256-bit key

            'twofish128-ctr', // OPTIONAL          Twofish in SDCTR mode, with 128-bit key
            'twofish192-ctr', // OPTIONAL          Twofish with 192-bit key
            'twofish256-ctr', // OPTIONAL          Twofish with 256-bit key

            'aes128-cbc',     // RECOMMENDED       AES with a 128-bit key
            'aes192-cbc',     // OPTIONAL          AES with a 192-bit key
            'aes256-cbc',     // OPTIONAL          AES in CBC mode, with a 256-bit key

            'twofish128-cbc', // OPTIONAL          Twofish with a 128-bit key
            'twofish192-cbc', // OPTIONAL          Twofish with a 192-bit key
            'twofish256-cbc',
            'twofish-cbc',    // OPTIONAL          alias for "twofish256-cbc"
                              //                   (this is being retained for historical reasons)

            'blowfish-ctr',   // OPTIONAL          Blowfish in SDCTR mode

            'blowfish-cbc',   // OPTIONAL          Blowfish in CBC mode

            '3des-ctr',       // RECOMMENDED       Three-key 3DES in SDCTR mode

            '3des-cbc',       // REQUIRED          three-key 3DES in CBC mode

             //'none'           // OPTIONAL          no encryption; NOT RECOMMENDED
        );

        if ($this->crypto_engine) {
            $engines = array($this->crypto_engine);
        } else {
            $engines = array(
                Base::ENGINE_OPENSSL,
                Base::ENGINE_MCRYPT,
                Base::ENGINE_INTERNAL
            );
        }

        $ciphers = array();
        foreach ($engines as $engine) {
            foreach ($algos as $algo) {
                $obj = $this->_encryption_algorithm_to_crypt_instance($algo);
                if ($obj instanceof Rijndael) {
                    $obj->setKeyLength(preg_replace('#[^\d]#', '', $algo));
                }
                switch ($algo) {
                    case 'arcfour128':
                    case 'arcfour256':
                        if ($engine != Base::ENGINE_INTERNAL) {
                            continue 2;
                        }
                }
                if ($obj->isValidEngine($engine)) {
                    $algos = array_diff($algos, array($algo));
                    $ciphers[] = $algo;
                }
            }
        }

        return $ciphers;
    }

    /**
     * Returns a list of MAC algorithms that phpseclib supports
     *
     * @return array
     * @access public
     */
    function getSupportedMACAlgorithms()
    {
        return array(
            // from <http://www.ietf.org/rfc/rfc6668.txt>:
            'hmac-sha2-256',// RECOMMENDED     HMAC-SHA256 (digest length = key length = 32)

            'hmac-sha1-96', // RECOMMENDED     first 96 bits of HMAC-SHA1 (digest length = 12, key length = 20)
            'hmac-sha1',    // REQUIRED        HMAC-SHA1 (digest length = key length = 20)
            'hmac-md5-96',  // OPTIONAL        first 96 bits of HMAC-MD5 (digest length = 12, key length = 16)
            'hmac-md5',     // OPTIONAL        HMAC-MD5 (digest length = key length = 16)
            //'none'          // OPTIONAL        no MAC; NOT RECOMMENDED
        );
    }

    /**
     * Returns a list of compression algorithms that phpseclib supports
     *
     * @return array
     * @access public
     */
    function getSupportedCompressionAlgorithms()
    {
        return array(
            'none'   // REQUIRED        no compression
            //'zlib' // OPTIONAL        ZLIB (LZ77) compression
        );
    }

    /**
     * Return list of negotiated algorithms
     *
     * Uses the same format as https://www.php.net/ssh2-methods-negotiated
     *
     * @return array
     * @access public
     */
    function getAlgorithmsNegotiated()
    {
        $this->_connect();

        return array(
            'kex' => $this->kex_algorithm,
            'hostkey' => $this->signature_format,
            'client_to_server' => array(
                'crypt' => $this->encrypt->name,
                'mac' => $this->hmac_create->name,
                'comp' => 'none',
            ),
            'server_to_client' => array(
                'crypt' => $this->decrypt->name,
                'mac' => $this->hmac_check->name,
                'comp' => 'none',
            )
        );
    }

    /**
     * Accepts an associative array with up to four parameters as described at
     * <https://www.php.net/manual/en/function.ssh2-connect.php>
     *
     * @param array $methods
     * @access public
     */
    function setPreferredAlgorithms($methods)
    {
        $preferred = $methods;

        if (isset($preferred['kex'])) {
            $preferred['kex'] = array_intersect(
                $preferred['kex'],
                $this->getSupportedKEXAlgorithms()
            );
        }

        if (isset($preferred['hostkey'])) {
            $preferred['hostkey'] = array_intersect(
                $preferred['hostkey'],
                $this->getSupportedHostKeyAlgorithms()
            );
        }

        $keys = array('client_to_server', 'server_to_client');
        foreach ($keys as $key) {
            if (isset($preferred[$key])) {
                $a = &$preferred[$key];
                if (isset($a['crypt'])) {
                    $a['crypt'] = array_intersect(
                        $a['crypt'],
                        $this->getSupportedEncryptionAlgorithms()
                    );
                }
                if (isset($a['comp'])) {
                    $a['comp'] = array_intersect(
                        $a['comp'],
                        $this->getSupportedCompressionAlgorithms()
                    );
                }
                if (isset($a['mac'])) {
                    $a['mac'] = array_intersect(
                        $a['mac'],
                        $this->getSupportedMACAlgorithms()
                    );
                }
            }
        }

        $keys = array(
            'kex',
            'hostkey',
            'client_to_server/crypt',
            'client_to_server/comp',
            'client_to_server/mac',
            'server_to_client/crypt',
            'server_to_client/comp',
            'server_to_client/mac',
        );
        foreach ($keys as $key) {
            $p = $preferred;
            $m = $methods;

            $subkeys = explode('/', $key);
            foreach ($subkeys as $subkey) {
                if (!isset($p[$subkey])) {
                    continue 2;
                }
                $p = $p[$subkey];
                $m = $m[$subkey];
            }

            if (count($p) != count($m)) {
                $diff = array_diff($m, $p);
                $msg = count($diff) == 1 ?
                    ' is not a supported algorithm' :
                    ' are not supported algorithms';
                user_error(implode(', ', $diff) . $msg);
                return false;
            }
        }

        $this->preferred = $preferred;
    }

    /**
     * Returns the banner message.
     *
     * Quoting from the RFC, "in some jurisdictions, sending a warning message before
     * authentication may be relevant for getting legal protection."
     *
     * @return string
     * @access public
     */
    function getBannerMessage()
    {
        return $this->banner_message;
    }

    /**
     * Returns the server public host key.
     *
     * Caching this the first time you connect to a server and checking the result on subsequent connections
     * is recommended.  Returns false if the server signature is not signed correctly with the public host key.
     *
     * @return mixed
     * @access public
     */
    function getServerPublicHostKey()
    {
        if (!($this->bitmap & self::MASK_CONSTRUCTOR)) {
            if (!$this->_connect()) {
                return false;
            }
        }

        $signature = $this->signature;
        $server_public_host_key = $this->server_public_host_key;

        if (strlen($server_public_host_key) < 4) {
            return false;
        }
        extract(unpack('Nlength', $this->_string_shift($server_public_host_key, 4)));
        $this->_string_shift($server_public_host_key, $length);

        if ($this->signature_validated) {
            return $this->bitmap ?
                $this->signature_format . ' ' . base64_encode($this->server_public_host_key) :
                false;
        }

        $this->signature_validated = true;

        switch ($this->signature_format) {
            case 'ssh-dss':
                $zero = new BigInteger();

                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $p = new BigInteger($this->_string_shift($server_public_host_key, $temp['length']), -256);

                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $q = new BigInteger($this->_string_shift($server_public_host_key, $temp['length']), -256);

                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $g = new BigInteger($this->_string_shift($server_public_host_key, $temp['length']), -256);

                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $y = new BigInteger($this->_string_shift($server_public_host_key, $temp['length']), -256);

                /* The value for 'dss_signature_blob' is encoded as a string containing
                   r, followed by s (which are 160-bit integers, without lengths or
                   padding, unsigned, and in network byte order). */
                $temp = unpack('Nlength', $this->_string_shift($signature, 4));
                if ($temp['length'] != 40) {
                    user_error('Invalid signature');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
                }

                $r = new BigInteger($this->_string_shift($signature, 20), 256);
                $s = new BigInteger($this->_string_shift($signature, 20), 256);

                switch (true) {
                    case $r->equals($zero):
                    case $r->compare($q) >= 0:
                    case $s->equals($zero):
                    case $s->compare($q) >= 0:
                        user_error('Invalid signature');
                        return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
                }

                $w = $s->modInverse($q);

                $u1 = $w->multiply(new BigInteger(sha1($this->exchange_hash), 16));
                list(, $u1) = $u1->divide($q);

                $u2 = $w->multiply($r);
                list(, $u2) = $u2->divide($q);

                $g = $g->modPow($u1, $p);
                $y = $y->modPow($u2, $p);

                $v = $g->multiply($y);
                list(, $v) = $v->divide($p);
                list(, $v) = $v->divide($q);

                if (!$v->equals($r)) {
                    user_error('Bad server signature');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
                }

                break;
            case 'ssh-rsa':
            case 'rsa-sha2-256':
            case 'rsa-sha2-512':
                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $e = new BigInteger($this->_string_shift($server_public_host_key, $temp['length']), -256);

                if (strlen($server_public_host_key) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($server_public_host_key, 4));
                $rawN = $this->_string_shift($server_public_host_key, $temp['length']);
                $n = new BigInteger($rawN, -256);
                $nLength = strlen(ltrim($rawN, "\0"));

                /*
                if (strlen($signature) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($signature, 4));
                $signature = $this->_string_shift($signature, $temp['length']);

                $rsa = new RSA();
                switch ($this->signature_format) {
                    case 'rsa-sha2-512':
                        $hash = 'sha512';
                        break;
                    case 'rsa-sha2-256':
                        $hash = 'sha256';
                        break;
                    //case 'ssh-rsa':
                    default:
                        $hash = 'sha1';
                }
                $rsa->setHash($hash);
                $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
                $rsa->loadKey(array('e' => $e, 'n' => $n), RSA::PUBLIC_FORMAT_RAW);

                if (!$rsa->verify($this->exchange_hash, $signature)) {
                    user_error('Bad server signature');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
                }
                */

                if (strlen($signature) < 4) {
                    return false;
                }
                $temp = unpack('Nlength', $this->_string_shift($signature, 4));
                $s = new BigInteger($this->_string_shift($signature, $temp['length']), 256);

                // validate an RSA signature per "8.2 RSASSA-PKCS1-v1_5", "5.2.2 RSAVP1", and "9.1 EMSA-PSS" in the
                // following URL:
                // ftp://ftp.rsasecurity.com/pub/pkcs/pkcs-1/pkcs-1v2-1.pdf

                // also, see SSHRSA.c (rsa2_verifysig) in PuTTy's source.

                if ($s->compare(new BigInteger()) < 0 || $s->compare($n->subtract(new BigInteger(1))) > 0) {
                    user_error('Invalid signature');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
                }

                $s = $s->modPow($e, $n);
                $s = $s->toBytes();

                switch ($this->signature_format) {
                    case 'rsa-sha2-512':
                        $hash = 'sha512';
                        break;
                    case 'rsa-sha2-256':
                        $hash = 'sha256';
                        break;
                    //case 'ssh-rsa':
                    default:
                        $hash = 'sha1';
                }
                $hashObj = new Hash($hash);
                switch ($this->signature_format) {
                    case 'rsa-sha2-512':
                        $h = pack('N5a*', 0x00305130, 0x0D060960, 0x86480165, 0x03040203, 0x05000440, $hashObj->hash($this->exchange_hash));
                        break;
                    case 'rsa-sha2-256':
                        $h = pack('N5a*', 0x00303130, 0x0D060960, 0x86480165, 0x03040201, 0x05000420, $hashObj->hash($this->exchange_hash));
                        break;
                    //case 'ssh-rsa':
                    default:
                        $hash = 'sha1';
                        $h = pack('N4a*', 0x00302130, 0x0906052B, 0x0E03021A, 0x05000414, $hashObj->hash($this->exchange_hash));
                }
                $h = chr(0x01) . str_repeat(chr(0xFF), $nLength - 2 - strlen($h)) . $h;

                if ($s != $h) {
                    user_error('Bad server signature');
                    return $this->_disconnect(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
                }
                break;
            default:
                user_error('Unsupported signature format');
                return $this->_disconnect(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
        }

        return $this->signature_format . ' ' . base64_encode($this->server_public_host_key);
    }

    /**
     * Returns the exit status of an SSH command or false.
     *
     * @return false|int
     * @access public
     */
    function getExitStatus()
    {
        if (is_null($this->exit_status)) {
            return false;
        }
        return $this->exit_status;
    }

    /**
     * Returns the number of columns for the terminal window size.
     *
     * @return int
     * @access public
     */
    function getWindowColumns()
    {
        return $this->windowColumns;
    }

    /**
     * Returns the number of rows for the terminal window size.
     *
     * @return int
     * @access public
     */
    function getWindowRows()
    {
        return $this->windowRows;
    }

    /**
     * Sets the number of columns for the terminal window size.
     *
     * @param int $value
     * @access public
     */
    function setWindowColumns($value)
    {
        $this->windowColumns = $value;
    }

    /**
     * Sets the number of rows for the terminal window size.
     *
     * @param int $value
     * @access public
     */
    function setWindowRows($value)
    {
        $this->windowRows = $value;
    }

    /**
     * Sets the number of columns and rows for the terminal window size.
     *
     * @param int $columns
     * @param int $rows
     * @access public
     */
    function setWindowSize($columns = 80, $rows = 24)
    {
        $this->windowColumns = $columns;
        $this->windowRows = $rows;
    }

    /**
     * Update packet types in log history
     *
     * @param string $old
     * @param string $new
     * @access private
     */
    function _updateLogHistory($old, $new)
    {
        if (defined('NET_SSH2_LOGGING') && NET_SSH2_LOGGING == self::LOG_COMPLEX) {
            $this->message_number_log[count($this->message_number_log) - 1] = str_replace(
                $old,
                $new,
                $this->message_number_log[count($this->message_number_log) - 1]
            );
        }
    }

    /**
     * Return the list of authentication methods that may productively continue authentication.
     *
     * @see https://tools.ietf.org/html/rfc4252#section-5.1
     * @return array|null
     */
    public function getAuthMethodsToContinue()
    {
        return $this->auth_methods_to_continue;
    }
}
