<?php
/**
 * PEAR_Command_Test (run-tests)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Test.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * base class
 */
require_once 'PEAR/Command/Common.php';

/**
 * PEAR commands for login/logout
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 0.1
 */

class PEAR_Command_Test extends PEAR_Command_Common
{
    var $commands = array(
        'run-tests' => array(
            'summary' => 'Run Regression Tests',
            'function' => 'doRunTests',
            'shortcut' => 'rt',
            'options' => array(
                'recur' => array(
                    'shortopt' => 'r',
                    'doc' => 'Run tests in child directories, recursively.  4 dirs deep maximum',
                ),
                'ini' => array(
                    'shortopt' => 'i',
                    'doc' => 'actual string of settings to pass to php in format " -d setting=blah"',
                    'arg' => 'SETTINGS'
                ),
                'realtimelog' => array(
                    'shortopt' => 'l',
                    'doc' => 'Log test runs/results as they are run',
                ),
                'quiet' => array(
                    'shortopt' => 'q',
                    'doc' => 'Only display detail for failed tests',
                ),
                'simple' => array(
                    'shortopt' => 's',
                    'doc' => 'Display simple output for all tests',
                ),
                'package' => array(
                    'shortopt' => 'p',
                    'doc' => 'Treat parameters as installed packages from which to run tests',
                ),
                'phpunit' => array(
                    'shortopt' => 'u',
                    'doc' => 'Search parameters for AllTests.php, and use that to run phpunit-based tests
If none is found, all .phpt tests will be tried instead.',
                ),
                'tapoutput' => array(
                    'shortopt' => 't',
                    'doc' => 'Output run-tests.log in TAP-compliant format',
                ),
                'cgi' => array(
                    'shortopt' => 'c',
                    'doc' => 'CGI php executable (needed for tests with POST/GET section)',
                    'arg' => 'PHPCGI',
                ),
                'coverage' => array(
                    'shortopt' => 'x',
                    'doc'      => 'Generate a code coverage report (requires Xdebug 2.0.0+)',
                ),
            ),
            'doc' => '[testfile|dir ...]
Run regression tests with PHP\'s regression testing script (run-tests.php).',
            ),
        );

    var $output;

