<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Scott Mattocks                                    |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Scott Mattocks <scottmattocks@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id: Gtk.php,v 1.2 2004/11/25 09:06:55 sebastian Exp $
/**
 * GTK GUI interface for PHPUnit.
 *
 * This class is a PHP port of junit.awtui.testrunner. Documentation
 * for junit.awtui.testrunner can be found at
 * http://junit.sourceforge.net
 *
 * Due to the limitations of PHP4 and PHP-Gtk, this class can not
 * duplicate all of the functionality of the JUnit GUI. Some of the
 * things this class cannot do include:
 * - Reloading the class for each run
 * - Stopping the test in progress
 *
 * To use simply intantiate the class and call main()
 * $gtk =& new PHPUnit_GUI_Gtk;
 * $gtk->main();
 *
 * Once the window has finished loading, you can enter the name of
 * a class that has been loaded (include/require some where in your
 * code, or you can pass the name of the file containing the class.
 *
 * You can also load classes using the SetupDecorator class.
 * require_once 'PHPUnit/GUI/SetupDecorator.php';
 * require_once 'PHPUnit/GUI/Gtk.php';
 * $gui = new PHPUnit_GUI_SetupDecorator(new PHPUnit_GUI_Gtk());
 * $gui->getSuitesFromDir('/path/to/test','.*\.php$',array('index.php','sql.php'));
 * $gui->show();
 *
 * @todo Allow file drop. (Gtk_FileDrop)
 *
 * @author     Scott Mattocks
 * @copyright  Copyright &copy; 2004 Scott Mattocks
 * @license    PHP 3.0
 * @version    @VER@
 * @category   PHP
 * @package    PHPUnit
 * @subpackage GUI
 */
class PHPUnit_GUI_Gtk {

    /**
     * The main gtk window
     * @var object
     */
    var $gui;
    /**
     * The text entry that contains the name of the
     * file that holds the test(s)/suite(s).
     * @var object
     */
    var $suiteField;
    /**
     * The label that shows the number of tests that
     * were run.
     * @var object
     */
    var $numberOfRuns;
    /**
     * The label that shows the number of errors that
     * were encountered.
     * @var object
     */
    var $numberOfErrors;
    /**
     * The label that shows the number of failures
     * that were encountered.
     * @var object
     */
    var $numberOfFailures;
    /**
     * The label for reporting user messages.
     * @var object
     */
    var $statusLine;
    /**
     * The text area for reporting messages from successful
     * test runs. (not necessarily successful tests)
     * @var object
     */
    var $reportArea;
    /**
     * The text area for reporting errors encountered when
     * running tests.
     * @var object
     */
    var $dumpArea;
    /**
     * The progress bar indicator. Shows the percentage of
     * passed tests.
     * @var object
     */
    var $progress;
    /**
     * A checkbox for the user to indicate whether or not they
     * would like to see results from all tests or just failures.
     * @object
     */
    var $showPassed;

