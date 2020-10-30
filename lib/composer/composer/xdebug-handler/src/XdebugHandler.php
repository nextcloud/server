<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

use Psr\Log\LoggerInterface;

/**
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 */
class XdebugHandler
{
    const SUFFIX_ALLOW = '_ALLOW_XDEBUG';
    const SUFFIX_INIS = '_ORIGINAL_INIS';
    const RESTART_ID = 'internal';
    const RESTART_SETTINGS = 'XDEBUG_HANDLER_SETTINGS';
    const DEBUG = 'XDEBUG_HANDLER_DEBUG';

    /** @var string|null */
    protected $tmpIni;

    private static $inRestart;
    private static $name;
    private static $skipped;

    private $cli;
    private $colorOption;
    private $debug;
    private $envAllowXdebug;
    private $envOriginalInis;
    private $loaded;
    private $persistent;
    private $script;
    /** @var Status|null */
    private $statusWriter;

    /**
     * Constructor
     *
     * The $envPrefix is used to create distinct environment variables. It is
     * uppercased and prepended to the default base values. For example 'myapp'
     * would result in MYAPP_ALLOW_XDEBUG and MYAPP_ORIGINAL_INIS.
     *
     * @param string $envPrefix Value used in environment variables
     * @param string $colorOption Command-line long option to force color output
     * @throws \RuntimeException If a parameter is invalid
     */
    public function __construct($envPrefix, $colorOption = '')
    {
        if (!is_string($envPrefix) || empty($envPrefix) || !is_string($colorOption)) {
            throw new \RuntimeException('Invalid constructor parameter');
        }

        self::$name = strtoupper($envPrefix);
        $this->envAllowXdebug = self::$name.self::SUFFIX_ALLOW;
        $this->envOriginalInis = self::$name.self::SUFFIX_INIS;

        $this->colorOption = $colorOption;

        if (extension_loaded('xdebug')) {
            $ext = new \ReflectionExtension('xdebug');
            $this->loaded = $ext->getVersion() ?: 'unknown';
        }

        if ($this->cli = PHP_SAPI === 'cli') {
            $this->debug = getenv(self::DEBUG);
        }

        $this->statusWriter = new Status($this->envAllowXdebug, (bool) $this->debug);
    }

    /**
     * Activates status message output to a PSR3 logger
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->statusWriter->setLogger($logger);
        return $this;
    }

    /**
     * Sets the main script location if it cannot be called from argv
     *
     * @param string $script
     *
     * @return $this
     */
    public function setMainScript($script)
    {
        $this->script = $script;
        return $this;
    }

    /**
     * Persist the settings to keep Xdebug out of sub-processes
     *
     * @return $this
     */
    public function setPersistent()
    {
        $this->persistent = true;
        return $this;
    }

    /**
     * Checks if Xdebug is loaded and the process needs to be restarted
     *
     * This behaviour can be disabled by setting the MYAPP_ALLOW_XDEBUG
     * environment variable to 1. This variable is used internally so that
     * the restarted process is created only once.
     */
    public function check()
    {
        $this->notify(Status::CHECK, $this->loaded);
        $envArgs = explode('|', (string) getenv($this->envAllowXdebug));

        if (empty($envArgs[0]) && $this->requiresRestart((bool) $this->loaded)) {
            // Restart required
            $this->notify(Status::RESTART);

            if ($this->prepareRestart()) {
                $command = $this->getCommand();
                $this->restart($command);
            }
            return;
        }

        if (self::RESTART_ID === $envArgs[0] && count($envArgs) === 5) {
            // Restarted, so unset environment variable and use saved values
            $this->notify(Status::RESTARTED);

            Process::setEnv($this->envAllowXdebug);
            self::$inRestart = true;

            if (!$this->loaded) {
                // Skipped version is only set if Xdebug is not loaded
                self::$skipped = $envArgs[1];
            }

            $this->tryEnableSignals();

            // Put restart settings in the environment
            $this->setEnvRestartSettings($envArgs);
            return;
        }

        $this->notify(Status::NORESTART);

        if ($settings = self::getRestartSettings()) {
            // Called with existing settings, so sync our settings
            $this->syncSettings($settings);
        }
    }

