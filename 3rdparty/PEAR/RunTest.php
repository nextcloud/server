<?php
/**
 * PEAR_RunTest
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: RunTest.php 313024 2011-07-06 19:51:24Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.3.3
 */

/**
 * for error handling
 */
require_once 'PEAR.php';
require_once 'PEAR/Config.php';

define('DETAILED', 1);
putenv("PHP_PEAR_RUNTESTS=1");

/**
 * Simplified version of PHP's test suite
 *
 * Try it with:
 *
 * $ php -r 'include "../PEAR/RunTest.php"; $t=new PEAR_RunTest; $o=$t->run("./pear_system.phpt");print_r($o);'
 *
 *
 * @category   pear
 * @package    PEAR
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.3.3
 */
class PEAR_RunTest
{
    var $_headers = array();
    var $_logger;
    var $_options;
    var $_php;
    var $tests_count;
    var $xdebug_loaded;
    /**
     * Saved value of php executable, used to reset $_php when we
     * have a test that uses cgi
     *
     * @var unknown_type
     */
    var $_savephp;
    var $ini_overwrites = array(
        'output_handler=',
        'open_basedir=',
        'safe_mode=0',
        'disable_functions=',
        'output_buffering=Off',
        'display_errors=1',
        'log_errors=0',
        'html_errors=0',
        'track_errors=1',
        'report_memleaks=0',
        'report_zend_debug=0',
        'docref_root=',
        'docref_ext=.html',
        'error_prepend_string=',
        'error_append_string=',
        'auto_prepend_file=',
        'auto_append_file=',
        'magic_quotes_runtime=0',
        'xdebug.default_enable=0',
        'allow_url_fopen=1',
    );

