<?php

namespace Safe;

use Safe\Exceptions\Ssh2Exception;

/**
 * Authenticate over SSH using the ssh agent
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $username Remote user name.
 * @throws Ssh2Exception
 *
 */
function ssh2_auth_agent($session, string $username): void
{
    error_clear_last();
    $result = \ssh2_auth_agent($session, $username);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Authenticate using a public hostkey read from a file.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $username
 * @param string $hostname
 * @param string $pubkeyfile
 * @param string $privkeyfile
 * @param string $passphrase If privkeyfile is encrypted (which it should
 * be), the passphrase must be provided.
 * @param string $local_username If local_username is omitted, then the value
 * for username will be used for it.
 * @throws Ssh2Exception
 *
 */
function ssh2_auth_hostbased_file($session, string $username, string $hostname, string $pubkeyfile, string $privkeyfile, string $passphrase = null, string $local_username = null): void
{
    error_clear_last();
    if ($local_username !== null) {
        $result = \ssh2_auth_hostbased_file($session, $username, $hostname, $pubkeyfile, $privkeyfile, $passphrase, $local_username);
    } elseif ($passphrase !== null) {
        $result = \ssh2_auth_hostbased_file($session, $username, $hostname, $pubkeyfile, $privkeyfile, $passphrase);
    } else {
        $result = \ssh2_auth_hostbased_file($session, $username, $hostname, $pubkeyfile, $privkeyfile);
    }
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Authenticate over SSH using a plain password. Since version 0.12 this function
 * also supports keyboard_interactive method.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $username Remote user name.
 * @param string $password Password for username
 * @throws Ssh2Exception
 *
 */
function ssh2_auth_password($session, string $username, string $password): void
{
    error_clear_last();
    $result = \ssh2_auth_password($session, $username, $password);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Authenticate using a public key read from a file.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $username
 * @param string $pubkeyfile The public key file needs to be in OpenSSH's format. It should look something like:
 *
 * ssh-rsa AAAAB3NzaC1yc2EAAA....NX6sqSnHA8= rsa-key-20121110
 * @param string $privkeyfile
 * @param string $passphrase If privkeyfile is encrypted (which it should
 * be), the passphrase must be provided.
 * @throws Ssh2Exception
 *
 */
function ssh2_auth_pubkey_file($session, string $username, string $pubkeyfile, string $privkeyfile, string $passphrase = null): void
{
    error_clear_last();
    if ($passphrase !== null) {
        $result = \ssh2_auth_pubkey_file($session, $username, $pubkeyfile, $privkeyfile, $passphrase);
    } else {
        $result = \ssh2_auth_pubkey_file($session, $username, $pubkeyfile, $privkeyfile);
    }
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Establish a connection to a remote SSH server.
 *
 * Once connected, the client should verify the server's hostkey using
 * ssh2_fingerprint, then authenticate using either
 * password or public key.
 *
 * @param string $host
 * @param int $port
 * @param array $methods methods may be an associative array with up to four parameters
 * as described below.
 *
 *
 * methods may be an associative array
 * with any or all of the following parameters.
 *
 *
 *
 * Index
 * Meaning
 * Supported Values*
 *
 *
 *
 *
 * kex
 *
 * List of key exchange methods to advertise, comma separated
 * in order of preference.
 *
 *
 * diffie-hellman-group1-sha1,
 * diffie-hellman-group14-sha1, and
 * diffie-hellman-group-exchange-sha1
 *
 *
 *
 * hostkey
 *
 * List of hostkey methods to advertise, comma separated
 * in order of preference.
 *
 *
 * ssh-rsa and
 * ssh-dss
 *
 *
 *
 * client_to_server
 *
 * Associative array containing crypt, compression, and
 * message authentication code (MAC) method preferences
 * for messages sent from client to server.
 *
 *
 *
 *
 * server_to_client
 *
 * Associative array containing crypt, compression, and
 * message authentication code (MAC) method preferences
 * for messages sent from server to client.
 *
 *
 *
 *
 *
 *
 *
 * * - Supported Values are dependent on methods supported by underlying library.
 * See libssh2 documentation for additional
 * information.
 *
 *
 *
 * client_to_server and
 * server_to_client may be an associative array
 * with any or all of the following parameters.
 *
 *
 *
 *
 * Index
 * Meaning
 * Supported Values*
 *
 *
 *
 *
 * crypt
 * List of crypto methods to advertise, comma separated
 * in order of preference.
 *
 * rijndael-cbc@lysator.liu.se,
 * aes256-cbc,
 * aes192-cbc,
 * aes128-cbc,
 * 3des-cbc,
 * blowfish-cbc,
 * cast128-cbc,
 * arcfour, and
 * none**
 *
 *
 *
 * comp
 * List of compression methods to advertise, comma separated
 * in order of preference.
 *
 * zlib and
 * none
 *
 *
 *
 * mac
 * List of MAC methods to advertise, comma separated
 * in order of preference.
 *
 * hmac-sha1,
 * hmac-sha1-96,
 * hmac-ripemd160,
 * hmac-ripemd160@openssh.com, and
 * none**
 *
 *
 *
 *
 *
 *
 *
 * Crypt and MAC method "none"
 *
 * For security reasons, none is disabled by the underlying
 * libssh2 library unless explicitly enabled
 * during build time by using the appropriate ./configure options.  See documentation
 * for the underlying library for more information.
 *
 *
 *
 * For security reasons, none is disabled by the underlying
 * libssh2 library unless explicitly enabled
 * during build time by using the appropriate ./configure options.  See documentation
 * for the underlying library for more information.
 * @param array $callbacks callbacks may be an associative array with any
 * or all of the following parameters.
 *
 *
 * Callbacks parameters
 *
 *
 *
 *
 * Index
 * Meaning
 * Prototype
 *
 *
 *
 *
 * ignore
 *
 * Name of function to call when an
 * SSH2_MSG_IGNORE packet is received
 *
 * void ignore_cb($message)
 *
 *
 * debug
 *
 * Name of function to call when an
 * SSH2_MSG_DEBUG packet is received
 *
 * void debug_cb($message, $language, $always_display)
 *
 *
 * macerror
 *
 * Name of function to call when a packet is received but the
 * message authentication code failed.  If the callback returns
 * TRUE, the mismatch will be ignored, otherwise the connection
 * will be terminated.
 *
 * bool macerror_cb($packet)
 *
 *
 * disconnect
 *
 * Name of function to call when an
 * SSH2_MSG_DISCONNECT packet is received
 *
 * void disconnect_cb($reason, $message, $language)
 *
 *
 *
 *
 * @return resource Returns a resource on success.
 * @throws Ssh2Exception
 *
 */
function ssh2_connect(string $host, int $port = 22, array $methods = null, array $callbacks = null)
{
    error_clear_last();
    if ($callbacks !== null) {
        $result = \ssh2_connect($host, $port, $methods, $callbacks);
    } elseif ($methods !== null) {
        $result = \ssh2_connect($host, $port, $methods);
    } else {
        $result = \ssh2_connect($host, $port);
    }
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Close a connection to a remote SSH server.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @throws Ssh2Exception
 *
 */
function ssh2_disconnect($session): void
{
    error_clear_last();
    $result = \ssh2_disconnect($session);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Execute a command at the remote end and allocate a channel for it.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $command
 * @param string $pty
 * @param array $env env may be passed as an associative array of
 * name/value pairs to set in the target environment.
 * @param int $width Width of the virtual terminal.
 * @param int $height Height of the virtual terminal.
 * @param int $width_height_type width_height_type should be one of
 * SSH2_TERM_UNIT_CHARS or
 * SSH2_TERM_UNIT_PIXELS.
 * @return resource Returns a stream on success.
 * @throws Ssh2Exception
 *
 */
function ssh2_exec($session, string $command, string $pty = null, array $env = null, int $width = 80, int $height = 25, int $width_height_type = SSH2_TERM_UNIT_CHARS)
{
    error_clear_last();
    if ($width_height_type !== SSH2_TERM_UNIT_CHARS) {
        $result = \ssh2_exec($session, $command, $pty, $env, $width, $height, $width_height_type);
    } elseif ($height !== 25) {
        $result = \ssh2_exec($session, $command, $pty, $env, $width, $height);
    } elseif ($width !== 80) {
        $result = \ssh2_exec($session, $command, $pty, $env, $width);
    } elseif ($env !== null) {
        $result = \ssh2_exec($session, $command, $pty, $env);
    } elseif ($pty !== null) {
        $result = \ssh2_exec($session, $command, $pty);
    } else {
        $result = \ssh2_exec($session, $command);
    }
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param resource $pkey Publickey Subsystem resource created by ssh2_publickey_init.
 * @param string $algoname Publickey algorithm (e.g.): ssh-dss, ssh-rsa
 * @param string $blob Publickey blob as raw binary data
 * @param bool $overwrite If the specified key already exists, should it be overwritten?
 * @param array $attributes Associative array of attributes to assign to this public key.
 * Refer to ietf-secsh-publickey-subsystem for a list of supported attributes.
 * To mark an attribute as mandatory, precede its name with an asterisk.
 * If the server is unable to support an attribute marked mandatory,
 * it will abort the add process.
 * @throws Ssh2Exception
 *
 */
function ssh2_publickey_add($pkey, string $algoname, string $blob, bool $overwrite = false, array $attributes = null): void
{
    error_clear_last();
    if ($attributes !== null) {
        $result = \ssh2_publickey_add($pkey, $algoname, $blob, $overwrite, $attributes);
    } else {
        $result = \ssh2_publickey_add($pkey, $algoname, $blob, $overwrite);
    }
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Request the Publickey subsystem from an already connected SSH2 server.
 *
 * The publickey subsystem allows an already connected and authenticated
 * client to manage the list of authorized public keys stored on the
 * target server in an implementation agnostic manner.
 * If the remote server does not support the publickey subsystem,
 * the ssh2_publickey_init function will return FALSE.
 *
 * @param resource $session
 * @return resource Returns an SSH2 Publickey Subsystem resource for use
 * with all other ssh2_publickey_*() methods.
 * @throws Ssh2Exception
 *
 */
function ssh2_publickey_init($session)
{
    error_clear_last();
    $result = \ssh2_publickey_init($session);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Removes an authorized publickey.
 *
 * @param resource $pkey Publickey Subsystem Resource
 * @param string $algoname Publickey algorithm (e.g.): ssh-dss, ssh-rsa
 * @param string $blob Publickey blob as raw binary data
 * @throws Ssh2Exception
 *
 */
function ssh2_publickey_remove($pkey, string $algoname, string $blob): void
{
    error_clear_last();
    $result = \ssh2_publickey_remove($pkey, $algoname, $blob);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Copy a file from the remote server to the local filesystem using the SCP protocol.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $remote_file Path to the remote file.
 * @param string $local_file Path to the local file.
 * @throws Ssh2Exception
 *
 */
function ssh2_scp_recv($session, string $remote_file, string $local_file): void
{
    error_clear_last();
    $result = \ssh2_scp_recv($session, $remote_file, $local_file);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Copy a file from the local filesystem to the remote server using the SCP protocol.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @param string $local_file Path to the local file.
 * @param string $remote_file Path to the remote file.
 * @param int $create_mode The file will be created with the mode specified by
 * create_mode.
 * @throws Ssh2Exception
 *
 */
function ssh2_scp_send($session, string $local_file, string $remote_file, int $create_mode = 0644): void
{
    error_clear_last();
    $result = \ssh2_scp_send($session, $local_file, $remote_file, $create_mode);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Attempts to change the mode of the specified file to that given in
 * mode.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $filename Path to the file.
 * @param int $mode Permissions on the file. See the chmod for more details on this parameter.
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_chmod($sftp, string $filename, int $mode): void
{
    error_clear_last();
    $result = \ssh2_sftp_chmod($sftp, $filename, $mode);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Creates a directory on the remote file server with permissions set to
 * mode.
 *
 * This function is similar to using mkdir with the
 * ssh2.sftp:// wrapper.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $dirname Path of the new directory.
 * @param int $mode Permissions on the new directory.
 * @param bool $recursive If recursive is TRUE any parent directories
 * required for dirname will be automatically created as well.
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_mkdir($sftp, string $dirname, int $mode = 0777, bool $recursive = false): void
{
    error_clear_last();
    $result = \ssh2_sftp_mkdir($sftp, $dirname, $mode, $recursive);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Renames a file on the remote filesystem.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $from The current file that is being renamed.
 * @param string $to The new file name that replaces from.
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_rename($sftp, string $from, string $to): void
{
    error_clear_last();
    $result = \ssh2_sftp_rename($sftp, $from, $to);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Removes a directory from the remote file server.
 *
 * This function is similar to using rmdir with the
 * ssh2.sftp:// wrapper.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $dirname
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_rmdir($sftp, string $dirname): void
{
    error_clear_last();
    $result = \ssh2_sftp_rmdir($sftp, $dirname);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Creates a symbolic link named link on the remote
 * filesystem pointing to target.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $target Target of the symbolic link.
 * @param string $link
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_symlink($sftp, string $target, string $link): void
{
    error_clear_last();
    $result = \ssh2_sftp_symlink($sftp, $target, $link);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Deletes a file on the remote filesystem.
 *
 * @param resource $sftp An SSH2 SFTP resource opened by ssh2_sftp.
 * @param string $filename
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp_unlink($sftp, string $filename): void
{
    error_clear_last();
    $result = \ssh2_sftp_unlink($sftp, $filename);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
}


/**
 * Request the SFTP subsystem from an already connected SSH2 server.
 *
 * @param resource $session An SSH connection link identifier, obtained from a call to
 * ssh2_connect.
 * @return resource This method returns an SSH2 SFTP resource for use with
 * all other ssh2_sftp_*() methods and the
 * ssh2.sftp:// fopen wrapper.
 * @throws Ssh2Exception
 *
 */
function ssh2_sftp($session)
{
    error_clear_last();
    $result = \ssh2_sftp($session);
    if ($result === false) {
        throw Ssh2Exception::createFromPhpError();
    }
    return $result;
}
