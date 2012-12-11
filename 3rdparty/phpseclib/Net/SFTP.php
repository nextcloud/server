<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP implementation of SFTP.
 *
 * PHP versions 4 and 5
 *
 * Currently only supports SFTPv2 and v3, which, according to wikipedia.org, "is the most widely used version,
 * implemented by the popular OpenSSH SFTP server".  If you want SFTPv4/5/6 support, provide me with access
 * to an SFTPv4/5/6 server.
 *
 * The API for this library is modeled after the API from PHP's {@link http://php.net/book.ftp FTP extension}.
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include('Net/SFTP.php');
 *
 *    $sftp = new Net_SFTP('www.domain.tld');
 *    if (!$sftp->login('username', 'password')) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $sftp->pwd() . "\r\n";
 *    $sftp->put('filename.ext', 'hello, world!');
 *    print_r($sftp->nlist());
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
 * @category   Net
 * @package    Net_SFTP
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMIX Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link       http://phpseclib.sourceforge.net
 */

/**
 * Include Net_SSH2
 */
if (!class_exists('Net_SSH2')) {
    require_once('Net/SSH2.php');
}

/**#@+
 * @access public
 * @see Net_SFTP::getLog()
 */
/**
 * Returns the message numbers
 */
define('NET_SFTP_LOG_SIMPLE',  NET_SSH2_LOG_SIMPLE);
/**
 * Returns the message content
 */
define('NET_SFTP_LOG_COMPLEX', NET_SSH2_LOG_COMPLEX);
/**
 * Outputs the message content in real-time.
 */
define('NET_SFTP_LOG_REALTIME', 3);
/**#@-*/

/**
 * SFTP channel constant
 *
 * Net_SSH2::exec() uses 0 and Net_SSH2::read() / Net_SSH2::write() use 1.
 *
 * @see Net_SSH2::_send_channel_packet()
 * @see Net_SSH2::_get_channel_packet()
 * @access private
 */
define('NET_SFTP_CHANNEL', 2);

/**#@+
 * @access public
 * @see Net_SFTP::put()
 */
/**
 * Reads data from a local file.
 */
define('NET_SFTP_LOCAL_FILE', 1);
/**
 * Reads data from a string.
 */
// this value isn't really used anymore but i'm keeping it reserved for historical reasons
define('NET_SFTP_STRING',  2);
/**
 * Resumes an upload
 */
define('NET_SFTP_RESUME',  4);
/**#@-*/

/**
 * Pure-PHP implementations of SFTP.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.1.0
 * @access  public
 * @package Net_SFTP
 */
class Net_SFTP extends Net_SSH2 {
    /**
     * Packet Types
     *
     * @see Net_SFTP::Net_SFTP()
     * @var Array
     * @access private
     */
    var $packet_types = array();

    /**
     * Status Codes
     *
     * @see Net_SFTP::Net_SFTP()
     * @var Array
     * @access private
     */
    var $status_codes = array();

    /**
     * The Request ID
     *
     * The request ID exists in the off chance that a packet is sent out-of-order.  Of course, this library doesn't support
     * concurrent actions, so it's somewhat academic, here.
     *
     * @var Integer
     * @see Net_SFTP::_send_sftp_packet()
     * @access private
     */
    var $request_id = false;

    /**
     * The Packet Type
     *
     * The request ID exists in the off chance that a packet is sent out-of-order.  Of course, this library doesn't support
     * concurrent actions, so it's somewhat academic, here.
     *
     * @var Integer
     * @see Net_SFTP::_get_sftp_packet()
     * @access private
     */
    var $packet_type = -1;

    /**
     * Packet Buffer
     *
     * @var String
     * @see Net_SFTP::_get_sftp_packet()
     * @access private
     */
    var $packet_buffer = '';

    /**
     * Extensions supported by the server
     *
     * @var Array
     * @see Net_SFTP::_initChannel()
     * @access private
     */
    var $extensions = array();

    /**
     * Server SFTP version
     *
     * @var Integer
     * @see Net_SFTP::_initChannel()
     * @access private
     */
    var $version;

    /**
     * Current working directory
     *
     * @var String
     * @see Net_SFTP::_realpath()
     * @see Net_SFTP::chdir()
     * @access private
     */
    var $pwd = false;

    /**
     * Packet Type Log
     *
     * @see Net_SFTP::getLog()
     * @var Array
     * @access private
     */
    var $packet_type_log = array();

    /**
     * Packet Log
     *
     * @see Net_SFTP::getLog()
     * @var Array
     * @access private
     */
    var $packet_log = array();

    /**
     * Error information
     *
     * @see Net_SFTP::getSFTPErrors()
     * @see Net_SFTP::getLastSFTPError()
     * @var String
     * @access private
     */
    var $sftp_errors = array();

    /**
     * File Type
     *
     * @see Net_SFTP::_parseLongname()
     * @var Integer
     * @access private
     */
    var $fileType = 0;

    /**
     * Directory Cache
     *
     * Rather than always having to open a directory and close it immediately there after to see if a file is a directory or
     * rather than always 
     *
     * @see Net_SFTP::_save_dir()
     * @see Net_SFTP::_remove_dir()
     * @see Net_SFTP::_is_dir()
     * @var Array
     * @access private
     */
    var $dirs = array();

