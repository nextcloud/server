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
// $Id: TestDecorator.php,v 1.12 2005/05/14 05:58:38 sebastian Exp $
//

require_once 'PHPUnit/TestCase.php';
require_once 'PHPUnit/TestSuite.php';

/**
 * A Decorator for Tests.
 *
 * Use TestDecorator as the base class for defining new
 * test decorators. Test decorator subclasses can be introduced
 * to add behaviour before or after a test is run.
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright   Copyright &copy; 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    Testing
 * @package     PHPUnit
 */
class PHPUnit_TestDecorator {
    /**
    * The Test to be decorated.
    *
    * @var    object
    * @access protected
    */
    var $_test = NULL;

    /**
    * Constructor.
    *
    * @param  object
    * @access public
    */
    function PHPUnit_TestDecorator(&$test) {
        if (is_object($test) &&
            (is_a($test, 'PHPUnit_TestCase') ||
             is_a($test, 'PHPUnit_TestSuite'))) {

            $this->_test = &$test;
        }
    }

    /**
    * Runs the test and collects the
    * result in a TestResult.
    *
    * @param  object
    * @access public
    */
    function basicRun(&$result) {
        $this->_test->run($result);
    }

    /**
    * Counts the number of test cases that
    * will be run by this test.
    *
    * @return integer
    * @access public
    */
    function countTestCases() {
        return $this->_test->countTestCases();
    }

    /**
    * Returns the test to be run.
    *
    * @return object
    * @access public
    */
    function &getTest() {
        return $this->_test;
    }

    /**
    * Runs the decorated test and collects the
    * result in a TestResult.
    *
    * @param  object
    * @access public
    * @abstract
    */
    function run(&$result) { /* abstract */ }

    /**
    * Returns a string representation of the test.
    *
    * @return string
    * @access public
    */
    function toString() {
        return $this->_test->toString();
    }
}
?>
