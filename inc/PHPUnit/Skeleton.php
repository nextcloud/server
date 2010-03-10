<?php
//
// +------------------------------------------------------------------------+
// | PEAR :: PHPUnit                                                        |
// +------------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de>. |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//
// $Id: Skeleton.php,v 1.6 2004/12/22 08:06:11 sebastian Exp $
//

/**
 * Class for creating a PHPUnit_TestCase skeleton file.
 *
 * This class will take a classname as a parameter on construction and will
 * create a PHP file that contains the skeleton of a PHPUnit_TestCase
 * subclass. The test case will contain a test foreach method of the class.
 * Methods of the parent class will, by default, be excluded from the test
 * class. Passing and optional construction parameter will include them.
 *
 * Example
 *
 *   <?php
 *   require_once 'PHPUnit/Skeleton.php';
 *   $ps = new PHPUnit_Skeleton('PHPUnit_Skeleton', 'PHPUnit/Skeleton.php');
 *
 *   // Generate the test class.
 *   // Default settings will not include any parent class methods, but
 *   // will include private methods.
 *   $ps->createTestClass();
 *
 *   // Write the new test class to file.
 *   // By default, code to run the test will be included.
 *   $ps->writeTestClass();
 *   ?>
 *
 * Now open the skeleton class and fill in the details.
 * If you run the test as is, all tests will fail and
 * you will see plenty of undefined constant errors.
 *
 * @author      Scott Mattocks <scott@crisscott.com>
 * @copyright   Copyright &copy; 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    Testing
 * @package     PHPUnit
 */
class PHPUnit_Skeleton {
    /**
     * Path to the class file to create a skeleton for.
     * @var string
     */
    var $classPath;

    /**
     * The name of the class
     * @var string
     */
    var $className;

    /**
     * Path to the configuration file needed by class to test.
     * @var string
     */
    var $configFile;

    /**
     * Whether or not to include the methods of the parent class when testing.
     * @var boolean
     */
    var $includeParents;

    /**
     * Whether or not to test private methods.
     * @var boolean
     */
    var $includePrivate;

    /**
     * The test class that will be created.
     * @var string
     */
    var $testClass;

    /**
     * Constructor. Sets the class members and check that the class
     * to test is accessible.
     *
     * @access public
     * @param  string  $className
     * @param  string  $classPath
     * @param  boolean $includeParents Wheter to include the parent's methods in the test.
     * @return void
     */
    function PHPUnit_Skeleton($className, $classPath, $includeParents = FALSE, $includePrivate = TRUE) {
        // Set up the members.
        if (@is_readable($classPath)) {
            $this->className = $className;
            $this->classPath = $classPath;
        } else {
            $this->_handleErrors($classPath . ' is not readable. Cannot create test class.');
        }

        // Do we want to include parent methods?
        $this->includeParents = $includeParents;

        // Do we want to allow private methods?
        $this->includePrivate = $includePrivate;
    }

    /**
     * The class to test may require a special config file before it can be
     * instantiated. This method lets you set that file.
     *
     * @access public
     * @param  string $configPath
     * @return void
     */
    function setConfigFile($configFile) {
        // Check that the file is readable
        if (@is_readable($configFile)) {
            $this->configFile = $configFile;
        } else {
            $this->_handleErrors($configFile . ' is not readable. Cannot create test class.');
        }
    }

    /**
     * Create the code that will be the skeleton of the test case.
     *
     * The test case must have a clss definition, one var, a constructor
     * setUp, tearDown, and methods. Optionally and by default the code
     * to run the test is added when the class is written to file.
     *
     * @access public
     * @param  none
     * @return void
     */
    function createTestClass() {
        // Instantiate the object.
        if (isset($this->configFile)) {
            require_once $this->configFile;
        }

        require_once $this->classPath;

        // Get the methods.
        $classMethods = get_class_methods($this->className);

        // Remove the parent methods if needed.
        if (!$this->includeParents) {
            $parentMethods = get_class_methods(get_parent_class($this->className));

            if (count($parentMethods)) {
                $classMethods = array_diff($classMethods, $parentMethods);
            }
        }

        // Create the class definition, constructor, setUp and tearDown.
        $this->_createDefinition();
        $this->_createConstructor();
        $this->_createSetUpTearDown();

        if (count($classMethods)) {
            // Foreach method create a test case.
            foreach ($classMethods as $method) {
                // Unless it is the constructor.
                if (strcasecmp($this->className, $method) !== 0) {
                  // Check for private methods.
                  if (!$this->includePrivate && strpos($method, '_') === 0) {
                      continue;
                  } else {
                      $this->_createMethod($method);
                  }
                }
            }
        }

        // Finis off the class.
        $this->_finishClass();
    }

