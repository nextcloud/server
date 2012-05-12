<?php
/**
 * <tasks:postinstallscript> - read/write version
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: rw.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a10
 */
/**
 * Base class
 */
require_once 'PEAR/Task/Postinstallscript.php';
/**
 * Abstracts the postinstallscript file task xml.
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a10
 */
class PEAR_Task_Postinstallscript_rw extends PEAR_Task_Postinstallscript
{
    /**
     * parent package file object
     *
     * @var PEAR_PackageFile_v2_rw
     */
    var $_pkg;
    /**
     * Enter description here...
     *
     * @param PEAR_PackageFile_v2_rw $pkg
     * @param PEAR_Config $config
     * @param PEAR_Frontend $logger
     * @param array $fileXml
     * @return PEAR_Task_Postinstallscript_rw
     */
    function PEAR_Task_Postinstallscript_rw(&$pkg, &$config, &$logger, $fileXml)
    {
        parent::PEAR_Task_Common($config, $logger, PEAR_TASK_PACKAGE);
        $this->_contents = $fileXml;
        $this->_pkg = &$pkg;
        $this->_params = array();
    }

    function validate()
    {
        return $this->validateXml($this->_pkg, $this->_params, $this->config, $this->_contents);
    }

    function getName()
    {
        return 'postinstallscript';
    }

    /**
     * add a simple <paramgroup> to the post-install script
     *
     * Order is significant, so call this method in the same
     * sequence the users should see the paramgroups.  The $params
     * parameter should either be the result of a call to {@link getParam()}
     * or an array of calls to getParam().
     *
     * Use {@link addConditionTypeGroup()} to add a <paramgroup> containing
     * a <conditiontype> tag
     * @param string $id <paramgroup> id as seen by the script
     * @param array|false $params array of getParam() calls, or false for no params
     * @param string|false $instructions
     */
    function addParamGroup($id, $params = false, $instructions = false)
    {
        if ($params && isset($params[0]) && !isset($params[1])) {
            $params = $params[0];
        }
        $stuff =
            array(
                $this->_pkg->getTasksNs() . ':id' => $id,
            );
        if ($instructions) {
            $stuff[$this->_pkg->getTasksNs() . ':instructions'] = $instructions;
        }
        if ($params) {
            $stuff[$this->_pkg->getTasksNs() . ':param'] = $params;
        }
        $this->_params[$this->_pkg->getTasksNs() . ':paramgroup'][] = $stuff;
    }

    /**
     * add a complex <paramgroup> to the post-install script with conditions
     *
     * This inserts a <paramgroup> with
     *
     * Order is significant, so call this method in the same
     * sequence the users should see the paramgroups.  The $params
     * parameter should either be the result of a call to {@link getParam()}
     * or an array of calls to getParam().
     *
     * Use {@link addParamGroup()} to add a simple <paramgroup>
     *
     * @param string $id <paramgroup> id as seen by the script
     * @param string $oldgroup <paramgroup> id of the section referenced by
     *                         <conditiontype>
     * @param string $param name of the <param> from the older section referenced
     *                      by <contitiontype>
     * @param string $value value to match of the parameter
     * @param string $conditiontype one of '=', '!=', 'preg_match'
     * @param array|false $params array of getParam() calls, or false for no params
     * @param string|false $instructions
     */
    function addConditionTypeGroup($id, $oldgroup, $param, $value, $conditiontype = '=',
                                   $params = false, $instructions = false)
    {
        if ($params && isset($params[0]) && !isset($params[1])) {
            $params = $params[0];
        }
        $stuff = array(
            $this->_pkg->getTasksNs() . ':id' => $id,
        );
        if ($instructions) {
            $stuff[$this->_pkg->getTasksNs() . ':instructions'] = $instructions;
        }
        $stuff[$this->_pkg->getTasksNs() . ':name'] = $oldgroup . '::' . $param;
        $stuff[$this->_pkg->getTasksNs() . ':conditiontype'] = $conditiontype;
        $stuff[$this->_pkg->getTasksNs() . ':value'] = $value;
        if ($params) {
            $stuff[$this->_pkg->getTasksNs() . ':param'] = $params;
        }
        $this->_params[$this->_pkg->getTasksNs() . ':paramgroup'][] = $stuff;
    }

    function getXml()
    {
        return $this->_params;
    }

    /**
     * Use to set up a param tag for use in creating a paramgroup
     * @static
     */
    function getParam($name, $prompt, $type = 'string', $default = null)
    {
        if ($default !== null) {
            return
            array(
                $this->_pkg->getTasksNs() . ':name' => $name,
                $this->_pkg->getTasksNs() . ':prompt' => $prompt,
                $this->_pkg->getTasksNs() . ':type' => $type,
                $this->_pkg->getTasksNs() . ':default' => $default
            );
        }
        return
            array(
                $this->_pkg->getTasksNs() . ':name' => $name,
                $this->_pkg->getTasksNs() . ':prompt' => $prompt,
                $this->_pkg->getTasksNs() . ':type' => $type,
            );
    }
}
?>