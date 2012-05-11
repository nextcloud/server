<?php
// /* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * PEAR_Command_Channels (list-channels, update-channels, channel-delete, channel-add,
 * channel-update, channel-info, channel-alias, channel-discover commands)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Channels.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */

/**
 * base class
 */
require_once 'PEAR/Command/Common.php';

define('PEAR_COMMAND_CHANNELS_CHANNEL_EXISTS', -500);

/**
 * PEAR commands for managing channels.
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a1
 */
class PEAR_Command_Channels extends PEAR_Command_Common
{
    var $commands = array(
        'list-channels' => array(
            'summary' => 'List Available Channels',
            'function' => 'doList',
            'shortcut' => 'lc',
            'options' => array(),
            'doc' => '
List all available channels for installation.
',
            ),
        'update-channels' => array(
            'summary' => 'Update the Channel List',
            'function' => 'doUpdateAll',
            'shortcut' => 'uc',
            'options' => array(),
            'doc' => '
List all installed packages in all channels.
'
            ),
        'channel-delete' => array(
            'summary' => 'Remove a Channel From the List',
            'function' => 'doDelete',
            'shortcut' => 'cde',
            'options' => array(),
            'doc' => '<channel name>
Delete a channel from the registry.  You may not
remove any channel that has installed packages.
'
            ),
        'channel-add' => array(
            'summary' => 'Add a Channel',
            'function' => 'doAdd',
            'shortcut' => 'ca',
            'options' => array(),
            'doc' => '<channel.xml>
Add a private channel to the channel list.  Note that all
public channels should be synced using "update-channels".
Parameter may be either a local file or remote URL to a
channel.xml.
'
            ),
        'channel-update' => array(
            'summary' => 'Update an Existing Channel',
            'function' => 'doUpdate',
            'shortcut' => 'cu',
            'options' => array(
                'force' => array(
                    'shortopt' => 'f',
                    'doc' => 'will force download of new channel.xml if an existing channel name is used',
                    ),
                'channel' => array(
                    'shortopt' => 'c',
                    'arg' => 'CHANNEL',
                    'doc' => 'will force download of new channel.xml if an existing channel name is used',
                    ),
),
            'doc' => '[<channel.xml>|<channel name>]
Update a channel in the channel list directly.  Note that all
public channels can be synced using "update-channels".
Parameter may be a local or remote channel.xml, or the name of
an existing channel.
'
            ),
        'channel-info' => array(
            'summary' => 'Retrieve Information on a Channel',
            'function' => 'doInfo',
            'shortcut' => 'ci',
            'options' => array(),
            'doc' => '<package>
List the files in an installed package.
'
            ),
        'channel-alias' => array(
            'summary' => 'Specify an alias to a channel name',
            'function' => 'doAlias',
            'shortcut' => 'cha',
            'options' => array(),
            'doc' => '<channel> <alias>
Specify a specific alias to use for a channel name.
The alias may not be an existing channel name or
alias.
'
            ),
        'channel-discover' => array(
            'summary' => 'Initialize a Channel from its server',
            'function' => 'doDiscover',
            'shortcut' => 'di',
            'options' => array(),
            'doc' => '[<channel.xml>|<channel name>]
Initialize a channel from its server and create a local channel.xml.
If <channel name> is in the format "<username>:<password>@<channel>" then
<username> and <password> will be set as the login username/password for
<channel>. Use caution when passing the username/password in this way, as
it may allow other users on your computer to briefly view your username/
password via the system\'s process list.
'
            ),
        'channel-login' => array(
            'summary' => 'Connects and authenticates to remote channel server',
            'shortcut' => 'cli',
            'function' => 'doLogin',
            'options' => array(),
            'doc' => '<channel name>
Log in to a remote channel server.  If <channel name> is not supplied,
the default channel is used. To use remote functions in the installer
that require any kind of privileges, you need to log in first.  The
username and password you enter here will be stored in your per-user
PEAR configuration (~/.pearrc on Unix-like systems).  After logging
in, your username and password will be sent along in subsequent
operations on the remote server.',
            ),
        'channel-logout' => array(
            'summary' => 'Logs out from the remote channel server',
            'shortcut' => 'clo',
            'function' => 'doLogout',
            'options' => array(),
            'doc' => '<channel name>
Logs out from a remote channel server.  If <channel name> is not supplied,
the default channel is used. This command does not actually connect to the
remote server, it only deletes the stored username and password from your user
configuration.',
            ),
        );