    /**
     * An object that supports the PEAR_Common->log() signature, or null
     * @param PEAR_Common|null
     */
    function PEAR_RunTest($logger = null, $options = array())
    {
        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 0);
        }
        if (!defined('E_STRICT')) {
            define('E_STRICT', 0);
        }
        $this->ini_overwrites[] = 'error_reporting=' . (E_ALL & ~(E_DEPRECATED | E_STRICT));
        if (is_null($logger)) {
            require_once 'PEAR/Common.php';
            $logger = new PEAR_Common;
        }
        $this->_logger  = $logger;
        $this->_options = $options;

        $conf = &PEAR_Config::singleton();
        $this->_php = $conf->get('php_bin');
    }

    /**
     * Taken from php-src/run-tests.php
     *
     * @param string $commandline command name
     * @param array $env
     * @param string $stdin standard input to pass to the command
     * @return unknown
     */
    function system_with_timeout($commandline, $env = null, $stdin = null)
    {
        $data = '';
        if (version_compare(phpversion(), '5.0.0', '<')) {
            $proc = proc_open($commandline, array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
                ), $pipes);
        } else {
            $proc = proc_open($commandline, array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
                ), $pipes, null, $env, array('suppress_errors' => true));
        }

        if (!$proc) {
            return false;
        }

        if (is_string($stdin)) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);

        while (true) {
            /* hide errors from interrupted syscalls */
            $r = $pipes;
            $e = $w = null;
            $n = @stream_select($r, $w, $e, 60);

            if ($n === 0) {
                /* timed out */
                $data .= "\n ** ERROR: process timed out **\n";
                proc_terminate($proc);
                return array(1234567890, $data);
            } else if ($n > 0) {
                $line = fread($pipes[1], 8192);
                if (strlen($line) == 0) {
                    /* EOF */
                    break;
                }
                $data .= $line;
            }
        }
        if (function_exists('proc_get_status')) {
            $stat = proc_get_status($proc);
            if ($stat['signaled']) {
                $data .= "\nTermsig=".$stat['stopsig'];
            }
        }
        $code = proc_close($proc);
        if (function_exists('proc_get_status')) {
            $code = $stat['exitcode'];
        }
        return array($code, $data);
    }

    /**
     * Turns a PHP INI string into an array
     *
     * Turns -d "include_path=/foo/bar" into this:
     * array(
     *   'include_path' => array(
     *          'operator' => '-d',
     *          'value'    => '/foo/bar',
     *   )
     * )
     * Works both with quotes and without
     *
     * @param string an PHP INI string, -d "include_path=/foo/bar"
     * @return array
     */
    function iniString2array($ini_string)
    {
        if (!$ini_string) {
            return array();
        }
        $split = preg_split('/[\s]|=/', $ini_string, -1, PREG_SPLIT_NO_EMPTY);
        $key   = $split[1][0] == '"'                     ? substr($split[1], 1)     : $split[1];
        $value = $split[2][strlen($split[2]) - 1] == '"' ? substr($split[2], 0, -1) : $split[2];
        // FIXME review if this is really the struct to go with
        $array = array($key => array('operator' => $split[0], 'value' => $value));
        return $array;
    }

    function settings2array($settings, $ini_settings)
    {
        foreach ($settings as $setting) {
            if (strpos($setting, '=') !== false) {
                $setting = explode('=', $setting, 2);
                $name  = trim(strtolower($setting[0]));
                $value = trim($setting[1]);
                $ini_settings[$name] = $value;
            }
        }
        return $ini_settings;
    }

    function settings2params($ini_settings)
    {
        $settings = '';
        foreach ($ini_settings as $name => $value) {
            if (is_array($value)) {
                $operator = $value['operator'];
                $value    = $value['value'];
            } else {
                $operator = '-d';
            }
            $value = addslashes($value);
            $settings .= " $operator \"$name=$value\"";
        }
        return $settings;
    }

    function _preparePhpBin($php, $file, $ini_settings)
    {
        $file = escapeshellarg($file);
        // This was fixed in php 5.3 and is not needed after that
        if (OS_WINDOWS && version_compare(PHP_VERSION, '5.3', '<')) {
            $cmd = '"'.escapeshellarg($php).' '.$ini_settings.' -f ' . $file .'"';
        } else {
            $cmd = $php . $ini_settings . ' -f ' . $file;
        }

        return $cmd;
    }

    function runPHPUnit($file, $ini_settings = '')
    {
        if (!file_exists($file) && file_exists(getcwd() . DIRECTORY_SEPARATOR . $file)) {
            $file = realpath(getcwd() . DIRECTORY_SEPARATOR . $file);
        } elseif (file_exists($file)) {
            $file = realpath($file);
        }

        $cmd = $this->_preparePhpBin($this->_php, $file, $ini_settings);
        if (isset($this->_logger)) {
            $this->_logger->log(2, 'Running command "' . $cmd . '"');
        }

        $savedir = getcwd(); // in case the test moves us around
        chdir(dirname($file));
        echo `$cmd`;
        chdir($savedir);
        return 'PASSED'; // we have no way of knowing this information so assume passing
    }

    /**
     * Runs an individual test case.
     *
     * @param string       The filename of the test
     * @param array|string INI settings to be applied to the test run
     * @param integer      Number what the current running test is of the
     *                     whole test suite being runned.
     *
     * @return string|object Returns PASSED, WARNED, FAILED depending on how the
     *                       test came out.
     *                       PEAR Error when the tester it self fails
     */
    function run($file, $ini_settings = array(), $test_number = 1)
    {
        if (isset($this->_savephp)) {
            $this->_php = $this->_savephp;
            unset($this->_savephp);
        }
        if (empty($this->_options['cgi'])) {
            // try to see if php-cgi is in the path
            $res = $this->system_with_timeout('php-cgi -v');
            if (false !== $res && !(is_array($res) && in_array($res[0], array(-1, 127)))) {
                $this->_options['cgi'] = 'php-cgi';
            }
        }
        if (1 < $len = strlen($this->tests_count)) {
            $test_number = str_pad($test_number, $len, ' ', STR_PAD_LEFT);
            $test_nr = "[$test_number/$this->tests_count] ";
        } else {
            $test_nr = '';
        }

        $file = realpath($file);
        $section_text = $this->_readFile($file);
        if (PEAR::isError($section_text)) {
            return $section_text;
        }

        if (isset($section_text['POST_RAW']) && isset($section_text['UPLOAD'])) {
            return PEAR::raiseError("Cannot contain both POST_RAW and UPLOAD in test file: $file");
        }

        $cwd = getcwd();

        $pass_options = '';
        if (!empty($this->_options['ini'])) {
            $pass_options = $this->_options['ini'];
        }

        if (is_string($ini_settings)) {
            $ini_settings = $this->iniString2array($ini_settings);
        }

        $ini_settings = $this->settings2array($this->ini_overwrites, $ini_settings);
        if ($section_text['INI']) {
            if (strpos($section_text['INI'], '{PWD}') !== false) {
                $section_text['INI'] = str_replace('{PWD}', dirname($file), $section_text['INI']);
            }
            $ini = preg_split( "/[\n\r]+/", $section_text['INI']);
            $ini_settings = $this->settings2array($ini, $ini_settings);
        }
        $ini_settings = $this->settings2params($ini_settings);
        $shortname = str_replace($cwd . DIRECTORY_SEPARATOR, '', $file);

        $tested = trim($section_text['TEST']);
        $tested.= !isset($this->_options['simple']) ? "[$shortname]" : ' ';

        if (!empty($section_text['POST']) || !empty($section_text['POST_RAW']) ||
              !empty($section_text['UPLOAD']) || !empty($section_text['GET']) ||
              !empty($section_text['COOKIE']) || !empty($section_text['EXPECTHEADERS'])) {
            if (empty($this->_options['cgi'])) {
                if (!isset($this->_options['quiet'])) {
                    $this->_logger->log(0, "SKIP $test_nr$tested (reason: --cgi option needed for this test, type 'pear help run-tests')");
                }
                if (isset($this->_options['tapoutput'])) {
                    return array('ok', ' # skip --cgi option needed for this test, "pear help run-tests" for info');
                }
                return 'SKIPPED';
            }
            $this->_savephp = $this->_php;
            $this->_php = $this->_options['cgi'];
        }

        $temp_dir = realpath(dirname($file));
        $main_file_name = basename($file, 'phpt');
        $diff_filename     = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'diff';
        $log_filename      = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'log';
        $exp_filename      = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'exp';
        $output_filename   = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'out';
        $memcheck_filename = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'mem';
        $temp_file         = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'php';
        $temp_skipif       = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'skip.php';
        $temp_clean        = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name.'clean.php';
        $tmp_post          = $temp_dir . DIRECTORY_SEPARATOR . uniqid('phpt.');

        // unlink old test results
        $this->_cleanupOldFiles($file);

        // Check if test should be skipped.
        $res  = $this->_runSkipIf($section_text, $temp_skipif, $tested, $ini_settings);
        if (count($res) != 2) {
            return $res;
        }
        $info = $res['info'];
        $warn = $res['warn'];

        // We've satisfied the preconditions - run the test!
        if (isset($this->_options['coverage']) && $this->xdebug_loaded) {
            $xdebug_file = $temp_dir . DIRECTORY_SEPARATOR . $main_file_name . 'xdebug';
            $text = "\n" . 'function coverage_shutdown() {' .
                    "\n" . '    $xdebug = var_export(xdebug_get_code_coverage(), true);';
            if (!function_exists('file_put_contents')) {
                $text .= "\n" . '    $fh = fopen(\'' . $xdebug_file . '\', "wb");' .
                        "\n" . '    if ($fh !== false) {' .
                        "\n" . '        fwrite($fh, $xdebug);' .
                        "\n" . '        fclose($fh);' .
                        "\n" . '    }';
            } else {
                $text .= "\n" . '    file_put_contents(\'' . $xdebug_file . '\', $xdebug);';
            }

            // Workaround for http://pear.php.net/bugs/bug.php?id=17292
            $lines             = explode("\n", $section_text['FILE']);
            $numLines          = count($lines);
            $namespace         = '';
            $coverage_shutdown = 'coverage_shutdown';

            if (
                substr($lines[0], 0, 2) == '<?' ||
                substr($lines[0], 0, 5) == '<?php'
            ) {
                unset($lines[0]);
            }


            for ($i = 0; $i < $numLines; $i++) {
                if (isset($lines[$i]) && substr($lines[$i], 0, 9) == 'namespace') {
                    $namespace         = substr($lines[$i], 10, -1);
                    $coverage_shutdown = $namespace . '\\coverage_shutdown';
                    $namespace         = "namespace " . $namespace . ";\n";

                    unset($lines[$i]);
                    break;
                }
            }

            $text .= "\n    xdebug_stop_code_coverage();" .
                "\n" . '} // end coverage_shutdown()' .
                "\n\n" . 'register_shutdown_function("' . $coverage_shutdown . '");';
            $text .= "\n" . 'xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);' . "\n";

            $this->save_text($temp_file, "<?php\n" . $namespace . $text  . "\n" . implode("\n", $lines));
        } else {
            $this->save_text($temp_file, $section_text['FILE']);
        }

        $args = $section_text['ARGS'] ? ' -- '.$section_text['ARGS'] : '';
        $cmd = $this->_preparePhpBin($this->_php, $temp_file, $ini_settings);
        $cmd.= "$args 2>&1";
        if (isset($this->_logger)) {
            $this->_logger->log(2, 'Running command "' . $cmd . '"');
        }

        // Reset environment from any previous test.
        $env = $this->_resetEnv($section_text, $temp_file);

        $section_text = $this->_processUpload($section_text, $file);
        if (PEAR::isError($section_text)) {
            return $section_text;
        }

        if (array_key_exists('POST_RAW', $section_text) && !empty($section_text['POST_RAW'])) {
            $post = trim($section_text['POST_RAW']);
            $raw_lines = explode("\n", $post);

            $request = '';
            $started = false;
            foreach ($raw_lines as $i => $line) {
                if (empty($env['CONTENT_TYPE']) &&
                    preg_match('/^Content-Type:(.*)/i', $line, $res)) {
                    $env['CONTENT_TYPE'] = trim(str_replace("\r", '', $res[1]));
                    continue;
                }
                if ($started) {
                    $request .= "\n";
                }
                $started = true;
                $request .= $line;
            }

            $env['CONTENT_LENGTH'] = strlen($request);
            $env['REQUEST_METHOD'] = 'POST';

            $this->save_text($tmp_post, $request);
            $cmd = "$this->_php$pass_options$ini_settings \"$temp_file\" 2>&1 < $tmp_post";
        } elseif (array_key_exists('POST', $section_text) && !empty($section_text['POST'])) {
            $post = trim($section_text['POST']);
            $this->save_text($tmp_post, $post);
            $content_length = strlen($post);

            $env['REQUEST_METHOD'] = 'POST';
            $env['CONTENT_TYPE']   = 'application/x-www-form-urlencoded';
            $env['CONTENT_LENGTH'] = $content_length;

            $cmd = "$this->_php$pass_options$ini_settings \"$temp_file\" 2>&1 < $tmp_post";
        } else {
            $env['REQUEST_METHOD'] = 'GET';
            $env['CONTENT_TYPE']   = '';
            $env['CONTENT_LENGTH'] = '';
        }

        if (OS_WINDOWS && isset($section_text['RETURNS'])) {
            ob_start();
            system($cmd, $return_value);
            $out = ob_get_contents();
            ob_end_clean();
            $section_text['RETURNS'] = (int) trim($section_text['RETURNS']);
            $returnfail = ($return_value != $section_text['RETURNS']);
        } else {
            $returnfail = false;
            $stdin = isset($section_text['STDIN']) ? $section_text['STDIN'] : null;
            $out = $this->system_with_timeout($cmd, $env, $stdin);
            $return_value = $out[0];
            $out = $out[1];
        }

        $output = preg_replace('/\r\n/', "\n", trim($out));

        if (isset($tmp_post) && realpath($tmp_post) && file_exists($tmp_post)) {
            @unlink(realpath($tmp_post));
        }
        chdir($cwd); // in case the test moves us around

        $this->_testCleanup($section_text, $temp_clean);

        /* when using CGI, strip the headers from the output */
        $output = $this->_stripHeadersCGI($output);

        if (isset($section_text['EXPECTHEADERS'])) {
            $testheaders = $this->_processHeaders($section_text['EXPECTHEADERS']);
            $missing = array_diff_assoc($testheaders, $this->_headers);
            $changed = '';
            foreach ($missing as $header => $value) {
                if (isset($this->_headers[$header])) {
                    $changed .= "-$header: $value\n+$header: ";
                    $changed .= $this->_headers[$header];
                } else {
                    $changed .= "-$header: $value\n";
                }
            }
            if ($missing) {
                // tack on failed headers to output:
                $output .= "\n====EXPECTHEADERS FAILURE====:\n$changed";
            }
        }
        // Does the output match what is expected?
        do {
            if (isset($section_text['EXPECTF']) || isset($section_text['EXPECTREGEX'])) {
                if (isset($section_text['EXPECTF'])) {
                    $wanted = trim($section_text['EXPECTF']);
                } else {
                    $wanted = trim($section_text['EXPECTREGEX']);
                }
                $wanted_re = preg_replace('/\r\n/', "\n", $wanted);
                if (isset($section_text['EXPECTF'])) {
                    $wanted_re = preg_quote($wanted_re, '/');
                    // Stick to basics
                    $wanted_re = str_replace("%s", ".+?", $wanted_re); //not greedy
                    $wanted_re = str_replace("%i", "[+\-]?[0-9]+", $wanted_re);
                    $wanted_re = str_replace("%d", "[0-9]+", $wanted_re);
                    $wanted_re = str_replace("%x", "[0-9a-fA-F]+", $wanted_re);
                    $wanted_re = str_replace("%f", "[+\-]?\.?[0-9]+\.?[0-9]*(E-?[0-9]+)?", $wanted_re);
                    $wanted_re = str_replace("%c", ".", $wanted_re);
                    // %f allows two points "-.0.0" but that is the best *simple* expression
                }

    /* DEBUG YOUR REGEX HERE
            var_dump($wanted_re);
            print(str_repeat('=', 80) . "\n");
            var_dump($output);
    */
                if (!$returnfail && preg_match("/^$wanted_re\$/s", $output)) {
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                    if (array_key_exists('FAIL', $section_text)) {
                        break;
                    }
                    if (!isset($this->_options['quiet'])) {
                        $this->_logger->log(0, "PASS $test_nr$tested$info");
                    }
                    if (isset($this->_options['tapoutput'])) {
                        return array('ok', ' - ' . $tested);
                    }
                    return 'PASSED';
                }
            } else {
                if (isset($section_text['EXPECTFILE'])) {
                    $f = $temp_dir . '/' . trim($section_text['EXPECTFILE']);
                    if (!($fp = @fopen($f, 'rb'))) {
                        return PEAR::raiseError('--EXPECTFILE-- section file ' .
                            $f . ' not found');
                    }
                    fclose($fp);
                    $section_text['EXPECT'] = file_get_contents($f);
                }

                if (isset($section_text['EXPECT'])) {
                    $wanted = preg_replace('/\r\n/', "\n", trim($section_text['EXPECT']));
                } else {
                    $wanted = '';
                }

                // compare and leave on success
                if (!$returnfail && 0 == strcmp($output, $wanted)) {
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                    if (array_key_exists('FAIL', $section_text)) {
                        break;
                    }
                    if (!isset($this->_options['quiet'])) {
                        $this->_logger->log(0, "PASS $test_nr$tested$info");
                    }
                    if (isset($this->_options['tapoutput'])) {
                        return array('ok', ' - ' . $tested);
                    }
                    return 'PASSED';
                }
            }
        } while (false);

        if (array_key_exists('FAIL', $section_text)) {
            // we expect a particular failure
            // this is only used for testing PEAR_RunTest
            $expectf  = isset($section_text['EXPECTF']) ? $wanted_re : null;
            $faildiff = $this->generate_diff($wanted, $output, null, $expectf);
            $faildiff = preg_replace('/\r/', '', $faildiff);
            $wanted   = preg_replace('/\r/', '', trim($section_text['FAIL']));
            if ($faildiff == $wanted) {
                if (!isset($this->_options['quiet'])) {
                    $this->_logger->log(0, "PASS $test_nr$tested$info");
                }
                if (isset($this->_options['tapoutput'])) {
                    return array('ok', ' - ' . $tested);
                }
                return 'PASSED';
            }
            unset($section_text['EXPECTF']);
            $output = $faildiff;
            if (isset($section_text['RETURNS'])) {
                return PEAR::raiseError('Cannot have both RETURNS and FAIL in the same test: ' .
                    $file);
            }
        }

        // Test failed so we need to report details.
        $txt = $warn ? 'WARN ' : 'FAIL ';
        $this->_logger->log(0, $txt . $test_nr . $tested . $info);

        // write .exp
        $res = $this->_writeLog($exp_filename, $wanted);
        if (PEAR::isError($res)) {
            return $res;
        }

        // write .out
        $res = $this->_writeLog($output_filename, $output);
        if (PEAR::isError($res)) {
            return $res;
        }

        // write .diff
        $returns = isset($section_text['RETURNS']) ?
                        array(trim($section_text['RETURNS']), $return_value) : null;
        $expectf = isset($section_text['EXPECTF']) ? $wanted_re : null;
        $data = $this->generate_diff($wanted, $output, $returns, $expectf);
        $res  = $this->_writeLog($diff_filename, $data);
        if (PEAR::isError($res)) {
            return $res;
        }

        // write .log
        $data = "
---- EXPECTED OUTPUT
$wanted
---- ACTUAL OUTPUT
$output
---- FAILED
";

        if ($returnfail) {
            $data .= "
---- EXPECTED RETURN
$section_text[RETURNS]
---- ACTUAL RETURN
$return_value
";
        }

        $res = $this->_writeLog($log_filename, $data);
        if (PEAR::isError($res)) {
            return $res;
        }

        if (isset($this->_options['tapoutput'])) {
            $wanted = explode("\n", $wanted);
            $wanted = "# Expected output:\n#\n#" . implode("\n#", $wanted);
            $output = explode("\n", $output);
            $output = "#\n#\n# Actual output:\n#\n#" . implode("\n#", $output);
            return array($wanted . $output . 'not ok', ' - ' . $tested);
        }
        return $warn ? 'WARNED' : 'FAILED';
    }

    function generate_diff($wanted, $output, $rvalue, $wanted_re)
    {
        $w  = explode("\n", $wanted);
        $o  = explode("\n", $output);
        $wr = explode("\n", $wanted_re);
        $w1 = array_diff_assoc($w, $o);
        $o1 = array_diff_assoc($o, $w);
        $o2 = $w2 = array();
        foreach ($w1 as $idx => $val) {
            if (!$wanted_re || !isset($wr[$idx]) || !isset($o1[$idx]) ||
                  !preg_match('/^' . $wr[$idx] . '\\z/', $o1[$idx])) {
                $w2[sprintf("%03d<", $idx)] = sprintf("%03d- ", $idx + 1) . $val;
            }
        }
        foreach ($o1 as $idx => $val) {
            if (!$wanted_re || !isset($wr[$idx]) ||
                  !preg_match('/^' . $wr[$idx] . '\\z/', $val)) {
                $o2[sprintf("%03d>", $idx)] = sprintf("%03d+ ", $idx + 1) . $val;
            }
        }
        $diff = array_merge($w2, $o2);
        ksort($diff);
        $extra = $rvalue ? "##EXPECTED: $rvalue[0]\r\n##RETURNED: $rvalue[1]" : '';
        return implode("\r\n", $diff) . $extra;
    }

    //  Write the given text to a temporary file, and return the filename.
    function save_text($filename, $text)
    {
        if (!$fp = fopen($filename, 'w')) {
            return PEAR::raiseError("Cannot open file '" . $filename . "' (save_text)");
        }
        fwrite($fp, $text);
        fclose($fp);
    if (1 < DETAILED) echo "
FILE $filename {{{
$text
}}}
";
    }

    function _cleanupOldFiles($file)
    {
        $temp_dir = realpath(dirname($file));
        $mainFileName = basename($file, 'phpt');
        $diff_filename     = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'diff';
        $log_filename      = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'log';
        $exp_filename      = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'exp';
        $output_filename   = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'out';
        $memcheck_filename = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'mem';
        $temp_file         = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'php';
        $temp_skipif       = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'skip.php';
        $temp_clean        = $temp_dir . DIRECTORY_SEPARATOR . $mainFileName.'clean.php';
        $tmp_post          = $temp_dir . DIRECTORY_SEPARATOR . uniqid('phpt.');

        // unlink old test results
        @unlink($diff_filename);
        @unlink($log_filename);
        @unlink($exp_filename);
        @unlink($output_filename);
        @unlink($memcheck_filename);
        @unlink($temp_file);
        @unlink($temp_skipif);
        @unlink($tmp_post);
        @unlink($temp_clean);
    }

    function _runSkipIf($section_text, $temp_skipif, $tested, $ini_settings)
    {
        $info = '';
        $warn = false;
        if (array_key_exists('SKIPIF', $section_text) && trim($section_text['SKIPIF'])) {
            $this->save_text($temp_skipif, $section_text['SKIPIF']);
            $output = $this->system_with_timeout("$this->_php$ini_settings -f \"$temp_skipif\"");
            $output = $output[1];
            $loutput = ltrim($output);
            unlink($temp_skipif);
            if (!strncasecmp('skip', $loutput, 4)) {
                $skipreason = "SKIP $tested";
                if (preg_match('/^\s*skip\s*(.+)\s*/i', $output, $m)) {
                    $skipreason .= '(reason: ' . $m[1] . ')';
                }
                if (!isset($this->_options['quiet'])) {
                    $this->_logger->log(0, $skipreason);
                }
                if (isset($this->_options['tapoutput'])) {
                    return array('ok', ' # skip ' . $reason);
                }
                return 'SKIPPED';
            }

            if (!strncasecmp('info', $loutput, 4)
                && preg_match('/^\s*info\s*(.+)\s*/i', $output, $m)) {
                $info = " (info: $m[1])";
            }

            if (!strncasecmp('warn', $loutput, 4)
                && preg_match('/^\s*warn\s*(.+)\s*/i', $output, $m)) {
                $warn = true; /* only if there is a reason */
                $info = " (warn: $m[1])";
            }
        }

        return array('warn' => $warn, 'info' => $info);
    }

    function _stripHeadersCGI($output)
    {
        $this->headers = array();
        if (!empty($this->_options['cgi']) &&
              $this->_php == $this->_options['cgi'] &&
              preg_match("/^(.*?)(?:\n\n(.*)|\\z)/s", $output, $match)) {
            $output = isset($match[2]) ? trim($match[2]) : '';
            $this->_headers = $this->_processHeaders($match[1]);
        }

        return $output;
    }

    /**
     * Return an array that can be used with array_diff() to compare headers
     *
     * @param string $text
     */
    function _processHeaders($text)
    {
        $headers = array();
        $rh = preg_split("/[\n\r]+/", $text);
        foreach ($rh as $line) {
            if (strpos($line, ':')!== false) {
                $line = explode(':', $line, 2);
                $headers[trim($line[0])] = trim($line[1]);
            }
        }
        return $headers;
    }

    function _readFile($file)
    {
        // Load the sections of the test file.
        $section_text = array(
            'TEST'   => '(unnamed test)',
            'SKIPIF' => '',
            'GET'    => '',
            'COOKIE' => '',
            'POST'   => '',
            'ARGS'   => '',
            'INI'    => '',
            'CLEAN'  => '',
        );

        if (!is_file($file) || !$fp = fopen($file, "r")) {
            return PEAR::raiseError("Cannot open test file: $file");
        }

        $section = '';
        while (!feof($fp)) {
            $line = fgets($fp);

            // Match the beginning of a section.
            if (preg_match('/^--([_A-Z]+)--/', $line, $r)) {
                $section = $r[1];
                $section_text[$section] = '';
                continue;
            } elseif (empty($section)) {
                fclose($fp);
                return PEAR::raiseError("Invalid sections formats in test file: $file");
            }

            // Add to the section text.
            $section_text[$section] .= $line;
        }
        fclose($fp);

        return $section_text;
    }

    function _writeLog($logname, $data)
    {
        if (!$log = fopen($logname, 'w')) {
            return PEAR::raiseError("Cannot create test log - $logname");
        }
        fwrite($log, $data);
        fclose($log);
    }

    function _resetEnv($section_text, $temp_file)
    {
        $env = $_ENV;
        $env['REDIRECT_STATUS'] = '';
        $env['QUERY_STRING']    = '';
        $env['PATH_TRANSLATED'] = '';
        $env['SCRIPT_FILENAME'] = '';
        $env['REQUEST_METHOD']  = '';
        $env['CONTENT_TYPE']    = '';
        $env['CONTENT_LENGTH']  = '';
        if (!empty($section_text['ENV'])) {
            if (strpos($section_text['ENV'], '{PWD}') !== false) {
                $section_text['ENV'] = str_replace('{PWD}', dirname($temp_file), $section_text['ENV']);
            }
            foreach (explode("\n", trim($section_text['ENV'])) as $e) {
                $e = explode('=', trim($e), 2);
                if (!empty($e[0]) && isset($e[1])) {
                    $env[$e[0]] = $e[1];
                }
            }
        }
        if (array_key_exists('GET', $section_text)) {
            $env['QUERY_STRING'] = trim($section_text['GET']);
        } else {
            $env['QUERY_STRING'] = '';
        }
        if (array_key_exists('COOKIE', $section_text)) {
            $env['HTTP_COOKIE'] = trim($section_text['COOKIE']);
        } else {
            $env['HTTP_COOKIE'] = '';
        }
        $env['REDIRECT_STATUS'] = '1';
        $env['PATH_TRANSLATED'] = $temp_file;
        $env['SCRIPT_FILENAME'] = $temp_file;

        return $env;
    }

    function _processUpload($section_text, $file)
    {
        if (array_key_exists('UPLOAD', $section_text) && !empty($section_text['UPLOAD'])) {
            $upload_files = trim($section_text['UPLOAD']);
            $upload_files = explode("\n", $upload_files);

            $request = "Content-Type: multipart/form-data; boundary=---------------------------20896060251896012921717172737\n" .
                       "-----------------------------20896060251896012921717172737\n";
            foreach ($upload_files as $fileinfo) {
                $fileinfo = explode('=', $fileinfo);
                if (count($fileinfo) != 2) {
                    return PEAR::raiseError("Invalid UPLOAD section in test file: $file");
                }
                if (!realpath(dirname($file) . '/' . $fileinfo[1])) {
                    return PEAR::raiseError("File for upload does not exist: $fileinfo[1] " .
                        "in test file: $file");
                }
                $file_contents = file_get_contents(dirname($file) . '/' . $fileinfo[1]);
                $fileinfo[1] = basename($fileinfo[1]);
                $request .= "Content-Disposition: form-data; name=\"$fileinfo[0]\"; filename=\"$fileinfo[1]\"\n";
                $request .= "Content-Type: text/plain\n\n";
                $request .= $file_contents . "\n" .
                    "-----------------------------20896060251896012921717172737\n";
            }

            if (array_key_exists('POST', $section_text) && !empty($section_text['POST'])) {
                // encode POST raw
                $post = trim($section_text['POST']);
                $post = explode('&', $post);
                foreach ($post as $i => $post_info) {
                    $post_info = explode('=', $post_info);
                    if (count($post_info) != 2) {
                        return PEAR::raiseError("Invalid POST data in test file: $file");
                    }
                    $post_info[0] = rawurldecode($post_info[0]);
                    $post_info[1] = rawurldecode($post_info[1]);
                    $post[$i] = $post_info;
                }
                foreach ($post as $post_info) {
                    $request .= "Content-Disposition: form-data; name=\"$post_info[0]\"\n\n";
                    $request .= $post_info[1] . "\n" .
                        "-----------------------------20896060251896012921717172737\n";
                }
                unset($section_text['POST']);
            }
            $section_text['POST_RAW'] = $request;
        }

        return $section_text;
    }

    function _testCleanup($section_text, $temp_clean)
    {
        if ($section_text['CLEAN']) {
            // perform test cleanup
            $this->save_text($temp_clean, $section_text['CLEAN']);
            $output = $this->system_with_timeout("$this->_php $temp_clean  2>&1");
            if (strlen($output[1])) {
                echo "BORKED --CLEAN-- section! output:\n", $output[1];
            }
            if (file_exists($temp_clean)) {
                unlink($temp_clean);
            }
        }
    }
}
