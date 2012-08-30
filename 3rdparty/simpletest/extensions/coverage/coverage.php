<?php
/**
* @package        SimpleTest
* @subpackage     Extensions
*/
/**
* load coverage data handle
*/
require_once dirname(__FILE__) . '/coverage_data_handler.php';

/**
 * Orchestrates code coverage both in this thread and in subthread under apache
 * Assumes this is running on same machine as apache.
 * @package        SimpleTest
 * @subpackage     Extensions
 */
class CodeCoverage  {
    var $log;
    var $root;
    var $includes;
    var $excludes;
    var $directoryDepth;
    var $maxDirectoryDepth = 20; // reasonable, otherwise arbitrary
    var $title = "Code Coverage";

    # NOTE: This assumes all code shares the same current working directory.
    var $settingsFile = './code-coverage-settings.dat';

    static $instance;

    function writeUntouched() {
        $touched = array_flip($this->getTouchedFiles());
        $untouched = array();
        $this->getUntouchedFiles($untouched, $touched, '.', '.');
        $this->includeUntouchedFiles($untouched);
    }

    function &getTouchedFiles() {
        $handler = new CoverageDataHandler($this->log);
        $touched = $handler->getFilenames();
        return $touched;
    }

    function includeUntouchedFiles($untouched) {
        $handler = new CoverageDataHandler($this->log);
        foreach ($untouched as $file) {
            $handler->writeUntouchedFile($file);
        }
    }

    function getUntouchedFiles(&$untouched, $touched, $parentPath, $rootPath, $directoryDepth = 1) {
        $parent = opendir($parentPath);
        while ($file = readdir($parent)) {
            $path = "$parentPath/$file";
            if (is_dir($path)) {
                if ($file != '.' && $file != '..') {
                    if ($this->isDirectoryIncluded($path, $directoryDepth)) {
                        $this->getUntouchedFiles($untouched, $touched, $path, $rootPath, $directoryDepth + 1);
                    }
                }
            }
            else if ($this->isFileIncluded($path)) {
                $relativePath = CoverageDataHandler::ltrim($rootPath .'/', $path);
                if (!array_key_exists($relativePath, $touched)) {
                    $untouched[] = $relativePath;
                }
            }
        }
        closedir($parent);
    }

    function resetLog() {
        error_log('reseting log');
        $new_file = fopen($this->log, "w");
        if (!$new_file) {
            throw new Exception("Could not create ". $this->log);
        }
        fclose($new_file);
        if (!chmod($this->log, 0666)) {
            throw new Exception("Could not change ownership on file  ". $this->log);
        }
        $handler = new CoverageDataHandler($this->log);
        $handler->createSchema();
    }

    function startCoverage() {
        $this->root = getcwd();
        if(!extension_loaded("xdebug")) {
            throw new Exception("Could not load xdebug extension");
        };
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    function stopCoverage() {
        $cov = xdebug_get_code_coverage();
        $this->filter($cov);
        $data = new CoverageDataHandler($this->log);
        chdir($this->root);
        $data->write($cov);
        unset($data); // release sqlite connection
        xdebug_stop_code_coverage();
        // make sure we wind up on same current working directory, otherwise
        // coverage handler writer doesn't know what directory to chop off
        chdir($this->root);
    }

    function readSettings() {
        if (file_exists($this->settingsFile)) {
            $this->setSettings(file_get_contents($this->settingsFile));
        } else {
            error_log("could not find file ". $this->settingsFile);
        }
    }

    function writeSettings() {       
        file_put_contents($this->settingsFile, $this->getSettings());
    }

    function getSettings() {
        $data = array(
    	'log' => realpath($this->log), 
    	'includes' => $this->includes, 
    	'excludes' => $this->excludes);
        return serialize($data);
    }

    function setSettings($settings) {
        $data = unserialize($settings);
        $this->log = $data['log'];
        $this->includes = $data['includes'];
        $this->excludes = $data['excludes'];
    }

    function filter(&$coverage) {
        foreach ($coverage as $file => $line) {
            if (!$this->isFileIncluded($file)) {
                unset($coverage[$file]);
            }
        }
    }

    function isFileIncluded($file)  {
        if (!empty($this->excludes)) {
            foreach ($this->excludes as $path) {
                if (preg_match('|' . $path . '|', $file)) {
                    return False;
                }
            }
        }

        if (!empty($this->includes)) {
            foreach ($this->includes as $path) {
                if (preg_match('|' . $path . '|', $file)) {
                    return True;
                }
            }
            return False;
        }

        return True;
    }

    function isDirectoryIncluded($dir, $directoryDepth)  {
        if ($directoryDepth >= $this->maxDirectoryDepth) {
            return false;
        }
        if (isset($this->excludes)) {
            foreach ($this->excludes as $path) {
                if (preg_match('|' . $path . '|', $dir)) {
                    return False;
                }
            }
        }

        return True;
    }

    static function isCoverageOn() {
        $coverage = self::getInstance();
        $coverage->readSettings();
        if (empty($coverage->log) || !file_exists($coverage->log)) {
            trigger_error('No coverage log');
            return False;
        }
        return True;
    }

    static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new CodeCoverage();
            self::$instance->readSettings();
        }
        return self::$instance;
    }
}
?>