    /**
     * Default Constructor.
     *
     * Connects to an SFTP server
     *
     * @param String $host
     * @param optional Integer $port
     * @param optional Integer $timeout
     * @return Net_SFTP
     * @access public
     */
    function Net_SFTP($host, $port = 22, $timeout = 10)
    {
        parent::Net_SSH2($host, $port, $timeout);
        $this->packet_types = array(
            1  => 'NET_SFTP_INIT',
            2  => 'NET_SFTP_VERSION',
            /* the format of SSH_FXP_OPEN changed between SFTPv4 and SFTPv5+:
                   SFTPv5+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.1
               pre-SFTPv5 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.3 */
            3  => 'NET_SFTP_OPEN',
            4  => 'NET_SFTP_CLOSE',
            5  => 'NET_SFTP_READ',
            6  => 'NET_SFTP_WRITE',
            7  => 'NET_SFTP_LSTAT',
            9  => 'NET_SFTP_SETSTAT',
            11 => 'NET_SFTP_OPENDIR',
            12 => 'NET_SFTP_READDIR',
            13 => 'NET_SFTP_REMOVE',
            14 => 'NET_SFTP_MKDIR',
            15 => 'NET_SFTP_RMDIR',
            16 => 'NET_SFTP_REALPATH',
            17 => 'NET_SFTP_STAT',
            /* the format of SSH_FXP_RENAME changed between SFTPv4 and SFTPv5+:
                   SFTPv5+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
               pre-SFTPv5 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.5 */
            18 => 'NET_SFTP_RENAME',

            101=> 'NET_SFTP_STATUS',
            102=> 'NET_SFTP_HANDLE',
            /* the format of SSH_FXP_NAME changed between SFTPv3 and SFTPv4+:
                   SFTPv4+: http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-9.4
               pre-SFTPv4 : http://tools.ietf.org/html/draft-ietf-secsh-filexfer-02#section-7 */
            103=> 'NET_SFTP_DATA',
            104=> 'NET_SFTP_NAME',
            105=> 'NET_SFTP_ATTRS',

            200=> 'NET_SFTP_EXTENDED'
        );
        $this->status_codes = array(
            0 => 'NET_SFTP_STATUS_OK',
            1 => 'NET_SFTP_STATUS_EOF',
            2 => 'NET_SFTP_STATUS_NO_SUCH_FILE',
            3 => 'NET_SFTP_STATUS_PERMISSION_DENIED',
            4 => 'NET_SFTP_STATUS_FAILURE',
            5 => 'NET_SFTP_STATUS_BAD_MESSAGE',
            6 => 'NET_SFTP_STATUS_NO_CONNECTION',
            7 => 'NET_SFTP_STATUS_CONNECTION_LOST',
            8 => 'NET_SFTP_STATUS_OP_UNSUPPORTED'
        );
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-7.1
        // the order, in this case, matters quite a lot - see Net_SFTP::_parseAttributes() to understand why
        $this->attributes = array(
            0x00000001 => 'NET_SFTP_ATTR_SIZE',
            0x00000002 => 'NET_SFTP_ATTR_UIDGID', // defined in SFTPv3, removed in SFTPv4+
            0x00000004 => 'NET_SFTP_ATTR_PERMISSIONS',
            0x00000008 => 'NET_SFTP_ATTR_ACCESSTIME',
            // 0x80000000 will yield a floating point on 32-bit systems and converting floating points to integers
            // yields inconsistent behavior depending on how php is compiled.  so we left shift -1 (which, in 
            // two's compliment, consists of all 1 bits) by 31.  on 64-bit systems this'll yield 0xFFFFFFFF80000000.
            // that's not a problem, however, and 'anded' and a 32-bit number, as all the leading 1 bits are ignored.
              -1 << 31 => 'NET_SFTP_ATTR_EXTENDED'
        );
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.3
        // the flag definitions change somewhat in SFTPv5+.  if SFTPv5+ support is added to this library, maybe name
        // the array for that $this->open5_flags and similarily alter the constant names.
        $this->open_flags = array(
            0x00000001 => 'NET_SFTP_OPEN_READ',
            0x00000002 => 'NET_SFTP_OPEN_WRITE',
            0x00000004 => 'NET_SFTP_OPEN_APPEND',
            0x00000008 => 'NET_SFTP_OPEN_CREATE',
            0x00000010 => 'NET_SFTP_OPEN_TRUNCATE'
        );
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-5.2
        // see Net_SFTP::_parseLongname() for an explanation
        $this->file_types = array(
            1 => 'NET_SFTP_TYPE_REGULAR',
            2 => 'NET_SFTP_TYPE_DIRECTORY',
            3 => 'NET_SFTP_TYPE_SYMLINK',
            4 => 'NET_SFTP_TYPE_SPECIAL'
        );
        $this->_define_array(
            $this->packet_types,
            $this->status_codes,
            $this->attributes,
            $this->open_flags,
            $this->file_types
        );
    }

