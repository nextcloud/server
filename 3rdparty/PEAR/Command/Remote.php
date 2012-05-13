<?php
/**
 * PEAR_Command_Remote (remote-info, list-upgrades, remote-list, search, list-all, download,
 * clear-cache commands)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Remote.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * base class
 */
require_once 'PEAR/Command/Common.php';
require_once 'PEAR/REST.php';

/**
 * PEAR commands for remote server querying
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
 */
class PEAR_Command_Remote extends PEAR_Command_Common
{
    var $commands = array(
        'remote-info' => array(
            'summary' => 'Information About Remote Packages',
            'function' => 'doRemoteInfo',
            'shortcut' => 'ri',
            'options' => array(),
            'doc' => '<package>
Get details on a package from the server.',
            ),
        'list-upgrades' => array(
            'summary' => 'List Available Upgrades',
            'function' => 'doListUpgrades',
            'shortcut' => 'lu',
            'options' => array(
                'channelinfo' => array(
                    'shortopt' => 'i',
                    'doc' => 'output fully channel-aware data, even on failure',
                    ),
            ),
            'doc' => '[preferred_state]
List releases on the server of packages you have installed where
a newer version is available with the same release state (stable etc.)
or the state passed as the second parameter.'
            ),
        'remote-list' => array(
            'summary' => 'List Remote Packages',
            'function' => 'doRemoteList',
            'shortcut' => 'rl',
            'options' => array(
                'channel' =>
                    array(
                    'shortopt' => 'c',
                    'doc' => 'specify a channel other than the default channel',
                    'arg' => 'CHAN',
                    )
                ),
            'doc' => '
Lists the packages available on the configured server along with the
latest stable release of each package.',
            ),
        'search' => array(
            'summary' => 'Search remote package database',
            'function' => 'doSearch',
            'shortcut' => 'sp',
            'options' => array(
                'channel' =>
                    array(
                    'shortopt' => 'c',
                    'doc' => 'specify a channel other than the default channel',
                    'arg' => 'CHAN',
                    ),
                'allchannels' => array(
                    'shortopt' => 'a',
                    'doc' => 'search packages from all known channels',
                    ),
                'channelinfo' => array(
                    'shortopt' => 'i',
                    'doc' => 'output fully channel-aware data, even on failure',
                    ),
                ),
            'doc' => '[packagename] [packageinfo]
Lists all packages which match the search parameters.  The first
parameter is a fragment of a packagename.  The default channel
will be used unless explicitly overridden.  The second parameter
will be used to match any portion of the summary/description',
            ),
        'list-all' => array(
            'summary' => 'List All Packages',
            'function' => 'doListAll',
            'shortcut' => 'la',
            'options' => array(
                'channel' =>
                    array(
                    'shortopt' => 'c',
                    'doc' => 'specify a channel other than the default channel',
                    'arg' => 'CHAN',
                    ),
                'channelinfo' => array(
                    'shortopt' => 'i',
                    'doc' => 'output fully channel-aware data, even on failure',
                    ),
                ),
            'doc' => '
Lists the packages available on the configured server along with the
latest stable release of each package.',
            ),
        'download' => array(
            'summary' => 'Download Package',
            'function' => 'doDownload',
            'shortcut' => 'd',
            'options' => array(
                'nocompress' => array(
                    'shortopt' => 'Z',
                    'doc' => 'download an uncompressed (.tar) file',
                    ),
                ),
            'doc' => '<package>...
Download package tarballs.  The files will be named as suggested by the
server, for example if you download the DB package and the latest stable
version of DB is 1.6.5, the downloaded file will be DB-1.6.5.tgz.',
            ),
        'clear-cache' => array(
            'summary' => 'Clear Web Services Cache',
            'function' => 'doClearCache',
            'shortcut' => 'cc',
            'options' => array(),
            'doc' => '
Clear the REST cache. See also the cache_ttl configuration
parameter.
',
            ),
        );

