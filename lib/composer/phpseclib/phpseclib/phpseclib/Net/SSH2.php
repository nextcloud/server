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
 *    $ssh = new \phpseclib3\Net\SSH2('www.domain.tld');
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
 *    $key = \phpseclib3\Crypt\PublicKeyLoader::load('...', '(optional) password');
 *
 *    $ssh = new \phpseclib3\Net\SSH2('www.domain.tld');
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
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Net;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Blowfish;
use phpseclib3\Crypt\ChaCha20;
use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\Common\SymmetricKey;
use phpseclib3\Crypt\DH;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\Hash;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\RC4;
use phpseclib3\Crypt\Rijndael;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\TripleDES; // Used to do Diffie-Hellman key exchange and DSA/RSA signature verification.
use phpseclib3\Crypt\Twofish;
use phpseclib3\Exception\ConnectionClosedException;
use phpseclib3\Exception\InsufficientSetupException;
use phpseclib3\Exception\InvalidPacketLengthException;
use phpseclib3\Exception\NoSupportedAlgorithmsException;
use phpseclib3\Exception\UnableToConnectException;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\Exception\UnsupportedCurveException;
use phpseclib3\Math\BigInteger;
use phpseclib3\System\SSH\Agent;

/**
 * Pure-PHP implementation of SSHv2.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class SSH2
{
    /**#@+
     * Compression Types
     *
     */
    /**
     * No compression
     */
    const NET_SSH2_COMPRESSION_NONE = 1;
    /**
     * zlib compression
     */
    const NET_SSH2_COMPRESSION_ZLIB = 2;
    /**
     * zlib@openssh.com
     */
    const NET_SSH2_COMPRESSION_ZLIB_AT_OPENSSH = 3;
    /**#@-*/

    // Execution Bitmap Masks
    const MASK_CONSTRUCTOR   = 0x00000001;
    const MASK_CONNECTED     = 0x00000002;
    const MASK_LOGIN_REQ     = 0x00000004;
    const MASK_LOGIN         = 0x00000008;
    const MASK_SHELL         = 0x00000010;
    const MASK_WINDOW_ADJUST = 0x00000020;

    /*
     * Channel constants
     *
     * RFC4254 refers not to client and server channels but rather to sender and recipient channels.  we don't refer
     * to them in that way because RFC4254 toggles the meaning. the client sends a SSH_MSG_CHANNEL_OPEN message with
     * a sender channel and the server sends a SSH_MSG_CHANNEL_OPEN_CONFIRMATION in response, with a sender and a
     * recipient channel.  at first glance, you might conclude that SSH_MSG_CHANNEL_OPEN_CONFIRMATION's sender channel
     * would be the same thing as SSH_MSG_CHANNEL_OPEN's sender channel, but it's not, per this snippet:
     *     The 'recipient channel' is the channel number given in the original
     *     open request, and 'sender channel' is the channel number allocated by
     *     the other side.
     *
     * @see \phpseclib3\Net\SSH2::send_channel_packet()
     * @see \phpseclib3\Net\SSH2::get_channel_packet()
     */
    const CHANNEL_EXEC          = 1; // PuTTy uses 0x100
    const CHANNEL_SHELL         = 2;
    const CHANNEL_SUBSYSTEM     = 3;
    const CHANNEL_AGENT_FORWARD = 4;
    const CHANNEL_KEEP_ALIVE    = 5;

    /**
     * Returns the message numbers
     *
     * @see \phpseclib3\Net\SSH2::getLog()
     */
    const LOG_SIMPLE = 1;
    /**
     * Returns the message content
     *
     * @see \phpseclib3\Net\SSH2::getLog()
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
     * Outputs the message numbers real-time
     */
    const LOG_SIMPLE_REALTIME = 5;
    /**
     * Make sure that the log never gets larger than this
     *
     * @see \phpseclib3\Net\SSH2::getLog()
     */
    const LOG_MAX_SIZE = 1048576; // 1024 * 1024

    /**
     * Returns when a string matching $expect exactly is found
     *
     * @see \phpseclib3\Net\SSH2::read()
     */
    const READ_SIMPLE = 1;
    /**
     * Returns when a string matching the regular expression $expect is found
     *
     * @see \phpseclib3\Net\SSH2::read()
     */
    const READ_REGEX = 2;
    /**
     * Returns whenever a data packet is received.
     *
     * Some data packets may only contain a single character so it may be necessary
     * to call read() multiple times when using this option
     *
     * @see \phpseclib3\Net\SSH2::read()
     */
    const READ_NEXT = 3;

    /**
     * The SSH identifier
     *
     * @var string
     */
    private $identifier;

    /**
     * The Socket Object
     *
     * @var resource|closed-resource|null
     */
    public $fsock;

    /**
     * Execution Bitmap
     *
     * The bits that are set represent functions that have been called already.  This is used to determine
     * if a requisite function has been successfully executed.  If not, an error should be thrown.
     *
     * @var int
     */
    protected $bitmap = 0;

    /**
     * Error information
     *
     * @see self::getErrors()
     * @see self::getLastError()
     * @var array
     */
    private $errors = [];

    /**
     * Server Identifier
     *
     * @see self::getServerIdentification()
     * @var string|false
     */
    protected $server_identifier = false;

    /**
     * Key Exchange Algorithms
     *
     * @see self::getKexAlgorithims()
     * @var array|false
     */
    private $kex_algorithms = false;

    /**
     * Key Exchange Algorithm
     *
     * @see self::getMethodsNegotiated()
     * @var string|false
     */
    private $kex_algorithm = false;

    /**
     * Minimum Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     */
    private $kex_dh_group_size_min = 1536;

    /**
     * Preferred Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     */
    private $kex_dh_group_size_preferred = 2048;

    /**
     * Maximum Diffie-Hellman Group Bit Size in RFC 4419 Key Exchange Methods
     *
     * @see self::_key_exchange()
     * @var int
     */
    private $kex_dh_group_size_max = 4096;

    /**
     * Server Host Key Algorithms
     *
     * @see self::getServerHostKeyAlgorithms()
     * @var array|false
     */
    private $server_host_key_algorithms = false;

    /**
     * Supported Private Key Algorithms
     *
     * In theory this should be the same as the Server Host Key Algorithms but, in practice,
     * some servers (eg. Azure) will support rsa-sha2-512 as a server host key algorithm but
     * not a private key algorithm
     *
     * @see self::privatekey_login()
     * @var array|false
     */
    private $supported_private_key_algorithms = false;

    /**
     * Encryption Algorithms: Client to Server
     *
     * @see self::getEncryptionAlgorithmsClient2Server()
     * @var array|false
     */
    private $encryption_algorithms_client_to_server = false;

    /**
     * Encryption Algorithms: Server to Client
     *
     * @see self::getEncryptionAlgorithmsServer2Client()
     * @var array|false
     */
    private $encryption_algorithms_server_to_client = false;

    /**
     * MAC Algorithms: Client to Server
     *
     * @see self::getMACAlgorithmsClient2Server()
     * @var array|false
     */
    private $mac_algorithms_client_to_server = false;

    /**
     * MAC Algorithms: Server to Client
     *
     * @see self::getMACAlgorithmsServer2Client()
     * @var array|false
     */
    private $mac_algorithms_server_to_client = false;

    /**
     * Compression Algorithms: Client to Server
     *
     * @see self::getCompressionAlgorithmsClient2Server()
     * @var array|false
     */
    private $compression_algorithms_client_to_server = false;

    /**
     * Compression Algorithms: Server to Client
     *
     * @see self::getCompressionAlgorithmsServer2Client()
     * @var array|false
     */
    private $compression_algorithms_server_to_client = false;

    /**
     * Languages: Server to Client
     *
     * @see self::getLanguagesServer2Client()
     * @var array|false
     */
    private $languages_server_to_client = false;

    /**
     * Languages: Client to Server
     *
     * @see self::getLanguagesClient2Server()
     * @var array|false
     */
    private $languages_client_to_server = false;

    /**
     * Preferred Algorithms
     *
     * @see self::setPreferredAlgorithms()
     * @var array
     */
    private $preferred = [];

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
     */
    private $encrypt_block_size = 8;

    /**
     * Block Size for Client to Server Encryption
     *
     * @see self::__construct()
     * @see self::_get_binary_packet()
     * @var int
     */
    private $decrypt_block_size = 8;

    /**
     * Server to Client Encryption Object
     *
     * @see self::_get_binary_packet()
     * @var SymmetricKey|false
     */
    private $decrypt = false;

    /**
     * Decryption Algorithm Name
     *
     * @var string|null
     */
    private $decryptName;

    /**
     * Decryption Invocation Counter
     *
     * Used by GCM
     *
     * @var string|null
     */
    private $decryptInvocationCounter;

    /**
     * Fixed Part of Nonce
     *
     * Used by GCM
     *
     * @var string|null
     */
    private $decryptFixedPart;

    /**
     * Server to Client Length Encryption Object
     *
     * @see self::_get_binary_packet()
     * @var object
     */
    private $lengthDecrypt = false;

    /**
     * Client to Server Encryption Object
     *
     * @see self::_send_binary_packet()
     * @var SymmetricKey|false
     */
    private $encrypt = false;

    /**
     * Encryption Algorithm Name
     *
     * @var string|null
     */
    private $encryptName;

    /**
     * Encryption Invocation Counter
     *
     * Used by GCM
     *
     * @var string|null
     */
    private $encryptInvocationCounter;

    /**
     * Fixed Part of Nonce
     *
     * Used by GCM
     *
     * @var string|null
     */
    private $encryptFixedPart;

    /**
     * Client to Server Length Encryption Object
     *
     * @see self::_send_binary_packet()
     * @var object
     */
    private $lengthEncrypt = false;

    /**
     * Client to Server HMAC Object
     *
     * @see self::_send_binary_packet()
     * @var object
     */
    private $hmac_create = false;

    /**
     * Client to Server HMAC Name
     *
     * @var string|false
     */
    private $hmac_create_name;

    /**
     * Client to Server ETM
     *
     * @var int|false
     */
    private $hmac_create_etm;

    /**
     * Server to Client HMAC Object
     *
     * @see self::_get_binary_packet()
     * @var object
     */
    private $hmac_check = false;

    /**
     * Server to Client HMAC Name
     *
     * @var string|false
     */
    private $hmac_check_name;

    /**
     * Server to Client ETM
     *
     * @var int|false
     */
    private $hmac_check_etm;

    /**
     * Size of server to client HMAC
     *
     * We need to know how big the HMAC will be for the server to client direction so that we know how many bytes to read.
     * For the client to server side, the HMAC object will make the HMAC as long as it needs to be.  All we need to do is
     * append it.
     *
     * @see self::_get_binary_packet()
     * @var int
     */
    private $hmac_size = false;

    /**
     * Server Public Host Key
     *
     * @see self::getServerPublicHostKey()
     * @var string
     */
    private $server_public_host_key;

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
     */
    private $session_id = false;

    /**
     * Exchange hash
     *
     * The current exchange hash
     *
     * @see self::_key_exchange()
     * @var string
     */
    private $exchange_hash = false;

    /**
     * Message Numbers
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    private static $message_numbers = [];

    /**
     * Disconnection Message 'reason codes' defined in RFC4253
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    private static $disconnect_reasons = [];

    /**
     * SSH_MSG_CHANNEL_OPEN_FAILURE 'reason codes', defined in RFC4254
     *
     * @see self::__construct()
     * @var array
     * @access private
     */
    private static $channel_open_failure_reasons = [];

    /**
     * Terminal Modes
     *
     * @link http://tools.ietf.org/html/rfc4254#section-8
     * @see self::__construct()
     * @var array
     * @access private
     */
    private static $terminal_modes = [];

    /**
     * SSH_MSG_CHANNEL_EXTENDED_DATA's data_type_codes
     *
     * @link http://tools.ietf.org/html/rfc4254#section-5.2
     * @see self::__construct()
     * @var array
     * @access private
     */
    private static $channel_extended_data_type_codes = [];

    /**
     * Send Sequence Number
     *
     * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
     *
     * @see self::_send_binary_packet()
     * @var int
     */
    private $send_seq_no = 0;

    /**
     * Get Sequence Number
     *
     * See 'Section 6.4.  Data Integrity' of rfc4253 for more info.
     *
     * @see self::_get_binary_packet()
     * @var int
     */
    private $get_seq_no = 0;

    /**
     * Server Channels
     *
     * Maps client channels to server channels
     *
     * @see self::get_channel_packet()
     * @see self::exec()
     * @var array
     */
    protected $server_channels = [];

    /**
     * Channel Buffers
     *
     * If a client requests a packet from one channel but receives two packets from another those packets should
     * be placed in a buffer
     *
     * @see self::get_channel_packet()
     * @see self::exec()
     * @var array
     */
    private $channel_buffers = [];

    /**
     * Channel Status
     *
     * Contains the type of the last sent message
     *
     * @see self::get_channel_packet()
     * @var array
     */
    protected $channel_status = [];

    /**
     * The identifier of the interactive channel which was opened most recently
     *
     * @see self::getInteractiveChannelId()
     * @var int
     */
    private $channel_id_last_interactive = 0;

    /**
     * Packet Size
     *
     * Maximum packet size indexed by channel
     *
     * @see self::send_channel_packet()
     * @var array
     */
    private $packet_size_client_to_server = [];

    /**
     * Message Number Log
     *
     * @see self::getLog()
     * @var array
     */
    private $message_number_log = [];

    /**
     * Message Log
     *
     * @see self::getLog()
     * @var array
     */
    private $message_log = [];

    /**
     * The Window Size
     *
     * Bytes the other party can send before it must wait for the window to be adjusted (0x7FFFFFFF = 2GB)
     *
     * @var int
     * @see self::send_channel_packet()
     * @see self::exec()
     */
    protected $window_size = 0x7FFFFFFF;

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
     */
    private $window_resize = 0x40000000;

    /**
     * Window size, server to client
     *
     * Window size indexed by channel
     *
     * @see self::send_channel_packet()
     * @var array
     */
    protected $window_size_server_to_client = [];

    /**
     * Window size, client to server
     *
     * Window size indexed by channel
     *
     * @see self::get_channel_packet()
     * @var array
     */
    private $window_size_client_to_server = [];

    /**
     * Server signature
     *
     * Verified against $this->session_id
     *
     * @see self::getServerPublicHostKey()
     * @var string
     */
    private $signature = '';

    /**
     * Server signature format
     *
     * ssh-rsa or ssh-dss.
     *
     * @see self::getServerPublicHostKey()
     * @var string
     */
    private $signature_format = '';

    /**
     * Interactive Buffer
     *
     * @see self::read()
     * @var string
     */
    private $interactiveBuffer = '';

    /**
     * Current log size
     *
     * Should never exceed self::LOG_MAX_SIZE
     *
     * @see self::_send_binary_packet()
     * @see self::_get_binary_packet()
     * @var int
     */
    private $log_size;

    /**
     * Timeout
     *
     * @see self::setTimeout()
     */
    protected $timeout;

    /**
     * Current Timeout
     *
     * @see self::get_channel_packet()
     */
    protected $curTimeout;

    /**
     * Keep Alive Interval
     *
     * @see self::setKeepAlive()
     */
    private $keepAlive;

    /**
     * Real-time log file pointer
     *
     * @see self::_append_log()
     * @var resource|closed-resource
     */
    private $realtime_log_file;

    /**
     * Real-time log file size
     *
     * @see self::_append_log()
     * @var int
     */
    private $realtime_log_size;

    /**
     * Has the signature been validated?
     *
     * @see self::getServerPublicHostKey()
     * @var bool
     */
    private $signature_validated = false;

    /**
     * Real-time log file wrap boolean
     *
     * @see self::_append_log()
     * @var bool
     */
    private $realtime_log_wrap;

    /**
     * Flag to suppress stderr from output
     *
     * @see self::enableQuietMode()
     */
    private $quiet_mode = false;

    /**
     * Time of first network activity
     *
     * @var float
     */
    private $last_packet;

    /**
     * Exit status returned from ssh if any
     *
     * @var int
     */
    private $exit_status;

    /**
     * Flag to request a PTY when using exec()
     *
     * @var bool
     * @see self::enablePTY()
     */
    private $request_pty = false;

    /**
     * Contents of stdError
     *
     * @var string
     */
    private $stdErrorLog;

    /**
     * The Last Interactive Response
     *
     * @see self::_keyboard_interactive_process()
     * @var string
     */
    private $last_interactive_response = '';

    /**
     * Keyboard Interactive Request / Responses
     *
     * @see self::_keyboard_interactive_process()
     * @var array
     */
    private $keyboard_requests_responses = [];

    /**
     * Banner Message
     *
     * Quoting from the RFC, "in some jurisdictions, sending a warning message before
     * authentication may be relevant for getting legal protection."
     *
     * @see self::_filter()
     * @see self::getBannerMessage()
     * @var string
     */
    private $banner_message = '';

    /**
     * Did read() timeout or return normally?
     *
     * @see self::isTimeout()
     * @var bool
     */
    private $is_timeout = false;

    /**
     * Log Boundary
     *
     * @see self::_format_log()
     * @var string
     */
    private $log_boundary = ':';

    /**
     * Log Long Width
     *
     * @see self::_format_log()
     * @var int
     */
    private $log_long_width = 65;

    /**
     * Log Short Width
     *
     * @see self::_format_log()
     * @var int
     */
    private $log_short_width = 16;

    /**
     * Hostname
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var string
     */
    private $host;

    /**
     * Port Number
     *
     * @see self::__construct()
     * @see self::_connect()
     * @var int
     */
    private $port;

    /**
     * Number of columns for terminal window size
     *
     * @see self::getWindowColumns()
     * @see self::setWindowColumns()
     * @see self::setWindowSize()
     * @var int
     */
    private $windowColumns = 80;

    /**
     * Number of columns for terminal window size
     *
     * @see self::getWindowRows()
     * @see self::setWindowRows()
     * @see self::setWindowSize()
     * @var int
     */
    private $windowRows = 24;

    /**
     * Crypto Engine
     *
     * @see self::setCryptoEngine()
     * @see self::_key_exchange()
     * @var int
     */
    private static $crypto_engine = false;

    /**
     * A System_SSH_Agent for use in the SSH2 Agent Forwarding scenario
     *
     * @var Agent
     */
    private $agent;

    /**
     * Connection storage to replicates ssh2 extension functionality:
     * {@link http://php.net/manual/en/wrappers.ssh2.php#refsect1-wrappers.ssh2-examples}
     *
     * @var array<string, SSH2|\WeakReference<SSH2>>
     */
    private static $connections;

    /**
     * Send the identification string first?
     *
     * @var bool
     */
    private $send_id_string_first = true;

    /**
     * Send the key exchange initiation packet first?
     *
     * @var bool
     */
    private $send_kex_first = true;

    /**
     * Some versions of OpenSSH incorrectly calculate the key size
     *
     * @var bool
     */
    private $bad_key_size_fix = false;

    /**
     * Should we try to re-connect to re-establish keys?
     *
     * @var bool
     */
    private $login_credentials_finalized = false;

    /**
     * Binary Packet Buffer
     *
     * @var object|null
     */
    private $binary_packet_buffer = null;

    /**
     * Preferred Signature Format
     *
     * @var string|false
     */
    protected $preferred_signature_format = false;

    /**
     * Authentication Credentials
     *
     * @var array
     */
    protected $auth = [];

    /**
     * Terminal
     *
     * @var string
     */
    private $term = 'vt100';

    /**
     * The authentication methods that may productively continue authentication.
     *
     * @see https://tools.ietf.org/html/rfc4252#section-5.1
     * @var array|null
     */
    private $auth_methods_to_continue = null;

    /**
     * Compression method
     *
     * @var int
     */
    private $compress = self::NET_SSH2_COMPRESSION_NONE;

    /**
     * Decompression method
     *
     * @var int
     */
    private $decompress = self::NET_SSH2_COMPRESSION_NONE;

    /**
     * Compression context
     *
     * @var resource|false|null
     */
    private $compress_context;

    /**
     * Decompression context
     *
     * @var resource|object
     */
    private $decompress_context;

    /**
     * Regenerate Compression Context
     *
     * @var bool
     */
    private $regenerate_compression_context = false;

    /**
     * Regenerate Decompression Context
     *
     * @var bool
     */
    private $regenerate_decompression_context = false;

    /**
     * Smart multi-factor authentication flag
     *
     * @var bool
     */
    private $smartMFA = true;

    /**
     * How many channels are currently opened
     *
     * @var int
     */
    private $channelCount = 0;

    /**
     * Does the server support multiple channels? If not then error out
     * when multiple channels are attempted to be opened
     *
     * @var bool
     */
    private $errorOnMultipleChannels;

    /**
     * Terrapin Countermeasure
     *
     * "During initial KEX, terminate the connection if any unexpected or out-of-sequence packet is received"
     * -- https://github.com/openssh/openssh-portable/commit/1edb00c58f8a6875fad6a497aa2bacf37f9e6cd5
     *
     * @var int
     */
    private $extra_packets;

    /**
     * Default Constructor.
     *
     * $host can either be a string, representing the host, or a stream resource.
     * If $host is a stream resource then $port doesn't do anything, altho $timeout
     * still will be used
     *
     * @param mixed $host
     * @param int $port
     * @param int $timeout
     * @see self::login()
     */
    public function __construct($host, $port = 22, $timeout = 10)
    {
        if (empty(self::$message_numbers)) {
            self::$message_numbers = [
                1 => 'NET_SSH2_MSG_DISCONNECT',
                2 => 'NET_SSH2_MSG_IGNORE',
                3 => 'NET_SSH2_MSG_UNIMPLEMENTED',
                4 => 'NET_SSH2_MSG_DEBUG',
                5 => 'NET_SSH2_MSG_SERVICE_REQUEST',
                6 => 'NET_SSH2_MSG_SERVICE_ACCEPT',
                7 => 'NET_SSH2_MSG_EXT_INFO', // RFC 8308
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
            ];
            self::$disconnect_reasons = [
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
            ];
            self::$channel_open_failure_reasons = [
                1 => 'NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED'
            ];
            self::$terminal_modes = [
                0 => 'NET_SSH2_TTY_OP_END'
            ];
            self::$channel_extended_data_type_codes = [
                1 => 'NET_SSH2_EXTENDED_DATA_STDERR'
            ];

            self::define_array(
                self::$message_numbers,
                self::$disconnect_reasons,
                self::$channel_open_failure_reasons,
                self::$terminal_modes,
                self::$channel_extended_data_type_codes,
                [60 => 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ'],
                [60 => 'NET_SSH2_MSG_USERAUTH_PK_OK'],
                [60 => 'NET_SSH2_MSG_USERAUTH_INFO_REQUEST',
                      61 => 'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE'],
                // RFC 4419 - diffie-hellman-group-exchange-sha{1,256}
                [30 => 'NET_SSH2_MSG_KEXDH_GEX_REQUEST_OLD',
                      31 => 'NET_SSH2_MSG_KEXDH_GEX_GROUP',
                      32 => 'NET_SSH2_MSG_KEXDH_GEX_INIT',
                      33 => 'NET_SSH2_MSG_KEXDH_GEX_REPLY',
                      34 => 'NET_SSH2_MSG_KEXDH_GEX_REQUEST'],
                // RFC 5656 - Elliptic Curves (for curve25519-sha256@libssh.org)
                [30 => 'NET_SSH2_MSG_KEX_ECDH_INIT',
                      31 => 'NET_SSH2_MSG_KEX_ECDH_REPLY']
            );
        }

        /**
         * Typehint is required due to a bug in Psalm: https://github.com/vimeo/psalm/issues/7508
         * @var \WeakReference<SSH2>|SSH2
         */
        self::$connections[$this->getResourceId()] = class_exists('WeakReference')
            ? \WeakReference::create($this)
            : $this;

        $this->timeout = $timeout;

        if (is_resource($host)) {
            $this->fsock = $host;
            return;
        }

        if (Strings::is_stringable($host)) {
            $this->host = $host;
            $this->port = $port;
        }
    }

    /**
     * Set Crypto Engine Mode
     *
     * Possible $engine values:
     * OpenSSL, mcrypt, Eval, PHP
     *
     * @param int $engine
     */
    public static function setCryptoEngine($engine)
    {
        self::$crypto_engine = $engine;
    }

    /**
     * Send Identification String First
     *
     * https://tools.ietf.org/html/rfc4253#section-4.2 says "when the connection has been established,
     * both sides MUST send an identification string". It does not say which side sends it first. In
     * theory it shouldn't matter but it is a fact of life that some SSH servers are simply buggy
     *
     */
    public function sendIdentificationStringFirst()
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
     */
    public function sendIdentificationStringLast()
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
     */
    public function sendKEXINITFirst()
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
     */
    public function sendKEXINITLast()
    {
        $this->send_kex_first = false;
    }

    /**
     * stream_select wrapper
     *
     * Quoting https://stackoverflow.com/a/14262151/569976,
     * "The general approach to `EINTR` is to simply handle the error and retry the operation again"
     *
     * This wrapper does that loop
     */
    private static function stream_select(&$read, &$write, &$except, $seconds, $microseconds = null)
    {
        $remaining = $seconds + $microseconds / 1000000;
        $start = microtime(true);
        while (true) {
            $result = @stream_select($read, $write, $except, $seconds, $microseconds);
            if ($result !== false) {
                return $result;
            }
            $elapsed = microtime(true) - $start;
            $seconds = (int) ($remaining - floor($elapsed));
            $microseconds = (int) (1000000 * ($remaining - $seconds));
            if ($elapsed >= $remaining) {
                return false;
            }
        }
    }

    /**
     * Connect to an SSHv2 server
     *
     * @throws \UnexpectedValueException on receipt of unexpected packets
     * @throws \RuntimeException on other errors
     */
    private function connect()
    {
        if ($this->bitmap & self::MASK_CONSTRUCTOR) {
            return;
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
                throw new UnableToConnectException(rtrim("Cannot connect to $host. Error $errno. $errstr"));
            }
            $elapsed = microtime(true) - $start;

            if ($this->curTimeout) {
                $this->curTimeout -= $elapsed;
                if ($this->curTimeout < 0) {
                    throw new \RuntimeException('Connection timed out whilst attempting to open socket connection');
                }
            }
        }

        $this->identifier = $this->generate_identifier();

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
                        throw new \RuntimeException('Connection timed out whilst receiving server identification string');
                    }
                    $read = [$this->fsock];
                    $write = $except = null;
                    $start = microtime(true);
                    $sec = (int) floor($this->curTimeout);
                    $usec = (int) (1000000 * ($this->curTimeout - $sec));
                    if (static::stream_select($read, $write, $except, $sec, $usec) === false) {
                        throw new \RuntimeException('Connection timed out whilst receiving server identification string');
                    }
                    $elapsed = microtime(true) - $start;
                    $this->curTimeout -= $elapsed;
                }

                $temp = stream_get_line($this->fsock, 255, "\n");
                if ($temp === false) {
                    throw new \RuntimeException('Error reading from socket');
                }
                if (strlen($temp) == 255) {
                    continue;
                }

                $line .= "$temp\n";

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

            $data .= $line;
        }

        if (feof($this->fsock)) {
            $this->bitmap = 0;
            throw new ConnectionClosedException('Connection closed by server');
        }

        $extra = $matches[1];

        if (defined('NET_SSH2_LOGGING')) {
            $this->append_log('<-', $matches[0]);
            $this->append_log('->', $this->identifier . "\r\n");
        }

        $this->server_identifier = trim($temp, "\r\n");
        if (strlen($extra)) {
            $this->errors[] = $data;
        }

        if (version_compare($matches[3], '1.99', '<')) {
            $this->bitmap = 0;
            throw new UnableToConnectException("Cannot connect to SSH $matches[3] servers");
        }

        // Ubuntu's OpenSSH from 5.8 to 6.9 didn't work with multiple channels. see
        // https://bugs.launchpad.net/ubuntu/+source/openssh/+bug/1334916 for more info.
        // https://lists.ubuntu.com/archives/oneiric-changes/2011-July/005772.html discusses
        // when consolekit was incorporated.
        // https://marc.info/?l=openssh-unix-dev&m=163409903417589&w=2 discusses some of the
        // issues with how Ubuntu incorporated consolekit
        $pattern = '#^SSH-2\.0-OpenSSH_([\d.]+)[^ ]* Ubuntu-.*$#';
        $match = preg_match($pattern, $this->server_identifier, $matches);
        $match = $match && version_compare('5.8', $matches[1], '<=');
        $match = $match && version_compare('6.9', $matches[1], '>=');
        $this->errorOnMultipleChannels = $match;

        if (!$this->send_id_string_first) {
            fputs($this->fsock, $this->identifier . "\r\n");
        }

        if (!$this->send_kex_first) {
            $response = $this->get_binary_packet();

            if (is_bool($response) || !strlen($response) || ord($response[0]) != NET_SSH2_MSG_KEXINIT) {
                $this->bitmap = 0;
                throw new \UnexpectedValueException('Expected SSH_MSG_KEXINIT');
            }

            $this->key_exchange($response);
        }

        if ($this->send_kex_first) {
            $this->key_exchange();
        }

        $this->bitmap |= self::MASK_CONNECTED;

        return true;
    }

    /**
     * Generates the SSH identifier
     *
     * You should overwrite this method in your own class if you want to use another identifier
     *
     * @return string
     */
    private function generate_identifier()
    {
        $identifier = 'SSH-2.0-phpseclib_3.0';

        $ext = [];
        if (extension_loaded('sodium')) {
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
     * @return bool
     * @param string|bool $kexinit_payload_server optional
     * @throws \UnexpectedValueException on receipt of unexpected packets
     * @throws \RuntimeException on other errors
     * @throws \phpseclib3\Exception\NoSupportedAlgorithmsException when none of the algorithms phpseclib has loaded are compatible
     */
    private function key_exchange($kexinit_payload_server = false)
    {
        $preferred = $this->preferred;
        $send_kex = true;

        $kex_algorithms = isset($preferred['kex']) ?
            $preferred['kex'] :
            SSH2::getSupportedKEXAlgorithms();
        $server_host_key_algorithms = isset($preferred['hostkey']) ?
            $preferred['hostkey'] :
            SSH2::getSupportedHostKeyAlgorithms();
        $s2c_encryption_algorithms = isset($preferred['server_to_client']['crypt']) ?
            $preferred['server_to_client']['crypt'] :
            SSH2::getSupportedEncryptionAlgorithms();
        $c2s_encryption_algorithms = isset($preferred['client_to_server']['crypt']) ?
            $preferred['client_to_server']['crypt'] :
            SSH2::getSupportedEncryptionAlgorithms();
        $s2c_mac_algorithms = isset($preferred['server_to_client']['mac']) ?
            $preferred['server_to_client']['mac'] :
            SSH2::getSupportedMACAlgorithms();
        $c2s_mac_algorithms = isset($preferred['client_to_server']['mac']) ?
            $preferred['client_to_server']['mac'] :
            SSH2::getSupportedMACAlgorithms();
        $s2c_compression_algorithms = isset($preferred['server_to_client']['comp']) ?
            $preferred['server_to_client']['comp'] :
            SSH2::getSupportedCompressionAlgorithms();
        $c2s_compression_algorithms = isset($preferred['client_to_server']['comp']) ?
            $preferred['client_to_server']['comp'] :
            SSH2::getSupportedCompressionAlgorithms();

        $kex_algorithms = array_merge($kex_algorithms, ['ext-info-c', 'kex-strict-c-v00@openssh.com']);

        // some SSH servers have buggy implementations of some of the above algorithms
        switch (true) {
            case $this->server_identifier == 'SSH-2.0-SSHD':
            case substr($this->server_identifier, 0, 13) == 'SSH-2.0-DLINK':
                if (!isset($preferred['server_to_client']['mac'])) {
                    $s2c_mac_algorithms = array_values(array_diff(
                        $s2c_mac_algorithms,
                        ['hmac-sha1-96', 'hmac-md5-96']
                    ));
                }
                if (!isset($preferred['client_to_server']['mac'])) {
                    $c2s_mac_algorithms = array_values(array_diff(
                        $c2s_mac_algorithms,
                        ['hmac-sha1-96', 'hmac-md5-96']
                    ));
                }
                break;
            case substr($this->server_identifier, 0, 24) == 'SSH-2.0-TurboFTP_SERVER_':
                if (!isset($preferred['server_to_client']['crypt'])) {
                    $s2c_encryption_algorithms = array_values(array_diff(
                        $s2c_encryption_algorithms,
                        ['aes128-gcm@openssh.com', 'aes256-gcm@openssh.com']
                    ));
                }
                if (!isset($preferred['client_to_server']['crypt'])) {
                    $c2s_encryption_algorithms = array_values(array_diff(
                        $c2s_encryption_algorithms,
                        ['aes128-gcm@openssh.com', 'aes256-gcm@openssh.com']
                    ));
                }
        }

        $client_cookie = Random::string(16);

        $kexinit_payload_client = pack('Ca*', NET_SSH2_MSG_KEXINIT, $client_cookie);
        $kexinit_payload_client .= Strings::packSSH2(
            'L10bN',
            $kex_algorithms,
            $server_host_key_algorithms,
            $c2s_encryption_algorithms,
            $s2c_encryption_algorithms,
            $c2s_mac_algorithms,
            $s2c_mac_algorithms,
            $c2s_compression_algorithms,
            $s2c_compression_algorithms,
            [], // language, client to server
            [], // language, server to client
            false, // first_kex_packet_follows
            0 // reserved for future extension
        );

        if ($kexinit_payload_server === false) {
            $this->send_binary_packet($kexinit_payload_client);

            $this->extra_packets = 0;
            $kexinit_payload_server = $this->get_binary_packet();

            if (
                is_bool($kexinit_payload_server)
                || !strlen($kexinit_payload_server)
                || ord($kexinit_payload_server[0]) != NET_SSH2_MSG_KEXINIT
            ) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
                throw new \UnexpectedValueException('Expected SSH_MSG_KEXINIT');
            }

            $send_kex = false;
        }

        $response = $kexinit_payload_server;
        Strings::shift($response, 1); // skip past the message number (it should be SSH_MSG_KEXINIT)
        $server_cookie = Strings::shift($response, 16);

        list(
            $this->kex_algorithms,
            $this->server_host_key_algorithms,
            $this->encryption_algorithms_client_to_server,
            $this->encryption_algorithms_server_to_client,
            $this->mac_algorithms_client_to_server,
            $this->mac_algorithms_server_to_client,
            $this->compression_algorithms_client_to_server,
            $this->compression_algorithms_server_to_client,
            $this->languages_client_to_server,
            $this->languages_server_to_client,
            $first_kex_packet_follows
        ) = Strings::unpackSSH2('L10C', $response);
        if (in_array('kex-strict-s-v00@openssh.com', $this->kex_algorithms)) {
            if ($this->session_id === false && $this->extra_packets) {
                throw new \UnexpectedValueException('Possible Terrapin Attack detected');
            }
        }

        $this->supported_private_key_algorithms = $this->server_host_key_algorithms;

        if ($send_kex) {
            $this->send_binary_packet($kexinit_payload_client);
        }

        // we need to decide upon the symmetric encryption algorithms before we do the diffie-hellman key exchange

        // we don't initialize any crypto-objects, yet - we do that, later. for now, we need the lengths to make the
        // diffie-hellman key exchange as fast as possible
        $decrypt = self::array_intersect_first($s2c_encryption_algorithms, $this->encryption_algorithms_server_to_client);
        if (!$decrypt || ($decryptKeyLength = $this->encryption_algorithm_to_key_size($decrypt)) === null) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible server to client encryption algorithms found');
        }

        $encrypt = self::array_intersect_first($c2s_encryption_algorithms, $this->encryption_algorithms_client_to_server);
        if (!$encrypt || ($encryptKeyLength = $this->encryption_algorithm_to_key_size($encrypt)) === null) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible client to server encryption algorithms found');
        }

        // through diffie-hellman key exchange a symmetric key is obtained
        $this->kex_algorithm = self::array_intersect_first($kex_algorithms, $this->kex_algorithms);
        if ($this->kex_algorithm === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible key exchange algorithms found');
        }

        $server_host_key_algorithm = self::array_intersect_first($server_host_key_algorithms, $this->server_host_key_algorithms);
        if ($server_host_key_algorithm === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible server host key algorithms found');
        }

        $mac_algorithm_out = self::array_intersect_first($c2s_mac_algorithms, $this->mac_algorithms_client_to_server);
        if ($mac_algorithm_out === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible client to server message authentication algorithms found');
        }

        $mac_algorithm_in = self::array_intersect_first($s2c_mac_algorithms, $this->mac_algorithms_server_to_client);
        if ($mac_algorithm_in === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible server to client message authentication algorithms found');
        }

        $compression_map = [
            'none' => self::NET_SSH2_COMPRESSION_NONE,
            'zlib' => self::NET_SSH2_COMPRESSION_ZLIB,
            'zlib@openssh.com' => self::NET_SSH2_COMPRESSION_ZLIB_AT_OPENSSH
        ];

        $compression_algorithm_in = self::array_intersect_first($s2c_compression_algorithms, $this->compression_algorithms_server_to_client);
        if ($compression_algorithm_in === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible server to client compression algorithms found');
        }
        $this->decompress = $compression_map[$compression_algorithm_in];

        $compression_algorithm_out = self::array_intersect_first($c2s_compression_algorithms, $this->compression_algorithms_client_to_server);
        if ($compression_algorithm_out === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED);
            throw new NoSupportedAlgorithmsException('No compatible client to server compression algorithms found');
        }
        $this->compress = $compression_map[$compression_algorithm_out];

        switch ($this->kex_algorithm) {
            case 'diffie-hellman-group15-sha512':
            case 'diffie-hellman-group16-sha512':
            case 'diffie-hellman-group17-sha512':
            case 'diffie-hellman-group18-sha512':
            case 'ecdh-sha2-nistp521':
                $kexHash = new Hash('sha512');
                break;
            case 'ecdh-sha2-nistp384':
                $kexHash = new Hash('sha384');
                break;
            case 'diffie-hellman-group-exchange-sha256':
            case 'diffie-hellman-group14-sha256':
            case 'ecdh-sha2-nistp256':
            case 'curve25519-sha256@libssh.org':
            case 'curve25519-sha256':
                $kexHash = new Hash('sha256');
                break;
            default:
                $kexHash = new Hash('sha1');
        }

        // Only relevant in diffie-hellman-group-exchange-sha{1,256}, otherwise empty.

        $exchange_hash_rfc4419 = '';

        if (strpos($this->kex_algorithm, 'curve25519-sha256') === 0 || strpos($this->kex_algorithm, 'ecdh-sha2-nistp') === 0) {
            $curve = strpos($this->kex_algorithm, 'curve25519-sha256') === 0 ?
                'Curve25519' :
                substr($this->kex_algorithm, 10);
            $ourPrivate = EC::createKey($curve);
            $ourPublicBytes = $ourPrivate->getPublicKey()->getEncodedCoordinates();
            $clientKexInitMessage = 'NET_SSH2_MSG_KEX_ECDH_INIT';
            $serverKexReplyMessage = 'NET_SSH2_MSG_KEX_ECDH_REPLY';
        } else {
            if (strpos($this->kex_algorithm, 'diffie-hellman-group-exchange') === 0) {
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
                $this->send_binary_packet($packet);
                $this->updateLogHistory('UNKNOWN (34)', 'NET_SSH2_MSG_KEXDH_GEX_REQUEST');

                $response = $this->get_binary_packet();

                list($type, $primeBytes, $gBytes) = Strings::unpackSSH2('Css', $response);
                if ($type != NET_SSH2_MSG_KEXDH_GEX_GROUP) {
                    $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
                    throw new \UnexpectedValueException('Expected SSH_MSG_KEX_DH_GEX_GROUP');
                }
                $this->updateLogHistory('NET_SSH2_MSG_KEXDH_REPLY', 'NET_SSH2_MSG_KEXDH_GEX_GROUP');
                $prime = new BigInteger($primeBytes, -256);
                $g = new BigInteger($gBytes, -256);

                $exchange_hash_rfc4419 = $dh_group_sizes_packed . Strings::packSSH2(
                    'ss',
                    $primeBytes,
                    $gBytes
                );

                $params = DH::createParameters($prime, $g);
                $clientKexInitMessage = 'NET_SSH2_MSG_KEXDH_GEX_INIT';
                $serverKexReplyMessage = 'NET_SSH2_MSG_KEXDH_GEX_REPLY';
            } else {
                $params = DH::createParameters($this->kex_algorithm);
                $clientKexInitMessage = 'NET_SSH2_MSG_KEXDH_INIT';
                $serverKexReplyMessage = 'NET_SSH2_MSG_KEXDH_REPLY';
            }

            $keyLength = min($kexHash->getLengthInBytes(), max($encryptKeyLength, $decryptKeyLength));

            $ourPrivate = DH::createKey($params, 16 * $keyLength); // 2 * 8 * $keyLength
            $ourPublic = $ourPrivate->getPublicKey()->toBigInteger();
            $ourPublicBytes = $ourPublic->toBytes(true);
        }

        $data = pack('CNa*', constant($clientKexInitMessage), strlen($ourPublicBytes), $ourPublicBytes);

        $this->send_binary_packet($data);

        switch ($clientKexInitMessage) {
            case 'NET_SSH2_MSG_KEX_ECDH_INIT':
                $this->updateLogHistory('NET_SSH2_MSG_KEXDH_INIT', 'NET_SSH2_MSG_KEX_ECDH_INIT');
                break;
            case 'NET_SSH2_MSG_KEXDH_GEX_INIT':
                $this->updateLogHistory('UNKNOWN (32)', 'NET_SSH2_MSG_KEXDH_GEX_INIT');
        }

        $response = $this->get_binary_packet();

        list(
            $type,
            $server_public_host_key,
            $theirPublicBytes,
            $this->signature
        ) = Strings::unpackSSH2('Csss', $response);

        if ($type != constant($serverKexReplyMessage)) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
            throw new \UnexpectedValueException("Expected $serverKexReplyMessage");
        }
        switch ($serverKexReplyMessage) {
            case 'NET_SSH2_MSG_KEX_ECDH_REPLY':
                $this->updateLogHistory('NET_SSH2_MSG_KEXDH_REPLY', 'NET_SSH2_MSG_KEX_ECDH_REPLY');
                break;
            case 'NET_SSH2_MSG_KEXDH_GEX_REPLY':
                $this->updateLogHistory('UNKNOWN (33)', 'NET_SSH2_MSG_KEXDH_GEX_REPLY');
        }

        $this->server_public_host_key = $server_public_host_key;
        list($public_key_format) = Strings::unpackSSH2('s', $server_public_host_key);
        if (strlen($this->signature) < 4) {
            throw new \LengthException('The signature needs at least four bytes');
        }
        $temp = unpack('Nlength', substr($this->signature, 0, 4));
        $this->signature_format = substr($this->signature, 4, $temp['length']);

        $keyBytes = DH::computeSecret($ourPrivate, $theirPublicBytes);
        if (($keyBytes & "\xFF\x80") === "\x00\x00") {
            $keyBytes = substr($keyBytes, 1);
        } elseif (($keyBytes[0] & "\x80") === "\x80") {
            $keyBytes = "\0$keyBytes";
        }

        $this->exchange_hash = Strings::packSSH2(
            's5',
            $this->identifier,
            $this->server_identifier,
            $kexinit_payload_client,
            $kexinit_payload_server,
            $this->server_public_host_key
        );
        $this->exchange_hash .= $exchange_hash_rfc4419;
        $this->exchange_hash .= Strings::packSSH2(
            's3',
            $ourPublicBytes,
            $theirPublicBytes,
            $keyBytes
        );

        $this->exchange_hash = $kexHash->hash($this->exchange_hash);

        if ($this->session_id === false) {
            $this->session_id = $this->exchange_hash;
        }

        switch ($server_host_key_algorithm) {
            case 'rsa-sha2-256':
            case 'rsa-sha2-512':
            //case 'ssh-rsa':
                $expected_key_format = 'ssh-rsa';
                break;
            default:
                $expected_key_format = $server_host_key_algorithm;
        }
        if ($public_key_format != $expected_key_format || $this->signature_format != $server_host_key_algorithm) {
            switch (true) {
                case $this->signature_format == $server_host_key_algorithm:
                case $server_host_key_algorithm != 'rsa-sha2-256' && $server_host_key_algorithm != 'rsa-sha2-512':
                case $this->signature_format != 'ssh-rsa':
                    $this->disconnect_helper(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
                    throw new \RuntimeException('Server Host Key Algorithm Mismatch (' . $this->signature_format . ' vs ' . $server_host_key_algorithm . ')');
            }
        }

        $packet = pack('C', NET_SSH2_MSG_NEWKEYS);
        $this->send_binary_packet($packet);

        $response = $this->get_binary_packet();

        if ($response === false) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_CONNECTION_LOST);
            throw new ConnectionClosedException('Connection closed by server');
        }

        list($type) = Strings::unpackSSH2('C', $response);
        if ($type != NET_SSH2_MSG_NEWKEYS) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
            throw new \UnexpectedValueException('Expected SSH_MSG_NEWKEYS');
        }

        if (in_array('kex-strict-s-v00@openssh.com', $this->kex_algorithms)) {
            $this->get_seq_no = $this->send_seq_no = 0;
        }

        $keyBytes = pack('Na*', strlen($keyBytes), $keyBytes);

        $this->encrypt = self::encryption_algorithm_to_crypt_instance($encrypt);
        if ($this->encrypt) {
            if (self::$crypto_engine) {
                $this->encrypt->setPreferredEngine(self::$crypto_engine);
            }
            if ($this->encrypt->getBlockLengthInBytes()) {
                $this->encrypt_block_size = $this->encrypt->getBlockLengthInBytes();
            }
            $this->encrypt->disablePadding();

            if ($this->encrypt->usesIV()) {
                $iv = $kexHash->hash($keyBytes . $this->exchange_hash . 'A' . $this->session_id);
                while ($this->encrypt_block_size > strlen($iv)) {
                    $iv .= $kexHash->hash($keyBytes . $this->exchange_hash . $iv);
                }
                $this->encrypt->setIV(substr($iv, 0, $this->encrypt_block_size));
            }

            switch ($encrypt) {
                case 'aes128-gcm@openssh.com':
                case 'aes256-gcm@openssh.com':
                    $nonce = $kexHash->hash($keyBytes . $this->exchange_hash . 'A' . $this->session_id);
                    $this->encryptFixedPart = substr($nonce, 0, 4);
                    $this->encryptInvocationCounter = substr($nonce, 4, 8);
                    // fall-through
                case 'chacha20-poly1305@openssh.com':
                    break;
                default:
                    $this->encrypt->enableContinuousBuffer();
            }

            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'C' . $this->session_id);
            while ($encryptKeyLength > strlen($key)) {
                $key .= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            switch ($encrypt) {
                case 'chacha20-poly1305@openssh.com':
                    $encryptKeyLength = 32;
                    $this->lengthEncrypt = self::encryption_algorithm_to_crypt_instance($encrypt);
                    $this->lengthEncrypt->setKey(substr($key, 32, 32));
            }
            $this->encrypt->setKey(substr($key, 0, $encryptKeyLength));
            $this->encryptName = $encrypt;
        }

        $this->decrypt = self::encryption_algorithm_to_crypt_instance($decrypt);
        if ($this->decrypt) {
            if (self::$crypto_engine) {
                $this->decrypt->setPreferredEngine(self::$crypto_engine);
            }
            if ($this->decrypt->getBlockLengthInBytes()) {
                $this->decrypt_block_size = $this->decrypt->getBlockLengthInBytes();
            }
            $this->decrypt->disablePadding();

            if ($this->decrypt->usesIV()) {
                $iv = $kexHash->hash($keyBytes . $this->exchange_hash . 'B' . $this->session_id);
                while ($this->decrypt_block_size > strlen($iv)) {
                    $iv .= $kexHash->hash($keyBytes . $this->exchange_hash . $iv);
                }
                $this->decrypt->setIV(substr($iv, 0, $this->decrypt_block_size));
            }

            switch ($decrypt) {
                case 'aes128-gcm@openssh.com':
                case 'aes256-gcm@openssh.com':
                    // see https://tools.ietf.org/html/rfc5647#section-7.1
                    $nonce = $kexHash->hash($keyBytes . $this->exchange_hash . 'B' . $this->session_id);
                    $this->decryptFixedPart = substr($nonce, 0, 4);
                    $this->decryptInvocationCounter = substr($nonce, 4, 8);
                    // fall-through
                case 'chacha20-poly1305@openssh.com':
                    break;
                default:
                    $this->decrypt->enableContinuousBuffer();
            }

            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'D' . $this->session_id);
            while ($decryptKeyLength > strlen($key)) {
                $key .= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            switch ($decrypt) {
                case 'chacha20-poly1305@openssh.com':
                    $decryptKeyLength = 32;
                    $this->lengthDecrypt = self::encryption_algorithm_to_crypt_instance($decrypt);
                    $this->lengthDecrypt->setKey(substr($key, 32, 32));
            }
            $this->decrypt->setKey(substr($key, 0, $decryptKeyLength));
            $this->decryptName = $decrypt;
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

        if (!$this->encrypt->usesNonce()) {
            list($this->hmac_create, $createKeyLength) = self::mac_algorithm_to_hash_instance($mac_algorithm_out);
        } else {
            $this->hmac_create = new \stdClass();
            $this->hmac_create_name = $mac_algorithm_out;
            //$mac_algorithm_out = 'none';
            $createKeyLength = 0;
        }

        if ($this->hmac_create instanceof Hash) {
            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'E' . $this->session_id);
            while ($createKeyLength > strlen($key)) {
                $key .= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            $this->hmac_create->setKey(substr($key, 0, $createKeyLength));
            $this->hmac_create_name = $mac_algorithm_out;
            $this->hmac_create_etm = preg_match('#-etm@openssh\.com$#', $mac_algorithm_out);
        }

        if (!$this->decrypt->usesNonce()) {
            list($this->hmac_check, $checkKeyLength) = self::mac_algorithm_to_hash_instance($mac_algorithm_in);
            $this->hmac_size = $this->hmac_check->getLengthInBytes();
        } else {
            $this->hmac_check = new \stdClass();
            $this->hmac_check_name = $mac_algorithm_in;
            //$mac_algorithm_in = 'none';
            $checkKeyLength = 0;
            $this->hmac_size = 0;
        }

        if ($this->hmac_check instanceof Hash) {
            $key = $kexHash->hash($keyBytes . $this->exchange_hash . 'F' . $this->session_id);
            while ($checkKeyLength > strlen($key)) {
                $key .= $kexHash->hash($keyBytes . $this->exchange_hash . $key);
            }
            $this->hmac_check->setKey(substr($key, 0, $checkKeyLength));
            $this->hmac_check_name = $mac_algorithm_in;
            $this->hmac_check_etm = preg_match('#-etm@openssh\.com$#', $mac_algorithm_in);
        }

        $this->regenerate_compression_context = $this->regenerate_decompression_context = true;

        return true;
    }

    /**
     * Maps an encryption algorithm name to the number of key bytes.
     *
     * @param string $algorithm Name of the encryption algorithm
     * @return int|null Number of bytes as an integer or null for unknown
     */
    private function encryption_algorithm_to_key_size($algorithm)
    {
        if ($this->bad_key_size_fix && self::bad_algorithm_candidate($algorithm)) {
            return 16;
        }

        switch ($algorithm) {
            case 'none':
                return 0;
            case 'aes128-gcm@openssh.com':
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
            case 'aes256-gcm@openssh.com':
            case 'aes256-cbc':
            case 'aes256-ctr':
            case 'arcfour256':
            case 'twofish-cbc':
            case 'twofish256-cbc':
            case 'twofish256-ctr':
                return 32;
            case 'chacha20-poly1305@openssh.com':
                return 64;
        }
        return null;
    }

    /**
     * Maps an encryption algorithm name to an instance of a subclass of
     * \phpseclib3\Crypt\Common\SymmetricKey.
     *
     * @param string $algorithm Name of the encryption algorithm
     * @return SymmetricKey|null
     */
    private static function encryption_algorithm_to_crypt_instance($algorithm)
    {
        switch ($algorithm) {
            case '3des-cbc':
                return new TripleDES('cbc');
            case '3des-ctr':
                return new TripleDES('ctr');
            case 'aes256-cbc':
            case 'aes192-cbc':
            case 'aes128-cbc':
                return new Rijndael('cbc');
            case 'aes256-ctr':
            case 'aes192-ctr':
            case 'aes128-ctr':
                return new Rijndael('ctr');
            case 'blowfish-cbc':
                return new Blowfish('cbc');
            case 'blowfish-ctr':
                return new Blowfish('ctr');
            case 'twofish128-cbc':
            case 'twofish192-cbc':
            case 'twofish256-cbc':
            case 'twofish-cbc':
                return new Twofish('cbc');
            case 'twofish128-ctr':
            case 'twofish192-ctr':
            case 'twofish256-ctr':
                return new Twofish('ctr');
            case 'arcfour':
            case 'arcfour128':
            case 'arcfour256':
                return new RC4();
            case 'aes128-gcm@openssh.com':
            case 'aes256-gcm@openssh.com':
                return new Rijndael('gcm');
            case 'chacha20-poly1305@openssh.com':
                return new ChaCha20();
        }
        return null;
    }

    /**
     * Maps an encryption algorithm name to an instance of a subclass of
     * \phpseclib3\Crypt\Hash.
     *
     * @param string $algorithm Name of the encryption algorithm
     * @return array{Hash, int}|null
     */
    private static function mac_algorithm_to_hash_instance($algorithm)
    {
        switch ($algorithm) {
            case 'umac-64@openssh.com':
            case 'umac-64-etm@openssh.com':
                return [new Hash('umac-64'), 16];
            case 'umac-128@openssh.com':
            case 'umac-128-etm@openssh.com':
                return [new Hash('umac-128'), 16];
            case 'hmac-sha2-512':
            case 'hmac-sha2-512-etm@openssh.com':
                return [new Hash('sha512'), 64];
            case 'hmac-sha2-256':
            case 'hmac-sha2-256-etm@openssh.com':
                return [new Hash('sha256'), 32];
            case 'hmac-sha1':
            case 'hmac-sha1-etm@openssh.com':
                return [new Hash('sha1'), 20];
            case 'hmac-sha1-96':
                return [new Hash('sha1-96'), 20];
            case 'hmac-md5':
                return [new Hash('md5'), 16];
            case 'hmac-md5-96':
                return [new Hash('md5-96'), 16];
        }
    }

    /**
     * Tests whether or not proposed algorithm has a potential for issues
     *
     * @link https://www.chiark.greenend.org.uk/~sgtatham/putty/wishlist/ssh2-aesctr-openssh.html
     * @link https://bugzilla.mindrot.org/show_bug.cgi?id=1291
     * @param string $algorithm Name of the encryption algorithm
     * @return bool
     */
    private static function bad_algorithm_candidate($algorithm)
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
     * The $password parameter can be a plaintext password, a \phpseclib3\Crypt\RSA|EC|DSA object, a \phpseclib3\System\SSH\Agent object or an array
     *
     * @param string $username
     * @param string|PrivateKey|array[]|Agent|null ...$args
     * @return bool
     * @see self::_login()
     */
    public function login($username, ...$args)
    {
        if (!$this->login_credentials_finalized) {
            $this->auth[] = func_get_args();
        }

        // try logging with 'none' as an authentication method first since that's what
        // PuTTY does
        if (substr($this->server_identifier, 0, 15) != 'SSH-2.0-CoreFTP' && $this->auth_methods_to_continue === null) {
            if ($this->sublogin($username)) {
                return true;
            }
            if (!count($args)) {
                return false;
            }
        }
        return $this->sublogin($username, ...$args);
    }

    /**
     * Login Helper
     *
     * @param string $username
     * @param string|PrivateKey|array[]|Agent|null ...$args
     * @return bool
     * @see self::_login_helper()
     */
    protected function sublogin($username, ...$args)
    {
        if (!($this->bitmap & self::MASK_CONSTRUCTOR)) {
            $this->connect();
        }

        if (empty($args)) {
            return $this->login_helper($username);
        }

        foreach ($args as $arg) {
            switch (true) {
                case $arg instanceof PublicKey:
                    throw new \UnexpectedValueException('A PublicKey object was passed to the login method instead of a PrivateKey object');
                case $arg instanceof PrivateKey:
                case $arg instanceof Agent:
                case is_array($arg):
                case Strings::is_stringable($arg):
                    break;
                default:
                    throw new \UnexpectedValueException('$password needs to either be an instance of \phpseclib3\Crypt\Common\PrivateKey, \System\SSH\Agent, an array or a string');
            }
        }

        while (count($args)) {
            if (!$this->auth_methods_to_continue || !$this->smartMFA) {
                $newargs = $args;
                $args = [];
            } else {
                $newargs = [];
                foreach ($this->auth_methods_to_continue as $method) {
                    switch ($method) {
                        case 'publickey':
                            foreach ($args as $key => $arg) {
                                if ($arg instanceof PrivateKey || $arg instanceof Agent) {
                                    $newargs[] = $arg;
                                    unset($args[$key]);
                                    break;
                                }
                            }
                            break;
                        case 'keyboard-interactive':
                            $hasArray = $hasString = false;
                            foreach ($args as $arg) {
                                if ($hasArray || is_array($arg)) {
                                    $hasArray = true;
                                    break;
                                }
                                if ($hasString || Strings::is_stringable($arg)) {
                                    $hasString = true;
                                    break;
                                }
                            }
                            if ($hasArray && $hasString) {
                                foreach ($args as $key => $arg) {
                                    if (is_array($arg)) {
                                        $newargs[] = $arg;
                                        break 2;
                                    }
                                }
                            }
                            // fall-through
                        case 'password':
                            foreach ($args as $key => $arg) {
                                $newargs[] = $arg;
                                unset($args[$key]);
                                break;
                            }
                    }
                }
            }

            if (!count($newargs)) {
                return false;
            }

            foreach ($newargs as $arg) {
                if ($this->login_helper($username, $arg)) {
                    $this->login_credentials_finalized = true;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Login Helper
     *
     * {@internal It might be worthwhile, at some point, to protect against {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
     *           by sending dummy SSH_MSG_IGNORE messages.}
     *
     * @param string $username
     * @param string|AsymmetricKey|array[]|Agent|null ...$args
     * @return bool
     * @throws \UnexpectedValueException on receipt of unexpected packets
     * @throws \RuntimeException on other errors
     */
    private function login_helper($username, $password = null)
    {
        if (!($this->bitmap & self::MASK_CONNECTED)) {
            return false;
        }

        if (!($this->bitmap & self::MASK_LOGIN_REQ)) {
            $packet = Strings::packSSH2('Cs', NET_SSH2_MSG_SERVICE_REQUEST, 'ssh-userauth');
            $this->send_binary_packet($packet);

            try {
                $response = $this->get_binary_packet();
            } catch (InvalidPacketLengthException $e) {
                // the first opportunity to encounter the "bad key size" error
                if (!$this->bad_key_size_fix && $this->decryptName != null && self::bad_algorithm_candidate($this->decryptName)) {
                    // bad_key_size_fix is only ever re-assigned to true here
                    // retry the connection with that new setting but we'll
                    // only try it once.
                    $this->bad_key_size_fix = true;
                    return $this->reconnect();
                }
                throw $e;
            } catch (\Exception $e) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_CONNECTION_LOST);
                throw $e;
            }

            list($type) = Strings::unpackSSH2('C', $response);
            list($service) = Strings::unpackSSH2('s', $response);

            if ($type != NET_SSH2_MSG_SERVICE_ACCEPT || $service != 'ssh-userauth') {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
                throw new \UnexpectedValueException('Expected SSH_MSG_SERVICE_ACCEPT');
            }
            $this->bitmap |= self::MASK_LOGIN_REQ;
        }

        if (strlen($this->last_interactive_response)) {
            return !Strings::is_stringable($password) && !is_array($password) ? false : $this->keyboard_interactive_process($password);
        }

        if ($password instanceof PrivateKey) {
            return $this->privatekey_login($username, $password);
        }

        if ($password instanceof Agent) {
            return $this->ssh_agent_login($username, $password);
        }

        if (is_array($password)) {
            if ($this->keyboard_interactive_login($username, $password)) {
                $this->bitmap |= self::MASK_LOGIN;
                return true;
            }
            return false;
        }

        if (!isset($password)) {
            $packet = Strings::packSSH2(
                'Cs3',
                NET_SSH2_MSG_USERAUTH_REQUEST,
                $username,
                'ssh-connection',
                'none'
            );

            $this->send_binary_packet($packet);

            $response = $this->get_binary_packet();

            list($type) = Strings::unpackSSH2('C', $response);
            switch ($type) {
                case NET_SSH2_MSG_USERAUTH_SUCCESS:
                    $this->bitmap |= self::MASK_LOGIN;
                    return true;
                case NET_SSH2_MSG_USERAUTH_FAILURE:
                    list($auth_methods) = Strings::unpackSSH2('L', $response);
                    $this->auth_methods_to_continue = $auth_methods;
                    // fall-through
                default:
                    return false;
            }
        }

        $packet = Strings::packSSH2(
            'Cs3bs',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            $username,
            'ssh-connection',
            'password',
            false,
            $password
        );

        // remove the username and password from the logged packet
        if (!defined('NET_SSH2_LOGGING')) {
            $logged = null;
        } else {
            $logged = Strings::packSSH2(
                'Cs3bs',
                NET_SSH2_MSG_USERAUTH_REQUEST,
                $username,
                'ssh-connection',
                'password',
                false,
                'password'
            );
        }

        $this->send_binary_packet($packet, $logged);

        $response = $this->get_binary_packet();
        if ($response === false) {
            return false;
        }
        list($type) = Strings::unpackSSH2('C', $response);
        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ: // in theory, the password can be changed
                $this->updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_PASSWD_CHANGEREQ');

                list($message) = Strings::unpackSSH2('s', $response);
                $this->errors[] = 'SSH_MSG_USERAUTH_PASSWD_CHANGEREQ: ' . $message;

                return $this->disconnect_helper(NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER);
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                // can we use keyboard-interactive authentication?  if not then either the login is bad or the server employees
                // multi-factor authentication
                list($auth_methods, $partial_success) = Strings::unpackSSH2('Lb', $response);
                $this->auth_methods_to_continue = $auth_methods;
                if (!$partial_success && in_array('keyboard-interactive', $auth_methods)) {
                    if ($this->keyboard_interactive_login($username, $password)) {
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
     * @param string|array $password
     * @return bool
     */
    private function keyboard_interactive_login($username, $password)
    {
        $packet = Strings::packSSH2(
            'Cs5',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            $username,
            'ssh-connection',
            'keyboard-interactive',
            '', // language tag
            '' // submethods
        );
        $this->send_binary_packet($packet);

        return $this->keyboard_interactive_process($password);
    }

    /**
     * Handle the keyboard-interactive requests / responses.
     *
     * @param string|array ...$responses
     * @return bool
     * @throws \RuntimeException on connection error
     */
    private function keyboard_interactive_process(...$responses)
    {
        if (strlen($this->last_interactive_response)) {
            $response = $this->last_interactive_response;
        } else {
            $orig = $response = $this->get_binary_packet();
        }

        list($type) = Strings::unpackSSH2('C', $response);
        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_INFO_REQUEST:
                list(
                    , // name; may be empty
                    , // instruction; may be empty
                    , // language tag; may be empty
                    $num_prompts
                ) = Strings::unpackSSH2('s3N', $response);

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
                        list(
                            $prompt, // prompt - ie. "Password: "; must not be empty
                            // echo
                        ) = Strings::unpackSSH2('sC', $response);
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
                    $this->updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_INFO_REQUEST');
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
                    $packet .= Strings::packSSH2('s', $responses[$i]);
                    $logged .= Strings::packSSH2('s', 'dummy-answer');
                }

                $this->send_binary_packet($packet, $logged);

                $this->updateLogHistory('UNKNOWN (61)', 'NET_SSH2_MSG_USERAUTH_INFO_RESPONSE');

                /*
                   After receiving the response, the server MUST send either an
                   SSH_MSG_USERAUTH_SUCCESS, SSH_MSG_USERAUTH_FAILURE, or another
                   SSH_MSG_USERAUTH_INFO_REQUEST message.
                */
                // maybe phpseclib should force close the connection after x request / responses?  unless something like that is done
                // there could be an infinite loop of request / responses.
                return $this->keyboard_interactive_process();
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                return true;
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                list($auth_methods) = Strings::unpackSSH2('L', $response);
                $this->auth_methods_to_continue = $auth_methods;
                return false;
        }

        return false;
    }

    /**
     * Login with an ssh-agent provided key
     *
     * @param string $username
     * @param \phpseclib3\System\SSH\Agent $agent
     * @return bool
     */
    private function ssh_agent_login($username, Agent $agent)
    {
        $this->agent = $agent;
        $keys = $agent->requestIdentities();
        $orig_algorithms = $this->supported_private_key_algorithms;
        foreach ($keys as $key) {
            if ($this->privatekey_login($username, $key)) {
                return true;
            }
            $this->supported_private_key_algorithms = $orig_algorithms;
        }

        return false;
    }

    /**
     * Login with an RSA private key
     *
     * {@internal It might be worthwhile, at some point, to protect against {@link http://tools.ietf.org/html/rfc4251#section-9.3.9 traffic analysis}
     *           by sending dummy SSH_MSG_IGNORE messages.}
     *
     * @param string $username
     * @param \phpseclib3\Crypt\Common\PrivateKey $privatekey
     * @return bool
     * @throws \RuntimeException on connection error
     */
    private function privatekey_login($username, PrivateKey $privatekey)
    {
        $publickey = $privatekey->getPublicKey();

        if ($publickey instanceof RSA) {
            $privatekey = $privatekey->withPadding(RSA::SIGNATURE_PKCS1);
            $algos = ['rsa-sha2-256', 'rsa-sha2-512', 'ssh-rsa'];
            if (isset($this->preferred['hostkey'])) {
                $algos = array_intersect($algos, $this->preferred['hostkey']);
            }
            $algo = self::array_intersect_first($algos, $this->supported_private_key_algorithms);
            switch ($algo) {
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
        } elseif ($publickey instanceof EC) {
            $privatekey = $privatekey->withSignatureFormat('SSH2');
            $curveName = $privatekey->getCurve();
            switch ($curveName) {
                case 'Ed25519':
                    $hash = 'sha512';
                    $signatureType = 'ssh-ed25519';
                    break;
                case 'secp256r1': // nistp256
                    $hash = 'sha256';
                    $signatureType = 'ecdsa-sha2-nistp256';
                    break;
                case 'secp384r1': // nistp384
                    $hash = 'sha384';
                    $signatureType = 'ecdsa-sha2-nistp384';
                    break;
                case 'secp521r1': // nistp521
                    $hash = 'sha512';
                    $signatureType = 'ecdsa-sha2-nistp521';
                    break;
                default:
                    if (is_array($curveName)) {
                        throw new UnsupportedCurveException('Specified Curves are not supported by SSH2');
                    }
                    throw new UnsupportedCurveException('Named Curve of ' . $curveName . ' is not supported by phpseclib3\'s SSH2 implementation');
            }
        } elseif ($publickey instanceof DSA) {
            $privatekey = $privatekey->withSignatureFormat('SSH2');
            $hash = 'sha1';
            $signatureType = 'ssh-dss';
        } else {
            throw new UnsupportedAlgorithmException('Please use either an RSA key, an EC one or a DSA key');
        }

        $publickeyStr = $publickey->toString('OpenSSH', ['binary' => true]);

        $part1 = Strings::packSSH2(
            'Csss',
            NET_SSH2_MSG_USERAUTH_REQUEST,
            $username,
            'ssh-connection',
            'publickey'
        );
        $part2 = Strings::packSSH2('ss', $signatureType, $publickeyStr);

        $packet = $part1 . chr(0) . $part2;
        $this->send_binary_packet($packet);

        $response = $this->get_binary_packet();

        list($type) = Strings::unpackSSH2('C', $response);
        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                list($auth_methods) = Strings::unpackSSH2('L', $response);
                if (in_array('publickey', $auth_methods) && substr($signatureType, 0, 9) == 'rsa-sha2-') {
                    $this->supported_private_key_algorithms = array_diff($this->supported_private_key_algorithms, ['rsa-sha2-256', 'rsa-sha2-512']);
                    return $this->privatekey_login($username, $privatekey);
                }
                $this->auth_methods_to_continue = $auth_methods;
                $this->errors[] = 'SSH_MSG_USERAUTH_FAILURE';
                return false;
            case NET_SSH2_MSG_USERAUTH_PK_OK:
                // we'll just take it on faith that the public key blob and the public key algorithm name are as
                // they should be
                $this->updateLogHistory('UNKNOWN (60)', 'NET_SSH2_MSG_USERAUTH_PK_OK');
                break;
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                $this->bitmap |= self::MASK_LOGIN;
                return true;
            default:
                $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                throw new ConnectionClosedException('Unexpected response to publickey authentication pt 1');
        }

        $packet = $part1 . chr(1) . $part2;
        $privatekey = $privatekey->withHash($hash);
        $signature = $privatekey->sign(Strings::packSSH2('s', $this->session_id) . $packet);
        if ($publickey instanceof RSA) {
            $signature = Strings::packSSH2('ss', $signatureType, $signature);
        }
        $packet .= Strings::packSSH2('s', $signature);

        $this->send_binary_packet($packet);

        $response = $this->get_binary_packet();

        list($type) = Strings::unpackSSH2('C', $response);
        switch ($type) {
            case NET_SSH2_MSG_USERAUTH_FAILURE:
                // either the login is bad or the server employs multi-factor authentication
                list($auth_methods) = Strings::unpackSSH2('L', $response);
                $this->auth_methods_to_continue = $auth_methods;
                return false;
            case NET_SSH2_MSG_USERAUTH_SUCCESS:
                $this->bitmap |= self::MASK_LOGIN;
                return true;
        }

        $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
        throw new ConnectionClosedException('Unexpected response to publickey authentication pt 2');
    }

    /**
     * Return the currently configured timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set Timeout
     *
     * $ssh->exec('ping 127.0.0.1'); on a Linux host will never return and will run indefinitely.  setTimeout() makes it so it'll timeout.
     * Setting $timeout to false or 0 will mean there is no timeout.
     *
     * @param mixed $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $this->curTimeout = $timeout;
    }

    /**
     * Set Keep Alive
     *
     * Sends an SSH2_MSG_IGNORE message every x seconds, if x is a positive non-zero number.
     *
     * @param int $interval
     */
    public function setKeepAlive($interval)
    {
        $this->keepAlive = $interval;
    }

    /**
     * Get the output from stdError
     *
     */
    public function getStdError()
    {
        return $this->stdErrorLog;
    }

    /**
     * Execute Command
     *
     * If $callback is set to false then \phpseclib3\Net\SSH2::get_channel_packet(self::CHANNEL_EXEC) will need to be called manually.
     * In all likelihood, this is not a feature you want to be taking advantage of.
     *
     * @param string $command
     * @return string|bool
     * @psalm-return ($callback is callable ? bool : string|bool)
     * @throws \RuntimeException on connection error
     */
    public function exec($command, callable $callback = null)
    {
        $this->curTimeout = $this->timeout;
        $this->is_timeout = false;
        $this->stdErrorLog = '';

        if (!$this->isAuthenticated()) {
            return false;
        }

        //if ($this->isPTYOpen()) {
        //    throw new \RuntimeException('If you want to run multiple exec()\'s you will need to disable (and re-enable if appropriate) a PTY for each one.');
        //}

        $this->open_channel(self::CHANNEL_EXEC);

        if ($this->request_pty === true) {
            $terminal_modes = pack('C', NET_SSH2_TTY_OP_END);
            $packet = Strings::packSSH2(
                'CNsCsN4s',
                NET_SSH2_MSG_CHANNEL_REQUEST,
                $this->server_channels[self::CHANNEL_EXEC],
                'pty-req',
                1,
                $this->term,
                $this->windowColumns,
                $this->windowRows,
                0,
                0,
                $terminal_modes
            );

            $this->send_binary_packet($packet);

            $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_REQUEST;
            if (!$this->get_channel_packet(self::CHANNEL_EXEC)) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                throw new \RuntimeException('Unable to request pseudo-terminal');
            }
        }

        // sending a pty-req SSH_MSG_CHANNEL_REQUEST message is unnecessary and, in fact, in most cases, slows things
        // down.  the one place where it might be desirable is if you're doing something like \phpseclib3\Net\SSH2::exec('ping localhost &').
        // with a pty-req SSH_MSG_CHANNEL_REQUEST, exec() will return immediately and the ping process will then
        // then immediately terminate.  without such a request exec() will loop indefinitely.  the ping process won't end but
        // neither will your script.

        // although, in theory, the size of SSH_MSG_CHANNEL_REQUEST could exceed the maximum packet size established by
        // SSH_MSG_CHANNEL_OPEN_CONFIRMATION, RFC4254#section-5.1 states that the "maximum packet size" refers to the
        // "maximum size of an individual data packet". ie. SSH_MSG_CHANNEL_DATA.  RFC4254#section-5.2 corroborates.
        $packet = Strings::packSSH2(
            'CNsCs',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_EXEC],
            'exec',
            1,
            $command
        );
        $this->send_binary_packet($packet);

        $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_REQUEST;

        if (!$this->get_channel_packet(self::CHANNEL_EXEC)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_DATA;

        if ($this->request_pty === true) {
            $this->channel_id_last_interactive = self::CHANNEL_EXEC;
            return true;
        }

        $output = '';
        while (true) {
            $temp = $this->get_channel_packet(self::CHANNEL_EXEC);
            switch (true) {
                case $temp === true:
                    return is_callable($callback) ? true : $output;
                case $temp === false:
                    return false;
                default:
                    if (is_callable($callback)) {
                        if ($callback($temp) === true) {
                            $this->close_channel(self::CHANNEL_EXEC);
                            return true;
                        }
                    } else {
                        $output .= $temp;
                    }
            }
        }
    }

    /**
     * How many channels are currently open?
     *
     * @return int
     */
    public function getOpenChannelCount()
    {
        return $this->channelCount;
    }

    /**
     * Opens a channel
     *
     * @param string $channel
     * @param bool $skip_extended
     * @return bool
     */
    protected function open_channel($channel, $skip_extended = false)
    {
        if (isset($this->channel_status[$channel]) && $this->channel_status[$channel] != NET_SSH2_MSG_CHANNEL_CLOSE) {
            throw new \RuntimeException('Please close the channel (' . $channel . ') before trying to open it again');
        }

        $this->channelCount++;

        if ($this->channelCount > 1 && $this->errorOnMultipleChannels) {
            throw new \RuntimeException("Ubuntu's OpenSSH from 5.8 to 6.9 doesn't work with multiple channels");
        }

        // RFC4254 defines the (client) window size as "bytes the other party can send before it must wait for the window to
        // be adjusted".  0x7FFFFFFF is, at 2GB, the max size.  technically, it should probably be decremented, but,
        // honestly, if you're transferring more than 2GB, you probably shouldn't be using phpseclib, anyway.
        // see http://tools.ietf.org/html/rfc4254#section-5.2 for more info
        $this->window_size_server_to_client[$channel] = $this->window_size;
        // 0x8000 is the maximum max packet size, per http://tools.ietf.org/html/rfc4253#section-6.1, although since PuTTy
        // uses 0x4000, that's what will be used here, as well.
        $packet_size = 0x4000;

        $packet = Strings::packSSH2(
            'CsN3',
            NET_SSH2_MSG_CHANNEL_OPEN,
            'session',
            $channel,
            $this->window_size_server_to_client[$channel],
            $packet_size
        );

        $this->send_binary_packet($packet);

        $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_OPEN;

        return $this->get_channel_packet($channel, $skip_extended);
    }

    /**
     * Creates an interactive shell
     *
     * Returns bool(true) if the shell was opened.
     * Returns bool(false) if the shell was already open.
     *
     * @see self::isShellOpen()
     * @see self::read()
     * @see self::write()
     * @return bool
     * @throws InsufficientSetupException if not authenticated
     * @throws \UnexpectedValueException on receipt of unexpected packets
     * @throws \RuntimeException on other errors
     */
    public function openShell()
    {
        if (!$this->isAuthenticated()) {
            throw new InsufficientSetupException('Operation disallowed prior to login()');
        }

        $this->open_channel(self::CHANNEL_SHELL);

        $terminal_modes = pack('C', NET_SSH2_TTY_OP_END);
        $packet = Strings::packSSH2(
            'CNsbsN4s',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SHELL],
            'pty-req',
            true, // want reply
            $this->term,
            $this->windowColumns,
            $this->windowRows,
            0,
            0,
            $terminal_modes
        );

        $this->send_binary_packet($packet);

        $this->channel_status[self::CHANNEL_SHELL] = NET_SSH2_MSG_CHANNEL_REQUEST;

        if (!$this->get_channel_packet(self::CHANNEL_SHELL)) {
            throw new \RuntimeException('Unable to request pty');
        }

        $packet = Strings::packSSH2(
            'CNsb',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SHELL],
            'shell',
            true // want reply
        );
        $this->send_binary_packet($packet);

        $response = $this->get_channel_packet(self::CHANNEL_SHELL);
        if ($response === false) {
            throw new \RuntimeException('Unable to request shell');
        }

        $this->channel_status[self::CHANNEL_SHELL] = NET_SSH2_MSG_CHANNEL_DATA;

        $this->channel_id_last_interactive = self::CHANNEL_SHELL;

        $this->bitmap |= self::MASK_SHELL;

        return true;
    }

    /**
     * Return the channel to be used with read(), write(), and reset(), if none were specified
     * @deprecated for lack of transparency in intended channel target, to be potentially replaced
     *             with method which guarantees open-ness of all yielded channels and throws
     *             error for multiple open channels
     * @see self::read()
     * @see self::write()
     * @return int
     */
    private function get_interactive_channel()
    {
        switch (true) {
            case $this->is_channel_status_data(self::CHANNEL_SUBSYSTEM):
                return self::CHANNEL_SUBSYSTEM;
            case $this->is_channel_status_data(self::CHANNEL_EXEC):
                return self::CHANNEL_EXEC;
            default:
                return self::CHANNEL_SHELL;
        }
    }

    /**
     * Indicates the DATA status on the given channel
     *
     * @param int $channel The channel number to evaluate
     * @return bool
     */
    private function is_channel_status_data($channel)
    {
        return isset($this->channel_status[$channel]) && $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_DATA;
    }

    /**
     * Return an available open channel
     *
     * @return int
     */
    private function get_open_channel()
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
     * Request agent forwarding of remote server
     *
     * @return bool
     */
    public function requestAgentForwarding()
    {
        $request_channel = $this->get_open_channel();
        if ($request_channel === false) {
            return false;
        }

        $packet = Strings::packSSH2(
            'CNsC',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[$request_channel],
            'auth-agent-req@openssh.com',
            1
        );

        $this->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_REQUEST;

        $this->send_binary_packet($packet);

        if (!$this->get_channel_packet($request_channel)) {
            return false;
        }

        $this->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_OPEN;

        return true;
    }

    /**
     * Returns the output of an interactive shell
     *
     * Returns when there's a match for $expect, which can take the form of a string literal or,
     * if $mode == self::READ_REGEX, a regular expression.
     *
     * If not specifying a channel, an open interactive channel will be selected, or, if there are
     * no open channels, an interactive shell will be created. If there are multiple open
     * interactive channels, a legacy behavior will apply in which channel selection prioritizes
     * an active subsystem, the exec pty, and, lastly, the shell. If using multiple interactive
     * channels, callers are discouraged from relying on this legacy behavior and should specify
     * the intended channel.
     *
     * @see self::write()
     * @param string $expect
     * @param int $mode One of the self::READ_* constants
     * @param int|null $channel Channel id returned by self::getInteractiveChannelId()
     * @return string|bool|null
     * @throws \RuntimeException on connection error
     * @throws InsufficientSetupException on unexpected channel status, possibly due to closure
     */
    public function read($expect = '', $mode = self::READ_SIMPLE, $channel = null)
    {
        if (!$this->isAuthenticated()) {
            throw new InsufficientSetupException('Operation disallowed prior to login()');
        }

        $this->curTimeout = $this->timeout;
        $this->is_timeout = false;

        if ($channel === null) {
            $channel = $this->get_interactive_channel();
        }

        if (!$this->is_channel_status_data($channel) && empty($this->channel_buffers[$channel])) {
            if ($channel != self::CHANNEL_SHELL) {
                throw new InsufficientSetupException('Data is not available on channel');
            } elseif (!$this->openShell()) {
                throw new \RuntimeException('Unable to initiate an interactive shell session');
            }
        }

        if ($mode == self::READ_NEXT) {
            return $this->get_channel_packet($channel);
        }

        $match = $expect;
        while (true) {
            if ($mode == self::READ_REGEX) {
                preg_match($expect, substr($this->interactiveBuffer, -1024), $matches);
                $match = isset($matches[0]) ? $matches[0] : '';
            }
            $pos = strlen($match) ? strpos($this->interactiveBuffer, $match) : false;
            if ($pos !== false) {
                return Strings::shift($this->interactiveBuffer, $pos + strlen($match));
            }
            $response = $this->get_channel_packet($channel);
            if ($response === true) {
                return Strings::shift($this->interactiveBuffer, strlen($this->interactiveBuffer));
            }

            $this->interactiveBuffer .= $response;
        }
    }

    /**
     * Inputs a command into an interactive shell.
     *
     * If not specifying a channel, an open interactive channel will be selected, or, if there are
     * no open channels, an interactive shell will be created. If there are multiple open
     * interactive channels, a legacy behavior will apply in which channel selection prioritizes
     * an active subsystem, the exec pty, and, lastly, the shell. If using multiple interactive
     * channels, callers are discouraged from relying on this legacy behavior and should specify
     * the intended channel.
     *
     * @see SSH2::read()
     * @param string $cmd
     * @param int|null $channel Channel id returned by self::getInteractiveChannelId()
     * @return void
     * @throws \RuntimeException on connection error
     * @throws InsufficientSetupException on unexpected channel status, possibly due to closure
     */
    public function write($cmd, $channel = null)
    {
        if (!$this->isAuthenticated()) {
            throw new InsufficientSetupException('Operation disallowed prior to login()');
        }

        if ($channel === null) {
            $channel = $this->get_interactive_channel();
        }

        if (!$this->is_channel_status_data($channel)) {
            if ($channel != self::CHANNEL_SHELL) {
                throw new InsufficientSetupException('Data is not available on channel');
            } elseif (!$this->openShell()) {
                throw new \RuntimeException('Unable to initiate an interactive shell session');
            }
        }

        $this->send_channel_packet($channel, $cmd);
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
     */
    public function startSubsystem($subsystem)
    {
        $this->open_channel(self::CHANNEL_SUBSYSTEM);

        $packet = Strings::packSSH2(
            'CNsCs',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SUBSYSTEM],
            'subsystem',
            1,
            $subsystem
        );
        $this->send_binary_packet($packet);

        $this->channel_status[self::CHANNEL_SUBSYSTEM] = NET_SSH2_MSG_CHANNEL_REQUEST;

        if (!$this->get_channel_packet(self::CHANNEL_SUBSYSTEM)) {
            return false;
        }

        $this->channel_status[self::CHANNEL_SUBSYSTEM] = NET_SSH2_MSG_CHANNEL_DATA;

        $this->channel_id_last_interactive = self::CHANNEL_SUBSYSTEM;

        return true;
    }

    /**
     * Stops a subsystem.
     *
     * @see self::startSubsystem()
     * @return bool
     */
    public function stopSubsystem()
    {
        if ($this->isInteractiveChannelOpen(self::CHANNEL_SUBSYSTEM)) {
            $this->close_channel(self::CHANNEL_SUBSYSTEM);
        }
        return true;
    }

    /**
     * Closes a channel
     *
     * If read() timed out you might want to just close the channel and have it auto-restart on the next read() call
     *
     * If not specifying a channel, an open interactive channel will be selected. If there are
     * multiple open interactive channels, a legacy behavior will apply in which channel selection
     * prioritizes an active subsystem, the exec pty, and, lastly, the shell. If using multiple
     * interactive channels, callers are discouraged from relying on this legacy behavior and
     * should specify the intended channel.
     *
     * @param int|null $channel Channel id returned by self::getInteractiveChannelId()
     * @return void
     */
    public function reset($channel = null)
    {
        if ($channel === null) {
            $channel = $this->get_interactive_channel();
        }
        if ($this->isInteractiveChannelOpen($channel)) {
            $this->close_channel($channel);
        }
    }

    /**
     * Is timeout?
     *
     * Did exec() or read() return because they timed out or because they encountered the end?
     *
     */
    public function isTimeout()
    {
        return $this->is_timeout;
    }

    /**
     * Disconnect
     *
     */
    public function disconnect()
    {
        $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
        if (isset($this->realtime_log_file) && is_resource($this->realtime_log_file)) {
            fclose($this->realtime_log_file);
        }
        unset(self::$connections[$this->getResourceId()]);
    }

    /**
     * Destructor.
     *
     * Will be called, automatically, if you're supporting just PHP5.  If you're supporting PHP4, you'll need to call
     * disconnect().
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Is the connection still active?
     *
     * $level has 3x possible values:
     * 0 (default): phpseclib takes a passive approach to see if the connection is still active by calling feof()
     *    on the socket
     * 1: phpseclib takes an active approach to see if the connection is still active by sending an SSH_MSG_IGNORE
     *    packet that doesn't require a response
     * 2: phpseclib takes an active approach to see if the connection is still active by sending an SSH_MSG_CHANNEL_OPEN
     *    packet and imediately trying to close that channel. some routers, in particular, however, will only let you
     *    open one channel, so this approach could yield false positives
     *
     * @param int $level
     * @return bool
     */
    public function isConnected($level = 0)
    {
        if (!is_int($level) || $level < 0 || $level > 2) {
            throw new \InvalidArgumentException('$level must be 0, 1 or 2');
        }

        if ($level == 0) {
            return ($this->bitmap & self::MASK_CONNECTED) && is_resource($this->fsock) && !feof($this->fsock);
        }
        try {
            if ($level == 1) {
                $this->send_binary_packet(pack('CN', NET_SSH2_MSG_IGNORE, 0));
            } else {
                $this->open_channel(self::CHANNEL_KEEP_ALIVE);
                $this->close_channel(self::CHANNEL_KEEP_ALIVE);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Have you successfully been logged in?
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return (bool) ($this->bitmap & self::MASK_LOGIN);
    }

    /**
     * Is the interactive shell active?
     *
     * @return bool
     */
    public function isShellOpen()
    {
        return $this->isInteractiveChannelOpen(self::CHANNEL_SHELL);
    }

    /**
     * Is the exec pty active?
     *
     * @return bool
     */
    public function isPTYOpen()
    {
        return $this->isInteractiveChannelOpen(self::CHANNEL_EXEC);
    }

    /**
     * Is the given interactive channel active?
     *
     * @param int $channel Channel id returned by self::getInteractiveChannelId()
     * @return bool
     */
    public function isInteractiveChannelOpen($channel)
    {
        return $this->isAuthenticated() && $this->is_channel_status_data($channel);
    }

    /**
     * Returns a channel identifier, presently of the last interactive channel opened, regardless of current status.
     * Returns 0 if no interactive channel has been opened.
     *
     * @see self::isInteractiveChannelOpen()
     * @return int
     */
    public function getInteractiveChannelId()
    {
        return $this->channel_id_last_interactive;
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * Inspired by http://php.net/manual/en/mysqli.ping.php
     *
     * @return bool
     */
    public function ping()
    {
        if (!$this->isAuthenticated()) {
            if (!empty($this->auth)) {
                return $this->reconnect();
            }
            return false;
        }

        try {
            $this->open_channel(self::CHANNEL_KEEP_ALIVE);
        } catch (\RuntimeException $e) {
            return $this->reconnect();
        }

        $this->close_channel(self::CHANNEL_KEEP_ALIVE);
        return true;
    }

    /**
     * In situ reconnect method
     *
     * @return boolean
     */
    private function reconnect()
    {
        $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
        $this->connect();
        foreach ($this->auth as $auth) {
            $result = $this->login(...$auth);
        }
        return $result;
    }

    /**
     * Resets a connection for re-use
     */
    protected function reset_connection()
    {
        if (is_resource($this->fsock) && get_resource_type($this->fsock) === 'stream') {
            fclose($this->fsock);
        }
        $this->fsock = null;
        $this->bitmap = 0;
        $this->binary_packet_buffer = null;
        $this->decrypt = $this->encrypt = false;
        $this->decrypt_block_size = $this->encrypt_block_size = 8;
        $this->hmac_check = $this->hmac_create = false;
        $this->hmac_size = false;
        $this->session_id = false;
        $this->last_packet = null;
        $this->get_seq_no = $this->send_seq_no = 0;
        $this->channel_status = [];
        $this->channel_id_last_interactive = 0;
    }

    /**
     * @return int[] second and microsecond stream timeout options based on user-requested timeout and keep-alive, 0 by default
     */
    private function get_stream_timeout()
    {
        $sec = 0;
        $usec = 0;
        if ($this->curTimeout > 0) {
            $sec = (int) floor($this->curTimeout);
            $usec = (int) (1000000 * ($this->curTimeout - $sec));
        }
        if ($this->keepAlive > 0) {
            $elapsed = microtime(true) - $this->last_packet;
            if ($elapsed < $this->curTimeout) {
                $sec = (int) floor($elapsed);
                $usec = (int) (1000000 * ($elapsed - $sec));
            }
        }
        return [$sec, $usec];
    }

    /**
     * Gets Binary Packets
     *
     * See '6. Binary Packet Protocol' of rfc4253 for more info.
     *
     * @see self::_send_binary_packet()
     * @return bool|string
     */
    private function get_binary_packet()
    {
        if (!is_resource($this->fsock)) {
            throw new \InvalidArgumentException('fsock is not a resource.');
        }
        if ($this->binary_packet_buffer == null) {
            // buffer the packet to permit continued reads across timeouts
            $this->binary_packet_buffer = (object) [
                'raw' => '', // the raw payload read from the socket
                'plain' => '', // the packet in plain text, excluding packet_length header
                'packet_length' => null, // the packet_length value pulled from the payload
                'size' => $this->decrypt_block_size, // the total size of this packet to be read from the socket
                                                     // initialize to read single block until packet_length is available
            ];
        }
        $packet = $this->binary_packet_buffer;
        while (strlen($packet->raw) < $packet->size) {
            if (feof($this->fsock)) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_CONNECTION_LOST);
                throw new ConnectionClosedException('Connection closed by server');
            }
            if ($this->curTimeout < 0) {
                $this->is_timeout = true;
                return true;
            }
            $this->send_keep_alive();

            list($sec, $usec) = $this->get_stream_timeout();
            stream_set_timeout($this->fsock, $sec, $usec);
            $start = microtime(true);
            $raw = stream_get_contents($this->fsock, $packet->size - strlen($packet->raw));
            $elapsed = microtime(true) - $start;
            if ($this->curTimeout > 0) {
                $this->curTimeout -= $elapsed;
            }
            if ($raw === false) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_CONNECTION_LOST);
                throw new ConnectionClosedException('Connection closed by server');
            } elseif (!strlen($raw)) {
                continue;
            }
            $packet->raw .= $raw;
            if (!$packet->packet_length) {
                $this->get_binary_packet_size($packet);
            }
        }

        if (strlen($packet->raw) != $packet->size) {
            throw new \RuntimeException('Size of packet was not expected length');
        }
        // destroy buffer as packet represents the entire payload and should be processed in full
        $this->binary_packet_buffer = null;
        // copy the raw payload, so as not to destroy original
        $raw = $packet->raw;
        if ($this->hmac_check instanceof Hash) {
            $hmac = Strings::pop($raw, $this->hmac_size);
        }
        $packet_length_header_size = 4;
        if ($this->decrypt) {
            switch ($this->decryptName) {
                case 'aes128-gcm@openssh.com':
                case 'aes256-gcm@openssh.com':
                    $this->decrypt->setNonce(
                        $this->decryptFixedPart .
                        $this->decryptInvocationCounter
                    );
                    Strings::increment_str($this->decryptInvocationCounter);
                    $this->decrypt->setAAD(Strings::shift($raw, $packet_length_header_size));
                    $this->decrypt->setTag(Strings::pop($raw, $this->decrypt_block_size));
                    $packet->plain = $this->decrypt->decrypt($raw);
                    break;
                case 'chacha20-poly1305@openssh.com':
                    // This should be impossible, but we are checking anyway to narrow the type for Psalm.
                    if (!($this->decrypt instanceof ChaCha20)) {
                        throw new \LogicException('$this->decrypt is not a ' . ChaCha20::class);
                    }
                    $this->decrypt->setNonce(pack('N2', 0, $this->get_seq_no));
                    $this->decrypt->setCounter(0);
                    // this is the same approach that's implemented in Salsa20::createPoly1305Key()
                    // but we don't want to use the same AEAD construction that RFC8439 describes
                    // for ChaCha20-Poly1305 so we won't rely on it (see Salsa20::poly1305())
                    $this->decrypt->setPoly1305Key(
                        $this->decrypt->encrypt(str_repeat("\0", 32))
                    );
                    $this->decrypt->setAAD(Strings::shift($raw, $packet_length_header_size));
                    $this->decrypt->setCounter(1);
                    $this->decrypt->setTag(Strings::pop($raw, 16));
                    $packet->plain = $this->decrypt->decrypt($raw);
                    break;
                default:
                    if (!$this->hmac_check instanceof Hash || !$this->hmac_check_etm) {
                        // first block was already decrypted for contained packet_length header
                        Strings::shift($raw, $this->decrypt_block_size);
                        if (strlen($raw) > 0) {
                            $packet->plain .= $this->decrypt->decrypt($raw);
                        }
                    } else {
                        Strings::shift($raw, $packet_length_header_size);
                        $packet->plain = $this->decrypt->decrypt($raw);
                    }
                    break;
            }
        } else {
            Strings::shift($raw, $packet_length_header_size);
            $packet->plain = $raw;
        }
        if ($this->hmac_check instanceof Hash) {
            $reconstructed = !$this->hmac_check_etm ?
                pack('Na*', $packet->packet_length, $packet->plain) :
                substr($packet->raw, 0, -$this->hmac_size);
            if (($this->hmac_check->getHash() & "\xFF\xFF\xFF\xFF") == 'umac') {
                $this->hmac_check->setNonce("\0\0\0\0" . pack('N', $this->get_seq_no));
                if ($hmac != $this->hmac_check->hash($reconstructed)) {
                    $this->disconnect_helper(NET_SSH2_DISCONNECT_MAC_ERROR);
                    throw new ConnectionClosedException('Invalid UMAC');
                }
            } else {
                if ($hmac != $this->hmac_check->hash(pack('Na*', $this->get_seq_no, $reconstructed))) {
                    $this->disconnect_helper(NET_SSH2_DISCONNECT_MAC_ERROR);
                    throw new ConnectionClosedException('Invalid HMAC');
                }
            }
        }
        $padding_length = 0;
        $payload = $packet->plain;
        extract(unpack('Cpadding_length', Strings::shift($payload, 1)));
        if ($padding_length > 0) {
            Strings::pop($payload, $padding_length);
        }
        if (empty($payload)) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
            throw new ConnectionClosedException('Plaintext is too short');
        }

        switch ($this->decompress) {
            case self::NET_SSH2_COMPRESSION_ZLIB_AT_OPENSSH:
                if (!$this->isAuthenticated()) {
                    break;
                }
                // fall-through
            case self::NET_SSH2_COMPRESSION_ZLIB:
                if ($this->regenerate_decompression_context) {
                    $this->regenerate_decompression_context = false;

                    $cmf = ord($payload[0]);
                    $cm = $cmf & 0x0F;
                    if ($cm != 8) { // deflate
                        user_error("Only CM = 8 ('deflate') is supported ($cm)");
                    }
                    $cinfo = ($cmf & 0xF0) >> 4;
                    if ($cinfo > 7) {
                        user_error("CINFO above 7 is not allowed ($cinfo)");
                    }
                    $windowSize = 1 << ($cinfo + 8);

                    $flg = ord($payload[1]);
                    //$fcheck = $flg && 0x0F;
                    if ((($cmf << 8) | $flg) % 31) {
                        user_error('fcheck failed');
                    }
                    $fdict = boolval($flg & 0x20);
                    $flevel = ($flg & 0xC0) >> 6;

                    $this->decompress_context = inflate_init(ZLIB_ENCODING_RAW, ['window' => $cinfo + 8]);
                    $payload = substr($payload, 2);
                }
                if ($this->decompress_context) {
                    $payload = inflate_add($this->decompress_context, $payload, ZLIB_PARTIAL_FLUSH);
                }
        }

        $this->get_seq_no++;

        if (defined('NET_SSH2_LOGGING')) {
            $current = microtime(true);
            $message_number = isset(self::$message_numbers[ord($payload[0])]) ? self::$message_numbers[ord($payload[0])] : 'UNKNOWN (' . ord($payload[0]) . ')';
            $message_number = '<- ' . $message_number .
                              ' (since last: ' . round($current - $this->last_packet, 4) . ', network: ' . round($elapsed, 4) . 's)';
            $this->append_log($message_number, $payload);
            $this->last_packet = $current;
        }

        return $this->filter($payload);
    }

    /**
     * @param object $packet The packet object being constructed, passed by reference
     *        The size, packet_length, and plain properties of this object may be modified in processing
     * @throws InvalidPacketLengthException if the packet length header is invalid
     */
    private function get_binary_packet_size(&$packet)
    {
        $packet_length_header_size = 4;
        if (strlen($packet->raw) < $packet_length_header_size) {
            return;
        }
        $packet_length = 0;
        $added_validation_length = 0; // indicates when the packet length header is included when validating packet length against block size
        if ($this->decrypt) {
            switch ($this->decryptName) {
                case 'aes128-gcm@openssh.com':
                case 'aes256-gcm@openssh.com':
                    extract(unpack('Npacket_length', substr($packet->raw, 0, $packet_length_header_size)));
                    $packet->size = $packet_length_header_size + $packet_length + $this->decrypt_block_size; // expect tag
                    break;
                case 'chacha20-poly1305@openssh.com':
                    $this->lengthDecrypt->setNonce(pack('N2', 0, $this->get_seq_no));
                    $packet_length_header = $this->lengthDecrypt->decrypt(substr($packet->raw, 0, $packet_length_header_size));
                    extract(unpack('Npacket_length', $packet_length_header));
                    $packet->size = $packet_length_header_size + $packet_length + 16; // expect tag
                    break;
                default:
                    if (!$this->hmac_check instanceof Hash || !$this->hmac_check_etm) {
                        if (strlen($packet->raw) < $this->decrypt_block_size) {
                            return;
                        }
                        $packet->plain = $this->decrypt->decrypt(substr($packet->raw, 0, $this->decrypt_block_size));
                        extract(unpack('Npacket_length', Strings::shift($packet->plain, $packet_length_header_size)));
                        $packet->size = $packet_length_header_size + $packet_length;
                        $added_validation_length = $packet_length_header_size;
                    } else {
                        extract(unpack('Npacket_length', substr($packet->raw, 0, $packet_length_header_size)));
                        $packet->size = $packet_length_header_size + $packet_length;
                    }
                    break;
            }
        } else {
            extract(unpack('Npacket_length', substr($packet->raw, 0, $packet_length_header_size)));
            $packet->size = $packet_length_header_size + $packet_length;
            $added_validation_length = $packet_length_header_size;
        }
        // quoting <http://tools.ietf.org/html/rfc4253#section-6.1>,
        // "implementations SHOULD check that the packet length is reasonable"
        // PuTTY uses 0x9000 as the actual max packet size and so to shall we
        if (
            $packet_length <= 0 || $packet_length > 0x9000
            || ($packet_length + $added_validation_length) % $this->decrypt_block_size != 0
        ) {
            $this->disconnect_helper(NET_SSH2_DISCONNECT_PROTOCOL_ERROR);
            throw new InvalidPacketLengthException('Invalid packet length');
        }
        if ($this->hmac_check instanceof Hash) {
            $packet->size += $this->hmac_size;
        }
        $packet->packet_length = $packet_length;
    }

    /**
     * Filter Binary Packets
     *
     * Because some binary packets need to be ignored...
     *
     * @see self::_get_binary_packet()
     * @param string $payload
     * @return string|bool
     */
    private function filter($payload)
    {
        switch (ord($payload[0])) {
            case NET_SSH2_MSG_DISCONNECT:
                Strings::shift($payload, 1);
                list($reason_code, $message) = Strings::unpackSSH2('Ns', $payload);
                $this->errors[] = 'SSH_MSG_DISCONNECT: ' . self::$disconnect_reasons[$reason_code] . "\r\n$message";
                $this->bitmap = 0;
                return false;
            case NET_SSH2_MSG_IGNORE:
                $this->extra_packets++;
                $payload = $this->get_binary_packet();
                break;
            case NET_SSH2_MSG_DEBUG:
                $this->extra_packets++;
                Strings::shift($payload, 2); // second byte is "always_display"
                list($message) = Strings::unpackSSH2('s', $payload);
                $this->errors[] = "SSH_MSG_DEBUG: $message";
                $payload = $this->get_binary_packet();
                break;
            case NET_SSH2_MSG_UNIMPLEMENTED:
                return false;
            case NET_SSH2_MSG_KEXINIT:
                // this is here for key re-exchanges after the initial key exchange
                if ($this->session_id !== false) {
                    if (!$this->key_exchange($payload)) {
                        $this->bitmap = 0;
                        return false;
                    }
                    $payload = $this->get_binary_packet();
                }
                break;
            case NET_SSH2_MSG_EXT_INFO:
                Strings::shift($payload, 1);
                list($nr_extensions) = Strings::unpackSSH2('N', $payload);
                for ($i = 0; $i < $nr_extensions; $i++) {
                    list($extension_name, $extension_value) = Strings::unpackSSH2('ss', $payload);
                    if ($extension_name == 'server-sig-algs') {
                        $this->supported_private_key_algorithms = explode(',', $extension_value);
                    }
                }
                $payload = $this->get_binary_packet();
        }

        // see http://tools.ietf.org/html/rfc4252#section-5.4; only called when the encryption has been activated and when we haven't already logged in
        if (($this->bitmap & self::MASK_CONNECTED) && !$this->isAuthenticated() && !is_bool($payload) && ord($payload[0]) == NET_SSH2_MSG_USERAUTH_BANNER) {
            Strings::shift($payload, 1);
            list($this->banner_message) = Strings::unpackSSH2('s', $payload);
            $payload = $this->get_binary_packet();
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
                                $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_SUCCESS, $this->server_channels[$channel]));
                            }
                            $payload = $this->get_binary_packet();
                        }
                    }
                    break;
                case NET_SSH2_MSG_GLOBAL_REQUEST: // see http://tools.ietf.org/html/rfc4254#section-4
                    Strings::shift($payload, 1);
                    list($request_name) = Strings::unpackSSH2('s', $payload);
                    $this->errors[] = "SSH_MSG_GLOBAL_REQUEST: $request_name";

                    try {
                        $this->send_binary_packet(pack('C', NET_SSH2_MSG_REQUEST_FAILURE));
                    } catch (\RuntimeException $e) {
                        return $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                    }

                    $payload = $this->get_binary_packet();
                    break;
                case NET_SSH2_MSG_CHANNEL_OPEN: // see http://tools.ietf.org/html/rfc4254#section-5.1
                    Strings::shift($payload, 1);
                    list($data, $server_channel) = Strings::unpackSSH2('sN', $payload);
                    switch ($data) {
                        case 'auth-agent':
                        case 'auth-agent@openssh.com':
                            if (isset($this->agent)) {
                                $new_channel = self::CHANNEL_AGENT_FORWARD;

                                list(
                                    $remote_window_size,
                                    $remote_maximum_packet_size
                                ) = Strings::unpackSSH2('NN', $payload);

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
                                $this->send_binary_packet($packet);
                            }
                            break;
                        default:
                            $packet = Strings::packSSH2(
                                'CN2ss',
                                NET_SSH2_MSG_CHANNEL_OPEN_FAILURE,
                                $server_channel,
                                NET_SSH2_OPEN_ADMINISTRATIVELY_PROHIBITED,
                                '', // description
                                '' // language tag
                            );

                            try {
                                $this->send_binary_packet($packet);
                            } catch (\RuntimeException $e) {
                                return $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                            }
                    }

                    $payload = $this->get_binary_packet();
                    break;
                case NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST:
                    Strings::shift($payload, 1);
                    list($channel, $window_size) = Strings::unpackSSH2('NN', $payload);

                    $this->window_size_client_to_server[$channel] += $window_size;

                    $payload = ($this->bitmap & self::MASK_WINDOW_ADJUST) ? true : $this->get_binary_packet();
            }
        }

        return $payload;
    }

    /**
     * Enable Quiet Mode
     *
     * Suppress stderr from output
     *
     */
    public function enableQuietMode()
    {
        $this->quiet_mode = true;
    }

    /**
     * Disable Quiet Mode
     *
     * Show stderr in output
     *
     */
    public function disableQuietMode()
    {
        $this->quiet_mode = false;
    }

    /**
     * Returns whether Quiet Mode is enabled or not
     *
     * @see self::enableQuietMode()
     * @see self::disableQuietMode()
     * @return bool
     */
    public function isQuietModeEnabled()
    {
        return $this->quiet_mode;
    }

    /**
     * Enable request-pty when using exec()
     *
     */
    public function enablePTY()
    {
        $this->request_pty = true;
    }

    /**
     * Disable request-pty when using exec()
     *
     */
    public function disablePTY()
    {
        if ($this->isPTYOpen()) {
            $this->close_channel(self::CHANNEL_EXEC);
        }
        $this->request_pty = false;
    }

    /**
     * Returns whether request-pty is enabled or not
     *
     * @see self::enablePTY()
     * @see self::disablePTY()
     * @return bool
     */
    public function isPTYEnabled()
    {
        return $this->request_pty;
    }

    /**
     * Gets channel data
     *
     * Returns the data as a string. bool(true) is returned if:
     *
     * - the server closes the channel
     * - if the connection times out
     * - if the channel status is CHANNEL_OPEN and the response was CHANNEL_OPEN_CONFIRMATION
     * - if the channel status is CHANNEL_REQUEST and the response was CHANNEL_SUCCESS
     * - if the channel status is CHANNEL_CLOSE and the response was CHANNEL_CLOSE
     *
     * bool(false) is returned if:
     *
     * - if the channel status is CHANNEL_REQUEST and the response was CHANNEL_FAILURE
     *
     * @param int $client_channel
     * @param bool $skip_extended
     * @return mixed
     * @throws \RuntimeException on connection error
     */
    protected function get_channel_packet($client_channel, $skip_extended = false)
    {
        if (!empty($this->channel_buffers[$client_channel])) {
            switch ($this->channel_status[$client_channel]) {
                case NET_SSH2_MSG_CHANNEL_REQUEST:
                    foreach ($this->channel_buffers[$client_channel] as $i => $packet) {
                        switch (ord($packet[0])) {
                            case NET_SSH2_MSG_CHANNEL_SUCCESS:
                            case NET_SSH2_MSG_CHANNEL_FAILURE:
                                unset($this->channel_buffers[$client_channel][$i]);
                                return substr($packet, 1);
                        }
                    }
                    break;
                default:
                    return substr(array_shift($this->channel_buffers[$client_channel]), 1);
            }
        }

        while (true) {
            $response = $this->get_binary_packet();
            if ($response === true && $this->is_timeout) {
                if ($client_channel == self::CHANNEL_EXEC && !$this->request_pty) {
                    $this->close_channel($client_channel);
                }
                return true;
            }
            if ($response === false) {
                $this->disconnect_helper(NET_SSH2_DISCONNECT_CONNECTION_LOST);
                throw new ConnectionClosedException('Connection closed by server');
            }
            if ($client_channel == -1 && $response === true) {
                return true;
            }
            list($type, $channel) = Strings::unpackSSH2('CN', $response);

            // will not be setup yet on incoming channel open request
            if (isset($channel) && isset($this->channel_status[$channel]) && isset($this->window_size_server_to_client[$channel])) {
                $this->window_size_server_to_client[$channel] -= strlen($response);

                // resize the window, if appropriate
                if ($this->window_size_server_to_client[$channel] < 0) {
                // PuTTY does something more analogous to the following:
                //if ($this->window_size_server_to_client[$channel] < 0x3FFFFFFF) {
                    $packet = pack('CNN', NET_SSH2_MSG_CHANNEL_WINDOW_ADJUST, $this->server_channels[$channel], $this->window_resize);
                    $this->send_binary_packet($packet);
                    $this->window_size_server_to_client[$channel] += $this->window_resize;
                }

                switch ($type) {
                    case NET_SSH2_MSG_CHANNEL_EXTENDED_DATA:
                        /*
                        if ($client_channel == self::CHANNEL_EXEC) {
                            $this->send_channel_packet($client_channel, chr(0));
                        }
                        */
                        // currently, there's only one possible value for $data_type_code: NET_SSH2_EXTENDED_DATA_STDERR
                        list($data_type_code, $data) = Strings::unpackSSH2('Ns', $response);
                        $this->stdErrorLog .= $data;
                        if ($skip_extended || $this->quiet_mode) {
                            continue 2;
                        }
                        if ($client_channel == $channel && $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_DATA) {
                            return $data;
                        }
                        $this->channel_buffers[$channel][] = chr($type) . $data;

                        continue 2;
                    case NET_SSH2_MSG_CHANNEL_REQUEST:
                        if ($this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_CLOSE) {
                            continue 2;
                        }
                        list($value) = Strings::unpackSSH2('s', $response);
                        switch ($value) {
                            case 'exit-signal':
                                list(
                                    , // FALSE
                                    $signal_name,
                                    , // core dumped
                                    $error_message
                                ) = Strings::unpackSSH2('bsbs', $response);

                                $this->errors[] = "SSH_MSG_CHANNEL_REQUEST (exit-signal): $signal_name";
                                if (strlen($error_message)) {
                                    $this->errors[count($this->errors) - 1] .= "\r\n$error_message";
                                }

                                $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_EOF, $this->server_channels[$client_channel]));
                                $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));

                                $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_EOF;

                                continue 3;
                            case 'exit-status':
                                list(, $this->exit_status) = Strings::unpackSSH2('CN', $response);

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
                                list(
                                    $this->server_channels[$channel],
                                    $window_size,
                                    $this->packet_size_client_to_server[$channel]
                                ) = Strings::unpackSSH2('NNN', $response);

                                if ($window_size < 0) {
                                    $window_size &= 0x7FFFFFFF;
                                    $window_size += 0x80000000;
                                }
                                $this->window_size_client_to_server[$channel] = $window_size;
                                $result = $client_channel == $channel ? true : $this->get_channel_packet($client_channel, $skip_extended);
                                $this->on_channel_open();
                                return $result;
                            case NET_SSH2_MSG_CHANNEL_OPEN_FAILURE:
                                $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                                throw new \RuntimeException('Unable to open channel');
                            default:
                                if ($client_channel == $channel) {
                                    $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                                    throw new \RuntimeException('Unexpected response to open request');
                                }
                                return $this->get_channel_packet($client_channel, $skip_extended);
                        }
                        break;
                    case NET_SSH2_MSG_CHANNEL_REQUEST:
                        switch ($type) {
                            case NET_SSH2_MSG_CHANNEL_SUCCESS:
                                return true;
                            case NET_SSH2_MSG_CHANNEL_FAILURE:
                                return false;
                            case NET_SSH2_MSG_CHANNEL_DATA:
                                list($data) = Strings::unpackSSH2('s', $response);
                                $this->channel_buffers[$channel][] = chr($type) . $data;
                                return $this->get_channel_packet($client_channel, $skip_extended);
                            default:
                                $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                                throw new \RuntimeException('Unable to fulfill channel request');
                        }
                    case NET_SSH2_MSG_CHANNEL_CLOSE:
                        if ($client_channel == $channel && $type == NET_SSH2_MSG_CHANNEL_CLOSE) {
                            return true;
                        }
                        return $this->get_channel_packet($client_channel, $skip_extended);
                }
            }

            // ie. $this->channel_status[$channel] == NET_SSH2_MSG_CHANNEL_DATA

            switch ($type) {
                case NET_SSH2_MSG_CHANNEL_DATA:
                    /*
                    if ($channel == self::CHANNEL_EXEC) {
                        // SCP requires null packets, such as this, be sent.  further, in the case of the ssh.com SSH server
                        // this actually seems to make things twice as fast.  more to the point, the message right after
                        // SSH_MSG_CHANNEL_DATA (usually SSH_MSG_IGNORE) won't block for as long as it would have otherwise.
                        // in OpenSSH it slows things down but only by a couple thousandths of a second.
                        $this->send_channel_packet($channel, chr(0));
                    }
                    */
                    list($data) = Strings::unpackSSH2('s', $response);

                    if ($channel == self::CHANNEL_AGENT_FORWARD) {
                        $agent_response = $this->agent->forwardData($data);
                        if (!is_bool($agent_response)) {
                            $this->send_channel_packet($channel, $agent_response);
                        }
                        break;
                    }

                    if ($client_channel == $channel) {
                        return $data;
                    }
                    $this->channel_buffers[$channel][] = chr($type) . $data;
                    break;
                case NET_SSH2_MSG_CHANNEL_CLOSE:
                    $this->curTimeout = 5;

                    $this->close_channel_bitmap($channel);

                    if ($this->channel_status[$channel] != NET_SSH2_MSG_CHANNEL_EOF) {
                        $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));
                    }

                    $this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_CLOSE;
                    $this->channelCount--;

                    if ($client_channel == $channel) {
                        return true;
                    }
                    // fall-through
                case NET_SSH2_MSG_CHANNEL_EOF:
                    break;
                default:
                    $this->disconnect_helper(NET_SSH2_DISCONNECT_BY_APPLICATION);
                    throw new \RuntimeException("Error reading channel data ($type)");
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
     * @return void
     */
    protected function send_binary_packet($data, $logged = null)
    {
        if (!is_resource($this->fsock) || feof($this->fsock)) {
            $this->bitmap = 0;
            throw new ConnectionClosedException('Connection closed prematurely');
        }

        if (!isset($logged)) {
            $logged = $data;
        }

        switch ($this->compress) {
            case self::NET_SSH2_COMPRESSION_ZLIB_AT_OPENSSH:
                if (!$this->isAuthenticated()) {
                    break;
                }
                // fall-through
            case self::NET_SSH2_COMPRESSION_ZLIB:
                if (!$this->regenerate_compression_context) {
                    $header = '';
                } else {
                    $this->regenerate_compression_context = false;
                    $this->compress_context = deflate_init(ZLIB_ENCODING_RAW, ['window' => 15]);
                    $header = "\x78\x9C";
                }
                if ($this->compress_context) {
                    $data = $header . deflate_add($this->compress_context, $data, ZLIB_PARTIAL_FLUSH);
                }
        }

        // 4 (packet length) + 1 (padding length) + 4 (minimal padding amount) == 9
        $packet_length = strlen($data) + 9;
        if ($this->encrypt && $this->encrypt->usesNonce()) {
            $packet_length -= 4;
        }
        // round up to the nearest $this->encrypt_block_size
        $packet_length += (($this->encrypt_block_size - 1) * $packet_length) % $this->encrypt_block_size;
        // subtracting strlen($data) is obvious - subtracting 5 is necessary because of packet_length and padding_length
        $padding_length = $packet_length - strlen($data) - 5;
        switch (true) {
            case $this->encrypt && $this->encrypt->usesNonce():
            case $this->hmac_create instanceof Hash && $this->hmac_create_etm:
                $padding_length += 4;
                $packet_length += 4;
        }

        $padding = Random::string($padding_length);

        // we subtract 4 from packet_length because the packet_length field isn't supposed to include itself
        $packet = pack('NCa*', $packet_length - 4, $padding_length, $data . $padding);

        $hmac = '';
        if ($this->hmac_create instanceof Hash && !$this->hmac_create_etm) {
            if (($this->hmac_create->getHash() & "\xFF\xFF\xFF\xFF") == 'umac') {
                $this->hmac_create->setNonce("\0\0\0\0" . pack('N', $this->send_seq_no));
                $hmac = $this->hmac_create->hash($packet);
            } else {
                $hmac = $this->hmac_create->hash(pack('Na*', $this->send_seq_no, $packet));
            }
        }

        if ($this->encrypt) {
            switch ($this->encryptName) {
                case 'aes128-gcm@openssh.com':
                case 'aes256-gcm@openssh.com':
                    $this->encrypt->setNonce(
                        $this->encryptFixedPart .
                        $this->encryptInvocationCounter
                    );
                    Strings::increment_str($this->encryptInvocationCounter);
                    $this->encrypt->setAAD($temp = ($packet & "\xFF\xFF\xFF\xFF"));
                    $packet = $temp . $this->encrypt->encrypt(substr($packet, 4));
                    break;
                case 'chacha20-poly1305@openssh.com':
                    // This should be impossible, but we are checking anyway to narrow the type for Psalm.
                    if (!($this->encrypt instanceof ChaCha20)) {
                        throw new \LogicException('$this->encrypt is not a ' . ChaCha20::class);
                    }

                    $nonce = pack('N2', 0, $this->send_seq_no);

                    $this->encrypt->setNonce($nonce);
                    $this->lengthEncrypt->setNonce($nonce);

                    $length = $this->lengthEncrypt->encrypt($packet & "\xFF\xFF\xFF\xFF");

                    $this->encrypt->setCounter(0);
                    // this is the same approach that's implemented in Salsa20::createPoly1305Key()
                    // but we don't want to use the same AEAD construction that RFC8439 describes
                    // for ChaCha20-Poly1305 so we won't rely on it (see Salsa20::poly1305())
                    $this->encrypt->setPoly1305Key(
                        $this->encrypt->encrypt(str_repeat("\0", 32))
                    );
                    $this->encrypt->setAAD($length);
                    $this->encrypt->setCounter(1);
                    $packet = $length . $this->encrypt->encrypt(substr($packet, 4));
                    break;
                default:
                    $packet = $this->hmac_create instanceof Hash && $this->hmac_create_etm ?
                        ($packet & "\xFF\xFF\xFF\xFF") . $this->encrypt->encrypt(substr($packet, 4)) :
                        $this->encrypt->encrypt($packet);
            }
        }

        if ($this->hmac_create instanceof Hash && $this->hmac_create_etm) {
            if (($this->hmac_create->getHash() & "\xFF\xFF\xFF\xFF") == 'umac') {
                $this->hmac_create->setNonce("\0\0\0\0" . pack('N', $this->send_seq_no));
                $hmac = $this->hmac_create->hash($packet);
            } else {
                $hmac = $this->hmac_create->hash(pack('Na*', $this->send_seq_no, $packet));
            }
        }

        $this->send_seq_no++;

        $packet .= $this->encrypt && $this->encrypt->usesNonce() ? $this->encrypt->getTag() : $hmac;

        $start = microtime(true);
        $sent = @fputs($this->fsock, $packet);
        $stop = microtime(true);

        if (defined('NET_SSH2_LOGGING')) {
            $current = microtime(true);
            $message_number = isset(self::$message_numbers[ord($logged[0])]) ? self::$message_numbers[ord($logged[0])] : 'UNKNOWN (' . ord($logged[0]) . ')';
            $message_number = '-> ' . $message_number .
                              ' (since last: ' . round($current - $this->last_packet, 4) . ', network: ' . round($stop - $start, 4) . 's)';
            $this->append_log($message_number, $logged);
            $this->last_packet = $current;
        }

        if (strlen($packet) != $sent) {
            $this->bitmap = 0;
            $message = $sent === false ?
                'Unable to write ' . strlen($packet) . ' bytes' :
                "Only $sent of " . strlen($packet) . " bytes were sent";
            throw new \RuntimeException($message);
        }
    }

    /**
     * Sends a keep-alive message, if keep-alive is enabled and interval is met
     */
    private function send_keep_alive()
    {
        if ($this->bitmap & self::MASK_CONNECTED) {
            $elapsed = microtime(true) - $this->last_packet;
            if ($this->keepAlive > 0 && $elapsed >= $this->keepAlive) {
                $this->send_binary_packet(pack('CN', NET_SSH2_MSG_IGNORE, 0));
            }
        }
    }

    /**
     * Logs data packets
     *
     * Makes sure that only the last 1MB worth of packets will be logged
     *
     * @param string $message_number
     * @param string $message
     */
    private function append_log($message_number, $message)
    {
        $this->append_log_helper(
            NET_SSH2_LOGGING,
            $message_number,
            $message,
            $this->message_number_log,
            $this->message_log,
            $this->log_size,
            $this->realtime_log_file,
            $this->realtime_log_wrap,
            $this->realtime_log_size
        );
    }

    /**
     * Logs data packet helper
     *
     * @param int $constant
     * @param string $message_number
     * @param string $message
     * @param array &$message_number_log
     * @param array &$message_log
     * @param int &$log_size
     * @param resource &$realtime_log_file
     * @param bool &$realtime_log_wrap
     * @param int &$realtime_log_size
     */
    protected function append_log_helper($constant, $message_number, $message, array &$message_number_log, array &$message_log, &$log_size, &$realtime_log_file, &$realtime_log_wrap, &$realtime_log_size)
    {
        // remove the byte identifying the message type from all but the first two messages (ie. the identification strings)
        if (strlen($message_number) > 2) {
            Strings::shift($message);
        }

        switch ($constant) {
            // useful for benchmarks
            case self::LOG_SIMPLE:
                $message_number_log[] = $message_number;
                break;
            case self::LOG_SIMPLE_REALTIME:
                echo $message_number;
                echo PHP_SAPI == 'cli' ? "\r\n" : '<br>';
                @flush();
                @ob_flush();
                break;
            // the most useful log for SSH2
            case self::LOG_COMPLEX:
                $message_number_log[] = $message_number;
                $log_size += strlen($message);
                $message_log[] = $message;
                while ($log_size > self::LOG_MAX_SIZE) {
                    $log_size -= strlen(array_shift($message_log));
                    array_shift($message_number_log);
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
                echo $start . $this->format_log([$message], [$message_number]) . $stop;
                @flush();
                @ob_flush();
                break;
            // basically the same thing as self::LOG_REALTIME with the caveat that NET_SSH2_LOG_REALTIME_FILENAME
            // needs to be defined and that the resultant log file will be capped out at self::LOG_MAX_SIZE.
            // the earliest part of the log file is denoted by the first <<< START >>> and is not going to necessarily
            // at the beginning of the file
            case self::LOG_REALTIME_FILE:
                if (!isset($realtime_log_file)) {
                    // PHP doesn't seem to like using constants in fopen()
                    $filename = NET_SSH2_LOG_REALTIME_FILENAME;
                    $fp = fopen($filename, 'w');
                    $realtime_log_file = $fp;
                }
                if (!is_resource($realtime_log_file)) {
                    break;
                }
                $entry = $this->format_log([$message], [$message_number]);
                if ($realtime_log_wrap) {
                    $temp = "<<< START >>>\r\n";
                    $entry .= $temp;
                    fseek($realtime_log_file, ftell($realtime_log_file) - strlen($temp));
                }
                $realtime_log_size += strlen($entry);
                if ($realtime_log_size > self::LOG_MAX_SIZE) {
                    fseek($realtime_log_file, 0);
                    $realtime_log_size = strlen($entry);
                    $realtime_log_wrap = true;
                }
                fputs($realtime_log_file, $entry);
        }
    }

    /**
     * Sends channel data
     *
     * Spans multiple SSH_MSG_CHANNEL_DATAs if appropriate
     *
     * @param int $client_channel
     * @param string $data
     * @return void
     */
    protected function send_channel_packet($client_channel, $data)
    {
        while (strlen($data)) {
            if (!$this->window_size_client_to_server[$client_channel]) {
                $this->bitmap ^= self::MASK_WINDOW_ADJUST;
                // using an invalid channel will let the buffers be built up for the valid channels
                $this->get_channel_packet(-1);
                $this->bitmap ^= self::MASK_WINDOW_ADJUST;
            }

            /* The maximum amount of data allowed is determined by the maximum
               packet size for the channel, and the current window size, whichever
               is smaller.
                 -- http://tools.ietf.org/html/rfc4254#section-5.2 */
            $max_size = min(
                $this->packet_size_client_to_server[$client_channel],
                $this->window_size_client_to_server[$client_channel]
            );

            $temp = Strings::shift($data, $max_size);
            $packet = Strings::packSSH2(
                'CNs',
                NET_SSH2_MSG_CHANNEL_DATA,
                $this->server_channels[$client_channel],
                $temp
            );
            $this->window_size_client_to_server[$client_channel] -= strlen($temp);
            $this->send_binary_packet($packet);
        }
    }

    /**
     * Closes and flushes a channel
     *
     * \phpseclib3\Net\SSH2 doesn't properly close most channels.  For exec() channels are normally closed by the server
     * and for SFTP channels are presumably closed when the client disconnects.  This functions is intended
     * for SCP more than anything.
     *
     * @param int $client_channel
     * @param bool $want_reply
     * @return void
     */
    private function close_channel($client_channel, $want_reply = false)
    {
        // see http://tools.ietf.org/html/rfc4254#section-5.3

        $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_EOF, $this->server_channels[$client_channel]));

        if (!$want_reply) {
            $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$client_channel]));
        }

        $this->channel_status[$client_channel] = NET_SSH2_MSG_CHANNEL_CLOSE;
        $this->channelCount--;

        $this->curTimeout = 5;

        while (!is_bool($this->get_channel_packet($client_channel))) {
        }

        if ($want_reply) {
            $this->send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$client_channel]));
        }

        $this->close_channel_bitmap($client_channel);
    }

    /**
     * Maintains execution state bitmap in response to channel closure
     *
     * @param int $client_channel The channel number to maintain closure status of
     * @return void
     */
    private function close_channel_bitmap($client_channel)
    {
        switch ($client_channel) {
            case self::CHANNEL_SHELL:
                // Shell status has been maintained in the bitmap for backwards
                //  compatibility sake, but can be removed going forward
                if ($this->bitmap & self::MASK_SHELL) {
                    $this->bitmap &= ~self::MASK_SHELL;
                }
                break;
        }
    }

    /**
     * Disconnect
     *
     * @param int $reason
     * @return false
     */
    protected function disconnect_helper($reason)
    {
        if ($this->bitmap & self::MASK_CONNECTED) {
            $data = Strings::packSSH2('CNss', NET_SSH2_MSG_DISCONNECT, $reason, '', '');
            try {
                $this->send_binary_packet($data);
            } catch (\Exception $e) {
            }
        }

        $this->reset_connection();

        return false;
    }

    /**
     * Define Array
     *
     * Takes any number of arrays whose indices are integers and whose values are strings and defines a bunch of
     * named constants from it, using the value as the name of the constant and the index as the value of the constant.
     * If any of the constants that would be defined already exists, none of the constants will be defined.
     *
     * @param mixed[] ...$args
     * @access protected
     */
    protected static function define_array(...$args)
    {
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
     * @return array|false|string
     */
    public function getLog()
    {
        if (!defined('NET_SSH2_LOGGING')) {
            return false;
        }

        switch (NET_SSH2_LOGGING) {
            case self::LOG_SIMPLE:
                return $this->message_number_log;
            case self::LOG_COMPLEX:
                $log = $this->format_log($this->message_log, $this->message_number_log);
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
     * @return string
     */
    protected function format_log(array $message_log, array $message_number_log)
    {
        $output = '';
        for ($i = 0; $i < count($message_log); $i++) {
            $output .= $message_number_log[$i] . "\r\n";
            $current_log = $message_log[$i];
            $j = 0;
            do {
                if (strlen($current_log)) {
                    $output .= str_pad(dechex($j), 7, '0', STR_PAD_LEFT) . '0  ';
                }
                $fragment = Strings::shift($current_log, $this->log_short_width);
                $hex = substr(preg_replace_callback('#.#s', function ($matches) {
                    return $this->log_boundary . str_pad(dechex(ord($matches[0])), 2, '0', STR_PAD_LEFT);
                }, $fragment), strlen($this->log_boundary));
                // replace non ASCII printable characters with dots
                // http://en.wikipedia.org/wiki/ASCII#ASCII_printable_characters
                // also replace < with a . since < messes up the output on web browsers
                $raw = preg_replace('#[^\x20-\x7E]|<#', '.', $fragment);
                $output .= str_pad($hex, $this->log_long_width - $this->log_short_width, ' ') . $raw . "\r\n";
                $j++;
            } while (strlen($current_log));
            $output .= "\r\n";
        }

        return $output;
    }

    /**
     * Helper function for agent->on_channel_open()
     *
     * Used when channels are created to inform agent
     * of said channel opening. Must be called after
     * channel open confirmation received
     *
     */
    private function on_channel_open()
    {
        if (isset($this->agent)) {
            $this->agent->registerChannelOpen($this);
        }
    }

    /**
     * Returns the first value of the intersection of two arrays or false if
     * the intersection is empty. The order is defined by the first parameter.
     *
     * @param array $array1
     * @param array $array2
     * @return mixed False if intersection is empty, else intersected value.
     */
    private static function array_intersect_first(array $array1, array $array2)
    {
        foreach ($array1 as $value) {
            if (in_array($value, $array2)) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Returns all errors / debug messages on the SSH layer
     *
     * If you are looking for messages from the SFTP layer, please see SFTP::getSFTPErrors()
     *
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns the last error received on the SSH layer
     *
     * If you are looking for messages from the SFTP layer, please see SFTP::getLastSFTPError()
     *
     * @return string
     */
    public function getLastError()
    {
        $count = count($this->errors);

        if ($count > 0) {
            return $this->errors[$count - 1];
        }
    }

    /**
     * Return the server identification.
     *
     * @return string|false
     */
    public function getServerIdentification()
    {
        $this->connect();

        return $this->server_identifier;
    }

    /**
     * Returns a list of algorithms the server supports
     *
     * @return array
     */
    public function getServerAlgorithms()
    {
        $this->connect();

        return [
            'kex' => $this->kex_algorithms,
            'hostkey' => $this->server_host_key_algorithms,
            'client_to_server' => [
                'crypt' => $this->encryption_algorithms_client_to_server,
                'mac' => $this->mac_algorithms_client_to_server,
                'comp' => $this->compression_algorithms_client_to_server,
                'lang' => $this->languages_client_to_server
            ],
            'server_to_client' => [
                'crypt' => $this->encryption_algorithms_server_to_client,
                'mac' => $this->mac_algorithms_server_to_client,
                'comp' => $this->compression_algorithms_server_to_client,
                'lang' => $this->languages_server_to_client
            ]
        ];
    }

    /**
     * Returns a list of KEX algorithms that phpseclib supports
     *
     * @return array
     */
    public static function getSupportedKEXAlgorithms()
    {
        $kex_algorithms = [
            // Elliptic Curve Diffie-Hellman Key Agreement (ECDH) using
            // Curve25519. See doc/curve25519-sha256@libssh.org.txt in the
            // libssh repository for more information.
            'curve25519-sha256',
            'curve25519-sha256@libssh.org',

            'ecdh-sha2-nistp256', // RFC 5656
            'ecdh-sha2-nistp384', // RFC 5656
            'ecdh-sha2-nistp521', // RFC 5656

            'diffie-hellman-group-exchange-sha256',// RFC 4419
            'diffie-hellman-group-exchange-sha1',  // RFC 4419

            // Diffie-Hellman Key Agreement (DH) using integer modulo prime
            // groups.
            'diffie-hellman-group14-sha256',
            'diffie-hellman-group14-sha1', // REQUIRED
            'diffie-hellman-group15-sha512',
            'diffie-hellman-group16-sha512',
            'diffie-hellman-group17-sha512',
            'diffie-hellman-group18-sha512',

            'diffie-hellman-group1-sha1', // REQUIRED
        ];

        return $kex_algorithms;
    }

    /**
     * Returns a list of host key algorithms that phpseclib supports
     *
     * @return array
     */
    public static function getSupportedHostKeyAlgorithms()
    {
        return [
            'ssh-ed25519', // https://tools.ietf.org/html/draft-ietf-curdle-ssh-ed25519-02
            'ecdsa-sha2-nistp256', // RFC 5656
            'ecdsa-sha2-nistp384', // RFC 5656
            'ecdsa-sha2-nistp521', // RFC 5656
            'rsa-sha2-256', // RFC 8332
            'rsa-sha2-512', // RFC 8332
            'ssh-rsa', // RECOMMENDED  sign   Raw RSA Key
            'ssh-dss'  // REQUIRED     sign   Raw DSS Key
        ];
    }

    /**
     * Returns a list of symmetric key algorithms that phpseclib supports
     *
     * @return array
     */
    public static function getSupportedEncryptionAlgorithms()
    {
        $algos = [
            // from <https://tools.ietf.org/html/rfc5647>:
            'aes128-gcm@openssh.com',
            'aes256-gcm@openssh.com',

            // from <http://tools.ietf.org/html/rfc4345#section-4>:
            'arcfour256',
            'arcfour128',

            //'arcfour',      // OPTIONAL          the ARCFOUR stream cipher with a 128-bit key

            // CTR modes from <http://tools.ietf.org/html/rfc4344#section-4>:
            'aes128-ctr',     // RECOMMENDED       AES (Rijndael) in SDCTR mode, with 128-bit key
            'aes192-ctr',     // RECOMMENDED       AES with 192-bit key
            'aes256-ctr',     // RECOMMENDED       AES with 256-bit key

            // from <https://github.com/openssh/openssh-portable/blob/001aa55/PROTOCOL.chacha20poly1305>:
            // one of the big benefits of chacha20-poly1305 is speed. the problem is...
            // libsodium doesn't generate the poly1305 keys in the way ssh does and openssl's PHP bindings don't even
            // seem to support poly1305 currently. so even if libsodium or openssl are being used for the chacha20
            // part, pure-PHP has to be used for the poly1305 part and that's gonna cause a big slow down.
            // speed-wise it winds up being faster to use AES (when openssl or mcrypt are available) and some HMAC
            // (which is always gonna be super fast to compute thanks to the hash extension, which
            // "is bundled and compiled into PHP by default")
            'chacha20-poly1305@openssh.com',

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
        ];

        if (self::$crypto_engine) {
            $engines = [self::$crypto_engine];
        } else {
            $engines = [
                'libsodium',
                'OpenSSL (GCM)',
                'OpenSSL',
                'mcrypt',
                'Eval',
                'PHP'
            ];
        }

        $ciphers = [];

        foreach ($engines as $engine) {
            foreach ($algos as $algo) {
                $obj = self::encryption_algorithm_to_crypt_instance($algo);
                if ($obj instanceof Rijndael) {
                    $obj->setKeyLength(preg_replace('#[^\d]#', '', $algo));
                }
                switch ($algo) {
                    // Eval engines do not exist for ChaCha20 or RC4 because they would not benefit from one.
                    // to benefit from an Eval engine they'd need to loop a variable amount of times, they'd
                    // need to do table lookups (eg. sbox subsitutions). ChaCha20 doesn't do either because
                    // it's a so-called ARX cipher, meaning that the only operations it does are add (A), rotate (R)
                    // and XOR (X). RC4 does do table lookups but being a stream cipher it works differently than
                    // block ciphers. with RC4 you XOR the plaintext against a keystream and the keystream changes
                    // as you encrypt stuff. the only table lookups are made against this keystream and thus table
                    // lookups are kinda unavoidable. with AES and DES, however, the table lookups that are done
                    // are done against substitution boxes (sboxes), which are invariant.

                    // OpenSSL can't be used as an engine, either, because OpenSSL doesn't support continuous buffers
                    // as SSH2 uses and altho you can emulate a continuous buffer with block ciphers you can't do so
                    // with stream ciphers. As for ChaCha20...  for the ChaCha20 part OpenSSL could prob be used but
                    // the big slow down isn't with ChaCha20 - it's with Poly1305. SSH constructs the key for that
                    // differently than how OpenSSL does it (OpenSSL does it as the RFC describes, SSH doesn't).

                    // libsodium can't be used because it doesn't support RC4 and it doesn't construct the Poly1305
                    // keys in the same way that SSH does

                    // mcrypt could prob be used for RC4 but mcrypt hasn't been included in PHP core for yearss
                    case 'chacha20-poly1305@openssh.com':
                    case 'arcfour128':
                    case 'arcfour256':
                        if ($engine != 'PHP') {
                            continue 2;
                        }
                        break;
                    case 'aes128-gcm@openssh.com':
                    case 'aes256-gcm@openssh.com':
                        if ($engine == 'OpenSSL') {
                            continue 2;
                        }
                        $obj->setNonce('dummydummydu');
                }
                if ($obj->isValidEngine($engine)) {
                    $algos = array_diff($algos, [$algo]);
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
     */
    public static function getSupportedMACAlgorithms()
    {
        return [
            'hmac-sha2-256-etm@openssh.com',
            'hmac-sha2-512-etm@openssh.com',
            'umac-64-etm@openssh.com',
            'umac-128-etm@openssh.com',
            'hmac-sha1-etm@openssh.com',

            // from <http://www.ietf.org/rfc/rfc6668.txt>:
            'hmac-sha2-256',// RECOMMENDED     HMAC-SHA256 (digest length = key length = 32)
            'hmac-sha2-512',// OPTIONAL        HMAC-SHA512 (digest length = key length = 64)

            // from <https://tools.ietf.org/html/draft-miller-secsh-umac-01>:
            'umac-64@openssh.com',
            'umac-128@openssh.com',

            'hmac-sha1-96', // RECOMMENDED     first 96 bits of HMAC-SHA1 (digest length = 12, key length = 20)
            'hmac-sha1',    // REQUIRED        HMAC-SHA1 (digest length = key length = 20)
            'hmac-md5-96',  // OPTIONAL        first 96 bits of HMAC-MD5 (digest length = 12, key length = 16)
            'hmac-md5',     // OPTIONAL        HMAC-MD5 (digest length = key length = 16)
            //'none'          // OPTIONAL        no MAC; NOT RECOMMENDED
        ];
    }

    /**
     * Returns a list of compression algorithms that phpseclib supports
     *
     * @return array
     */
    public static function getSupportedCompressionAlgorithms()
    {
        $algos = ['none']; // REQUIRED        no compression
        if (function_exists('deflate_init')) {
            $algos[] = 'zlib@openssh.com'; // https://datatracker.ietf.org/doc/html/draft-miller-secsh-compression-delayed
            $algos[] = 'zlib';
        }
        return $algos;
    }

    /**
     * Return list of negotiated algorithms
     *
     * Uses the same format as https://www.php.net/ssh2-methods-negotiated
     *
     * @return array
     */
    public function getAlgorithmsNegotiated()
    {
        $this->connect();

        $compression_map = [
            self::NET_SSH2_COMPRESSION_NONE => 'none',
            self::NET_SSH2_COMPRESSION_ZLIB => 'zlib',
            self::NET_SSH2_COMPRESSION_ZLIB_AT_OPENSSH => 'zlib@openssh.com'
        ];

        return [
            'kex' => $this->kex_algorithm,
            'hostkey' => $this->signature_format,
            'client_to_server' => [
                'crypt' => $this->encryptName,
                'mac' => $this->hmac_create_name,
                'comp' => $compression_map[$this->compress],
            ],
            'server_to_client' => [
                'crypt' => $this->decryptName,
                'mac' => $this->hmac_check_name,
                'comp' => $compression_map[$this->decompress],
            ]
        ];
    }

    /**
     * Force multiple channels (even if phpseclib has decided to disable them)
     */
    public function forceMultipleChannels()
    {
        $this->errorOnMultipleChannels = false;
    }

    /**
     * Allows you to set the terminal
     *
     * @param string $term
     */
    public function setTerminal($term)
    {
        $this->term = $term;
    }

    /**
     * Accepts an associative array with up to four parameters as described at
     * <https://www.php.net/manual/en/function.ssh2-connect.php>
     *
     * @param array $methods
     */
    public function setPreferredAlgorithms(array $methods)
    {
        $preferred = $methods;

        if (isset($preferred['kex'])) {
            $preferred['kex'] = array_intersect(
                $preferred['kex'],
                static::getSupportedKEXAlgorithms()
            );
        }

        if (isset($preferred['hostkey'])) {
            $preferred['hostkey'] = array_intersect(
                $preferred['hostkey'],
                static::getSupportedHostKeyAlgorithms()
            );
        }

        $keys = ['client_to_server', 'server_to_client'];
        foreach ($keys as $key) {
            if (isset($preferred[$key])) {
                $a = &$preferred[$key];
                if (isset($a['crypt'])) {
                    $a['crypt'] = array_intersect(
                        $a['crypt'],
                        static::getSupportedEncryptionAlgorithms()
                    );
                }
                if (isset($a['comp'])) {
                    $a['comp'] = array_intersect(
                        $a['comp'],
                        static::getSupportedCompressionAlgorithms()
                    );
                }
                if (isset($a['mac'])) {
                    $a['mac'] = array_intersect(
                        $a['mac'],
                        static::getSupportedMACAlgorithms()
                    );
                }
            }
        }

        $keys = [
            'kex',
            'hostkey',
            'client_to_server/crypt',
            'client_to_server/comp',
            'client_to_server/mac',
            'server_to_client/crypt',
            'server_to_client/comp',
            'server_to_client/mac',
        ];
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
                throw new UnsupportedAlgorithmException(implode(', ', $diff) . $msg);
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
     */
    public function getBannerMessage()
    {
        return $this->banner_message;
    }

    /**
     * Returns the server public host key.
     *
     * Caching this the first time you connect to a server and checking the result on subsequent connections
     * is recommended.  Returns false if the server signature is not signed correctly with the public host key.
     *
     * @return string|false
     * @throws \RuntimeException on badly formatted keys
     * @throws \phpseclib3\Exception\NoSupportedAlgorithmsException when the key isn't in a supported format
     */
    public function getServerPublicHostKey()
    {
        if (!($this->bitmap & self::MASK_CONSTRUCTOR)) {
            $this->connect();
        }

        $signature = $this->signature;
        $server_public_host_key = base64_encode($this->server_public_host_key);

        if ($this->signature_validated) {
            return $this->bitmap ?
                $this->signature_format . ' ' . $server_public_host_key :
                false;
        }

        $this->signature_validated = true;

        switch ($this->signature_format) {
            case 'ssh-ed25519':
            case 'ecdsa-sha2-nistp256':
            case 'ecdsa-sha2-nistp384':
            case 'ecdsa-sha2-nistp521':
                $key = EC::loadFormat('OpenSSH', $server_public_host_key)
                    ->withSignatureFormat('SSH2');
                switch ($this->signature_format) {
                    case 'ssh-ed25519':
                        $hash = 'sha512';
                        break;
                    case 'ecdsa-sha2-nistp256':
                        $hash = 'sha256';
                        break;
                    case 'ecdsa-sha2-nistp384':
                        $hash = 'sha384';
                        break;
                    case 'ecdsa-sha2-nistp521':
                        $hash = 'sha512';
                }
                $key = $key->withHash($hash);
                break;
            case 'ssh-dss':
                $key = DSA::loadFormat('OpenSSH', $server_public_host_key)
                    ->withSignatureFormat('SSH2')
                    ->withHash('sha1');
                break;
            case 'ssh-rsa':
            case 'rsa-sha2-256':
            case 'rsa-sha2-512':
                // could be ssh-rsa, rsa-sha2-256, rsa-sha2-512
                // we don't check here because we already checked in key_exchange
                // some signatures have the type embedded within the message and some don't
                list(, $signature) = Strings::unpackSSH2('ss', $signature);

                $key = RSA::loadFormat('OpenSSH', $server_public_host_key)
                    ->withPadding(RSA::SIGNATURE_PKCS1);
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
                $key = $key->withHash($hash);
                break;
            default:
                $this->disconnect_helper(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
                throw new NoSupportedAlgorithmsException('Unsupported signature format');
        }

        if (!$key->verify($this->exchange_hash, $signature)) {
            return $this->disconnect_helper(NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE);
        };

        return $this->signature_format . ' ' . $server_public_host_key;
    }

    /**
     * Returns the exit status of an SSH command or false.
     *
     * @return false|int
     */
    public function getExitStatus()
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
     */
    public function getWindowColumns()
    {
        return $this->windowColumns;
    }

    /**
     * Returns the number of rows for the terminal window size.
     *
     * @return int
     */
    public function getWindowRows()
    {
        return $this->windowRows;
    }

    /**
     * Sets the number of columns for the terminal window size.
     *
     * @param int $value
     */
    public function setWindowColumns($value)
    {
        $this->windowColumns = $value;
    }

    /**
     * Sets the number of rows for the terminal window size.
     *
     * @param int $value
     */
    public function setWindowRows($value)
    {
        $this->windowRows = $value;
    }

    /**
     * Sets the number of columns and rows for the terminal window size.
     *
     * @param int $columns
     * @param int $rows
     */
    public function setWindowSize($columns = 80, $rows = 24)
    {
        $this->windowColumns = $columns;
        $this->windowRows = $rows;
    }

    /**
     * To String Magic Method
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function __toString()
    {
        return $this->getResourceId();
    }

    /**
     * Get Resource ID
     *
     * We use {} because that symbols should not be in URL according to
     * {@link http://tools.ietf.org/html/rfc3986#section-2 RFC}.
     * It will safe us from any conflicts, because otherwise regexp will
     * match all alphanumeric domains.
     *
     * @return string
     */
    public function getResourceId()
    {
        return '{' . spl_object_hash($this) . '}';
    }

    /**
     * Return existing connection
     *
     * @param string $id
     *
     * @return bool|SSH2 will return false if no such connection
     */
    public static function getConnectionByResourceId($id)
    {
        if (isset(self::$connections[$id])) {
            return self::$connections[$id] instanceof \WeakReference ? self::$connections[$id]->get() : self::$connections[$id];
        }
        return false;
    }

    /**
     * Return all excising connections
     *
     * @return array<string, SSH2>
     */
    public static function getConnections()
    {
        if (!class_exists('WeakReference')) {
            /** @var array<string, SSH2> */
            return self::$connections;
        }
        $temp = [];
        foreach (self::$connections as $key => $ref) {
            $temp[$key] = $ref->get();
        }
        return $temp;
    }

    /*
     * Update packet types in log history
     *
     * @param string $old
     * @param string $new
     */
    private function updateLogHistory($old, $new)
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

    /**
     * Enables "smart" multi-factor authentication (MFA)
     */
    public function enableSmartMFA()
    {
        $this->smartMFA = true;
    }

    /**
     * Disables "smart" multi-factor authentication (MFA)
     */
    public function disableSmartMFA()
    {
        $this->smartMFA = false;
    }
}
