<?php

/**
 * Smarty read include path plugin
 *
 * @package Smarty
 * @subpackage PluginsInternal
 * @author Monte Ohrt
 */

/**
 * Smarty Internal Read Include Path Class
 */
class Smarty_Internal_Get_Include_Path {
    /**
     * Return full file path from PHP include_path
     *
     * @param string $filepath filepath
     * @return mixed full filepath or false
     */
    public static function getIncludePath($filepath)
    {
    static $_path_array = null;

    if(!isset($_path_array)) {
        $_ini_include_path = ini_get('include_path');

        if(strstr($_ini_include_path,';')) {
            // windows pathnames
            $_path_array = explode(';',$_ini_include_path);
        } else {
            $_path_array = explode(':',$_ini_include_path);
        }
    }
    foreach ($_path_array as $_include_path) {
        if (file_exists($_include_path . DS . $filepath)) {
            return $_include_path . DS . $filepath;
        }
    }
    return false;
    }
}

?>