    /**
     * PEAR_Command_Registry constructor.
     *
     * @access public
     */
    function PEAR_Command_Channels(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    function _sortChannels($a, $b)
    {
        return strnatcasecmp($a->getName(), $b->getName());
    }

    function doList($command, $options, $params)
    {
        $reg = &$this->config->getRegistry();
        $registered = $reg->getChannels();
        usort($registered, array(&$this, '_sortchannels'));
        $i = $j = 0;
        $data = array(
            'caption' => 'Registered Channels:',
            'border' => true,
            'headline' => array('Channel', 'Alias', 'Summary')
            );
        foreach ($registered as $channel) {
            $data['data'][] = array($channel->getName(),
                                    $channel->getAlias(),
                                    $channel->getSummary());
        }

        if (count($registered) === 0) {
            $data = '(no registered channels)';
        }
        $this->ui->outputData($data, $command);
        return true;
    }

    function doUpdateAll($command, $options, $params)
    {
        $reg = &$this->config->getRegistry();
        $channels = $reg->getChannels();

        $success = true;
        foreach ($channels as $channel) {
            if ($channel->getName() != '__uri') {
                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $err = $this->doUpdate('channel-update',
                                          $options,
                                          array($channel->getName()));
                if (PEAR::isError($err)) {
                    $this->ui->outputData($err->getMessage(), $command);
                    $success = false;
                } else {
                    $success &= $err;
                }
            }
        }
        return $success;
    }

    function doInfo($command, $options, $params)
    {
        if (count($params) !== 1) {
            return $this->raiseError("No channel specified");
        }

        $reg     = &$this->config->getRegistry();
        $channel = strtolower($params[0]);
        if ($reg->channelExists($channel)) {
            $chan = $reg->getChannel($channel);
            if (PEAR::isError($chan)) {
                return $this->raiseError($chan);
            }
        } else {
            if (strpos($channel, '://')) {
                $downloader = &$this->getDownloader();
                $tmpdir = $this->config->get('temp_dir');
                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $loc = $downloader->downloadHttp($channel, $this->ui, $tmpdir);
                PEAR::staticPopErrorHandling();
                if (PEAR::isError($loc)) {
                    return $this->raiseError('Cannot open "' . $channel .
                        '" (' . $loc->getMessage() . ')');
                } else {
                    $contents = implode('', file($loc));
                }
            } else {
                if (!file_exists($params[0])) {
                    return $this->raiseError('Unknown channel "' . $channel . '"');
                }

                $fp = fopen($params[0], 'r');
                if (!$fp) {
                    return $this->raiseError('Cannot open "' . $params[0] . '"');
                }

                $contents = '';
                while (!feof($fp)) {
                    $contents .= fread($fp, 1024);
                }
                fclose($fp);
            }

            if (!class_exists('PEAR_ChannelFile')) {
                require_once 'PEAR/ChannelFile.php';
            }

            $chan = new PEAR_ChannelFile;
            $chan->fromXmlString($contents);
            $chan->validate();
            if ($errs = $chan->getErrors(true)) {
                foreach ($errs as $err) {
                    $this->ui->outputData($err['level'] . ': ' . $err['message']);
                }
                return $this->raiseError('Channel file "' . $params[0] . '" is not valid');
            }
        }

        if (!$chan) {
            return $this->raiseError('Serious error: Channel "' . $params[0] .
                '" has a corrupted registry entry');
        }

        $channel = $chan->getName();
        $caption = 'Channel ' . $channel . ' Information:';
        $data1 = array(
            'caption' => $caption,
            'border' => true);
        $data1['data']['server'] = array('Name and Server', $chan->getName());
        if ($chan->getAlias() != $chan->getName()) {
            $data1['data']['alias'] = array('Alias', $chan->getAlias());
        }

        $data1['data']['summary'] = array('Summary', $chan->getSummary());
        $validate = $chan->getValidationPackage();
        $data1['data']['vpackage'] = array('Validation Package Name', $validate['_content']);
        $data1['data']['vpackageversion'] =
            array('Validation Package Version', $validate['attribs']['version']);
        $d = array();
        $d['main'] = $data1;

        $data['data'] = array();
        $data['caption'] = 'Server Capabilities';
        $data['headline'] = array('Type', 'Version/REST type', 'Function Name/REST base');
        if ($chan->supportsREST()) {
            if ($chan->supportsREST()) {
                $funcs = $chan->getFunctions('rest');
                if (!isset($funcs[0])) {
                    $funcs = array($funcs);
                }
                foreach ($funcs as $protocol) {
                    $data['data'][] = array('rest', $protocol['attribs']['type'],
                        $protocol['_content']);
                }
            }
        } else {
            $data['data'][] = array('No supported protocols');
        }

        $d['protocols'] = $data;
        $data['data'] = array();
        $mirrors = $chan->getMirrors();
        if ($mirrors) {
            $data['caption'] = 'Channel ' . $channel . ' Mirrors:';
            unset($data['headline']);
            foreach ($mirrors as $mirror) {
                $data['data'][] = array($mirror['attribs']['host']);
                $d['mirrors'] = $data;
            }

            foreach ($mirrors as $i => $mirror) {
                $data['data'] = array();
                $data['caption'] = 'Mirror ' . $mirror['attribs']['host'] . ' Capabilities';
                $data['headline'] = array('Type', 'Version/REST type', 'Function Name/REST base');
                if ($chan->supportsREST($mirror['attribs']['host'])) {
                    if ($chan->supportsREST($mirror['attribs']['host'])) {
                        $funcs = $chan->getFunctions('rest', $mirror['attribs']['host']);
                        if (!isset($funcs[0])) {
                            $funcs = array($funcs);
                        }

                        foreach ($funcs as $protocol) {
                            $data['data'][] = array('rest', $protocol['attribs']['type'],
                                $protocol['_content']);
                        }
                    }
                } else {
                    $data['data'][] = array('No supported protocols');
                }
                $d['mirrorprotocols' . $i] = $data;
            }
        }
        $this->ui->outputData($d, 'channel-info');
    }

    // }}}

    function doDelete($command, $options, $params)
    {
        if (count($params) !== 1) {
            return $this->raiseError('channel-delete: no channel specified');
        }

        $reg = &$this->config->getRegistry();
        if (!$reg->channelExists($params[0])) {
            return $this->raiseError('channel-delete: channel "' . $params[0] . '" does not exist');
        }

        $channel = $reg->channelName($params[0]);
        if ($channel == 'pear.php.net') {
            return $this->raiseError('Cannot delete the pear.php.net channel');
        }

        if ($channel == 'pecl.php.net') {
            return $this->raiseError('Cannot delete the pecl.php.net channel');
        }

        if ($channel == 'doc.php.net') {
            return $this->raiseError('Cannot delete the doc.php.net channel');
        }

        if ($channel == '__uri') {
            return $this->raiseError('Cannot delete the __uri pseudo-channel');
        }

        if (PEAR::isError($err = $reg->listPackages($channel))) {
            return $err;
        }

        if (count($err)) {
            return $this->raiseError('Channel "' . $channel .
                '" has installed packages, cannot delete');
        }

        if (!$reg->deleteChannel($channel)) {
            return $this->raiseError('Channel "' . $channel . '" deletion failed');
        } else {
            $this->config->deleteChannel($channel);
            $this->ui->outputData('Channel "' . $channel . '" deleted', $command);
        }
    }

    function doAdd($command, $options, $params)
    {
        if (count($params) !== 1) {
            return $this->raiseError('channel-add: no channel file specified');
        }

        if (strpos($params[0], '://')) {
            $downloader = &$this->getDownloader();
            $tmpdir = $this->config->get('temp_dir');
            if (!file_exists($tmpdir)) {
                require_once 'System.php';
                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $err = System::mkdir(array('-p', $tmpdir));
                PEAR::staticPopErrorHandling();
                if (PEAR::isError($err)) {
                    return $this->raiseError('channel-add: temp_dir does not exist: "' .
                        $tmpdir .
                        '" - You can change this location with "pear config-set temp_dir"');
                }
            }

            if (!is_writable($tmpdir)) {
                return $this->raiseError('channel-add: temp_dir is not writable: "' .
                    $tmpdir .
                    '" - You can change this location with "pear config-set temp_dir"');
            }

            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $loc = $downloader->downloadHttp($params[0], $this->ui, $tmpdir, null, false);
            PEAR::staticPopErrorHandling();
            if (PEAR::isError($loc)) {
                return $this->raiseError('channel-add: Cannot open "' . $params[0] .
                    '" (' . $loc->getMessage() . ')');
            }

            list($loc, $lastmodified) = $loc;
            $contents = implode('', file($loc));
        } else {
            $lastmodified = $fp = false;
            if (file_exists($params[0])) {
                $fp = fopen($params[0], 'r');
            }

            if (!$fp) {
                return $this->raiseError('channel-add: cannot open "' . $params[0] . '"');
            }

            $contents = '';
            while (!feof($fp)) {
                $contents .= fread($fp, 1024);
            }
            fclose($fp);
        }

        if (!class_exists('PEAR_ChannelFile')) {
            require_once 'PEAR/ChannelFile.php';
        }

        $channel = new PEAR_ChannelFile;
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $channel->fromXmlString($contents);
        PEAR::staticPopErrorHandling();
        if (!$result) {
            $exit = false;
            if (count($errors = $channel->getErrors(true))) {
                foreach ($errors as $error) {
                    $this->ui->outputData(ucfirst($error['level'] . ': ' . $error['message']));
                    if (!$exit) {
                        $exit = $error['level'] == 'error' ? true : false;
                    }
                }
                if ($exit) {
                    return $this->raiseError('channel-add: invalid channel.xml file');
                }
            }
        }

        $reg = &$this->config->getRegistry();
        if ($reg->channelExists($channel->getName())) {
            return $this->raiseError('channel-add: Channel "' . $channel->getName() .
                '" exists, use channel-update to update entry', PEAR_COMMAND_CHANNELS_CHANNEL_EXISTS);
        }

        $ret = $reg->addChannel($channel, $lastmodified);
        if (PEAR::isError($ret)) {
            return $ret;
        }

        if (!$ret) {
            return $this->raiseError('channel-add: adding Channel "' . $channel->getName() .
                '" to registry failed');
        }

        $this->config->setChannels($reg->listChannels());
        $this->config->writeConfigFile();
        $this->ui->outputData('Adding Channel "' . $channel->getName() . '" succeeded', $command);
    }

    function doUpdate($command, $options, $params)
    {
        if (count($params) !== 1) {
            return $this->raiseError("No channel file specified");
        }

        $tmpdir = $this->config->get('temp_dir');
        if (!file_exists($tmpdir)) {
            require_once 'System.php';
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $err = System::mkdir(array('-p', $tmpdir));
            PEAR::staticPopErrorHandling();
            if (PEAR::isError($err)) {
                return $this->raiseError('channel-add: temp_dir does not exist: "' .
                    $tmpdir .
                    '" - You can change this location with "pear config-set temp_dir"');
            }
        }

        if (!is_writable($tmpdir)) {
            return $this->raiseError('channel-add: temp_dir is not writable: "' .
                $tmpdir .
                '" - You can change this location with "pear config-set temp_dir"');
        }

        $reg = &$this->config->getRegistry();
        $lastmodified = false;
        if ((!file_exists($params[0]) || is_dir($params[0]))
              && $reg->channelExists(strtolower($params[0]))) {
            $c = $reg->getChannel(strtolower($params[0]));
            if (PEAR::isError($c)) {
                return $this->raiseError($c);
            }

            $this->ui->outputData("Updating channel \"$params[0]\"", $command);
            $dl = &$this->getDownloader(array());
            // if force is specified, use a timestamp of "1" to force retrieval
            $lastmodified = isset($options['force']) ? false : $c->lastModified();
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $contents = $dl->downloadHttp('http://' . $c->getName() . '/channel.xml',
                $this->ui, $tmpdir, null, $lastmodified);
            PEAR::staticPopErrorHandling();
            if (PEAR::isError($contents)) {
                // Attempt to fall back to https
                $this->ui->outputData("Channel \"$params[0]\" is not responding over http://, failed with message: " . $contents->getMessage());
                $this->ui->outputData("Trying channel \"$params[0]\" over https:// instead");
                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $contents = $dl->downloadHttp('https://' . $c->getName() . '/channel.xml',
                    $this->ui, $tmpdir, null, $lastmodified);
                PEAR::staticPopErrorHandling();
                if (PEAR::isError($contents)) {
                    return $this->raiseError('Cannot retrieve channel.xml for channel "' .
                        $c->getName() . '" (' . $contents->getMessage() . ')');
                }
            }

            list($contents, $lastmodified) = $contents;
            if (!$contents) {
                $this->ui->outputData("Channel \"$params[0]\" is up to date");
                return;
            }

            $contents = implode('', file($contents));
            if (!class_exists('PEAR_ChannelFile')) {
                require_once 'PEAR/ChannelFile.php';
            }

            $channel = new PEAR_ChannelFile;
            $channel->fromXmlString($contents);
            if (!$channel->getErrors()) {
                // security check: is the downloaded file for the channel we got it from?
                if (strtolower($channel->getName()) != strtolower($c->getName())) {
                    if (!isset($options['force'])) {
                        return $this->raiseError('ERROR: downloaded channel definition file' .
                            ' for channel "' . $channel->getName() . '" from channel "' .
                            strtolower($c->getName()) . '"');
                    }

                    $this->ui->log(0, 'WARNING: downloaded channel definition file' .
                        ' for channel "' . $channel->getName() . '" from channel "' .
                        strtolower($c->getName()) . '"');
                }
            }
        } else {
            if (strpos($params[0], '://')) {
                $dl = &$this->getDownloader();
                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $loc = $dl->downloadHttp($params[0],
                    $this->ui, $tmpdir, null, $lastmodified);
                PEAR::staticPopErrorHandling();
                if (PEAR::isError($loc)) {
                    return $this->raiseError("Cannot open " . $params[0] .
                         ' (' . $loc->getMessage() . ')');
                }

                list($loc, $lastmodified) = $loc;
                $contents = implode('', file($loc));
            } else {
                $fp = false;
                if (file_exists($params[0])) {
                    $fp = fopen($params[0], 'r');
                }

                if (!$fp) {
                    return $this->raiseError("Cannot open " . $params[0]);
                }

                $contents = '';
                while (!feof($fp)) {
                    $contents .= fread($fp, 1024);
                }
                fclose($fp);
            }

            if (!class_exists('PEAR_ChannelFile')) {
                require_once 'PEAR/ChannelFile.php';
            }

            $channel = new PEAR_ChannelFile;
            $channel->fromXmlString($contents);
        }

        $exit = false;
        if (count($errors = $channel->getErrors(true))) {
            foreach ($errors as $error) {
                $this->ui->outputData(ucfirst($error['level'] . ': ' . $error['message']));
                if (!$exit) {
                    $exit = $error['level'] == 'error' ? true : false;
                }
            }
            if ($exit) {
                return $this->raiseError('Invalid channel.xml file');
            }
        }

        if (!$reg->channelExists($channel->getName())) {
            return $this->raiseError('Error: Channel "' . $channel->getName() .
                '" does not exist, use channel-add to add an entry');
        }

        $ret = $reg->updateChannel($channel, $lastmodified);
        if (PEAR::isError($ret)) {
            return $ret;
        }

        if (!$ret) {
            return $this->raiseError('Updating Channel "' . $channel->getName() .
                '" in registry failed');
        }

        $this->config->setChannels($reg->listChannels());
        $this->config->writeConfigFile();
        $this->ui->outputData('Update of Channel "' . $channel->getName() . '" succeeded');
    }

    function &getDownloader()
    {
        if (!class_exists('PEAR_Downloader')) {
            require_once 'PEAR/Downloader.php';
        }
        $a = new PEAR_Downloader($this->ui, array(), $this->config);
        return $a;
    }

    function doAlias($command, $options, $params)
    {
        if (count($params) === 1) {
            return $this->raiseError('No channel alias specified');
        }

        if (count($params) !== 2 || (!empty($params[1]) && $params[1]{0} == '-')) {
            return $this->raiseError(
                'Invalid format, correct is: channel-alias channel alias');
        }

        $reg = &$this->config->getRegistry();
        if (!$reg->channelExists($params[0], true)) {
            $extra = '';
            if ($reg->isAlias($params[0])) {
                $extra = ' (use "channel-alias ' . $reg->channelName($params[0]) . ' ' .
                    strtolower($params[1]) . '")';
            }

            return $this->raiseError('"' . $params[0] . '" is not a valid channel' . $extra);
        }

        if ($reg->isAlias($params[1])) {
            return $this->raiseError('Channel "' . $reg->channelName($params[1]) . '" is ' .
                'already aliased to "' . strtolower($params[1]) . '", cannot re-alias');
        }

        $chan = &$reg->getChannel($params[0]);
        if (PEAR::isError($chan)) {
            return $this->raiseError('Corrupt registry?  Error retrieving channel "' . $params[0] .
                '" information (' . $chan->getMessage() . ')');
        }

        // make it a local alias
        if (!$chan->setAlias(strtolower($params[1]), true)) {
            return $this->raiseError('Alias "' . strtolower($params[1]) .
                '" is not a valid channel alias');
        }

        $reg->updateChannel($chan);
        $this->ui->outputData('Channel "' . $chan->getName() . '" aliased successfully to "' .
            strtolower($params[1]) . '"');
    }

    /**
     * The channel-discover command
     *
     * @param string $command command name
     * @param array  $options option_name => value
     * @param array  $params  list of additional parameters.
     *               $params[0] should contain a string with either:
     *               - <channel name> or
     *               - <username>:<password>@<channel name>
     * @return null|PEAR_Error
     */
    function doDiscover($command, $options, $params)
    {
        if (count($params) !== 1) {
            return $this->raiseError("No channel server specified");
        }

        // Look for the possible input format "<username>:<password>@<channel>"
        if (preg_match('/^(.+):(.+)@(.+)\\z/', $params[0], $matches)) {
            $username = $matches[1];
            $password = $matches[2];
            $channel  = $matches[3];
        } else {
            $channel = $params[0];
        }

        $reg = &$this->config->getRegistry();
        if ($reg->channelExists($channel)) {
            if (!$reg->isAlias($channel)) {
                return $this->raiseError("Channel \"$channel\" is already initialized", PEAR_COMMAND_CHANNELS_CHANNEL_EXISTS);
            }

            return $this->raiseError("A channel alias named \"$channel\" " .
                'already exists, aliasing channel "' . $reg->channelName($channel)
                . '"');
        }

        $this->pushErrorHandling(PEAR_ERROR_RETURN);
        $err = $this->doAdd($command, $options, array('http://' . $channel . '/channel.xml'));
        $this->popErrorHandling();
        if (PEAR::isError($err)) {
            if ($err->getCode() === PEAR_COMMAND_CHANNELS_CHANNEL_EXISTS) {
                return $this->raiseError("Discovery of channel \"$channel\" failed (" .
                    $err->getMessage() . ')');
            }
            // Attempt fetch via https
            $this->ui->outputData("Discovering channel $channel over http:// failed with message: " . $err->getMessage());
            $this->ui->outputData("Trying to discover channel $channel over https:// instead");
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $err = $this->doAdd($command, $options, array('https://' . $channel . '/channel.xml'));
            $this->popErrorHandling();
            if (PEAR::isError($err)) {
                return $this->raiseError("Discovery of channel \"$channel\" failed (" .
                    $err->getMessage() . ')');
            }
        }

        // Store username/password if they were given
        // Arguably we should do a logintest on the channel here, but since
        // that's awkward on a REST-based channel (even "pear login" doesn't
        // do it for those), and XML-RPC is deprecated, it's fairly pointless.
        if (isset($username)) {
            $this->config->set('username', $username, 'user', $channel);
            $this->config->set('password', $password, 'user', $channel);
            $this->config->store();
            $this->ui->outputData("Stored login for channel \"$channel\" using username \"$username\"", $command);
        }

        $this->ui->outputData("Discovery of channel \"$channel\" succeeded", $command);
    }

    /**
     * Execute the 'login' command.
     *
     * @param string $command command name
     * @param array $options option_name => value
     * @param array $params list of additional parameters
     *
     * @return bool TRUE on success or
     * a PEAR error on failure
     *
     * @access public
     */
    function doLogin($command, $options, $params)
    {
        $reg = &$this->config->getRegistry();

        // If a parameter is supplied, use that as the channel to log in to
        $channel = isset($params[0]) ? $params[0] : $this->config->get('default_channel');

        $chan = $reg->getChannel($channel);
        if (PEAR::isError($chan)) {
            return $this->raiseError($chan);
        }

        $server   = $this->config->get('preferred_mirror', null, $channel);
        $username = $this->config->get('username',         null, $channel);
        if (empty($username)) {
            $username = isset($_ENV['USER']) ? $_ENV['USER'] : null;
        }
        $this->ui->outputData("Logging in to $server.", $command);

        list($username, $password) = $this->ui->userDialog(
            $command,
            array('Username', 'Password'),
            array('text',     'password'),
            array($username,  '')
            );
        $username = trim($username);
        $password = trim($password);

        $ourfile = $this->config->getConfFile('user');
        if (!$ourfile) {
            $ourfile = $this->config->getConfFile('system');
        }

        $this->config->set('username', $username, 'user', $channel);
        $this->config->set('password', $password, 'user', $channel);

        if ($chan->supportsREST()) {
            $ok = true;
        }

        if ($ok !== true) {
            return $this->raiseError('Login failed!');
        }

        $this->ui->outputData("Logged in.", $command);
        // avoid changing any temporary settings changed with -d
        $ourconfig = new PEAR_Config($ourfile, $ourfile);
        $ourconfig->set('username', $username, 'user', $channel);
        $ourconfig->set('password', $password, 'user', $channel);
        $ourconfig->store();

        return true;
    }

    /**
     * Execute the 'logout' command.
     *
     * @param string $command command name
     * @param array $options option_name => value
     * @param array $params list of additional parameters
     *
     * @return bool TRUE on success or
     * a PEAR error on failure
     *
     * @access public
     */
    function doLogout($command, $options, $params)
    {
        $reg     = &$this->config->getRegistry();

        // If a parameter is supplied, use that as the channel to log in to
        $channel = isset($params[0]) ? $params[0] : $this->config->get('default_channel');

        $chan    = $reg->getChannel($channel);
        if (PEAR::isError($chan)) {
            return $this->raiseError($chan);
        }

        $server = $this->config->get('preferred_mirror', null, $channel);
        $this->ui->outputData("Logging out from $server.", $command);
        $this->config->remove('username', 'user', $channel);
        $this->config->remove('password', 'user', $channel);
        $this->config->store();
        return true;
    }
}