    /**
     * Constructor.
     *
     * The constructor checks for the gtk extension and loads it
     * if needed. Then it creates the GUI. The GUI is not shown
     * nor is the main gtk loop started until main() is called.
     *
     * @access public
     * @param  none
     * @return void
     */
    function PHPUnit_GUI_Gtk()
    {
        // Check for php-gtk extension.
        if (!extension_loaded('gtk')) {
            dl( 'php_gtk.' . PHP_SHLIB_SUFFIX);
        }

        // Create the interface but don't start the loop
        $this->_createUI();
    }
    /**
     * Start the main gtk loop.
     *
     * main() first sets the default state of the showPassed
     * check box. Next all widgets that are part of the GUI
     * are shown. Finally the main gtk loop is started.
     *
     * @access public
     * @param  boolean $showPassed
     * @return void
     */
    function main($showPassed = true)
    {
        $this->showPassed->set_active($showPassed);
        $this->gui->show_all();

        gtk::main();
    }
    /**
     * Create the user interface.
     *
     * The user interface is pretty simple. It consists of a
     * menu, text entry, run button, some labels, a progress
     * indicator, and a couple of areas for notification of
     * any messages.
     *
     * @access private
     * @param  none
     * @return void
     */
    function _createUI()
    {
        // Create a window.
        $window =& new GtkWindow;
        $window->set_title('PHPUnit Gtk');
        $window->set_usize(400, -1);

        // Create the main box.
        $mainBox =& new GtkVBox;
        $window->add($mainBox);

        // Start with the menu.
        $mainBox->pack_start($this->_createMenu());

        // Then add the suite field entry.
        $mainBox->pack_start($this->_createSuiteEntry());

        // Then add the report labels.
        $mainBox->pack_start($this->_createReportLabels());

        // Next add the progress bar.
        $mainBox->pack_start($this->_createProgressBar());

        // Then add the report area and the dump area.
        $mainBox->pack_start($this->_createReportAreas());

        // Finish off with the status line.
        $mainBox->pack_start($this->_createStatusLine());

        // Connect the destroy signal.
        $window->connect_object('destroy', array('gtk', 'main_quit'));

        // Assign the member.
        $this->gui =& $window;
    }
    /**
     * Create the menu.
     *
     * The menu is very simple. It an exit menu item, which exits
     * the application, and an about menu item, which shows some
     * basic information about the application itself.
     *
     * @access private
     * @param  none
     * @return &object The GtkMenuBar
     */
    function &_createMenu()
    {
        // Create the menu bar.
        $menuBar =& new GtkMenuBar;

        // Create the main (only) menu item.
        $phpHeader =& new GtkMenuItem('PHPUnit');

        // Add the menu item to the menu bar.
        $menuBar->append($phpHeader);

        // Create the PHPUnit menu.
        $phpMenu =& new GtkMenu;

        // Add the menu items
        $about =& new GtkMenuItem('About...');
        $about->connect('activate', array(&$this, 'about'));
        $phpMenu->append($about);

        $exit =& new GtkMenuItem('Exit');
        $exit->connect_object('activate', array('gtk', 'main_quit'));
        $phpMenu->append($exit);

        // Complete the menu.
        $phpHeader->set_submenu($phpMenu);

        return $menuBar;
    }
    /**
     * Create the suite entry and related widgets.
     *
     * The suite entry has some supporting components such as a
     * label, the show passed check box and the run button. All
     * of these items are packed into two nested boxes.
     *
     * @access private
     * @param  none
     * @return &object A box that contains all of the suite entry pieces.
     */
    function &_createSuiteEntry()
    {
        // Create the outermost box.
        $outerBox         =& new GtkVBox;

        // Create the suite label, box, and field.
        $suiteLabel       =& new GtkLabel('Test class name:');
        $suiteBox         =& new GtkHBox;
        $this->suiteField =& new GtkEntry;
        $this->suiteField->set_text($suiteName != NULL ? $suiteName : '');

        // Create the button the user will use to start the test.
        $runButton =& new GtkButton('Run');
        $runButton->connect_object('clicked', array(&$this, 'run'));

        // Create the check box that lets the user show only failures.
        $this->showPassed =& new GtkCheckButton('Show passed tests');

        // Add the components to their respective boxes.
        $suiteLabel->set_alignment(0, 0);
        $outerBox->pack_start($suiteLabel);
        $outerBox->pack_start($suiteBox);
        $outerBox->pack_start($this->showPassed);

        $suiteBox->pack_start($this->suiteField);
        $suiteBox->pack_start($runButton);

        return $outerBox;
    }

    /**
     * Create the labels that tell the user what has happened.
     *
     * There are three labels, one each for total runs, errors and
     * failures. There is also one label for each of these that
     * describes what the label is. It could be done with one label
     * instead of two but that would make updates much harder.
     *
     * @access private
     * @param  none
     * @return &object A box containing the labels.
     */
    function &_createReportLabels()
    {
        // Create a box to hold everything.
        $labelBox         =& new GtkHBox;

        // Create the non-updated labels.
        $numberOfRuns     =& new GtkLabel('Runs:');
        $numberOfErrors   =& new GtkLabel('Errors:');
        $numberOfFailures =& new GtkLabel('Failures:');

        // Create the labels that will be updated.
        // These are asssigned to members to make it easier to
        // set their values later.
        $this->numberOfRuns     =& new GtkLabel(0);
        $this->numberOfErrors   =& new GtkLabel(0);
        $this->numberOfFailures =& new GtkLabel(0);

        // Pack everything in.
        $labelBox->pack_start($numberOfRuns);
        $labelBox->pack_start($this->numberOfRuns);
        $labelBox->pack_start($numberOfErrors);
        $labelBox->pack_start($this->numberOfErrors);
        $labelBox->pack_start($numberOfFailures);
        $labelBox->pack_start($this->numberOfFailures);

        return $labelBox;
    }

    /**
     * Create the success/failure indicator.
     *
     * A GtkProgressBar is used to visually indicate how many
     * tests were successful compared to how many were not. The
     * progress bar shows the percentage of success and will
     * change from green to red if there are any failures.
     *
     * @access private
     * @param  none
     * @return &object The progress bar
     */
    function &_createProgressBar()
    {
        // Create the progress bar.
        $this->progress =& new GtkProgressBar(new GtkAdjustment(0, 0, 1, .1, 1, 0));

        // Set the progress bar to print the percentage.
        $this->progress->set_show_text(true);

        return $this->progress;
    }