    /**
     * Returns an array of php.ini locations with at least one entry
     *
     * The equivalent of calling php_ini_loaded_file then php_ini_scanned_files.
     * The loaded ini location is the first entry and may be empty.
     *
     * @return array
     */
    public static function getAllIniFiles()
    {
        if (!empty(self::$name)) {
            $env = getenv(self::$name.self::SUFFIX_INIS);

            if (false !== $env) {
                return explode(PATH_SEPARATOR, $env);
            }
        }

        $paths = array((string) php_ini_loaded_file());

        if ($scanned = php_ini_scanned_files()) {
            $paths = array_merge($paths, array_map('trim', explode(',', $scanned)));
        }

        return $paths;
    }

    /**
     * Returns an array of restart settings or null
     *
     * Settings will be available if the current process was restarted, or
     * called with the settings from an existing restart.
     *
     * @return array|null
     */
    public static function getRestartSettings()
    {
        $envArgs = explode('|', (string) getenv(self::RESTART_SETTINGS));

        if (count($envArgs) !== 6
            || (!self::$inRestart && php_ini_loaded_file() !== $envArgs[0])) {
            return;
        }

        return array(
            'tmpIni' => $envArgs[0],
            'scannedInis' => (bool) $envArgs[1],
            'scanDir' => '*' === $envArgs[2] ? false : $envArgs[2],
            'phprc' => '*' === $envArgs[3] ? false : $envArgs[3],
            'inis' => explode(PATH_SEPARATOR, $envArgs[4]),
            'skipped' => $envArgs[5],
        );
    }

    /**
     * Returns the Xdebug version that triggered a successful restart
     *
     * @return string
     */
    public static function getSkippedVersion()
    {
        return (string) self::$skipped;
    }

    /**
     * Returns true if Xdebug is loaded, or as directed by an extending class
     *
     * @param bool $isLoaded Whether Xdebug is loaded
     *
     * @return bool
     */
    protected function requiresRestart($isLoaded)
    {
        return $isLoaded;
    }

    /**
     * Allows an extending class to access the tmpIni
     *
     * @param string $command
     */
    protected function restart($command)
    {
        $this->doRestart($command);
    }

    /**
     * Executes the restarted command then deletes the tmp ini
     *
     * @param string $command
     */
    private function doRestart($command)
    {
        $this->tryEnableSignals();
        $this->notify(Status::RESTARTING, $command);

        passthru($command, $exitCode);
        $this->notify(Status::INFO, 'Restarted process exited '.$exitCode);

        if ($this->debug === '2') {
            $this->notify(Status::INFO, 'Temp ini saved: '.$this->tmpIni);
        } else {
            @unlink($this->tmpIni);
        }

        exit($exitCode);
    }

    /**
     * Returns true if everything was written for the restart
     *
     * If any of the following fails (however unlikely) we must return false to
     * stop potential recursion:
     *   - tmp ini file creation
     *   - environment variable creation
     *
     * @return bool
     */
    private function prepareRestart()
    {
        $error = '';
        $iniFiles = self::getAllIniFiles();
        $scannedInis = count($iniFiles) > 1;
        $tmpDir = sys_get_temp_dir();

        if (!$this->cli) {
            $error = 'Unsupported SAPI: '.PHP_SAPI;
        } elseif (!defined('PHP_BINARY')) {
            $error = 'PHP version is too old: '.PHP_VERSION;
        } elseif (!$this->checkConfiguration($info)) {
            $error = $info;
        } elseif (!$this->checkScanDirConfig()) {
            $error = 'PHP version does not report scanned inis: '.PHP_VERSION;
        } elseif (!$this->checkMainScript()) {
            $error = 'Unable to access main script: '.$this->script;
        } elseif (!$this->writeTmpIni($iniFiles, $tmpDir, $error)) {
            $error = $error ?: 'Unable to create temp ini file at: '.$tmpDir;
        } elseif (!$this->setEnvironment($scannedInis, $iniFiles)) {
            $error = 'Unable to set environment variables';
        }

        if ($error) {
            $this->notify(Status::ERROR, $error);
        }

        return empty($error);
    }

