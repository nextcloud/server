<?php
/**
 * PEAR_Frontend_CLI
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: CLI.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */
/**
 * base class
 */
require_once 'PEAR/Frontend.php';

/**
 * Command-line Frontend for the PEAR Installer
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
class PEAR_Frontend_CLI extends PEAR_Frontend
{
    /**
     * What type of user interface this frontend is for.
     * @var string
     * @access public
     */
    var $type = 'CLI';
    var $lp = ''; // line prefix

    var $params = array();
    var $term = array(
        'bold'   => '',
        'normal' => '',
    );

    function PEAR_Frontend_CLI()
    {
        parent::PEAR();
        $term = getenv('TERM'); //(cox) $_ENV is empty for me in 4.1.1
        if (function_exists('posix_isatty') && !posix_isatty(1)) {
            // output is being redirected to a file or through a pipe
        } elseif ($term) {
            if (preg_match('/^(xterm|vt220|linux)/', $term)) {
                $this->term['bold']   = sprintf("%c%c%c%c", 27, 91, 49, 109);
                $this->term['normal'] = sprintf("%c%c%c", 27, 91, 109);
            } elseif (preg_match('/^vt100/', $term)) {
                $this->term['bold']   = sprintf("%c%c%c%c%c%c", 27, 91, 49, 109, 0, 0);
                $this->term['normal'] = sprintf("%c%c%c%c%c", 27, 91, 109, 0, 0);
            }
        } elseif (OS_WINDOWS) {
            // XXX add ANSI codes here
        }
    }

    /**
     * @param object PEAR_Error object
     */
    function displayError($e)
    {
        return $this->_displayLine($e->getMessage());
    }

    /**
     * @param object PEAR_Error object
     */
    function displayFatalError($eobj)
    {
        $this->displayError($eobj);
        if (class_exists('PEAR_Config')) {
            $config = &PEAR_Config::singleton();
            if ($config->get('verbose') > 5) {
                if (function_exists('debug_print_backtrace')) {
                    debug_print_backtrace();
                    exit(1);
                }

                $raised = false;
                foreach (debug_backtrace() as $i => $frame) {
                    if (!$raised) {
                        if (isset($frame['class'])
                            && strtolower($frame['class']) == 'pear'
                            && strtolower($frame['function']) == 'raiseerror'
                        ) {
                            $raised = true;
                        } else {
                            continue;
                        }
                    }

                    $frame['class']    = !isset($frame['class'])    ? '' : $frame['class'];
                    $frame['type']     = !isset($frame['type'])     ? '' : $frame['type'];
                    $frame['function'] = !isset($frame['function']) ? '' : $frame['function'];
                    $frame['line']     = !isset($frame['line'])     ? '' : $frame['line'];
                    $this->_displayLine("#$i: $frame[class]$frame[type]$frame[function] $frame[line]");
                }
            }
        }

        exit(1);
    }

    /**
     * Instruct the runInstallScript method to skip a paramgroup that matches the
     * id value passed in.
     *
     * This method is useful for dynamically configuring which sections of a post-install script
     * will be run based on the user's setup, which is very useful for making flexible
     * post-install scripts without losing the cross-Frontend ability to retrieve user input
     * @param string
     */
    function skipParamgroup($id)
    {
        $this->_skipSections[$id] = true;
    }

    function runPostinstallScripts(&$scripts)
    {
        foreach ($scripts as $i => $script) {
            $this->runInstallScript($scripts[$i]->_params, $scripts[$i]->_obj);
        }
    }

    /**
     * @param array $xml contents of postinstallscript tag
     * @param object $script post-installation script
     * @param string install|upgrade
     */
    function runInstallScript($xml, &$script)
    {
        $this->_skipSections = array();
        if (!is_array($xml) || !isset($xml['paramgroup'])) {
            $script->run(array(), '_default');
            return;
        }

        $completedPhases = array();
        if (!isset($xml['paramgroup'][0])) {
            $xml['paramgroup'] = array($xml['paramgroup']);
        }

        foreach ($xml['paramgroup'] as $group) {
            if (isset($this->_skipSections[$group['id']])) {
                // the post-install script chose to skip this section dynamically
                continue;
            }

            if (isset($group['name'])) {
                $paramname = explode('::', $group['name']);
                if ($lastgroup['id'] != $paramname[0]) {
                    continue;
                }

                $group['name'] = $paramname[1];
                if (!isset($answers)) {
                    return;
                }

                if (isset($answers[$group['name']])) {
                    switch ($group['conditiontype']) {
                        case '=' :
                            if ($answers[$group['name']] != $group['value']) {
                                continue 2;
                            }
                        break;
                        case '!=' :
                            if ($answers[$group['name']] == $group['value']) {
                                continue 2;
                            }
                        break;
                        case 'preg_match' :
                            if (!@preg_match('/' . $group['value'] . '/',
                                  $answers[$group['name']])) {
                                continue 2;
                            }
                        break;
                        default :
                        return;
                    }
                }
            }

            $lastgroup = $group;
            if (isset($group['instructions'])) {
                $this->_display($group['instructions']);
            }

            if (!isset($group['param'][0])) {
                $group['param'] = array($group['param']);
            }

            if (isset($group['param'])) {
                if (method_exists($script, 'postProcessPrompts')) {
                    $prompts = $script->postProcessPrompts($group['param'], $group['id']);
                    if (!is_array($prompts) || count($prompts) != count($group['param'])) {
                        $this->outputData('postinstall', 'Error: post-install script did not ' .
                            'return proper post-processed prompts');
                        $prompts = $group['param'];
                    } else {
                        foreach ($prompts as $i => $var) {
                            if (!is_array($var) || !isset($var['prompt']) ||
                                  !isset($var['name']) ||
                                  ($var['name'] != $group['param'][$i]['name']) ||
                                  ($var['type'] != $group['param'][$i]['type'])
                            ) {
                                $this->outputData('postinstall', 'Error: post-install script ' .
                                    'modified the variables or prompts, severe security risk. ' .
                                    'Will instead use the defaults from the package.xml');
                                $prompts = $group['param'];
                            }
                        }
                    }

                    $answers = $this->confirmDialog($prompts);
                } else {
                    $answers = $this->confirmDialog($group['param']);
                }
            }

            if ((isset($answers) && $answers) || !isset($group['param'])) {
                if (!isset($answers)) {
                    $answers = array();
                }

                array_unshift($completedPhases, $group['id']);
                if (!$script->run($answers, $group['id'])) {
                    $script->run($completedPhases, '_undoOnError');
                    return;
                }
            } else {
                $script->run($completedPhases, '_undoOnError');
                return;
            }
        }
    }

    /**
     * Ask for user input, confirm the answers and continue until the user is satisfied
     * @param array an array of arrays, format array('name' => 'paramname', 'prompt' =>
     *              'text to display', 'type' => 'string'[, default => 'default value'])
     * @return array
     */
    function confirmDialog($params)
    {
        $answers = $prompts = $types = array();
        foreach ($params as $param) {
            $prompts[$param['name']] = $param['prompt'];
            $types[$param['name']]   = $param['type'];
            $answers[$param['name']] = isset($param['default']) ? $param['default'] : '';
        }

        $tried = false;
        do {
            if ($tried) {
                $i = 1;
                foreach ($answers as $var => $value) {
                    if (!strlen($value)) {
                        echo $this->bold("* Enter an answer for #" . $i . ": ({$prompts[$var]})\n");
                    }
                    $i++;
                }
            }

            $answers = $this->userDialog('', $prompts, $types, $answers);
            $tried   = true;
        } while (is_array($answers) && count(array_filter($answers)) != count($prompts));

        return $answers;
    }

    function userDialog($command, $prompts, $types = array(), $defaults = array(), $screensize = 20)
    {
        if (!is_array($prompts)) {
            return array();
        }

        $testprompts = array_keys($prompts);
        $result      = $defaults;

        reset($prompts);
        if (count($prompts) === 1) {
            foreach ($prompts as $key => $prompt) {
                $type    = $types[$key];
                $default = @$defaults[$key];
                print "$prompt ";
                if ($default) {
                    print "[$default] ";
                }
                print ": ";

                $line         = fgets(STDIN, 2048);
                $result[$key] =  ($default && trim($line) == '') ? $default : trim($line);
            }

            return $result;
        }

        $first_run = true;
        while (true) {
            $descLength = max(array_map('strlen', $prompts));
            $descFormat = "%-{$descLength}s";
            $last       = count($prompts);

            $i = 0;
            foreach ($prompts as $n => $var) {
                $res = isset($result[$n]) ? $result[$n] : null;
                printf("%2d. $descFormat : %s\n", ++$i, $prompts[$n], $res);
            }
            print "\n1-$last, 'all', 'abort', or Enter to continue: ";

            $tmp = trim(fgets(STDIN, 1024));
            if (empty($tmp)) {
                break;
            }

            if ($tmp == 'abort') {
                return false;
            }

            if (isset($testprompts[(int)$tmp - 1])) {
                $var     = $testprompts[(int)$tmp - 1];
                $desc    = $prompts[$var];
                $current = @$result[$var];
                print "$desc [$current] : ";
                $tmp = trim(fgets(STDIN, 1024));
                if ($tmp !== '') {
                    $result[$var] = $tmp;
                }
            } elseif ($tmp == 'all') {
                foreach ($prompts as $var => $desc) {
                    $current = $result[$var];
                    print "$desc [$current] : ";
                    $tmp = trim(fgets(STDIN, 1024));
                    if (trim($tmp) !== '') {
                        $result[$var] = trim($tmp);
                    }
                }
            }

            $first_run = false;
        }

        return $result;
    }

    function userConfirm($prompt, $default = 'yes')
    {
        trigger_error("PEAR_Frontend_CLI::userConfirm not yet converted", E_USER_ERROR);
        static $positives = array('y', 'yes', 'on', '1');
        static $negatives = array('n', 'no', 'off', '0');
        print "$this->lp$prompt [$default] : ";
        $fp = fopen("php://stdin", "r");
        $line = fgets($fp, 2048);
        fclose($fp);
        $answer = strtolower(trim($line));
        if (empty($answer)) {
            $answer = $default;
        }
        if (in_array($answer, $positives)) {
            return true;
        }
        if (in_array($answer, $negatives)) {
            return false;
        }
        if (in_array($default, $positives)) {
            return true;
        }
        return false;
    }

    function outputData($data, $command = '_default')
    {
        switch ($command) {
            case 'channel-info':
                foreach ($data as $type => $section) {
                    if ($type == 'main') {
                        $section['data'] = array_values($section['data']);
                    }

                    $this->outputData($section);
                }
                break;
            case 'install':
            case 'upgrade':
            case 'upgrade-all':
                if (is_array($data) && isset($data['release_warnings'])) {
                    $this->_displayLine('');
                    $this->_startTable(array(
                        'border' => false,
                        'caption' => 'Release Warnings'
                    ));
                    $this->_tableRow(array($data['release_warnings']), null, array(1 => array('wrap' => 55)));
                    $this->_endTable();
                    $this->_displayLine('');
                }

                $this->_displayLine(is_array($data) ? $data['data'] : $data);
                break;
            case 'search':
                $this->_startTable($data);
                if (isset($data['headline']) && is_array($data['headline'])) {
                    $this->_tableRow($data['headline'], array('bold' => true), array(1 => array('wrap' => 55)));
                }

                $packages = array();
                foreach($data['data'] as $category) {
                    foreach($category as $name => $pkg) {
                        $packages[$pkg[0]] = $pkg;
                    }
                }

                $p = array_keys($packages);
                natcasesort($p);
                foreach ($p as $name) {
                    $this->_tableRow($packages[$name], null, array(1 => array('wrap' => 55)));
                }

                $this->_endTable();
                break;
            case 'list-all':
                if (!isset($data['data'])) {
                      $this->_displayLine('No packages in channel');
                      break;
                }

                $this->_startTable($data);
                if (isset($data['headline']) && is_array($data['headline'])) {
                    $this->_tableRow($data['headline'], array('bold' => true), array(1 => array('wrap' => 55)));
                }

                $packages = array();
                foreach($data['data'] as $category) {
                    foreach($category as $name => $pkg) {
                        $packages[$pkg[0]] = $pkg;
                    }
                }

                $p = array_keys($packages);
                natcasesort($p);
                foreach ($p as $name) {
                    $pkg = $packages[$name];
                    unset($pkg[4], $pkg[5]);
                    $this->_tableRow($pkg, null, array(1 => array('wrap' => 55)));
                }

                $this->_endTable();
                break;
            case 'config-show':
                $data['border'] = false;
                $opts = array(
                    0 => array('wrap' => 30),
                    1 => array('wrap' => 20),
                    2 => array('wrap' => 35)
                );

                $this->_startTable($data);
                if (isset($data['headline']) && is_array($data['headline'])) {
                    $this->_tableRow($data['headline'], array('bold' => true), $opts);
                }

                foreach ($data['data'] as $group) {
                    foreach ($group as $value) {
                        if ($value[2] == '') {
                            $value[2] = "<not set>";
                        }

                        $this->_tableRow($value, null, $opts);
                    }
                }

                $this->_endTable();
                break;
            case 'remote-info':
                $d = $data;
                $data = array(
                    'caption' => 'Package details:',
                    'border'  => false,
                    'data'    => array(
                        array("Latest",      $data['stable']),
                        array("Installed",   $data['installed']),
                        array("Package",     $data['name']),
                        array("License",     $data['license']),
                        array("Category",    $data['category']),
                        array("Summary",     $data['summary']),
                        array("Description", $data['description']),
                    ),
                );

                if (isset($d['deprecated']) && $d['deprecated']) {
                    $conf = &PEAR_Config::singleton();
                    $reg = $conf->getRegistry();
                    $name = $reg->parsedPackageNameToString($d['deprecated'], true);
                    $data['data'][] = array('Deprecated! use', $name);
                }
            default: {
                if (is_array($data)) {
                    $this->_startTable($data);
                    $count = count($data['data'][0]);
                    if ($count == 2) {
                        $opts = array(0 => array('wrap' => 25),
                                      1 => array('wrap' => 48)
                        );
                    } elseif ($count == 3) {
                        $opts = array(0 => array('wrap' => 30),
                                      1 => array('wrap' => 20),
                                      2 => array('wrap' => 35)
                        );
                    } else {
                        $opts = null;
                    }
                    if (isset($data['headline']) && is_array($data['headline'])) {
                        $this->_tableRow($data['headline'],
                                         array('bold' => true),
                                         $opts);
                    }

                    if (is_array($data['data'])) {
                        foreach($data['data'] as $row) {
                            $this->_tableRow($row, null, $opts);
                        }
                    } else {
                        $this->_tableRow(array($data['data']), null, $opts);
                     }
                    $this->_endTable();
                } else {
                    $this->_displayLine($data);
                }
            }
        }
    }

    function log($text, $append_crlf = true)
    {
        if ($append_crlf) {
            return $this->_displayLine($text);
        }

        return $this->_display($text);
    }

    function bold($text)
    {
        if (empty($this->term['bold'])) {
            return strtoupper($text);
        }

        return $this->term['bold'] . $text . $this->term['normal'];
    }

    function _displayHeading($title)
    {
        print $this->lp.$this->bold($title)."\n";
        print $this->lp.str_repeat("=", strlen($title))."\n";
    }

    function _startTable($params = array())
    {
        $params['table_data'] = array();
        $params['widest']     = array();  // indexed by column
        $params['highest']    = array(); // indexed by row
        $params['ncols']      = 0;
        $this->params         = $params;
    }

    function _tableRow($columns, $rowparams = array(), $colparams = array())
    {
        $highest = 1;
        for ($i = 0; $i < count($columns); $i++) {
            $col = &$columns[$i];
            if (isset($colparams[$i]) && !empty($colparams[$i]['wrap'])) {
                $col = wordwrap($col, $colparams[$i]['wrap']);
            }

            if (strpos($col, "\n") !== false) {
                $multiline = explode("\n", $col);
                $w = 0;
                foreach ($multiline as $n => $line) {
                    $len = strlen($line);
                    if ($len > $w) {
                        $w = $len;
                    }
                }
                $lines = count($multiline);
            } else {
                $w = strlen($col);
            }

            if (isset($this->params['widest'][$i])) {
                if ($w > $this->params['widest'][$i]) {
                    $this->params['widest'][$i] = $w;
                }
            } else {
                $this->params['widest'][$i] = $w;
            }

            $tmp = count_chars($columns[$i], 1);
            // handle unix, mac and windows formats
            $lines = (isset($tmp[10]) ? $tmp[10] : (isset($tmp[13]) ? $tmp[13] : 0)) + 1;
            if ($lines > $highest) {
                $highest = $lines;
            }
        }

        if (count($columns) > $this->params['ncols']) {
            $this->params['ncols'] = count($columns);
        }

        $new_row = array(
            'data'      => $columns,
            'height'    => $highest,
            'rowparams' => $rowparams,
            'colparams' => $colparams,
        );
        $this->params['table_data'][] = $new_row;
    }

    function _endTable()
    {
        extract($this->params);
        if (!empty($caption)) {
            $this->_displayHeading($caption);
        }

        if (count($table_data) === 0) {
            return;
        }

        if (!isset($width)) {
            $width = $widest;
        } else {
            for ($i = 0; $i < $ncols; $i++) {
                if (!isset($width[$i])) {
                    $width[$i] = $widest[$i];
                }
            }
        }

        $border = false;
        if (empty($border)) {
            $cellstart  = '';
            $cellend    = ' ';
            $rowend     = '';
            $padrowend  = false;
            $borderline = '';
        } else {
            $cellstart  = '| ';
            $cellend    = ' ';
            $rowend     = '|';
            $padrowend  = true;
            $borderline = '+';
            foreach ($width as $w) {
                $borderline .= str_repeat('-', $w + strlen($cellstart) + strlen($cellend) - 1);
                $borderline .= '+';
            }
        }

        if ($borderline) {
            $this->_displayLine($borderline);
        }

        for ($i = 0; $i < count($table_data); $i++) {
            extract($table_data[$i]);
            if (!is_array($rowparams)) {
                $rowparams = array();
            }

            if (!is_array($colparams)) {
                $colparams = array();
            }

            $rowlines = array();
            if ($height > 1) {
                for ($c = 0; $c < count($data); $c++) {
                    $rowlines[$c] = preg_split('/(\r?\n|\r)/', $data[$c]);
                    if (count($rowlines[$c]) < $height) {
                        $rowlines[$c] = array_pad($rowlines[$c], $height, '');
                    }
                }
            } else {
                for ($c = 0; $c < count($data); $c++) {
                    $rowlines[$c] = array($data[$c]);
                }
            }

            for ($r = 0; $r < $height; $r++) {
                $rowtext = '';
                for ($c = 0; $c < count($data); $c++) {
                    if (isset($colparams[$c])) {
                        $attribs = array_merge($rowparams, $colparams);
                    } else {
                        $attribs = $rowparams;
                    }

                    $w = isset($width[$c]) ? $width[$c] : 0;
                    //$cell = $data[$c];
                    $cell = $rowlines[$c][$r];
                    $l = strlen($cell);
                    if ($l > $w) {
                        $cell = substr($cell, 0, $w);
                    }

                    if (isset($attribs['bold'])) {
                        $cell = $this->bold($cell);
                    }

                    if ($l < $w) {
                        // not using str_pad here because we may
                        // add bold escape characters to $cell
                        $cell .= str_repeat(' ', $w - $l);
                    }

                    $rowtext .= $cellstart . $cell . $cellend;
                }

                if (!$border) {
                    $rowtext = rtrim($rowtext);
                }

                $rowtext .= $rowend;
                $this->_displayLine($rowtext);
            }
        }

        if ($borderline) {
            $this->_displayLine($borderline);
        }
    }

    function _displayLine($text)
    {
        print "$this->lp$text\n";
    }

    function _display($text)
    {
        print $text;
    }
}