    /**
     * Create the report text areas.
     *
     * The report area consists of one text area for failures, one
     * text area for errors and one label for identification purposes.
     * All three widgets are packed into a box.
     *
     * @access private
     * @param  none
     * @return &object The box containing the report areas.
     */
    function &_createReportAreas()
    {
        // Create the containing box.
        $reportBox =& new GtkVBox;

        // Create the identification label
        $reportLabel =& new GtkLabel('Errors and Failures:');
        $reportLabel->set_alignment(0, 0);

        // Create the scrolled windows for the text areas.
        $reportScroll =& new GtkScrolledWindow;
        $dumpScroll   =& new GtkScrolledWindow;

        // Make the scroll areas big enough.
        $reportScroll->set_usize(-1, 150);
        $dumpScroll->set_usize(-1, 150);

        // Only show the vertical scroll bar when needed.
        // Never show the horizontal scroll bar.
        $reportScroll->set_policy(GTK_POLICY_NEVER, GTK_POLICY_AUTOMATIC);
        $dumpScroll->set_policy(GTK_POLICY_NEVER, GTK_POLICY_AUTOMATIC);

        // Create the text areas.
        $this->reportArea =& new GtkText;
        $this->dumpArea =& new GtkText;

        // Don't let words get broken.
        $this->reportArea->set_word_wrap(true);
        $this->dumpArea->set_word_wrap(true);

        // Pack everything in.
        $reportBox->pack_start($reportLabel);
        $reportScroll->add($this->reportArea);
        $reportBox->pack_start($reportScroll);
        $dumpScroll->add($this->dumpArea);
        $reportBox->pack_start($dumpScroll);

        return $reportBox;
    }

    /**
     * Create a status label.
     *
     * A status line at the bottom of the application is used
     * to notify the user of non-test related messages such as
     * failures loading a test suite.
     *
     * @access private
     * @param  none
     * @return &object The status label.
     */
    function &_createStatusLine()
    {
        // Create the status label.
        $this->statusLine =& new GtkLabel('');
        $this->statusLine->set_alignment(0, 0);

        return $this->statusLine;
    }

    /**
     * Show a popup with information about the application.
     *
     * The popup should show information about the version,
     * the author, the license, where to get the latest
     * version and a short description.
     *
     * @access public
     * @param  none
     * @return void
     */
    function about()
    {
        // Create the new window.
        $about =& new GtkWindow;
        $about->set_title('About PHPUnit GUI Gtk');
        $about->set_usize(250, -1);

        // Put two vboxes in the hbox.
        $vBox =& new GtkVBox;
        $about->add($vBox);

        // Create the labels.
        $version     =& new GtkLabel(" Version: 1.0");
        $license     =& new GtkLabel(" License: PHP License v3.0");
        $where       =& new GtkLabel(" Download from: http://pear.php.net/PHPUnit/");
        $unitAuth    =& new GtkLabel(" PHPUnit Author: Sebastian Bergman");
        $gtkAuth     =& new GtkLabel(" Gtk GUI Author: Scott Mattocks");

        // Align everything to the left
        $where->set_alignment(0, .5);
        $version->set_alignment(0, .5);
        $license->set_alignment(0, .5);
        $gtkAuth->set_alignment(0, .5);
        $unitAuth->set_alignment(0, .5);

        // Pack everything into the vBox;
        $vBox->pack_start($version);
        $vBox->pack_start($license);
        $vBox->pack_start($where);
        $vBox->pack_start($unitAuth);
        $vBox->pack_start($gtkAuth);

        // Connect the destroy signal.
        $about->connect('destroy', array('gtk', 'true'));

        // Show the goods.
        $about->show_all();
    }

    /**
     * Load the test suite.
     *
     * This method tries to load test suite based on the user
     * info. If the user passes the name of a tests suite, it
     * is instantiated and a new object is returned. If the
     * user passes a file that contains a test suite, the class
     * is instantiated and a new object is returned. If the user
     * passes a file that contains a test case, the test case is
     * passed to a new test suite and the new suite object is
     * returned.
     *
     * @access public
     * @param  string  The file that contains a test case/suite or the classname.
     * @return &object The new test suite.
     */
    function &loadTest(&$file)
    {
        // Check to see if a class name was given.
        if (is_a($file, 'PHPUnit_TestSuite')) {
            return $file;
        } elseif (class_exists($file)) {
            require_once 'PHPUnit/TestSuite.php';
            return new PHPUnit_TestSuite($file);
        }

        // Check that the file exists.
        if (!@is_readable($file)) {
            $this->_showStatus('Cannot find file: ' . $file);
            return false;
        }

        $this->_showStatus('Loading test suite...');

        // Instantiate the class.
        // If the path is /path/to/test/TestClass.php
        // the class name should be test_TestClass
        require_once $file;
        $className = str_replace(DIRECTORY_SEPARATOR, '_', $file);
        $className = substr($className, 0, strpos($className, '.'));

        require_once 'PHPUnit/TestSuite.php';
        return new PHPUnit_TestSuite($className);
    }