    /**
     * Returns true if the tmp ini file was written
     *
     * @param array $iniFiles All ini files used in the current process
     * @param string $tmpDir The system temporary directory
     * @param string $error Set by method if ini file cannot be read
     *
     * @return bool
     */
    private function writeTmpIni(array $iniFiles, $tmpDir, &$error)
    {
        if (!$this->tmpIni = @tempnam($tmpDir, '')) {
            return false;
        }

        // $iniFiles has at least one item and it may be empty
        if (empty($iniFiles[0])) {
            array_shift($iniFiles);
        }

        $content = '';
        $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

        foreach ($iniFiles as $file) {
            // Check for inaccessible ini files
            if (($data = @file_get_contents($file)) === false) {
                $error = 'Unable to read ini: '.$file;
                return false;
            }
            $content .= preg_replace($regex, ';$1', $data).PHP_EOL;
        }

        // Merge loaded settings into our ini content, if it is valid
        if ($config = parse_ini_string($content)) {
            $loaded = ini_get_all(null, false);
            $content .= $this->mergeLoadedConfig($loaded, $config);
        }

        // Work-around for https://bugs.php.net/bug.php?id=75932
        $content .= 'opcache.enable_cli=0'.PHP_EOL;

        return @file_put_contents($this->tmpIni, $content);
    }

    /**
     * Returns the restart command line
     *
     * @return string
     */
    private function getCommand()
    {
        $php = array(PHP_BINARY);
        $args = array_slice($_SERVER['argv'], 1);

        if (!$this->persistent) {
            // Use command-line options
            array_push($php, '-n', '-c', $this->tmpIni);
        }

        if (defined('STDOUT') && Process::supportsColor(STDOUT)) {
            $args = Process::addColorOption($args, $this->colorOption);
        }

        $args = array_merge($php, array($this->script), $args);

        $cmd = Process::escape(array_shift($args), true, true);
        foreach ($args as $arg) {
            $cmd .= ' '.Process::escape($arg);
        }

        return $cmd;
    }

    /**
     * Returns true if the restart environment variables were set
     *
     * No need to update $_SERVER since this is set in the restarted process.
     *
     * @param bool $scannedInis Whether there were scanned ini files
     * @param array $iniFiles All ini files used in the current process
     *
     * @return bool
     */
    private function setEnvironment($scannedInis, array $iniFiles)
    {
        $scanDir = getenv('PHP_INI_SCAN_DIR');
        $phprc = getenv('PHPRC');

        // Make original inis available to restarted process
        if (!putenv($this->envOriginalInis.'='.implode(PATH_SEPARATOR, $iniFiles))) {
            return false;
        }

        if ($this->persistent) {
            // Use the environment to persist the settings
            if (!putenv('PHP_INI_SCAN_DIR=') || !putenv('PHPRC='.$this->tmpIni)) {
                return false;
            }
        }

        // Flag restarted process and save values for it to use
        $envArgs = array(
            self::RESTART_ID,
            $this->loaded,
            (int) $scannedInis,
            false === $scanDir ? '*' : $scanDir,
            false === $phprc ? '*' : $phprc,
        );

        return putenv($this->envAllowXdebug.'='.implode('|', $envArgs));
    }

    /**
     * Logs status messages
     *
     * @param string $op Status handler constant
     * @param null|string $data Optional data
     */
    private function notify($op, $data = null)
    {
        $this->statusWriter->report($op, $data);
    }

