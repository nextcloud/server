<?php
/**
 * PEAR_Command_Auth (login, logout commands)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Auth.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 * @deprecated since 1.8.0alpha1
 */

/**
 * base class
 */
require_once 'PEAR/Command/Channels.php';

/**
 * PEAR commands for login/logout
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 0.1
 * @deprecated since 1.8.0alpha1
 */
class PEAR_Command_Auth extends PEAR_Command_Channels
{
    var $commands = array(
        'login' => array(
            'summary' => 'Connects and authenticates to remote server [Deprecated in favor of channel-login]',
            'shortcut' => 'li',
            'function' => 'doLogin',
            'options' => array(),
            'doc' => '<channel name>
WARNING: This function is deprecated in favor of using channel-login

Log in to a remote channel server.  If <channel name> is not supplied,
the default channel is used. To use remote functions in the installer
that require any kind of privileges, you need to log in first.  The
username and password you enter here will be stored in your per-user
PEAR configuration (~/.pearrc on Unix-like systems).  After logging
in, your username and password will be sent along in subsequent
operations on the remote server.',
            ),
        'logout' => array(
            'summary' => 'Logs out from the remote server [Deprecated in favor of channel-logout]',
            'shortcut' => 'lo',
            'function' => 'doLogout',
            'options' => array(),
            'doc' => '
WARNING: This function is deprecated in favor of using channel-logout

Logs out from the remote server.  This command does not actually
connect to the remote server, it only deletes the stored username and
password from your user configuration.',
            )

        );

    /**
     * PEAR_Command_Auth constructor.
     *
     * @access public
     */
    function PEAR_Command_Auth(&$ui, &$config)
    {
        parent::PEAR_Command_Channels($ui, $config);
    }
}