    /**
     * Create the class definition.
     *
     * The definition consist of a header comment, require statment
     * for getting the PHPUnit file, the actual class definition,
     * and the definition of the class member variable.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createDefinition() {
        // Create header comment.
        $this->testClass =
          "/**\n" .
          " * PHPUnit test case for " . $this->className . "\n" .
          " * \n" .
          " * The method skeletons below need to be filled in with \n" .
          " * real data so that the tests will run correctly. Replace \n" .
          " * all EXPECTED_VAL and PARAM strings with real data. \n" .
          " * \n" .
          " * Created with PHPUnit_Skeleton on " . date('Y-m-d') . "\n" .
          " */\n";

        // Add the require statements.
        $this->testClass .= "require_once 'PHPUnit.php';\n";

        // Add the class definition and variable definition.
        $this->testClass .=
          "class " . $this->className . "Test extends PHPUnit_TestCase {\n\n" .
          "    var \$" . $this->className . ";\n\n";
    }

    /**
     * Create the class constructor. (PHP4 style)
     *
     * The constructor simply calls the PHPUnit_TestCase method.
     * This code is taken from the PHPUnit documentation.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createConstructor() {
        // Create the test class constructor.
        $this->testClass.=
          "    function " . $this->className . "Test(\$name)\n" .
          "    {\n" .
          "        \$this->PHPUnit_TestCase(\$name);\n" .
          "    }\n\n";
    }

    /**
     * Create setUp and tearDown methods.
     *
     * The setUp method creates the instance of the object to test.
     * The tearDown method releases the instance.
     * This code is taken from the PHPUnit documentation.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createSetUpTearDown() {
        // Create the setUp method.
        $this->testClass .=
          "    function setUp()\n" .
          "    {\n";

        if (isset($this->configFile)) {
            $this->testClass .=
            "        require_once '" . $this->configFile . "';\n";
        }

        $this->testClass .=
          "        require_once '" . $this->classPath . "';\n" .
          "        \$this->" . $this->className . " =& new " . $this->className . "(PARAM);\n" .
          "    }\n\n";

        // Create the tearDown method.
        $this->testClass .=
          "    function tearDown()\n" .
          "    {\n" .
          "        unset(\$this->" . $this->className . ");\n" .
          "    }\n\n";
    }

    /**
     * Create a basic skeleton for test methods.
     *
     * This code is taken from the PHPUnit documentation.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createMethod($methodName) {
        // Create a test method.
        $this->testClass .=
          "    function test" . $methodName . "()\n" .
          "    {\n" .
          "        \$result   = \$this->" . $this->className . "->" . $methodName . "(PARAM);\n" .
          "        \$expected = EXPECTED_VAL;\n" .
          "        \$this->assertEquals(\$expected, \$result);\n" .
          "    }\n\n";
    }

    /**
     * Add the closing brace needed for a proper class definition.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _finishClass() {
        // Close off the class.
        $this->testClass.= "}\n";
    }

    /**
     * Create the code that will actually run the test.
     *
     * This code is added by default so that the test can be run
     * just by running the file. To have it not added pass false
     * as the second parameter to the writeTestClass method.
     * This code is taken from the PHPUnit documentation.
     *
     * All of the code needed for the new class is stored in the
     * testClass member.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createTest() {
        // Create a call to the test.
        $test =
          "// Running the test.\n" .
          "\$suite  = new PHPUnit_TestSuite('" . $this->className . "Test');\n" .
          "\$result = PHPUnit::run(\$suite);\n" .
          "echo \$result->toString();\n";

        return $test;
    }

    /**
     * Write the test class to file.
     *
     * This will write the test class created using the createTestClass
     * method to a file called <className>Test.php. By default the file
     * is written to the current directory and will have code to run
     * the test appended to the bottom of the file.
     *
     * @access public
     * @param  string  $destination The directory to write the file to.
     * @param  boolean $addTest     Wheter to add the test running code.
     * @return void
     */
    function writeTestClass($destination = './', $addTest = TRUE) {
        // Check for something to write to file.
        if (!isset($this->testClass)) {
            $this->_handleErrors('Noting to write.', PHPUS_WARNING);
            return;
        }

        // Open the destination file.
        $fp = fopen($destination . $this->className . 'Test.php', 'w');
        fwrite($fp, "<?php\n");

        // Write the test class.
        fwrite($fp, $this->testClass);

        // Add the call to test the class in the file if we were asked to.
        if ($addTest) {
            fwrite($fp, $this->_createTest());
        }

        // Close the file.
        fwrite($fp, "?>\n");
        fclose($fp);
    }

    /**
     * Error handler.
     *
     * This method should be rewritten to use the prefered error
     * handling method. (PEAR_ErrorStack)
     *
     * @access private
     * @param  string  $message The error message.
     * @param  integer $type    An indication of the severity of the error.
     * @return void             Code may cause PHP to exit.
     */
    function _handleErrors($message, $type = E_USER_ERROR) {
        // For now just echo the message.
        echo $message;

        // Check to see if we should quit.
        if ($type == E_USER_ERROR) {
            exit;
        }
    }
}
?>