    /**
     * Run the test suite.
     *
     * This method runs the test suite and updates the messages
     * for the user. When finished it changes the status line
     * to 'Test Complete'
     *
     * @access public
     * @param  none
     * @return void
     */
    function runTest()
    {
        // Notify the user that the test is running.
        $this->_showStatus('Running Test...');

        // Run the test.
        $result = PHPUnit::run($this->suite);

        // Update the labels.
        $this->_setLabelValue($this->numberOfRuns,     $result->runCount());
        $this->_setLabelValue($this->numberOfErrors,   $result->errorCount());
        $this->_setLabelValue($this->numberOfFailures, $result->failureCount());

        // Update the progress bar.
        $this->_updateProgress($result->runCount(),
                               $result->errorCount(),
                               $result->failureCount()
                               );

        // Show the errors.
        $this->_showFailures($result->errors(), $this->dumpArea);

        // Show the messages from the tests.
        if ($this->showPassed->get_active()) {
            // Show failures and success.
            $this->_showAll($result, $this->reportArea);
        } else {
            // Show only failures.
            $this->_showFailures($result->failures(), $this->reportArea);
        }

        // Update the status message.
        $this->_showStatus('Test complete');
    }

    /**
     * Set the text of a label.
     *
     * Change the text of a given label.
     *
     * @access private
     * @param  widget  &$label The label whose value is to be changed.
     * @param  string  $value  The new text of the label.
     * @return void
     */
    function _setLabelValue(&$label, $value)
    {
        $label->set_text($value);
    }

    /**
     * The main work of the application.
     *
     * Load the test suite and then execute the tests.
     *
     * @access public
     * @param  none
     * @return void
     */
    function run()
    {
        // Load the test suite.
        $this->suite =& $this->loadTest($this->suiteField->get_text());

        // Check to make sure the suite was loaded properly.
        if (!is_object($this->suite)) {
            // Raise an error.
            $this->_showStatus('Could not load test suite.');
            return false;
        }

        // Run the tests.
        $this->runTest();
    }

    /**
     * Update the status message.
     *
     * @access private
     * @param  string  $status The new message.
     * @return void
     */
    function _showStatus($status)
    {
        $this->statusLine->set_text($status);
    }

    /**
     * Alias for main()
     *
     * @see main
     */
    function show($showPassed = true)
    {
        $this->main($showPassed);
    }

    /**
     * Add a suite to the tests.
     *
     * This method is require by SetupDecorator. It adds a
     * suite to the the current set of suites.
     *
     * @access public
     * @param  object $testSuite The suite to add.
     * @return void
     */
    function addSuites($testSuite)
    {
        if (!is_array($testSuite)) {
            settype($testSuite, 'array');
        }

        foreach ($testSuite as $suite) {

            if (is_a($this->suite, 'PHPUnit_TestSuite')) {
                $this->suite->addTestSuite($suite->getName());
            } else {
                $this->suite =& $this->loadTest($suite);
            }

            // Set the suite field.
            $text = $this->suiteField->get_text();
            if (empty($text)) {
                $this->suiteField->set_text($this->suite->getName());
            }
        }
    }

    /**
     * Show all test messages.
     *
     * @access private
     * @param  object  The TestResult from the test suite.
     * @return void
     */
    function _showAll(&$result)
    {
        // Clear the area first.
        $this->reportArea->delete_text(0, -1);
        $this->reportArea->insert_text($result->toString(), 0);
    }

    /**
     * Show failure/error messages in the given text area.
     *
     * @access private
     * @param  object  &$results The results of the test.
     * @param  widget  &$area    The area to show the results in.
     */
    function _showFailures(&$results, &$area)
    {
        $area->delete_text(0, -1);
        foreach (array_reverse($results, true) as $result) {
            $area->insert_text($result->toString(), 0);
        }
    }

    /**
     * Update the progress indicator.
     *
     * @access private
     * @param  integer $runs
     * @param  integer $errors
     * @param  integer $failures
     * @return void
     */
    function _updateProgress($runs, $errors, $failures)
    {
        $percentage = 1 - (($errors + $failures) / $runs);
        $this->progress->set_percentage($percentage);
    }
}
?>