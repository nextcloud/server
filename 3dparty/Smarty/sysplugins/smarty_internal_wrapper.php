<?php

/**
 * Project:     Smarty: the PHP compiling template engine
 * File:        smarty_internal_wrapper.php
 * SVN:         $Id: $
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * Smarty mailing list. Send a blank e-mail to
 * smarty-discussion-subscribe@googlegroups.com
 *
 * @link http://www.smarty.net/
 * @copyright 2008 New Digital Group, Inc.
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author Uwe Tews
 * @package Smarty
 * @subpackage PluginsInternal
 * @version 3-SVN$Rev: 3286 $
 */

/*
 * Smarty Backward Compatability Wrapper
 */

class Smarty_Internal_Wrapper {

    protected $smarty;

    function __construct($smarty) {
      $this->smarty = $smarty;
    }

    /**
     * Converts smarty2-style function call to smarty 3-style function call
     * This is expensive, be sure to port your code to Smarty 3!
     *
     * @param string $name Smarty 2 function name
     * @param array $args Smarty 2 function args
     */
    function convert($name, $args) {
       // throw notice about deprecated function
       if($this->smarty->deprecation_notices)
         trigger_error("function call '$name' is unknown or deprecated.",E_USER_NOTICE);
       // get first and last part of function name
       $name_parts = explode('_',$name,2);
       switch($name_parts[0]) {
         case 'register':
         case 'unregister':
           switch($name_parts[1]) {
              case 'object':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Object"),$args);
              case 'compiler_function':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Plugin"),array_merge(array('compiler'),$args));
              case 'prefilter':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Filter"),array_merge(array('pre'),$args));
              case 'postfilter':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Filter"),array_merge(array('post'),$args));
              case 'outputfilter':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Filter"),array_merge(array('output'),$args));
             case 'resource':
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Resource"),$args);
              default:
                 return call_user_func_array(array($this->smarty,"{$name_parts[0]}Plugin"),array_merge(array($name_parts[1]),$args));
           }
           case 'get':
           switch($name_parts[1]) {
              case 'template_vars':
                 return call_user_func_array(array($this->smarty,'getTemplateVars'),$args);
              case 'config_vars':
                 return call_user_func_array(array($this->smarty,'getConfigVars'),$args);
              default:
                 return call_user_func_array(array($myobj,$name_parts[1]),$args);
           }
           case 'clear':
           switch($name_parts[1]) {
              case 'all_assign':
                 return call_user_func_array(array($this->smarty,'clearAllAssign'),$args);
              case 'assign':
                 return call_user_func_array(array($this->smarty,'clearAssign'),$args);
              case 'all_cache':
                 return call_user_func_array(array($this->smarty,'clearAllCache'),$args);
              case 'cache':
                 return call_user_func_array(array($this->smarty,'clearCache'),$args);
              case 'compiled_template':
                 return call_user_func_array(array($this->smarty,'clearCompiledTemplate'),$args);
           }
           case 'config':
           switch($name_parts[1]) {
              case 'load':
                 return call_user_func_array(array($this->smarty,'configLoad'),$args);
           }
           case 'trigger':
           switch($name_parts[1]) {
              case 'error':
                 return call_user_func_array('trigger_error',$args);
           }
           case 'load':
           switch($name_parts[1]) {
              case 'filter':
                 return call_user_func_array(array($this->smarty,'loadFilter'),$args);
           }
       }
       throw new SmartyException("unknown method '$name'");
    }

    /**
     * trigger Smarty error
     *
     * @param string $error_msg
     * @param integer $error_type
     */
    function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        trigger_error("Smarty error: $error_msg", $error_type);
    }
}
?>