    /**
     * Returns default, changed and command-line ini settings
     *
     * @param array $loadedConfig All current ini settings
     * @param array $iniConfig Settings from user ini files
     *
     * @return string
     */
    private function mergeLoadedConfig(array $loadedConfig, array $iniConfig)
    {
        $content = '';

        foreach ($loadedConfig as $name => $value) {
            // Value will either be null, string or array (HHVM only)
            if (!is_string($value)
                || strpos($name, 'xdebug') === 0
                || $name === 'apc.mmap_file_mask') {
                continue;
            }

            if (!isset($iniConfig[$name]) || $iniConfig[$name] !== $value) {
                // Double-quote escape each value
                $content .= $name.'="'.addcslashes($value, '\\"').'"'.PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * Returns true if the script name can be used
     *
     * @return bool
     */
    private function checkMainScript()
    {
        if (null !== $this->script) {
            // Allow an application to set -- for standard input
            return file_exists($this->script) || '--' === $this->script;
        }

        if (file_exists($this->script = $_SERVER['argv'][0])) {
            return true;
        }

        // Use a backtrace to resolve Phar and chdir issues
        $options = PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : false;
        $trace = debug_backtrace($options);

        if (($main = end($trace)) && isset($main['file'])) {
            return file_exists($this->script = $main['file']);
        }

        return false;
    }

    /**
     * Adds restart settings to the environment
     *
     * @param string $envArgs
     */
    private function setEnvRestartSettings($envArgs)
    {
        $settings = array(
            php_ini_loaded_file(),
            $envArgs[2],
            $envArgs[3],
            $envArgs[4],
            getenv($this->envOriginalInis),
            self::$skipped,
        );

        Process::setEnv(self::RESTART_SETTINGS, implode('|', $settings));
    }

    /**
     * Syncs settings and the environment if called with existing settings
     *
     * @param array $settings
     */
    private function syncSettings(array $settings)
    {
        if (false === getenv($this->envOriginalInis)) {
            // Called by another app, so make original inis available
            Process::setEnv($this->envOriginalInis, implode(PATH_SEPARATOR, $settings['inis']));
        }

        self::$skipped = $settings['skipped'];
        $this->notify(Status::INFO, 'Process called with existing restart settings');
    }

    /**
     * Returns true if there are scanned inis and PHP is able to report them
     *
     * php_ini_scanned_files will fail when PHP_CONFIG_FILE_SCAN_DIR is empty.
     * Fixed in 7.1.13 and 7.2.1
     *
     * @return bool
     */
    private function checkScanDirConfig()
    {
        return !(getenv('PHP_INI_SCAN_DIR')
            && !PHP_CONFIG_FILE_SCAN_DIR
            && (PHP_VERSION_ID < 70113
            || PHP_VERSION_ID === 70200));
    }

    /**
     * Returns true if there are no known configuration issues
     *
     * @param string $info Set by method
     */
    private function checkConfiguration(&$info)
    {
        if (false !== strpos(ini_get('disable_functions'), 'passthru')) {
            $info = 'passthru function is disabled';
            return false;
        }

        if (extension_loaded('uopz') && !ini_get('uopz.disable')) {
            // uopz works at opcode level and disables exit calls
            if (function_exists('uopz_allow_exit')) {
                @uopz_allow_exit(true);
            } else {
                $info = 'uopz extension is not compatible';
                return false;
            }
        }

        return true;
    }

    /**
     * Enables async signals and control interrupts in the restarted process
     *
     * Only available on Unix PHP 7.1+ with the pcntl extension. To replicate on
     * Windows would require PHP 7.4+ using proc_open rather than passthru.
     */
    private function tryEnableSignals()
    {
        if (!function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        $message = 'Async signals enabled';

        if (!self::$inRestart) {
            // Restarting, so ignore SIGINT in parent
            pcntl_signal(SIGINT, SIG_IGN);
            $message .= ' (SIGINT = SIG_IGN)';
        } elseif (is_int(pcntl_signal_get_handler(SIGINT))) {
            // Restarted, no handler set so force default action
            pcntl_signal(SIGINT, SIG_DFL);
            $message .= ' (SIGINT = SIG_DFL)';
        }

        $this->notify(Status::INFO, $message);
    }
}
