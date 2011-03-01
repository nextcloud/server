<?php
//
// +------------------------------------------------------------------------+
// | PEAR :: PHPUnit                                                        |
// +------------------------------------------------------------------------+
// | Copyright (c) 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>. |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//
// $Id: SetupDecorator.php,v 1.12 2005/05/14 05:58:38 sebastian Exp $
//

/**
 * This decorator actually just adds the functionality to read the
 * test-suite classes from a given directory and instanciate them
 * automatically, use it as given in the example below.
 *
 * <code>
 * <?php
 * $gui = new PHPUnit_GUI_SetupDecorator(new PHPUnit_GUI_HTML());
 * $gui->getSuitesFromDir('/path/to/dir/tests','.*\.php$',array('index.php','sql.php'));
 * $gui->show();
 * ?>
 * </code>
 *
 * The example calls this class and tells it to:
 *
 *   - find all file under the directory /path/to/dir/tests
 *   - for files, which end with '.php' (this is a piece of a regexp, that's why the . is escaped)
 *   - and to exclude the files 'index.php' and 'sql.php'
 *   - and include all the files that are left in the tests.
 *
 * Given that the path (the first parameter) ends with 'tests' it will be assumed
 * that the classes are named tests_* where * is the directory plus the filename,
 * according to PEAR standards.
 *
 * So that:
 *
 *   - 'testMe.php' in the dir 'tests' bill be assumed to contain a class tests_testMe
 *   - '/moretests/aTest.php' should contain a class 'tests_moretests_aTest'
 *
 * @author      Wolfram Kriesing <wolfram@kriesing.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    Testing
 * @package     PHPUnit
 * @subpackage  GUI
 */
class PHPUnit_GUI_SetupDecorator
{
    /**
    *
    *
    */
    function PHPUnit_GUI_SetupDecorator(&$gui)
    {
        $this->_gui = &$gui;
    }

    /**
    *   just forwarding the action to the decorated class.
    *
    */
    function show($showPassed=TRUE)
    {
        $this->_gui->show($showPassed);
    }

    /**
    * Setup test suites that can be found in the given directory
    * Using the second parameter you can also choose a subsets of the files found
    * in the given directory. I.e. only all the files that contain '_UnitTest_',
    * in order to do this simply call it like this:
    * <code>getSuitesFromDir($dir,'.*_UnitTest_.*')</code>.
    * There you can already see that the pattern is built for the use within a regular expression.
    *
    * @param  string  the directory where to search for test-suite files
    * @param  string  the pattern (a regexp) by which to find the files
    * @param  array   an array of file names that shall be excluded
    */
    function getSuitesFromDir($dir, $filenamePattern = '', $exclude = array())
    {
        if ($dir{strlen($dir)-1} == DIRECTORY_SEPARATOR) {
            $dir = substr($dir, 0, -1);
        }

        $files = $this->_getFiles($dir, $filenamePattern, $exclude, realpath($dir . '/..'));
        asort($files);

        foreach ($files as $className => $aFile) {
            include_once($aFile);

            if (class_exists($className)) {
                $suites[] =& new PHPUnit_TestSuite($className);
            } else {
                trigger_error("$className could not be found in $dir$aFile!");
            }
        }

        $this->_gui->addSuites($suites);
    }

    /**
    * This method searches recursively through the directories
    * to find all the files that shall be added to the be visible.
    *
    * @param  string  the path where find the files
    * @param  srting  the string pattern by which to find the files
    * @param  string  the file names to be excluded
    * @param  string  the root directory, which serves as the prefix to the fully qualified filename
    * @access private
    */
    function _getFiles($dir, $filenamePattern, $exclude, $rootDir)
    {
        $files = array();

        if ($dp = opendir($dir)) {
            while (FALSE !== ($file = readdir($dp))) {
                $filename = $dir . DIRECTORY_SEPARATOR . $file;
                $match    = TRUE;

                if ($filenamePattern && !preg_match("~$filenamePattern~", $file)) {
                    $match = FALSE;
                }

                if (sizeof($exclude)) {
                    foreach ($exclude as $aExclude) {
                        if (strpos($file, $aExclude) !== FALSE) {
                            $match = FALSE;
                            break;
                        }
                    }
                }

                if (is_file($filename) && $match) {
                    $tmp = str_replace($rootDir, '', $filename);

                    if (strpos($tmp, DIRECTORY_SEPARATOR) === 0) {
                        $tmp = substr($tmp, 1);
                    }

                    if (strpos($tmp, '/') === 0) {
                        $tmp = substr($tmp, 1);
                    }

                    $className = str_replace(DIRECTORY_SEPARATOR, '_', $tmp);
                    $className = basename($className, '.php');

                    $files[$className] = $filename;
                }

                if ($file != '.' && $file != '..' && is_dir($filename)) {
                    $files = array_merge($files, $this->_getFiles($filename, $filenamePattern, $exclude, $rootDir));
                }
            }

            closedir($dp);
        }

        return $files;
    }
}
?>
