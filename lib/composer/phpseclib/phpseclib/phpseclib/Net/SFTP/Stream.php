<?php

/**
 * SFTP Stream Wrapper
 *
 * Creates an sftp:// protocol handler that can be used with, for example, fopen(), dir(), etc.
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2013 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Net\SFTP;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

/**
 * SFTP Stream Wrapper
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Stream
{
    /**
     * SFTP instances
     *
     * Rather than re-create the connection we re-use instances if possible
     *
     * @var array
     */
    public static $instances;

    /**
     * SFTP instance
     *
     * @var object
     */
    private $sftp;

    /**
     * Path
     *
     * @var string
     */
    private $path;

    /**
     * Mode
     *
     * @var string
     */
    private $mode;

    /**
     * Position
     *
     * @var int
     */
    private $pos;

    /**
     * Size
     *
     * @var int
     */
    private $size;

    /**
     * Directory entries
     *
     * @var array
     */
    private $entries;

    /**
     * EOF flag
     *
     * @var bool
     */
    private $eof;

    /**
     * Context resource
     *
     * Technically this needs to be publicly accessible so PHP can set it directly
     *
     * @var resource
     */
    public $context;

    /**
     * Notification callback function
     *
     * @var callable
     */
    private $notification;

    /**
     * Registers this class as a URL wrapper.
     *
     * @param string $protocol The wrapper name to be registered.
     * @return bool True on success, false otherwise.
     */
    public static function register($protocol = 'sftp')
    {
        if (in_array($protocol, stream_get_wrappers(), true)) {
            return false;
        }
        return stream_wrapper_register($protocol, get_called_class());
    }

    /**
     * The Constructor
     *
     */
    public function __construct()
    {
        if (defined('NET_SFTP_STREAM_LOGGING')) {
            echo "__construct()\r\n";
        }
    }

    /**
     * Path Parser
     *
     * Extract a path from a URI and actually connect to an SSH server if appropriate
     *
     * If "notification" is set as a context parameter the message code for successful login is
     * NET_SSH2_MSG_USERAUTH_SUCCESS. For a failed login it's NET_SSH2_MSG_USERAUTH_FAILURE.
     *
     * @param string $path
     * @return string
     */
    protected function parse_path($path)
    {
        $orig = $path;
        extract(parse_url($path) + ['port' => 22]);
        if (isset($query)) {
            $path .= '?' . $query;
        } elseif (preg_match('/(\?|\?#)$/', $orig)) {
            $path .= '?';
        }
        if (isset($fragment)) {
            $path .= '#' . $fragment;
        } elseif ($orig[strlen($orig) - 1] == '#') {
            $path .= '#';
        }

        if (!isset($host)) {
            return false;
        }

        if (isset($this->context)) {
            $context = stream_context_get_params($this->context);
            if (isset($context['notification'])) {
                $this->notification = $context['notification'];
            }
        }

        if (preg_match('/^{[a-z0-9]+}$/i', $host)) {
            $host = SSH2::getConnectionByResourceId($host);
            if ($host === false) {
                return false;
            }
            $this->sftp = $host;
        } else {
            if (isset($this->context)) {
                $context = stream_context_get_options($this->context);
            }
            if (isset($context[$scheme]['session'])) {
                $sftp = $context[$scheme]['session'];
            }
            if (isset($context[$scheme]['sftp'])) {
                $sftp = $context[$scheme]['sftp'];
            }
            if (isset($sftp) && $sftp instanceof SFTP) {
                $this->sftp = $sftp;
                return $path;
            }
            if (isset($context[$scheme]['username'])) {
                $user = $context[$scheme]['username'];
            }
            if (isset($context[$scheme]['password'])) {
                $pass = $context[$scheme]['password'];
            }
            if (isset($context[$scheme]['privkey']) && $context[$scheme]['privkey'] instanceof PrivateKey) {
                $pass = $context[$scheme]['privkey'];
            }

            if (!isset($user) || !isset($pass)) {
                return false;
            }

            // casting $pass to a string is necessary in the event that it's a \phpseclib3\Crypt\RSA object
            if (isset(self::$instances[$host][$port][$user][(string) $pass])) {
                $this->sftp = self::$instances[$host][$port][$user][(string) $pass];
            } else {
                $this->sftp = new SFTP($host, $port);
                $this->sftp->disableStatCache();
                if (isset($this->notification) && is_callable($this->notification)) {
                    /* if !is_callable($this->notification) we could do this:

                       user_error('fopen(): failed to call user notifier', E_USER_WARNING);

                       the ftp wrapper gives errors like that when the notifier isn't callable.
                       i've opted not to do that, however, since the ftp wrapper gives the line
                       on which the fopen occurred as the line number - not the line that the
                       user_error is on.
                    */
                    call_user_func($this->notification, STREAM_NOTIFY_CONNECT, STREAM_NOTIFY_SEVERITY_INFO, '', 0, 0, 0);
                    call_user_func($this->notification, STREAM_NOTIFY_AUTH_REQUIRED, STREAM_NOTIFY_SEVERITY_INFO, '', 0, 0, 0);
                    if (!$this->sftp->login($user, $pass)) {
                        call_user_func($this->notification, STREAM_NOTIFY_AUTH_RESULT, STREAM_NOTIFY_SEVERITY_ERR, 'Login Failure', NET_SSH2_MSG_USERAUTH_FAILURE, 0, 0);
                        return false;
                    }
                    call_user_func($this->notification, STREAM_NOTIFY_AUTH_RESULT, STREAM_NOTIFY_SEVERITY_INFO, 'Login Success', NET_SSH2_MSG_USERAUTH_SUCCESS, 0, 0);
                } else {
                    if (!$this->sftp->login($user, $pass)) {
                        return false;
                    }
                }
                self::$instances[$host][$port][$user][(string) $pass] = $this->sftp;
            }
        }

        return $path;
    }

    /**
     * Opens file or URL
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string $opened_path
     * @return bool
     */
    private function _stream_open($path, $mode, $options, &$opened_path)
    {
        $path = $this->parse_path($path);

        if ($path === false) {
            return false;
        }
        $this->path = $path;

        $this->size = $this->sftp->filesize($path);
        $this->mode = preg_replace('#[bt]$#', '', $mode);
        $this->eof = false;

        if ($this->size === false) {
            if ($this->mode[0] == 'r') {
                return false;
            } else {
                $this->sftp->touch($path);
                $this->size = 0;
            }
        } else {
            switch ($this->mode[0]) {
                case 'x':
                    return false;
                case 'w':
                    $this->sftp->truncate($path, 0);
                    $this->size = 0;
            }
        }

        $this->pos = $this->mode[0] != 'a' ? 0 : $this->size;

        return true;
    }

    /**
     * Read from stream
     *
     * @param int $count
     * @return mixed
     */
    private function _stream_read($count)
    {
        switch ($this->mode) {
            case 'w':
            case 'a':
            case 'x':
            case 'c':
                return false;
        }

        // commented out because some files - eg. /dev/urandom - will say their size is 0 when in fact it's kinda infinite
        //if ($this->pos >= $this->size) {
        //    $this->eof = true;
        //    return false;
        //}

        $result = $this->sftp->get($this->path, false, $this->pos, $count);
        if (isset($this->notification) && is_callable($this->notification)) {
            if ($result === false) {
                call_user_func($this->notification, STREAM_NOTIFY_FAILURE, STREAM_NOTIFY_SEVERITY_ERR, $this->sftp->getLastSFTPError(), NET_SFTP_OPEN, 0, 0);
                return 0;
            }
            // seems that PHP calls stream_read in 8k chunks
            call_user_func($this->notification, STREAM_NOTIFY_PROGRESS, STREAM_NOTIFY_SEVERITY_INFO, '', 0, strlen($result), $this->size);
        }

        if (empty($result)) { // ie. false or empty string
            $this->eof = true;
            return false;
        }
        $this->pos += strlen($result);

        return $result;
    }

    /**
     * Write to stream
     *
     * @param string $data
     * @return int|false
     */
    private function _stream_write($data)
    {
        switch ($this->mode) {
            case 'r':
                return false;
        }

        $result = $this->sftp->put($this->path, $data, SFTP::SOURCE_STRING, $this->pos);
        if (isset($this->notification) && is_callable($this->notification)) {
            if (!$result) {
                call_user_func($this->notification, STREAM_NOTIFY_FAILURE, STREAM_NOTIFY_SEVERITY_ERR, $this->sftp->getLastSFTPError(), NET_SFTP_OPEN, 0, 0);
                return 0;
            }
            // seems that PHP splits up strings into 8k blocks before calling stream_write
            call_user_func($this->notification, STREAM_NOTIFY_PROGRESS, STREAM_NOTIFY_SEVERITY_INFO, '', 0, strlen($data), strlen($data));
        }

        if ($result === false) {
            return false;
        }
        $this->pos += strlen($data);
        if ($this->pos > $this->size) {
            $this->size = $this->pos;
        }
        $this->eof = false;
        return strlen($data);
    }

    /**
     * Retrieve the current position of a stream
     *
     * @return int
     */
    private function _stream_tell()
    {
        return $this->pos;
    }

    /**
     * Tests for end-of-file on a file pointer
     *
     * In my testing there are four classes functions that normally effect the pointer:
     * fseek, fputs  / fwrite, fgets / fread and ftruncate.
     *
     * Only fgets / fread, however, results in feof() returning true. do fputs($fp, 'aaa') on a blank file and feof()
     * will return false. do fread($fp, 1) and feof() will then return true. do fseek($fp, 10) on ablank file and feof()
     * will return false. do fread($fp, 1) and feof() will then return true.
     *
     * @return bool
     */
    private function _stream_eof()
    {
        return $this->eof;
    }

    /**
     * Seeks to specific location in a stream
     *
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    private function _stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < 0) {
                    return false;
                }
                break;
            case SEEK_CUR:
                $offset += $this->pos;
                break;
            case SEEK_END:
                $offset += $this->size;
        }

        $this->pos = $offset;
        $this->eof = false;
        return true;
    }

    /**
     * Change stream options
     *
     * @param string $path
     * @param int $option
     * @param mixed $var
     * @return bool
     */
    private function _stream_metadata($path, $option, $var)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }

        // stream_metadata was introduced in PHP 5.4.0 but as of 5.4.11 the constants haven't been defined
        // see http://www.php.net/streamwrapper.stream-metadata and https://bugs.php.net/64246
        //     and https://github.com/php/php-src/blob/master/main/php_streams.h#L592
        switch ($option) {
            case 1: // PHP_STREAM_META_TOUCH
                $time = isset($var[0]) ? $var[0] : null;
                $atime = isset($var[1]) ? $var[1] : null;
                return $this->sftp->touch($path, $time, $atime);
            case 2: // PHP_STREAM_OWNER_NAME
            case 3: // PHP_STREAM_GROUP_NAME
                return false;
            case 4: // PHP_STREAM_META_OWNER
                return $this->sftp->chown($path, $var);
            case 5: // PHP_STREAM_META_GROUP
                return $this->sftp->chgrp($path, $var);
            case 6: // PHP_STREAM_META_ACCESS
                return $this->sftp->chmod($path, $var) !== false;
        }
    }

    /**
     * Retrieve the underlaying resource
     *
     * @param int $cast_as
     * @return resource
     */
    private function _stream_cast($cast_as)
    {
        return $this->sftp->fsock;
    }

    /**
     * Advisory file locking
     *
     * @param int $operation
     * @return bool
     */
    private function _stream_lock($operation)
    {
        return false;
    }

    /**
     * Renames a file or directory
     *
     * Attempts to rename oldname to newname, moving it between directories if necessary.
     * If newname exists, it will be overwritten.  This is a departure from what \phpseclib3\Net\SFTP
     * does.
     *
     * @param string $path_from
     * @param string $path_to
     * @return bool
     */
    private function _rename($path_from, $path_to)
    {
        $path1 = parse_url($path_from);
        $path2 = parse_url($path_to);
        unset($path1['path'], $path2['path']);
        if ($path1 != $path2) {
            return false;
        }

        $path_from = $this->parse_path($path_from);
        $path_to = parse_url($path_to);
        if ($path_from === false) {
            return false;
        }

        $path_to = $path_to['path']; // the $component part of parse_url() was added in PHP 5.1.2
        // "It is an error if there already exists a file with the name specified by newpath."
        //  -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-02#section-6.5
        if (!$this->sftp->rename($path_from, $path_to)) {
            if ($this->sftp->stat($path_to)) {
                return $this->sftp->delete($path_to, true) && $this->sftp->rename($path_from, $path_to);
            }
            return false;
        }

        return true;
    }

    /**
     * Open directory handle
     *
     * The only $options is "whether or not to enforce safe_mode (0x04)". Since safe mode was deprecated in 5.3 and
     * removed in 5.4 I'm just going to ignore it.
     *
     * Also, nlist() is the best that this function is realistically going to be able to do. When an SFTP client
     * sends a SSH_FXP_READDIR packet you don't generally get info on just one file but on multiple files. Quoting
     * the SFTP specs:
     *
     *    The SSH_FXP_NAME response has the following format:
     *
     *        uint32     id
     *        uint32     count
     *        repeats count times:
     *                string     filename
     *                string     longname
     *                ATTRS      attrs
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    private function _dir_opendir($path, $options)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }
        $this->pos = 0;
        $this->entries = $this->sftp->nlist($path);
        return $this->entries !== false;
    }

    /**
     * Read entry from directory handle
     *
     * @return mixed
     */
    private function _dir_readdir()
    {
        if (isset($this->entries[$this->pos])) {
            return $this->entries[$this->pos++];
        }
        return false;
    }

    /**
     * Rewind directory handle
     *
     * @return bool
     */
    private function _dir_rewinddir()
    {
        $this->pos = 0;
        return true;
    }

    /**
     * Close directory handle
     *
     * @return bool
     */
    private function _dir_closedir()
    {
        return true;
    }

    /**
     * Create a directory
     *
     * Only valid $options is STREAM_MKDIR_RECURSIVE
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    private function _mkdir($path, $mode, $options)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }

        return $this->sftp->mkdir($path, $mode, $options & STREAM_MKDIR_RECURSIVE);
    }

    /**
     * Removes a directory
     *
     * Only valid $options is STREAM_MKDIR_RECURSIVE per <http://php.net/streamwrapper.rmdir>, however,
     * <http://php.net/rmdir>  does not have a $recursive parameter as mkdir() does so I don't know how
     * STREAM_MKDIR_RECURSIVE is supposed to be set. Also, when I try it out with rmdir() I get 8 as
     * $options. What does 8 correspond to?
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    private function _rmdir($path, $options)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }

        return $this->sftp->rmdir($path);
    }

    /**
     * Flushes the output
     *
     * See <http://php.net/fflush>. Always returns true because \phpseclib3\Net\SFTP doesn't cache stuff before writing
     *
     * @return bool
     */
    private function _stream_flush()
    {
        return true;
    }

    /**
     * Retrieve information about a file resource
     *
     * @return mixed
     */
    private function _stream_stat()
    {
        $results = $this->sftp->stat($this->path);
        if ($results === false) {
            return false;
        }
        return $results;
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    private function _unlink($path)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }

        return $this->sftp->delete($path, false);
    }

    /**
     * Retrieve information about a file
     *
     * Ignores the STREAM_URL_STAT_QUIET flag because the entirety of \phpseclib3\Net\SFTP\Stream is quiet by default
     * might be worthwhile to reconstruct bits 12-16 (ie. the file type) if mode doesn't have them but we'll
     * cross that bridge when and if it's reached
     *
     * @param string $path
     * @param int $flags
     * @return mixed
     */
    private function _url_stat($path, $flags)
    {
        $path = $this->parse_path($path);
        if ($path === false) {
            return false;
        }

        $results = $flags & STREAM_URL_STAT_LINK ? $this->sftp->lstat($path) : $this->sftp->stat($path);
        if ($results === false) {
            return false;
        }

        return $results;
    }

    /**
     * Truncate stream
     *
     * @param int $new_size
     * @return bool
     */
    private function _stream_truncate($new_size)
    {
        if (!$this->sftp->truncate($this->path, $new_size)) {
            return false;
        }

        $this->eof = false;
        $this->size = $new_size;

        return true;
    }

    /**
     * Change stream options
     *
     * STREAM_OPTION_WRITE_BUFFER isn't supported for the same reason stream_flush isn't.
     * The other two aren't supported because of limitations in \phpseclib3\Net\SFTP.
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     * @return bool
     */
    private function _stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    /**
     * Close an resource
     *
     */
    private function _stream_close()
    {
    }

    /**
     * __call Magic Method
     *
     * When you're utilizing an SFTP stream you're not calling the methods in this class directly - PHP is calling them for you.
     * Which kinda begs the question... what methods is PHP calling and what parameters is it passing to them? This function
     * lets you figure that out.
     *
     * If NET_SFTP_STREAM_LOGGING is defined all calls will be output on the screen and then (regardless of whether or not
     * NET_SFTP_STREAM_LOGGING is enabled) the parameters will be passed through to the appropriate method.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (defined('NET_SFTP_STREAM_LOGGING')) {
            echo $name . '(';
            $last = count($arguments) - 1;
            foreach ($arguments as $i => $argument) {
                var_export($argument);
                if ($i != $last) {
                    echo ',';
                }
            }
            echo ")\r\n";
        }
        $name = '_' . $name;
        if (!method_exists($this, $name)) {
            return false;
        }
        return $this->$name(...$arguments);
    }
}