    /**
     * PEAR_Command_Test constructor.
     *
     * @access public
     */
    function PEAR_Command_Test(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    function doRunTests($command, $options, $params)
    {
        if (isset($options['phpunit']) && isset($options['tapoutput'])) {
            return $this->raiseError('ERROR: cannot use both --phpunit and --tapoutput at the same time');
        }

        require_once 'PEAR/Common.php';
        require_once 'System.php';
        $log = new PEAR_Common;
        $log->ui = &$this->ui; // slightly hacky, but it will work
        $tests = array();
        $depth = isset($options['recur']) ? 14 : 1;

        if (!count($params)) {
            $params[] = '.';
        }

        if (isset($options['package'])) {
            $oldparams = $params;
            $params = array();
            $reg = &$this->config->getRegistry();
            foreach ($oldparams as $param) {
                $pname = $reg->parsePackageName($param, $this->config->get('default_channel'));
                if (PEAR::isError($pname)) {
                    return $this->raiseError($pname);
                }

                $package = &$reg->getPackage($pname['package'], $pname['channel']);
                if (!$package) {
                    return PEAR::raiseError('Unknown package "' .
                        $reg->parsedPackageNameToString($pname) . '"');
                }

                $filelist = $package->getFilelist();
                foreach ($filelist as $name => $atts) {
                    if (isset($atts['role']) && $atts['role'] != 'test') {
                        continue;
                    }

                    if (isset($options['phpunit']) && preg_match('/AllTests\.php\\z/i', $name)) {
                        $params[] = $atts['installed_as'];
                        continue;
                    } elseif (!preg_match('/\.phpt\\z/', $name)) {
                        continue;
                    }
                    $params[] = $atts['installed_as'];
                }
            }
        }

        foreach ($params as $p) {
            if (is_dir($p)) {
                if (isset($options['phpunit'])) {
                    $dir = System::find(array($p, '-type', 'f',
                                                '-maxdepth', $depth,
                                                '-name', 'AllTests.php'));
                    if (count($dir)) {
                        foreach ($dir as $p) {
                            $p = realpath($p);
                            if (!count($tests) ||
                                  (count($tests) && strlen($p) < strlen($tests[0]))) {
                                // this is in a higher-level directory, use this one instead.
                                $tests = array($p);
                            }
                        }
                    }
                    continue;
                }

                $args  = array($p, '-type', 'f', '-name', '*.phpt');
            } else {
                if (isset($options['phpunit'])) {
                    if (preg_match('/AllTests\.php\\z/i', $p)) {
                        $p = realpath($p);
                        if (!count($tests) ||
                              (count($tests) && strlen($p) < strlen($tests[0]))) {
                            // this is in a higher-level directory, use this one instead.
                            $tests = array($p);
                        }
                    }
                    continue;
                }

                if (file_exists($p) && preg_match('/\.phpt$/', $p)) {
                    $tests[] = $p;
                    continue;
                }

                if (!preg_match('/\.phpt\\z/', $p)) {
                    $p .= '.phpt';
                }

                $args  = array(dirname($p), '-type', 'f', '-name', $p);
            }

            if (!isset($options['recur'])) {
                $args[] = '-maxdepth';
                $args[] = 1;
            }

            $dir   = System::find($args);
            $tests = array_merge($tests, $dir);
        }

        $ini_settings = '';
        if (isset($options['ini'])) {
            $ini_settings .= $options['ini'];
        }

        if (isset($_ENV['TEST_PHP_INCLUDE_PATH'])) {
            $ini_settings .= " -d include_path={$_ENV['TEST_PHP_INCLUDE_PATH']}";
        }

        if ($ini_settings) {
            $this->ui->outputData('Using INI settings: "' . $ini_settings . '"');
        }

        $skipped = $passed = $failed = array();
        $tests_count = count($tests);
        $this->ui->outputData('Running ' . $tests_count . ' tests', $command);
        $start = time();
        if (isset($options['realtimelog']) && file_exists('run-tests.log')) {
            unlink('run-tests.log');
        }

        if (isset($options['tapoutput'])) {
            $tap = '1..' . $tests_count . "\n";
        }

        require_once 'PEAR/RunTest.php';
        $run = new PEAR_RunTest($log, $options);
        $run->tests_count = $tests_count;

        if (isset($options['coverage']) && extension_loaded('xdebug')){
            $run->xdebug_loaded = true;
        } else {
            $run->xdebug_loaded = false;
        }

        $j = $i = 1;
        foreach ($tests as $t) {
            if (isset($options['realtimelog'])) {
                $fp = @fopen('run-tests.log', 'a');
                if ($fp) {
                    fwrite($fp, "Running test [$i / $tests_count] $t...");
                    fclose($fp);
                }
            }
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            if (isset($options['phpunit'])) {
                $result = $run->runPHPUnit($t, $ini_settings);
            } else {
                $result = $run->run($t, $ini_settings, $j);
            }
            PEAR::staticPopErrorHandling();
            if (PEAR::isError($result)) {
                $this->ui->log($result->getMessage());
                continue;
            }

            if (isset($options['tapoutput'])) {
                $tap .= $result[0] . ' ' . $i . $result[1] . "\n";
                continue;
            }

            if (isset($options['realtimelog'])) {
                $fp = @fopen('run-tests.log', 'a');
                if ($fp) {
                    fwrite($fp, "$result\n");
                    fclose($fp);
                }
            }

            if ($result == 'FAILED') {
                $failed[] = $t;
            }
            if ($result == 'PASSED') {
                $passed[] = $t;
            }
            if ($result == 'SKIPPED') {
                $skipped[] = $t;
            }

            $j++;
        }

        $total = date('i:s', time() - $start);
        if (isset($options['tapoutput'])) {
            $fp = @fopen('run-tests.log', 'w');
            if ($fp) {
                fwrite($fp, $tap, strlen($tap));
                fclose($fp);
                $this->ui->outputData('wrote TAP-format log to "' .realpath('run-tests.log') .
                    '"', $command);
            }
        } else {
            if (count($failed)) {
                $output = "TOTAL TIME: $total\n";
                $output .= count($passed) . " PASSED TESTS\n";
                $output .= count($skipped) . " SKIPPED TESTS\n";
                $output .= count($failed) . " FAILED TESTS:\n";
                foreach ($failed as $failure) {
                    $output .= $failure . "\n";
                }

                $mode = isset($options['realtimelog']) ? 'a' : 'w';
                $fp   = @fopen('run-tests.log', $mode);

                if ($fp) {
                    fwrite($fp, $output, strlen($output));
                    fclose($fp);
                    $this->ui->outputData('wrote log to "' . realpath('run-tests.log') . '"', $command);
                }
            } elseif (file_exists('run-tests.log') && !is_dir('run-tests.log')) {
                @unlink('run-tests.log');
            }
        }
        $this->ui->outputData('TOTAL TIME: ' . $total);
        $this->ui->outputData(count($passed) . ' PASSED TESTS', $command);
        $this->ui->outputData(count($skipped) . ' SKIPPED TESTS', $command);
        if (count($failed)) {
            $this->ui->outputData(count($failed) . ' FAILED TESTS:', $command);
            foreach ($failed as $failure) {
                $this->ui->outputData($failure, $command);
            }
        }

        return true;
    }
}