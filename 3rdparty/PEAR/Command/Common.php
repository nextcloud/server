<?php
/**
 * PEAR_Command_Common base class
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Common.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * base class
 */
require_once 'PEAR.php';

/**
 * PEAR commands base class
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
class PEAR_Command_Common extends PEAR
{
    /**
     * PEAR_Config object used to pass user system and configuration
     * on when executing commands
     *
     * @var PEAR_Config
     */
    var $config;
    /**
     * @var PEAR_Registry
     * @access protected
     */
    var $_registry;

    /**
     * User Interface object, for all interaction with the user.
     * @var object
     */
    var $ui;

    var $_deps_rel_trans = array(
                                 'lt' => '<',
                                 'le' => '<=',
                                 'eq' => '=',
                                 'ne' => '!=',
                                 'gt' => '>',
                                 'ge' => '>=',
                                 'has' => '=='
                                 );

    var $_deps_type_trans = array(
                                  'pkg' => 'package',
                                  'ext' => 'extension',
                                  'php' => 'PHP',
                                  'prog' => 'external program',
                                  'ldlib' => 'external library for linking',
                                  'rtlib' => 'external runtime library',
                                  'os' => 'operating system',
                                  'websrv' => 'web server',
                                  'sapi' => 'SAPI backend'
                                  );

    /**
     * PEAR_Command_Common constructor.
     *
     * @access public
     */
    function PEAR_Command_Common(&$ui, &$config)
    {
        parent::PEAR();
        $this->config = &$config;
        $this->ui = &$ui;
    }

    /**
     * Return a list of all the commands defined by this class.
     * @return array list of commands
     * @access public
     */
    function getCommands()
    {
        $ret = array();
        foreach (array_keys($this->commands) as $command) {
            $ret[$command] = $this->commands[$command]['summary'];
        }

        return $ret;
    }

    /**
     * Return a list of all the command shortcuts defined by this class.
     * @return array shortcut => command
     * @access public
     */
    function getShortcuts()
    {
        $ret = array();
        foreach (array_keys($this->commands) as $command) {
            if (isset($this->commands[$command]['shortcut'])) {
                $ret[$this->commands[$command]['shortcut']] = $command;
            }
        }

        return $ret;
    }

    function getOptions($command)
    {
        $shortcuts = $this->getShortcuts();
        if (isset($shortcuts[$command])) {
            $command = $shortcuts[$command];
        }

        if (isset($this->commands[$command]) &&
              isset($this->commands[$command]['options'])) {
            return $this->commands[$command]['options'];
        }

        return null;
    }

    function getGetoptArgs($command, &$short_args, &$long_args)
    {
        $short_args = '';
        $long_args = array();
        if (empty($this->commands[$command]) || empty($this->commands[$command]['options'])) {
            return;
        }

        reset($this->commands[$command]['options']);
        while (list($option, $info) = each($this->commands[$command]['options'])) {
            $larg = $sarg = '';
            if (isset($info['arg'])) {
                if ($info['arg']{0} == '(') {
                    $larg = '==';
                    $sarg = '::';
                    $arg = substr($info['arg'], 1, -1);
                } else {
                    $larg = '=';
                    $sarg = ':';
                    $arg = $info['arg'];
                }
            }

            if (isset($info['shortopt'])) {
                $short_args .= $info['shortopt'] . $sarg;
            }

            $long_args[] = $option . $larg;
        }
    }

    /**
    * Returns the help message for the given command
    *
    * @param string $command The command
    * @return mixed A fail string if the command does not have help or
    *               a two elements array containing [0]=>help string,
    *               [1]=> help string for the accepted cmd args
    */
    function getHelp($command)
    {
        $config = &PEAR_Config::singleton();
        if (!isset($this->commands[$command])) {
            return "No such command \"$command\"";
        }

        $help = null;
        if (isset($this->commands[$command]['doc'])) {
            $help = $this->commands[$command]['doc'];
        }

        if (empty($help)) {
            // XXX (cox) Fallback to summary if there is no doc (show both?)
            if (!isset($this->commands[$command]['summary'])) {
                return "No help for command \"$command\"";
            }
            $help = $this->commands[$command]['summary'];
        }

        if (preg_match_all('/{config\s+([^\}]+)}/e', $help, $matches)) {
            foreach($matches[0] as $k => $v) {
                $help = preg_replace("/$v/", $config->get($matches[1][$k]), $help);
            }
        }

        return array($help, $this->getHelpArgs($command));
    }

    /**
     * Returns the help for the accepted arguments of a command
     *
     * @param  string $command
     * @return string The help string
     */
    function getHelpArgs($command)
    {
        if (isset($this->commands[$command]['options']) &&
            count($this->commands[$command]['options']))
        {
            $help = "Options:\n";
            foreach ($this->commands[$command]['options'] as $k => $v) {
                if (isset($v['arg'])) {
                    if ($v['arg'][0] == '(') {
                        $arg = substr($v['arg'], 1, -1);
                        $sapp = " [$arg]";
                        $lapp = "[=$arg]";
                    } else {
                        $sapp = " $v[arg]";
                        $lapp = "=$v[arg]";
                    }
                } else {
                    $sapp = $lapp = "";
                }

                if (isset($v['shortopt'])) {
                    $s = $v['shortopt'];
                    $help .= "  -$s$sapp, --$k$lapp\n";
                } else {
                    $help .= "  --$k$lapp\n";
                }

                $p = "        ";
                $doc = rtrim(str_replace("\n", "\n$p", $v['doc']));
                $help .= "        $doc\n";
            }

            return $help;
        }

        return null;
    }

    function run($command, $options, $params)
    {
        if (empty($this->commands[$command]['function'])) {
            // look for shortcuts
            foreach (array_keys($this->commands) as $cmd) {
                if (isset($this->commands[$cmd]['shortcut']) && $this->commands[$cmd]['shortcut'] == $command) {
                    if (empty($this->commands[$cmd]['function'])) {
                        return $this->raiseError("unknown command `$command'");
                    } else {
                        $func = $this->commands[$cmd]['function'];
                    }
                    $command = $cmd;

                    //$command = $this->commands[$cmd]['function'];
                    break;
                }
            }
        } else {
            $func = $this->commands[$command]['function'];
        }

        return $this->$func($command, $options, $params);
    }
}