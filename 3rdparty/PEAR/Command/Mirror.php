<?php
/**
 * PEAR_Command_Mirror (download-all command)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Alexander Merz <alexmerz@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Mirror.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.2.0
 */

/**
 * base class
 */
require_once 'PEAR/Command/Common.php';

/**
 * PEAR commands for providing file mirrors
 *
 * @category   pear
 * @package    PEAR
 * @author     Alexander Merz <alexmerz@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.2.0
 */
class PEAR_Command_Mirror extends PEAR_Command_Common
{
    var $commands = array(
        'download-all' => array(
            'summary' => 'Downloads each available package from the default channel',
            'function' => 'doDownloadAll',
            'shortcut' => 'da',
            'options' => array(
                'channel' =>
                    array(
                    'shortopt' => 'c',
                    'doc' => 'specify a channel other than the default channel',
                    'arg' => 'CHAN',
                    ),
                ),
            'doc' => '
Requests a list of available packages from the default channel ({config default_channel})
and downloads them to current working directory.  Note: only
packages within preferred_state ({config preferred_state}) will be downloaded'
            ),
        );

    /**
     * PEAR_Command_Mirror constructor.
     *
     * @access public
     * @param object PEAR_Frontend a reference to an frontend
     * @param object PEAR_Config a reference to the configuration data
     */
    function PEAR_Command_Mirror(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    /**
     * For unit-testing
     */
    function &factory($a)
    {
        $a = &PEAR_Command::factory($a, $this->config);
        return $a;
    }

    /**
    * retrieves a list of avaible Packages from master server
    * and downloads them
    *
    * @access public
    * @param string $command the command
    * @param array $options the command options before the command
    * @param array $params the stuff after the command name
    * @return bool true if succesful
    * @throw PEAR_Error
    */
    function doDownloadAll($command, $options, $params)
    {
        $savechannel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        $channel = isset($options['channel']) ? $options['channel'] :
            $this->config->get('default_channel');
        if (!$reg->channelExists($channel)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError('Channel "' . $channel . '" does not exist');
        }
        $this->config->set('default_channel', $channel);

        $this->ui->outputData('Using Channel ' . $this->config->get('default_channel'));
        $chan = $reg->getChannel($channel);
        if (PEAR::isError($chan)) {
            return $this->raiseError($chan);
        }

        if ($chan->supportsREST($this->config->get('preferred_mirror')) &&
              $base = $chan->getBaseURL('REST1.0', $this->config->get('preferred_mirror'))) {
            $rest = &$this->config->getREST('1.0', array());
            $remoteInfo = array_flip($rest->listPackages($base, $channel));
        }

        if (PEAR::isError($remoteInfo)) {
            return $remoteInfo;
        }

        $cmd = &$this->factory("download");
        if (PEAR::isError($cmd)) {
            return $cmd;
        }

        $this->ui->outputData('Using Preferred State of ' .
            $this->config->get('preferred_state'));
        $this->ui->outputData('Gathering release information, please wait...');

        /**
         * Error handling not necessary, because already done by
         * the download command
         */
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $err = $cmd->run('download', array('downloadonly' => true), array_keys($remoteInfo));
        PEAR::staticPopErrorHandling();
        $this->config->set('default_channel', $savechannel);
        if (PEAR::isError($err)) {
            $this->ui->outputData($err->getMessage());
        }

        return true;
    }
}