    /**
     * PEAR_Command_Remote constructor.
     *
     * @access public
     */
    function PEAR_Command_Remote(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    function _checkChannelForStatus($channel, $chan)
    {
        if (PEAR::isError($chan)) {
            $this->raiseError($chan);
        }
        if (!is_a($chan, 'PEAR_ChannelFile')) {
            return $this->raiseError('Internal corruption error: invalid channel "' .
                $channel . '"');
        }
        $rest = new PEAR_REST($this->config);
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $mirror = $this->config->get('preferred_mirror', null,
                                     $channel);
        $a = $rest->downloadHttp('http://' . $channel .
            '/channel.xml', $chan->lastModified());
        PEAR::staticPopErrorHandling();
        if (!PEAR::isError($a) && $a) {
            $this->ui->outputData('WARNING: channel "' . $channel . '" has ' .
                'updated its protocols, use "' . PEAR_RUNTYPE . ' channel-update ' . $channel .
                '" to update');
        }
    }

    function doRemoteInfo($command, $options, $params)
    {
        if (sizeof($params) != 1) {
            return $this->raiseError("$command expects one param: the remote package name");
        }
        $savechannel = $channel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        $package = $params[0];
        $parsed = $reg->parsePackageName($package, $channel);
        if (PEAR::isError($parsed)) {
            return $this->raiseError('Invalid package name "' . $package . '"');
        }

        $channel = $parsed['channel'];
        $this->config->set('default_channel', $channel);
        $chan = $reg->getChannel($channel);
        if (PEAR::isError($e = $this->_checkChannelForStatus($channel, $chan))) {
            return $e;
        }

        $mirror = $this->config->get('preferred_mirror');
        if ($chan->supportsREST($mirror) && $base = $chan->getBaseURL('REST1.0', $mirror)) {
            $rest = &$this->config->getREST('1.0', array());
            $info = $rest->packageInfo($base, $parsed['package'], $channel);
        }

        if (!isset($info)) {
            return $this->raiseError('No supported protocol was found');
        }

        if (PEAR::isError($info)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError($info);
        }

        if (!isset($info['name'])) {
            return $this->raiseError('No remote package "' . $package . '" was found');
        }

        $installed = $reg->packageInfo($info['name'], null, $channel);
        $info['installed'] = $installed['version'] ? $installed['version'] : '- no -';
        if (is_array($info['installed'])) {
            $info['installed'] = $info['installed']['release'];
        }

        $this->ui->outputData($info, $command);
        $this->config->set('default_channel', $savechannel);

        return true;
    }

    function doRemoteList($command, $options, $params)
    {
        $savechannel = $channel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        if (isset($options['channel'])) {
            $channel = $options['channel'];
            if (!$reg->channelExists($channel)) {
                return $this->raiseError('Channel "' . $channel . '" does not exist');
            }

            $this->config->set('default_channel', $channel);
        }

        $chan = $reg->getChannel($channel);
        if (PEAR::isError($e = $this->_checkChannelForStatus($channel, $chan))) {
            return $e;
        }

        $list_options = false;
        if ($this->config->get('preferred_state') == 'stable') {
            $list_options = true;
        }

        $available = array();
        if ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.1', $this->config->get('preferred_mirror'))
        ) {
            // use faster list-all if available
            $rest = &$this->config->getREST('1.1', array());
            $available = $rest->listAll($base, $list_options, true, false, false, $chan->getName());
        } elseif ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.0', $this->config->get('preferred_mirror'))) {
            $rest = &$this->config->getREST('1.0', array());
            $available = $rest->listAll($base, $list_options, true, false, false, $chan->getName());
        }

        if (PEAR::isError($available)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError($available);
        }

        $i = $j = 0;
        $data = array(
            'caption' => 'Channel ' . $channel . ' Available packages:',
            'border' => true,
            'headline' => array('Package', 'Version'),
            'channel' => $channel
            );