    /**
     * Login
     *
     * @param String $username
     * @param optional String $password
     * @return Boolean
     * @access public
     */
    function login($username, $password = '')
    {
        if (!parent::login($username, $password)) {
            return false;
        }

        $this->window_size_client_to_server[NET_SFTP_CHANNEL] = $this->window_size;

        $packet = pack('CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN, strlen('session'), 'session', NET_SFTP_CHANNEL, $this->window_size, 0x4000);

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[NET_SFTP_CHANNEL] = NET_SSH2_MSG_CHANNEL_OPEN;

        $response = $this->_get_channel_packet(NET_SFTP_CHANNEL);
        if ($response === false) {
            return false;
        }

        $packet = pack('CNNa*CNa*',
            NET_SSH2_MSG_CHANNEL_REQUEST, $this->server_channels[NET_SFTP_CHANNEL], strlen('subsystem'), 'subsystem', 1, strlen('sftp'), 'sftp');
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $this->channel_status[NET_SFTP_CHANNEL] = NET_SSH2_MSG_CHANNEL_REQUEST;

        $response = $this->_get_channel_packet(NET_SFTP_CHANNEL);
        if ($response === false) {
            return false;
        }

        $this->channel_status[NET_SFTP_CHANNEL] = NET_SSH2_MSG_CHANNEL_DATA;

        if (!$this->_send_sftp_packet(NET_SFTP_INIT, "\0\0\0\3")) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_VERSION) {
            user_error('Expected SSH_FXP_VERSION', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nversion', $this->_string_shift($response, 4)));
        $this->version = $version;
        while (!empty($response)) {
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $key = $this->_string_shift($response, $length);
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $value = $this->_string_shift($response, $length);
            $this->extensions[$key] = $value;
        }

        /*
         SFTPv4+ defines a 'newline' extension.  SFTPv3 seems to have unofficial support for it via 'newline@vandyke.com',
         however, I'm not sure what 'newline@vandyke.com' is supposed to do (the fact that it's unofficial means that it's
         not in the official SFTPv3 specs) and 'newline@vandyke.com' / 'newline' are likely not drop-in substitutes for
         one another due to the fact that 'newline' comes with a SSH_FXF_TEXT bitmask whereas it seems unlikely that
         'newline@vandyke.com' would.
        */
        /*
        if (isset($this->extensions['newline@vandyke.com'])) {
            $this->extensions['newline'] = $this->extensions['newline@vandyke.com'];
            unset($this->extensions['newline@vandyke.com']);
        }
        */

        $this->request_id = 1;

        /*
         A Note on SFTPv4/5/6 support:
         <http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-5.1> states the following:

         "If the client wishes to interoperate with servers that support noncontiguous version
          numbers it SHOULD send '3'"

         Given that the server only sends its version number after the client has already done so, the above
         seems to be suggesting that v3 should be the default version.  This makes sense given that v3 is the
         most popular.

         <http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-5.5> states the following;

         "If the server did not send the "versions" extension, or the version-from-list was not included, the
          server MAY send a status response describing the failure, but MUST then close the channel without
          processing any further requests."

         So what do you do if you have a client whose initial SSH_FXP_INIT packet says it implements v3 and
         a server whose initial SSH_FXP_VERSION reply says it implements v4 and only v4?  If it only implements
         v4, the "versions" extension is likely not going to have been sent so version re-negotiation as discussed
         in draft-ietf-secsh-filexfer-13 would be quite impossible.  As such, what Net_SFTP would do is close the
         channel and reopen it with a new and updated SSH_FXP_INIT packet.
        */
        switch ($this->version) {
            case 2:
            case 3:
                break;
            default:
                return false;
        }

        $this->pwd = $this->_realpath('.', false);

        $this->_save_dir($this->pwd);

        return true;
    }

    /**
     * Returns the current directory name
     *
     * @return Mixed
     * @access public
     */
    function pwd()
    {
        return $this->pwd;
    }

    /**
     * Logs errors
     *
     * @param String $response
     * @param optional Integer $status
     * @access public
     */
    function _logError($response, $status = -1) {
        if ($status == -1) {
            extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        }

        $error = $this->status_codes[$status];

        if ($this->version > 2) {
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $this->sftp_errors[] = $error . ': ' . $this->_string_shift($response, $length);
        } else {
            $this->sftp_errors[] = $error;
        }
    }

    /**
     * Canonicalize the Server-Side Path Name
     *
     * SFTP doesn't provide a mechanism by which the current working directory can be changed, so we'll emulate it.  Returns
     * the absolute (canonicalized) path.
     *
     * @see Net_SFTP::chdir()
     * @param String $dir
     * @return Mixed
     * @access private
     */
    function _realpath($dir, $check_dir = true)
    {
        if ($check_dir && $this->_is_dir($dir)) {
            return true;
        }

        /*
        "This protocol represents file names as strings.  File names are
         assumed to use the slash ('/') character as a directory separator.

         File names starting with a slash are "absolute", and are relative to
         the root of the file system.  Names starting with any other character
         are relative to the user's default directory (home directory).  Note
         that identifying the user is assumed to take place outside of this
         protocol."

         -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-6
        */
        $file = '';
        if ($this->pwd !== false) {
            // if the SFTP server returned the canonicalized path even for non-existant files this wouldn't be necessary
            // on OpenSSH it isn't necessary but on other SFTP servers it is.  that and since the specs say nothing on
            // the subject, we'll go ahead and work around it with the following.
            if (empty($dir) || $dir[strlen($dir) - 1] != '/') {
                $file = basename($dir);
                $dir = dirname($dir);
            }

            $dir = $dir[0] == '/' ? '/' . rtrim(substr($dir, 1), '/') : rtrim($dir, '/');

            if ($dir == '.' || $dir == $this->pwd) {
                $temp = $this->pwd;
                if (!empty($file)) {
                    $temp.= '/' . $file;
                }
                return $temp;
            }

            if ($dir[0] != '/') {
                $dir = $this->pwd . '/' . $dir;
            }
            // on the surface it seems like maybe resolving a path beginning with / is unnecessary, but such paths
            // can contain .'s and ..'s just like any other.  we could parse those out as appropriate or we can let
            // the server do it.  we'll do the latter.
        }

        /*
         that SSH_FXP_REALPATH returns SSH_FXP_NAME does not necessarily mean that anything actually exists at the
         specified path.  generally speaking, no attributes are returned with this particular SSH_FXP_NAME packet
         regardless of whether or not a file actually exists.  and in SFTPv3, the longname field and the filename
         field match for this particular SSH_FXP_NAME packet.  for other SSH_FXP_NAME packets, this will likely
         not be the case, but for this one, it is.
        */
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.9
        if (!$this->_send_sftp_packet(NET_SFTP_REALPATH, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_NAME:
                // although SSH_FXP_NAME is implemented differently in SFTPv3 than it is in SFTPv4+, the following
                // should work on all SFTP versions since the only part of the SSH_FXP_NAME packet the following looks
                // at is the first part and that part is defined the same in SFTP versions 3 through 6.
                $this->_string_shift($response, 4); // skip over the count - it should be 1, anyway
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $realpath = $this->_string_shift($response, $length);
                // the following is SFTPv3 only code.  see Net_SFTP::_parseLongname() for more information.
                // per the above comment, this is a shot in the dark that, on most servers, won't help us in determining
                // the file type for Net_SFTP::stat() and Net_SFTP::lstat() but it's worth a shot.
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->fileType = $this->_parseLongname($this->_string_shift($response, $length));
                break;
            case NET_SFTP_STATUS:
                $this->_logError($response);
                return false;
            default:
                user_error('Expected SSH_FXP_NAME or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        // if $this->pwd isn't set than the only thing $realpath could be is for '.', which is pretty much guaranteed to
        // be a bonafide directory
        if (!empty($file)) {
            $realpath.= '/' . $file;
        }

        return $realpath;
    }

    /**
     * Changes the current directory
     *
     * @param String $dir
     * @return Boolean
     * @access public
     */
    function chdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        if ($dir[strlen($dir) - 1] != '/') {
            $dir.= '/';
        }

        // confirm that $dir is, in fact, a valid directory
        if ($this->_is_dir($dir)) {
            $this->pwd = $dir;
            return true;
        }

        $dir = $this->_realpath($dir, false);

        if ($this->_is_dir($dir)) {
            $this->pwd = $dir;
            return true;
        }

        if (!$this->_send_sftp_packet(NET_SFTP_OPENDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        // see Net_SFTP::nlist() for a more thorough explanation of the following
        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                $this->_logError($response);
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            return false;
        }

        $this->_save_dir($dir);

        $this->pwd = $dir;
        return true;
    }

    /**
     * Returns a list of files in the given directory
     *
     * @param optional String $dir
     * @return Mixed
     * @access public
     */
    function nlist($dir = '.')
    {
        return $this->_list($dir, false);
    }

    /**
     * Returns a detailed list of files in the given directory
     *
     * @param optional String $dir
     * @return Mixed
     * @access public
     */
    function rawlist($dir = '.')
    {
        return $this->_list($dir, true);
    }

    /**
     * Reads a list, be it detailed or not, of files in the given directory
     *
     * @param optional String $dir
     * @return Mixed
     * @access private
     */
    function _list($dir, $raw = true, $realpath = true)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $dir = $this->_realpath($dir . '/');
        if ($dir === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.2
        if (!$this->_send_sftp_packet(NET_SFTP_OPENDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-9.2
                // since 'handle' is the last field in the SSH_FXP_HANDLE packet, we'll just remove the first four bytes that
                // represent the length of the string and leave it at that
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                $this->_logError($response);
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        $this->_save_dir($dir);

        $contents = array();
        while (true) {
            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.2
            // why multiple SSH_FXP_READDIR packets would be sent when the response to a single one can span arbitrarily many
            // SSH_MSG_CHANNEL_DATA messages is not known to me.
            if (!$this->_send_sftp_packet(NET_SFTP_READDIR, pack('Na*', strlen($handle), $handle))) {
                return false;
            }

            $response = $this->_get_sftp_packet();
            switch ($this->packet_type) {
                case NET_SFTP_NAME:
                    extract(unpack('Ncount', $this->_string_shift($response, 4)));
                    for ($i = 0; $i < $count; $i++) {
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $shortname = $this->_string_shift($response, $length);
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $longname = $this->_string_shift($response, $length);
                        $attributes = $this->_parseAttributes($response); // we also don't care about the attributes
                        if (!$raw) {
                            $contents[] = $shortname;
                        } else {
                            $contents[$shortname] = $attributes;
                            $fileType = $this->_parseLongname($longname);
                            if ($fileType) {
                                if ($fileType == NET_SFTP_TYPE_DIRECTORY && ($shortname != '.' && $shortname != '..')) {
                                    $this->_save_dir($dir . '/' . $shortname);
                                }
                                $contents[$shortname]['type'] = $fileType;
                            }
                        }
                        // SFTPv6 has an optional boolean end-of-list field, but we'll ignore that, since the
                        // final SSH_FXP_STATUS packet should tell us that, already.
                    }
                    break;
                case NET_SFTP_STATUS:
                    extract(unpack('Nstatus', $this->_string_shift($response, 4)));
                    if ($status != NET_SFTP_STATUS_EOF) {
                        $this->_logError($response, $status);
                        return false;
                    }
                    break 2;
                default:
                    user_error('Expected SSH_FXP_NAME or SSH_FXP_STATUS', E_USER_NOTICE);
                    return false;
            }
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        // "The client MUST release all resources associated with the handle regardless of the status."
        //  -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.3
        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            return false;
        }

        return $contents;
    }

    /**
     * Returns the file size, in bytes, or false, on failure
     *
     * Files larger than 4GB will show up as being exactly 4GB.
     *
     * @param String $filename
     * @return Mixed
     * @access public
     */
    function size($filename)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $filename = $this->_realpath($filename);
        if ($filename === false) {
            return false;
        }

        return $this->_size($filename);
    }

    /**
     * Save directories to cache
     *
     * @param String $dir
     * @access private
     */
    function _save_dir($dir)
    {
        // preg_replace('#^/|/(?=/)|/$#', '', $dir) == str_replace('//', '/', trim($dir, '/'))
        $dirs = explode('/', preg_replace('#^/|/(?=/)|/$#', '', $dir));

        $temp = &$this->dirs;
        foreach ($dirs as $dir) {
            if (!isset($temp[$dir])) {
                $temp[$dir] = array();
            }
            $temp = &$temp[$dir];
        }
    }

    /**
     * Remove directories from cache
     *
     * @param String $dir
     * @access private
     */
    function _remove_dir($dir)
    {
        $dirs = explode('/', preg_replace('#^/|/(?=/)|/$#', '', $dir));

        $temp = &$this->dirs;
        foreach ($dirs as $dir) {
            if ($dir == end($dirs)) {
                unset($temp[$dir]);
                return true;
            }
            if (!isset($temp[$dir])) {
                return false;
            }
            $temp = &$temp[$dir];
        }
    }

    /**
     * Checks cache for directory
     *
     * @param String $dir
     * @access private
     */
    function _is_dir($dir)
    {
        $dirs = explode('/', preg_replace('#^/|/(?=/)|/$#', '', $dir));

        $temp = &$this->dirs;
        foreach ($dirs as $dir) {
            if (!isset($temp[$dir])) {
                return false;
            }
            $temp = &$temp[$dir];
        }
    }

    /**
     * Returns general information about a file.
     *
     * Returns an array on success and false otherwise.
     *
     * @param String $filename
     * @return Mixed
     * @access public
     */
    function stat($filename)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $filename = $this->_realpath($filename);
        if ($filename === false) {
            return false;
        }

        $stat = $this->_stat($filename, NET_SFTP_STAT);
        if ($stat === false) {
            return false;
        }

        $pwd = $this->pwd;
        $stat['type'] = $this->chdir($filename) ?
            NET_SFTP_TYPE_DIRECTORY :
            NET_SFTP_TYPE_REGULAR;
        $this->pwd = $pwd;

        return $stat;
    }

    /**
     * Returns general information about a file or symbolic link.
     *
     * Returns an array on success and false otherwise.
     *
     * @param String $filename
     * @return Mixed
     * @access public
     */
    function lstat($filename)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $filename = $this->_realpath($filename);
        if ($filename === false) {
            return false;
        }

        $lstat = $this->_stat($filename, NET_SFTP_LSTAT);
        $stat = $this->_stat($filename, NET_SFTP_STAT);
        if ($stat === false) {
            return false;
        }

        if ($lstat != $stat) {
            return array_merge($lstat, array('type' => NET_SFTP_TYPE_SYMLINK));
        }

        $pwd = $this->pwd;
        $lstat['type'] = $this->chdir($filename) ?
            NET_SFTP_TYPE_DIRECTORY :
            NET_SFTP_TYPE_REGULAR;
        $this->pwd = $pwd;

        return $lstat;
    }

    /**
     * Returns general information about a file or symbolic link
     *
     * Determines information without calling Net_SFTP::_realpath().
     * The second parameter can be either NET_SFTP_STAT or NET_SFTP_LSTAT.
     *
     * @param String $filename
     * @param Integer $type
     * @return Mixed
     * @access private
     */
    function _stat($filename, $type)
    {
        // SFTPv4+ adds an additional 32-bit integer field - flags - to the following:
        $packet = pack('Na*', strlen($filename), $filename);
        if (!$this->_send_sftp_packet($type, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_ATTRS:
                $attributes = $this->_parseAttributes($response);
                if ($this->fileType) {
                    $attributes['type'] = $this->fileType;
                }
                return $attributes;
            case NET_SFTP_STATUS:
                $this->_logError($response);
                return false;
        }

        user_error('Expected SSH_FXP_ATTRS or SSH_FXP_STATUS', E_USER_NOTICE);
        return false;
    }

    /**
     * Attempt to identify the file type
     *
     * @param String $path
     * @param Array $stat
     * @param Array $lstat
     * @return Integer
     * @access private
     */
    function _identify_type($path, $stat1, $stat2)
    {
        $stat1 = $this->_stat($path, $stat1);
        $stat2 = $this->_stat($path, $stat2);

        if ($stat1 != $stat2) {
            return array_merge($stat1, array('type' => NET_SFTP_TYPE_SYMLINK));
        }

        $pwd = $this->pwd;
        $stat1['type'] = $this->chdir($path) ?
            NET_SFTP_TYPE_DIRECTORY :
            NET_SFTP_TYPE_REGULAR;
        $this->pwd = $pwd;

        return $stat1;
    }

    /**
     * Returns the file size, in bytes, or false, on failure
     *
     * Determines the size without calling Net_SFTP::_realpath()
     *
     * @param String $filename
     * @return Mixed
     * @access private
     */
    function _size($filename)
    {
        $result = $this->_stat($filename, NET_SFTP_LSTAT);
        if ($result === false) {
            return false;
        }
        return isset($result['size']) ? $result['size'] : -1;
    }

    /**
     * Set permissions on a file.
     *
     * Returns the new file permissions on success or FALSE on error.
     *
     * @param Integer $mode
     * @param String $filename
     * @return Mixed
     * @access public
     */
    function chmod($mode, $filename, $recursive = false)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $filename = $this->_realpath($filename);
        if ($filename === false) {
            return false;
        }

        if ($recursive) {
            $i = 0;
            $result = $this->_chmod_recursive($mode, $filename, $i);
            $this->_read_put_responses($i);
            return $result;
        }

        // SFTPv4+ has an additional byte field - type - that would need to be sent, as well. setting it to
        // SSH_FILEXFER_TYPE_UNKNOWN might work. if not, we'd have to do an SSH_FXP_STAT before doing an SSH_FXP_SETSTAT.
        $attr = pack('N2', NET_SFTP_ATTR_PERMISSIONS, $mode & 07777);
        if (!$this->_send_sftp_packet(NET_SFTP_SETSTAT, pack('Na*a*', strlen($filename), $filename, $attr))) {
            return false;
        }

        /*
         "Because some systems must use separate system calls to set various attributes, it is possible that a failure 
          response will be returned, but yet some of the attributes may be have been successfully modified.  If possible,
          servers SHOULD avoid this situation; however, clients MUST be aware that this is possible."

          -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.6
        */
        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
        }

        // rather than return what the permissions *should* be, we'll return what they actually are.  this will also
        // tell us if the file actually exists.
        // incidentally, SFTPv4+ adds an additional 32-bit integer field - flags - to the following:
        $packet = pack('Na*', strlen($filename), $filename);
        if (!$this->_send_sftp_packet(NET_SFTP_STAT, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_ATTRS:
                $attrs = $this->_parseAttributes($response);
                return $attrs['permissions'];
            case NET_SFTP_STATUS:
                $this->_logError($response);
                return false;
        }

        user_error('Expected SSH_FXP_ATTRS or SSH_FXP_STATUS', E_USER_NOTICE);
        return false;
    }

    /**
     * Recursively chmods directories on the SFTP server
     *
     * Minimizes directory lookups and SSH_FXP_STATUS requests for speed.
     *
     * @param Integer $mode
     * @param String $filename
     * @return Boolean
     * @access private
     */
    function _chmod_recursive($mode, $path, &$i)
    {
        if (!$this->_read_put_responses($i)) {
            return false;
        }
        $i = 0;
        $entries = $this->_list($path, true, false);

        if ($entries === false) {
            return $this->chmod($mode, $path);
        }

        // normally $entries would have at least . and .. but it might not if the directories
        // permissions didn't allow reading
        if (empty($entries)) {
            return false;
        }

        foreach ($entries as $filename=>$props) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }

            if (!isset($props['type'])) {
                return false;
            }

            $temp = $path . '/' . $filename;
            if ($props['type'] == NET_SFTP_TYPE_DIRECTORY) {
                if (!$this->_chmod_recursive($mode, $temp, $i)) {
                    return false;
                }
            } else {
                $attr = pack('N2', NET_SFTP_ATTR_PERMISSIONS, $mode & 07777);
                if (!$this->_send_sftp_packet(NET_SFTP_SETSTAT, pack('Na*a*', strlen($temp), $temp, $attr))) {
                    return false;
                }

                $i++;

                if ($i >= 50) {
                    if (!$this->_read_put_responses($i)) {
                        return false;
                    }
                    $i = 0;
                }
            }
        }

        $attr = pack('N2', NET_SFTP_ATTR_PERMISSIONS, $mode & 07777);
        if (!$this->_send_sftp_packet(NET_SFTP_SETSTAT, pack('Na*a*', strlen($path), $path, $attr))) {
            return false;
        }

        $i++;

        if ($i >= 50) {
            if (!$this->_read_put_responses($i)) {
                return false;
            }
            $i = 0;
        }

        return true;
    }

    /**
     * Creates a directory.
     *
     * @param String $dir
     * @return Boolean
     * @access public
     */
    function mkdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        if ($dir[0] != '/') {
            $dir = $this->_realpath(rtrim($dir, '/'));
            if ($dir === false) {
                return false;
            }
            if (!$this->_mkdir_helper($dir)) {
                return false;
            }
        } else {
            $dirs = explode('/', preg_replace('#^/|/(?=/)|/$#', '', $dir));
            $temp = '';
            foreach ($dirs as $dir) {
                $temp.= '/' . $dir;
                $result = $this->_mkdir_helper($temp);
            }
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper function for directory creation
     *
     * @param String $dir
     * @return Boolean
     * @access private
     */
    function _mkdir_helper($dir)
    {
        // by not providing any permissions, hopefully the server will use the logged in users umask - their 
        // default permissions.
        if (!$this->_send_sftp_packet(NET_SFTP_MKDIR, pack('Na*N', strlen($dir), $dir, 0))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            return false;
        }

        $this->_save_dir($dir);

        return true;
    }

    /**
     * Removes a directory.
     *
     * @param String $dir
     * @return Boolean
     * @access public
     */
    function rmdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $dir = $this->_realpath($dir);
        if ($dir === false) {
            return false;
        }

        if (!$this->_send_sftp_packet(NET_SFTP_RMDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED?
            $this->_logError($response, $status);
            return false;
        }

        $this->_remove_dir($dir);

        return true;
    }

    /**
     * Uploads a file to the SFTP server.
     *
     * By default, Net_SFTP::put() does not read from the local filesystem.  $data is dumped directly into $remote_file.
     * So, for example, if you set $data to 'filename.ext' and then do Net_SFTP::get(), you will get a file, twelve bytes
     * long, containing 'filename.ext' as its contents.
     *
     * Setting $mode to NET_SFTP_LOCAL_FILE will change the above behavior.  With NET_SFTP_LOCAL_FILE, $remote_file will 
     * contain as many bytes as filename.ext does on your local filesystem.  If your filename.ext is 1MB then that is how
     * large $remote_file will be, as well.
     *
     * Currently, only binary mode is supported.  As such, if the line endings need to be adjusted, you will need to take
     * care of that, yourself.
     *
     * @param String $remote_file
     * @param String $data
     * @param optional Integer $mode
     * @return Boolean
     * @access public
     * @internal ASCII mode for SFTPv4/5/6 can be supported by adding a new function - Net_SFTP::setMode().
     */
    function put($remote_file, $data, $mode = NET_SFTP_STRING)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $remote_file = $this->_realpath($remote_file);
        if ($remote_file === false) {
            return false;
        }

        $flags = NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE;
        // according to the SFTP specs, NET_SFTP_OPEN_APPEND should "force all writes to append data at the end of the file."
        // in practice, it doesn't seem to do that.
        //$flags|= ($mode & NET_SFTP_RESUME) ? NET_SFTP_OPEN_APPEND : NET_SFTP_OPEN_TRUNCATE;

        // if NET_SFTP_OPEN_APPEND worked as it should the following (up until the -----------) wouldn't be necessary
        $offset = 0;
        if ($mode & NET_SFTP_RESUME) {
            $size = $this->_size($remote_file);
            $offset = $size !== false ? $size : 0;
        } else {
            $flags|= NET_SFTP_OPEN_TRUNCATE;
        }
        // --------------

        $packet = pack('Na*N2', strlen($remote_file), $remote_file, $flags, 0);
        if (!$this->_send_sftp_packet(NET_SFTP_OPEN, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                $this->_logError($response);
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        $initialize = true;

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.3
        if ($mode & NET_SFTP_LOCAL_FILE) {
            if (!is_file($data)) {
                user_error("$data is not a valid file", E_USER_NOTICE);
                return false;
            }
            $fp = @fopen($data, 'rb');
            if (!$fp) {
                return false;
            }
            $size = filesize($data);
        } else {
            $size = strlen($data);
        }

        $sent = 0;
        $size = $size < 0 ? ($size & 0x7FFFFFFF) + 0x80000000 : $size;

        $sftp_packet_size = 4096; // PuTTY uses 4096
        $i = 0;
        while ($sent < $size) {
            $temp = $mode & NET_SFTP_LOCAL_FILE ? fread($fp, $sftp_packet_size) : $this->_string_shift($data, $sftp_packet_size);
            $packet = pack('Na*N3a*', strlen($handle), $handle, 0, $offset + $sent, strlen($temp), $temp);
            if (!$this->_send_sftp_packet(NET_SFTP_WRITE, $packet)) {
                fclose($fp);
                return false;
            }
            $sent+= strlen($temp);

            $i++;

            if ($i == 50) {
                if (!$this->_read_put_responses($i)) {
                    $i = 0;
                    break;
                }
                $i = 0;
            }
        }

        if (!$this->_read_put_responses($i)) {
            return false;
        }

        if ($mode & NET_SFTP_LOCAL_FILE) {
            fclose($fp);
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            return false;
        }

        return true;
    }

    /**
     * Reads multiple successive SSH_FXP_WRITE responses
     *
     * Sending an SSH_FXP_WRITE packet and immediately reading its response isn't as efficient as blindly sending out $i
     * SSH_FXP_WRITEs, in succession, and then reading $i responses.
     *
     * @param Integer $i
     * @return Boolean
     * @access private
     */
    function _read_put_responses($i)
    {
        while ($i--) {
            $response = $this->_get_sftp_packet();
            if ($this->packet_type != NET_SFTP_STATUS) {
                user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
            }

            extract(unpack('Nstatus', $this->_string_shift($response, 4)));
            if ($status != NET_SFTP_STATUS_OK) {
                $this->_logError($response, $status);
                break;
            }
        }

        return $i < 0;
    }

    /**
     * Downloads a file from the SFTP server.
     *
     * Returns a string containing the contents of $remote_file if $local_file is left undefined or a boolean false if
     * the operation was unsuccessful.  If $local_file is defined, returns true or false depending on the success of the
     * operation
     *
     * @param String $remote_file
     * @param optional String $local_file
     * @return Mixed
     * @access public
     */
    function get($remote_file, $local_file = false)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $remote_file = $this->_realpath($remote_file);
        if ($remote_file === false) {
            return false;
        }

        $packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_READ, 0);
        if (!$this->_send_sftp_packet(NET_SFTP_OPEN, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS: // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                $this->_logError($response);
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        if ($local_file !== false) {
            $fp = fopen($local_file, 'wb');
            if (!$fp) {
                return false;
            }
        } else {
            $content = '';
        }

        $read = 0;
        while (true) {
            $packet = pack('Na*N3', strlen($handle), $handle, 0, $read, 1 << 20);
            if (!$this->_send_sftp_packet(NET_SFTP_READ, $packet)) {
                if ($local_file !== false) {
                    fclose($fp);
                }
                return false;
            }

            $response = $this->_get_sftp_packet();
            switch ($this->packet_type) {
                case NET_SFTP_DATA:
                    $temp = substr($response, 4);
                    $read+= strlen($temp);
                    if ($local_file === false) {
                        $content.= $temp;
                    } else {
                        fputs($fp, $temp);
                    }
                    break;
                case NET_SFTP_STATUS:
                    $this->_logError($response);
                    break 2;
                default:
                    user_error('Expected SSH_FXP_DATA or SSH_FXP_STATUS', E_USER_NOTICE);
                    if ($local_file !== false) {
                        fclose($fp);
                    }
                    return false;
            }
        }

        if ($local_file !== false) {
            fclose($fp);
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        $this->_logError($response);

        // check the status from the NET_SFTP_STATUS case in the above switch after the file has been closed
        if ($status != NET_SFTP_STATUS_OK) {
            return false;
        }

        if (isset($content)) {
            return $content;
        }

        return true;
    }

    /**
     * Deletes a file on the SFTP server.
     *
     * @param String $path
     * @param Boolean $recursive
     * @return Boolean
     * @access public
     */
    function delete($path, $recursive = true)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $path = $this->_realpath($path);
        if ($path === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
        if (!$this->_send_sftp_packet(NET_SFTP_REMOVE, pack('Na*', strlen($path), $path))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            if (!$recursive) {
                return false;
            }
            $i = 0;
            $result = $this->_delete_recursive($path, $i);
            $this->_read_put_responses($i);
            return $result;
        }

        return true;
    }

    /**
     * Recursively deletes directories on the SFTP server
     *
     * Minimizes directory lookups and SSH_FXP_STATUS requests for speed.
     *
     * @param String $path
     * @param Integer $i
     * @return Boolean
     * @access private
     */
    function _delete_recursive($path, &$i)
    {
        if (!$this->_read_put_responses($i)) {
            return false;
        }
        $i = 0;
        $entries = $this->_list($path, true, false);

        // normally $entries would have at least . and .. but it might not if the directories
        // permissions didn't allow reading
        if (empty($entries)) {
            return false;
        }

        foreach ($entries as $filename=>$props) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }

            if (!isset($props['type'])) {
                return false;
            }

            $temp = $path . '/' . $filename;
            if ($props['type'] == NET_SFTP_TYPE_DIRECTORY) {
                if (!$this->_delete_recursive($temp, $i)) {
                    return false;
                }
            } else {
                if (!$this->_send_sftp_packet(NET_SFTP_REMOVE, pack('Na*', strlen($temp), $temp))) {
                    return false;
                }

                $i++;

                if ($i >= 50) {
                    if (!$this->_read_put_responses($i)) {
                        return false;
                    }
                    $i = 0;
                }
            }
        }

        if (!$this->_send_sftp_packet(NET_SFTP_RMDIR, pack('Na*', strlen($path), $path))) {
            return false;
        }
        $this->_remove_dir($path);

        $i++;

        if ($i >= 50) {
            if (!$this->_read_put_responses($i)) {
                return false;
            }
            $i = 0;
        }

        return true;
    }

    /**
     * Renames a file or a directory on the SFTP server
     *
     * @param String $oldname
     * @param String $newname
     * @return Boolean
     * @access public
     */
    function rename($oldname, $newname)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $oldname = $this->_realpath($oldname);
        $newname = $this->_realpath($newname);
        if ($oldname === false || $newname === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
        $packet = pack('Na*Na*', strlen($oldname), $oldname, strlen($newname), $newname);
        if (!$this->_send_sftp_packet(NET_SFTP_RENAME, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            $this->_logError($response, $status);
            return false;
        }

        return true;
    }

    /**
     * Parse Attributes
     *
     * See '7.  File Attributes' of draft-ietf-secsh-filexfer-13 for more info.
     *
     * @param String $response
     * @return Array
     * @access private
     */
    function _parseAttributes(&$response)
    {
        $attr = array();
        extract(unpack('Nflags', $this->_string_shift($response, 4)));
        // SFTPv4+ have a type field (a byte) that follows the above flag field
        foreach ($this->attributes as $key => $value) {
            switch ($flags & $key) {
                case NET_SFTP_ATTR_SIZE: // 0x00000001
                    // size is represented by a 64-bit integer, so we perhaps ought to be doing the following:
                    // $attr['size'] = new Math_BigInteger($this->_string_shift($response, 8), 256);
                    // of course, you shouldn't be using Net_SFTP to transfer files that are in excess of 4GB
                    // (0xFFFFFFFF bytes), anyway.  as such, we'll just represent all file sizes that are bigger than
                    // 4GB as being 4GB.
                    extract(unpack('Nupper/Nsize', $this->_string_shift($response, 8)));
                    if ($upper) {
                        $attr['size'] = 0xFFFFFFFF;
                    } else {
                        $attr['size'] = $size < 0 ? ($size & 0x7FFFFFFF) + 0x80000000 : $size;
                    }
                    break;
                case NET_SFTP_ATTR_UIDGID: // 0x00000002 (SFTPv3 only)
                    $attr+= unpack('Nuid/Ngid', $this->_string_shift($response, 8));
                    break;
                case NET_SFTP_ATTR_PERMISSIONS: // 0x00000004
                    $attr+= unpack('Npermissions', $this->_string_shift($response, 4));
                    break;
                case NET_SFTP_ATTR_ACCESSTIME: // 0x00000008
                    $attr+= unpack('Natime/Nmtime', $this->_string_shift($response, 8));
                    break;
                case NET_SFTP_ATTR_EXTENDED: // 0x80000000
                    extract(unpack('Ncount', $this->_string_shift($response, 4)));
                    for ($i = 0; $i < $count; $i++) {
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $key = $this->_string_shift($response, $length);
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $attr[$key] = $this->_string_shift($response, $length);                        
                    }
            }
        }
        return $attr;
    }

    /**
     * Parse Longname
     *
     * SFTPv3 doesn't provide any easy way of identifying a file type.  You could try to open
     * a file as a directory and see if an error is returned or you could try to parse the
     * SFTPv3-specific longname field of the SSH_FXP_NAME packet.  That's what this function does.
     * The result is returned using the
     * {@link http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-5.2 SFTPv4 type constants}.
     *
     * If the longname is in an unrecognized format bool(false) is returned.
     *
     * @param String $longname
     * @return Mixed
     * @access private
     */
    function _parseLongname($longname)
    {
        // http://en.wikipedia.org/wiki/Unix_file_types
        // http://en.wikipedia.org/wiki/Filesystem_permissions#Notation_of_traditional_Unix_permissions
        if (preg_match('#^[^/]([r-][w-][xstST-]){3}#', $longname)) {
            switch ($longname[0]) {
                case '-':
                    return NET_SFTP_TYPE_REGULAR;
                case 'd':
                    return NET_SFTP_TYPE_DIRECTORY;
                case 'l':
                    return NET_SFTP_TYPE_SYMLINK;
                default:
                    return NET_SFTP_TYPE_SPECIAL;
            }
        }

        return false;
    }

    /**
     * Sends SFTP Packets
     *
     * See '6. General Packet Format' of draft-ietf-secsh-filexfer-13 for more info.
     *
     * @param Integer $type
     * @param String $data
     * @see Net_SFTP::_get_sftp_packet()
     * @see Net_SSH2::_send_channel_packet()
     * @return Boolean
     * @access private
     */
    function _send_sftp_packet($type, $data)
    {
        $packet = $this->request_id !== false ?
            pack('NCNa*', strlen($data) + 5, $type, $this->request_id, $data) :
            pack('NCa*',  strlen($data) + 1, $type, $data);

        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
        $result = $this->_send_channel_packet(NET_SFTP_CHANNEL, $packet);
        $stop = strtok(microtime(), ' ') + strtok('');

        if (defined('NET_SFTP_LOGGING')) {
            $packet_type = '-> ' . $this->packet_types[$type] . 
                           ' (' . round($stop - $start, 4) . 's)';
            if (NET_SFTP_LOGGING == NET_SFTP_LOG_REALTIME) {
                echo "<pre>\r\n" . $this->_format_log(array($data), array($packet_type)) . "\r\n</pre>\r\n";
                flush();
                ob_flush();
            } else {
                $this->packet_type_log[] = $packet_type;
                if (NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX) {
                    $this->packet_log[] = $data;
                }
            }
        }

        return $result;
    }

    /**
     * Receives SFTP Packets
     *
     * See '6. General Packet Format' of draft-ietf-secsh-filexfer-13 for more info.
     *
     * Incidentally, the number of SSH_MSG_CHANNEL_DATA messages has no bearing on the number of SFTP packets present.
     * There can be one SSH_MSG_CHANNEL_DATA messages containing two SFTP packets or there can be two SSH_MSG_CHANNEL_DATA
     * messages containing one SFTP packet.
     *
     * @see Net_SFTP::_send_sftp_packet()
     * @return String
     * @access private
     */
    function _get_sftp_packet()
    {
        $this->curTimeout = false;

        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838

        // SFTP packet length
        while (strlen($this->packet_buffer) < 4) {
            $temp = $this->_get_channel_packet(NET_SFTP_CHANNEL);
            if (is_bool($temp)) {
                $this->packet_type = false;
                $this->packet_buffer = '';
                return false;
            }
            $this->packet_buffer.= $temp;
        }
        extract(unpack('Nlength', $this->_string_shift($this->packet_buffer, 4)));
        $tempLength = $length;
        $tempLength-= strlen($this->packet_buffer);

        // SFTP packet type and data payload
        while ($tempLength > 0) {
            $temp = $this->_get_channel_packet(NET_SFTP_CHANNEL);
            if (is_bool($temp)) {
                $this->packet_type = false;
                $this->packet_buffer = '';
                return false;
            }
            $this->packet_buffer.= $temp;
            $tempLength-= strlen($temp);
        }

        $stop = strtok(microtime(), ' ') + strtok('');

        $this->packet_type = ord($this->_string_shift($this->packet_buffer));

        if ($this->request_id !== false) {
            $this->_string_shift($this->packet_buffer, 4); // remove the request id
            $length-= 5; // account for the request id and the packet type
        } else {
            $length-= 1; // account for the packet type
        }

        $packet = $this->_string_shift($this->packet_buffer, $length);

        if (defined('NET_SFTP_LOGGING')) {
            $packet_type = '<- ' . $this->packet_types[$this->packet_type] . 
                           ' (' . round($stop - $start, 4) . 's)';
            if (NET_SFTP_LOGGING == NET_SFTP_LOG_REALTIME) {
                echo "<pre>\r\n" . $this->_format_log(array($packet), array($packet_type)) . "\r\n</pre>\r\n";
                flush();
                ob_flush();
            } else {
                $this->packet_type_log[] = $packet_type;
                if (NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX) {
                    $this->packet_log[] = $packet;
                }
            }
        }

        return $packet;
    }

    /**
     * Returns a log of the packets that have been sent and received.
     *
     * Returns a string if NET_SFTP_LOGGING == NET_SFTP_LOG_COMPLEX, an array if NET_SFTP_LOGGING == NET_SFTP_LOG_SIMPLE and false if !defined('NET_SFTP_LOGGING')
     *
     * @access public
     * @return String or Array
     */
    function getSFTPLog()
    {
        if (!defined('NET_SFTP_LOGGING')) {
            return false;
        }

        switch (NET_SFTP_LOGGING) {
            case NET_SFTP_LOG_COMPLEX:
                return $this->_format_log($this->packet_log, $this->packet_type_log);
                break;
            //case NET_SFTP_LOG_SIMPLE:
            default:
                return $this->packet_type_log;
        }
    }

    /**
     * Returns all errors
     *
     * @return String
     * @access public
     */
    function getSFTPErrors()
    {
        return $this->sftp_errors;
    }

    /**
     * Returns the last error
     *
     * @return String
     * @access public
     */
    function getLastSFTPError()
    {
        return count($this->sftp_errors) ? $this->sftp_errors[count($this->sftp_errors) - 1] : '';
    }

    /**
     * Get supported SFTP versions
     *
     * @return Array
     * @access public
     */
    function getSupportedVersions()
    {
        $temp = array('version' => $this->version);
        if (isset($this->extensions['versions'])) {
            $temp['extensions'] = $this->extensions['versions'];
        }
        return $temp;
    }

    /**
     * Disconnect
     *
     * @param Integer $reason
     * @return Boolean
     * @access private
     */
    function _disconnect($reason)
    {
        $this->pwd = false;
        parent::_disconnect($reason);
    }
}