        if (count($available) == 0) {
            $data = '(no packages available yet)';
        } else {
            foreach ($available as $name => $info) {
                $version = (isset($info['stable']) && $info['stable']) ? $info['stable'] : '-n/a-';
                $data['data'][] = array($name, $version);
            }
        }
        $this->ui->outputData($data, $command);
        $this->config->set('default_channel', $savechannel);
        return true;
    }

    function doListAll($command, $options, $params)
    {
        $savechannel = $channel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        if (isset($options['channel'])) {
            $channel = $options['channel'];
            if (!$reg->channelExists($channel)) {
                return $this->raiseError("Channel \"$channel\" does not exist");
            }

            $this->config->set('default_channel', $channel);
        }

        $list_options = false;
        if ($this->config->get('preferred_state') == 'stable') {
            $list_options = true;
        }

        $chan = $reg->getChannel($channel);
        if (PEAR::isError($e = $this->_checkChannelForStatus($channel, $chan))) {
            return $e;
        }

        if ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.1', $this->config->get('preferred_mirror'))) {
            // use faster list-all if available
            $rest = &$this->config->getREST('1.1', array());
            $available = $rest->listAll($base, $list_options, false, false, false, $chan->getName());
        } elseif ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.0', $this->config->get('preferred_mirror'))) {
            $rest = &$this->config->getREST('1.0', array());
            $available = $rest->listAll($base, $list_options, false, false, false, $chan->getName());
        }

        if (PEAR::isError($available)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError('The package list could not be fetched from the remote server. Please try again. (Debug info: "' . $available->getMessage() . '")');
        }

        $data = array(
            'caption' => 'All packages [Channel ' . $channel . ']:',
            'border' => true,
            'headline' => array('Package', 'Latest', 'Local'),
            'channel' => $channel,
            );

        if (isset($options['channelinfo'])) {
            // add full channelinfo
            $data['caption'] = 'Channel ' . $channel . ' All packages:';
            $data['headline'] = array('Channel', 'Package', 'Latest', 'Local',
                'Description', 'Dependencies');
        }
        $local_pkgs = $reg->listPackages($channel);

        foreach ($available as $name => $info) {
            $installed = $reg->packageInfo($name, null, $channel);
            if (is_array($installed['version'])) {
                $installed['version'] = $installed['version']['release'];
            }
            $desc = $info['summary'];
            if (isset($params[$name])) {
                $desc .= "\n\n".$info['description'];
            }
            if (isset($options['mode']))
            {
                if ($options['mode'] == 'installed' && !isset($installed['version'])) {
                    continue;
                }
                if ($options['mode'] == 'notinstalled' && isset($installed['version'])) {
                    continue;
                }
                if ($options['mode'] == 'upgrades'
                      && (!isset($installed['version']) || version_compare($installed['version'],
                      $info['stable'], '>='))) {
                    continue;
                }
            }
            $pos = array_search(strtolower($name), $local_pkgs);
            if ($pos !== false) {
                unset($local_pkgs[$pos]);
            }

            if (isset($info['stable']) && !$info['stable']) {
                $info['stable'] = null;
            }

            if (isset($options['channelinfo'])) {
                // add full channelinfo
                if ($info['stable'] === $info['unstable']) {
                    $state = $info['state'];
                } else {
                    $state = 'stable';
                }
                $latest = $info['stable'].' ('.$state.')';
                $local = '';
                if (isset($installed['version'])) {
                    $inst_state = $reg->packageInfo($name, 'release_state', $channel);
                    $local = $installed['version'].' ('.$inst_state.')';
                }

                $packageinfo = array(
                    $channel,
                    $name,
                    $latest,
                    $local,
                    isset($desc) ? $desc : null,
                    isset($info['deps']) ? $info['deps'] : null,
                );
            } else {
                $packageinfo = array(
                    $reg->channelAlias($channel) . '/' . $name,
                    isset($info['stable']) ? $info['stable'] : null,
                    isset($installed['version']) ? $installed['version'] : null,
                    isset($desc) ? $desc : null,
                    isset($info['deps']) ? $info['deps'] : null,
                );
            }
            $data['data'][$info['category']][] = $packageinfo;
        }

        if (isset($options['mode']) && in_array($options['mode'], array('notinstalled', 'upgrades'))) {
            $this->config->set('default_channel', $savechannel);
            $this->ui->outputData($data, $command);
            return true;
        }

        foreach ($local_pkgs as $name) {
            $info = &$reg->getPackage($name, $channel);
            $data['data']['Local'][] = array(
                $reg->channelAlias($channel) . '/' . $info->getPackage(),
                '',
                $info->getVersion(),
                $info->getSummary(),
                $info->getDeps()
                );
        }

        $this->config->set('default_channel', $savechannel);
        $this->ui->outputData($data, $command);
        return true;
    }

    function doSearch($command, $options, $params)
    {
        if ((!isset($params[0]) || empty($params[0]))
            && (!isset($params[1]) || empty($params[1])))
        {
            return $this->raiseError('no valid search string supplied');
        }

        $channelinfo = isset($options['channelinfo']);
        $reg = &$this->config->getRegistry();
        if (isset($options['allchannels'])) {
            // search all channels
            unset($options['allchannels']);
            $channels = $reg->getChannels();
            $errors = array();
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            foreach ($channels as $channel) {
                if ($channel->getName() != '__uri') {
                    $options['channel'] = $channel->getName();
                    $ret = $this->doSearch($command, $options, $params);
                    if (PEAR::isError($ret)) {
                        $errors[] = $ret;
                    }
                }
            }

            PEAR::staticPopErrorHandling();
            if (count($errors) !== 0) {
                // for now, only give first error
                return PEAR::raiseError($errors[0]);
            }

            return true;
        }

        $savechannel = $channel = $this->config->get('default_channel');
        $package = strtolower($params[0]);
        $summary = isset($params[1]) ? $params[1] : false;
        if (isset($options['channel'])) {
            $reg = &$this->config->getRegistry();
            $channel = $options['channel'];
            if (!$reg->channelExists($channel)) {
                return $this->raiseError('Channel "' . $channel . '" does not exist');
            }

            $this->config->set('default_channel', $channel);
        }

        $chan = $reg->getChannel($channel);
        if (PEAR::isError($e = $this->_checkChannelForStatus($channel, $chan))) {
            return $e;
        }

        if ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.0', $this->config->get('preferred_mirror'))) {
            $rest = &$this->config->getREST('1.0', array());
            $available = $rest->listAll($base, false, false, $package, $summary, $chan->getName());
        }

        if (PEAR::isError($available)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError($available);
        }

        if (!$available && !$channelinfo) {
            // clean exit when not found, no error !
            $data = 'no packages found that match pattern "' . $package . '", for channel '.$channel.'.';
            $this->ui->outputData($data);
            $this->config->set('default_channel', $channel);
            return true;
        }

        if ($channelinfo) {
            $data = array(
                'caption' => 'Matched packages, channel ' . $channel . ':',
                'border' => true,
                'headline' => array('Channel', 'Package', 'Stable/(Latest)', 'Local'),
                'channel' => $channel
                );
        } else {
            $data = array(
                'caption' => 'Matched packages, channel ' . $channel . ':',
                'border' => true,
                'headline' => array('Package', 'Stable/(Latest)', 'Local'),
                'channel' => $channel
                );
        }

        if (!$available && $channelinfo) {
            unset($data['headline']);
            $data['data'] = 'No packages found that match pattern "' . $package . '".';
            $available = array();
        }

        foreach ($available as $name => $info) {
            $installed = $reg->packageInfo($name, null, $channel);
            $desc = $info['summary'];
            if (isset($params[$name]))
                $desc .= "\n\n".$info['description'];

            if (!isset($info['stable']) || !$info['stable']) {
                $version_remote = 'none';
            } else {
                if ($info['unstable']) {
                    $version_remote = $info['unstable'];
                } else {
                    $version_remote = $info['stable'];
                }
                $version_remote .= ' ('.$info['state'].')';
            }
            $version = is_array($installed['version']) ? $installed['version']['release'] :
                $installed['version'];
            if ($channelinfo) {
                $packageinfo = array(
                    $channel,
                    $name,
                    $version_remote,
                    $version,
                    $desc,
                );
            } else {
                $packageinfo = array(
                    $name,
                    $version_remote,
                    $version,
                    $desc,
                );
            }
            $data['data'][$info['category']][] = $packageinfo;
        }

        $this->ui->outputData($data, $command);
        $this->config->set('default_channel', $channel);
        return true;
    }

    function &getDownloader($options)
    {
        if (!class_exists('PEAR_Downloader')) {
            require_once 'PEAR/Downloader.php';
        }
        $a = &new PEAR_Downloader($this->ui, $options, $this->config);
        return $a;
    }

    function doDownload($command, $options, $params)
    {
        // make certain that dependencies are ignored
        $options['downloadonly'] = 1;

        // eliminate error messages for preferred_state-related errors
        /* TODO: Should be an option, but until now download does respect
           prefered state */
        /* $options['ignorepreferred_state'] = 1; */
        // eliminate error messages for preferred_state-related errors

        $downloader = &$this->getDownloader($options);
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $e = $downloader->setDownloadDir(getcwd());
        PEAR::staticPopErrorHandling();
        if (PEAR::isError($e)) {
            return $this->raiseError('Current directory is not writeable, cannot download');
        }

        $errors = array();
        $downloaded = array();
        $err = $downloader->download($params);
        if (PEAR::isError($err)) {
            return $err;
        }

        $errors = $downloader->getErrorMsgs();
        if (count($errors)) {
            foreach ($errors as $error) {
                if ($error !== null) {
                    $this->ui->outputData($error);
                }
            }

            return $this->raiseError("$command failed");
        }

        $downloaded = $downloader->getDownloadedPackages();
        foreach ($downloaded as $pkg) {
            $this->ui->outputData("File $pkg[file] downloaded", $command);
        }

        return true;
    }

    function downloadCallback($msg, $params = null)
    {
        if ($msg == 'done') {
            $this->bytes_downloaded = $params;
        }
    }

    function doListUpgrades($command, $options, $params)
    {
        require_once 'PEAR/Common.php';
        if (isset($params[0]) && !is_array(PEAR_Common::betterStates($params[0]))) {
            return $this->raiseError($params[0] . ' is not a valid state (stable/beta/alpha/devel/etc.) try "pear help list-upgrades"');
        }

        $savechannel = $channel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        foreach ($reg->listChannels() as $channel) {
            $inst = array_flip($reg->listPackages($channel));
            if (!count($inst)) {
                continue;
            }

            if ($channel == '__uri') {
                continue;
            }

            $this->config->set('default_channel', $channel);
            $state = empty($params[0]) ? $this->config->get('preferred_state') : $params[0];

            $caption = $channel . ' Available Upgrades';
            $chan = $reg->getChannel($channel);
            if (PEAR::isError($e = $this->_checkChannelForStatus($channel, $chan))) {
                return $e;
            }

            $latest = array();
            $base2  = false;
            $preferred_mirror = $this->config->get('preferred_mirror');
            if ($chan->supportsREST($preferred_mirror) &&
                (
                   //($base2 = $chan->getBaseURL('REST1.4', $preferred_mirror)) ||
                   ($base  = $chan->getBaseURL('REST1.0', $preferred_mirror))
                )

            ) {
                if ($base2) {
                    $rest = &$this->config->getREST('1.4', array());
                    $base = $base2;
                } else {
                    $rest = &$this->config->getREST('1.0', array());
                }

                if (empty($state) || $state == 'any') {
                    $state = false;
                } else {
                    $caption .= ' (' . implode(', ', PEAR_Common::betterStates($state, true)) . ')';
                }

                PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                $latest = $rest->listLatestUpgrades($base, $state, $inst, $channel, $reg);
                PEAR::staticPopErrorHandling();
            }

            if (PEAR::isError($latest)) {
                $this->ui->outputData($latest->getMessage());
                continue;
            }

            $caption .= ':';
            if (PEAR::isError($latest)) {
                $this->config->set('default_channel', $savechannel);
                return $latest;
            }

            $data = array(
                'caption' => $caption,
                'border' => 1,
                'headline' => array('Channel', 'Package', 'Local', 'Remote', 'Size'),
                'channel' => $channel
                );

            foreach ((array)$latest as $pkg => $info) {
                $package = strtolower($pkg);
                if (!isset($inst[$package])) {
                    // skip packages we don't have installed
                    continue;
                }

                extract($info);
                $inst_version = $reg->packageInfo($package, 'version', $channel);
                $inst_state   = $reg->packageInfo($package, 'release_state', $channel);
                if (version_compare("$version", "$inst_version", "le")) {
                    // installed version is up-to-date
                    continue;
                }

                if ($filesize >= 20480) {
                    $filesize += 1024 - ($filesize % 1024);
                    $fs = sprintf("%dkB", $filesize / 1024);
                } elseif ($filesize > 0) {
                    $filesize += 103 - ($filesize % 103);
                    $fs = sprintf("%.1fkB", $filesize / 1024.0);
                } else {
                    $fs = "  -"; // XXX center instead
                }

                $data['data'][] = array($channel, $pkg, "$inst_version ($inst_state)", "$version ($state)", $fs);
            }

            if (isset($options['channelinfo'])) {
                if (empty($data['data'])) {
                    unset($data['headline']);
                    if (count($inst) == 0) {
                        $data['data'] = '(no packages installed)';
                    } else {
                        $data['data'] = '(no upgrades available)';
                    }
                }
                $this->ui->outputData($data, $command);
            } else {
                if (empty($data['data'])) {
                    $this->ui->outputData('Channel ' . $channel . ': No upgrades available');
                } else {
                    $this->ui->outputData($data, $command);
                }
            }
        }

        $this->config->set('default_channel', $savechannel);
        return true;
    }

    function doClearCache($command, $options, $params)
    {
        $cache_dir = $this->config->get('cache_dir');
        $verbose   = $this->config->get('verbose');
        $output = '';
        if (!file_exists($cache_dir) || !is_dir($cache_dir)) {
            return $this->raiseError("$cache_dir does not exist or is not a directory");
        }

        if (!($dp = @opendir($cache_dir))) {
            return $this->raiseError("opendir($cache_dir) failed: $php_errormsg");
        }

        if ($verbose >= 1) {
            $output .= "reading directory $cache_dir\n";
        }

        $num = 0;
        while ($ent = readdir($dp)) {
            if (preg_match('/rest.cache(file|id)\\z/', $ent)) {
                $path = $cache_dir . DIRECTORY_SEPARATOR . $ent;
                if (file_exists($path)) {
                    $ok = @unlink($path);
                } else {
                    $ok = false;
                    $php_errormsg = '';
                }

                if ($ok) {
                    if ($verbose >= 2) {
                        $output .= "deleted $path\n";
                    }
                    $num++;
                } elseif ($verbose >= 1) {
                    $output .= "failed to delete $path $php_errormsg\n";
                }
            }
        }

        closedir($dp);
        if ($verbose >= 1) {
            $output .= "$num cache entries cleared\n";
        }

        $this->ui->outputData(rtrim($output), $command);
        return $num;
    }
}