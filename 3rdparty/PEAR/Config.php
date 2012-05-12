<?php
/**
 * PEAR_Config, customized configuration handling for the PEAR Installer
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Config.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * Required for error handling
 */
require_once 'PEAR.php';
require_once 'PEAR/Registry.php';
require_once 'PEAR/Installer/Role.php';
require_once 'System.php';

/**
 * Last created PEAR_Config instance.
 * @var object
 */
$GLOBALS['_PEAR_Config_instance'] = null;
if (!defined('PEAR_INSTALL_DIR') || !PEAR_INSTALL_DIR) {
    $PEAR_INSTALL_DIR = PHP_LIBDIR . DIRECTORY_SEPARATOR . 'pear';
} else {
    $PEAR_INSTALL_DIR = PEAR_INSTALL_DIR;
}

// Below we define constants with default values for all configuration
// parameters except username/password.  All of them can have their
// defaults set through environment variables.  The reason we use the
// PHP_ prefix is for some security, PHP protects environment
// variables starting with PHP_*.

// default channel and preferred mirror is based on whether we are invoked through
// the "pear" or the "pecl" command
if (!defined('PEAR_RUNTYPE')) {
    define('PEAR_RUNTYPE', 'pear');
}

if (PEAR_RUNTYPE == 'pear') {
    define('PEAR_CONFIG_DEFAULT_CHANNEL', 'pear.php.net');
} else {
    define('PEAR_CONFIG_DEFAULT_CHANNEL', 'pecl.php.net');
}

if (getenv('PHP_PEAR_SYSCONF_DIR')) {
    define('PEAR_CONFIG_SYSCONFDIR', getenv('PHP_PEAR_SYSCONF_DIR'));
} elseif (getenv('SystemRoot')) {
    define('PEAR_CONFIG_SYSCONFDIR', getenv('SystemRoot'));
} else {
    define('PEAR_CONFIG_SYSCONFDIR', PHP_SYSCONFDIR);
}

// Default for master_server
if (getenv('PHP_PEAR_MASTER_SERVER')) {
    define('PEAR_CONFIG_DEFAULT_MASTER_SERVER', getenv('PHP_PEAR_MASTER_SERVER'));
} else {
    define('PEAR_CONFIG_DEFAULT_MASTER_SERVER', 'pear.php.net');
}

// Default for http_proxy
if (getenv('PHP_PEAR_HTTP_PROXY')) {
    define('PEAR_CONFIG_DEFAULT_HTTP_PROXY', getenv('PHP_PEAR_HTTP_PROXY'));
} elseif (getenv('http_proxy')) {
    define('PEAR_CONFIG_DEFAULT_HTTP_PROXY', getenv('http_proxy'));
} else {
    define('PEAR_CONFIG_DEFAULT_HTTP_PROXY', '');
}

// Default for php_dir
if (getenv('PHP_PEAR_INSTALL_DIR')) {
    define('PEAR_CONFIG_DEFAULT_PHP_DIR', getenv('PHP_PEAR_INSTALL_DIR'));
} else {
    if (@file_exists($PEAR_INSTALL_DIR) && is_dir($PEAR_INSTALL_DIR)) {
        define('PEAR_CONFIG_DEFAULT_PHP_DIR', $PEAR_INSTALL_DIR);
    } else {
        define('PEAR_CONFIG_DEFAULT_PHP_DIR', $PEAR_INSTALL_DIR);
    }
}

// Default for ext_dir
if (getenv('PHP_PEAR_EXTENSION_DIR')) {
    define('PEAR_CONFIG_DEFAULT_EXT_DIR', getenv('PHP_PEAR_EXTENSION_DIR'));
} else {
    if (ini_get('extension_dir')) {
        define('PEAR_CONFIG_DEFAULT_EXT_DIR', ini_get('extension_dir'));
    } elseif (defined('PEAR_EXTENSION_DIR') &&
              file_exists(PEAR_EXTENSION_DIR) && is_dir(PEAR_EXTENSION_DIR)) {
        define('PEAR_CONFIG_DEFAULT_EXT_DIR', PEAR_EXTENSION_DIR);
    } elseif (defined('PHP_EXTENSION_DIR')) {
        define('PEAR_CONFIG_DEFAULT_EXT_DIR', PHP_EXTENSION_DIR);
    } else {
        define('PEAR_CONFIG_DEFAULT_EXT_DIR', '.');
    }
}

// Default for doc_dir
if (getenv('PHP_PEAR_DOC_DIR')) {
    define('PEAR_CONFIG_DEFAULT_DOC_DIR', getenv('PHP_PEAR_DOC_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_DOC_DIR',
           $PEAR_INSTALL_DIR.DIRECTORY_SEPARATOR.'docs');
}

// Default for bin_dir
if (getenv('PHP_PEAR_BIN_DIR')) {
    define('PEAR_CONFIG_DEFAULT_BIN_DIR', getenv('PHP_PEAR_BIN_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_BIN_DIR', PHP_BINDIR);
}

// Default for data_dir
if (getenv('PHP_PEAR_DATA_DIR')) {
    define('PEAR_CONFIG_DEFAULT_DATA_DIR', getenv('PHP_PEAR_DATA_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_DATA_DIR',
           $PEAR_INSTALL_DIR.DIRECTORY_SEPARATOR.'data');
}

// Default for cfg_dir
if (getenv('PHP_PEAR_CFG_DIR')) {
    define('PEAR_CONFIG_DEFAULT_CFG_DIR', getenv('PHP_PEAR_CFG_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_CFG_DIR',
           $PEAR_INSTALL_DIR.DIRECTORY_SEPARATOR.'cfg');
}

// Default for www_dir
if (getenv('PHP_PEAR_WWW_DIR')) {
    define('PEAR_CONFIG_DEFAULT_WWW_DIR', getenv('PHP_PEAR_WWW_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_WWW_DIR',
           $PEAR_INSTALL_DIR.DIRECTORY_SEPARATOR.'www');
}

// Default for test_dir
if (getenv('PHP_PEAR_TEST_DIR')) {
    define('PEAR_CONFIG_DEFAULT_TEST_DIR', getenv('PHP_PEAR_TEST_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_TEST_DIR',
           $PEAR_INSTALL_DIR.DIRECTORY_SEPARATOR.'tests');
}

// Default for temp_dir
if (getenv('PHP_PEAR_TEMP_DIR')) {
    define('PEAR_CONFIG_DEFAULT_TEMP_DIR', getenv('PHP_PEAR_TEMP_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_TEMP_DIR',
           System::tmpdir() . DIRECTORY_SEPARATOR . 'pear' .
           DIRECTORY_SEPARATOR . 'temp');
}

// Default for cache_dir
if (getenv('PHP_PEAR_CACHE_DIR')) {
    define('PEAR_CONFIG_DEFAULT_CACHE_DIR', getenv('PHP_PEAR_CACHE_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_CACHE_DIR',
           System::tmpdir() . DIRECTORY_SEPARATOR . 'pear' .
           DIRECTORY_SEPARATOR . 'cache');
}

// Default for download_dir
if (getenv('PHP_PEAR_DOWNLOAD_DIR')) {
    define('PEAR_CONFIG_DEFAULT_DOWNLOAD_DIR', getenv('PHP_PEAR_DOWNLOAD_DIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_DOWNLOAD_DIR',
           System::tmpdir() . DIRECTORY_SEPARATOR . 'pear' .
           DIRECTORY_SEPARATOR . 'download');
}

// Default for php_bin
if (getenv('PHP_PEAR_PHP_BIN')) {
    define('PEAR_CONFIG_DEFAULT_PHP_BIN', getenv('PHP_PEAR_PHP_BIN'));
} else {
    define('PEAR_CONFIG_DEFAULT_PHP_BIN', PEAR_CONFIG_DEFAULT_BIN_DIR.
           DIRECTORY_SEPARATOR.'php'.(OS_WINDOWS ? '.exe' : ''));
}

// Default for verbose
if (getenv('PHP_PEAR_VERBOSE')) {
    define('PEAR_CONFIG_DEFAULT_VERBOSE', getenv('PHP_PEAR_VERBOSE'));
} else {
    define('PEAR_CONFIG_DEFAULT_VERBOSE', 1);
}

// Default for preferred_state
if (getenv('PHP_PEAR_PREFERRED_STATE')) {
    define('PEAR_CONFIG_DEFAULT_PREFERRED_STATE', getenv('PHP_PEAR_PREFERRED_STATE'));
} else {
    define('PEAR_CONFIG_DEFAULT_PREFERRED_STATE', 'stable');
}

// Default for umask
if (getenv('PHP_PEAR_UMASK')) {
    define('PEAR_CONFIG_DEFAULT_UMASK', getenv('PHP_PEAR_UMASK'));
} else {
    define('PEAR_CONFIG_DEFAULT_UMASK', decoct(umask()));
}

// Default for cache_ttl
if (getenv('PHP_PEAR_CACHE_TTL')) {
    define('PEAR_CONFIG_DEFAULT_CACHE_TTL', getenv('PHP_PEAR_CACHE_TTL'));
} else {
    define('PEAR_CONFIG_DEFAULT_CACHE_TTL', 3600);
}

// Default for sig_type
if (getenv('PHP_PEAR_SIG_TYPE')) {
    define('PEAR_CONFIG_DEFAULT_SIG_TYPE', getenv('PHP_PEAR_SIG_TYPE'));
} else {
    define('PEAR_CONFIG_DEFAULT_SIG_TYPE', 'gpg');
}

// Default for sig_bin
if (getenv('PHP_PEAR_SIG_BIN')) {
    define('PEAR_CONFIG_DEFAULT_SIG_BIN', getenv('PHP_PEAR_SIG_BIN'));
} else {
    define('PEAR_CONFIG_DEFAULT_SIG_BIN',
           System::which(
               'gpg', OS_WINDOWS ? 'c:\gnupg\gpg.exe' : '/usr/local/bin/gpg'));
}

// Default for sig_keydir
if (getenv('PHP_PEAR_SIG_KEYDIR')) {
    define('PEAR_CONFIG_DEFAULT_SIG_KEYDIR', getenv('PHP_PEAR_SIG_KEYDIR'));
} else {
    define('PEAR_CONFIG_DEFAULT_SIG_KEYDIR',
           PEAR_CONFIG_SYSCONFDIR . DIRECTORY_SEPARATOR . 'pearkeys');
}

/**
 * This is a class for storing configuration data, keeping track of
 * which are system-defined, user-defined or defaulted.
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
class PEAR_Config extends PEAR
{
    /**
     * Array of config files used.
     *
     * @var array layer => config file
     */
    var $files = array(
        'system' => '',
        'user' => '',
        );

    var $layers = array();

    /**
     * Configuration data, two-dimensional array where the first
     * dimension is the config layer ('user', 'system' and 'default'),
     * and the second dimension is keyname => value.
     *
     * The order in the first dimension is important!  Earlier
     * layers will shadow later ones when a config value is
     * requested (if a 'user' value exists, it will be returned first,
     * then 'system' and finally 'default').
     *
     * @var array layer => array(keyname => value, ...)
     */
    var $configuration = array(
        'user' => array(),
        'system' => array(),
        'default' => array(),
        );

    /**
     * Configuration values that can be set for a channel
     *
     * All other configuration values can only have a global value
     * @var array
     * @access private
     */
    var $_channelConfigInfo = array(
        'php_dir', 'ext_dir', 'doc_dir', 'bin_dir', 'data_dir', 'cfg_dir',
        'test_dir', 'www_dir', 'php_bin', 'php_prefix', 'php_suffix', 'username',
        'password', 'verbose', 'preferred_state', 'umask', 'preferred_mirror', 'php_ini'
        );

    /**
     * Channels that can be accessed
     * @see setChannels()
     * @var array
     * @access private
     */
    var $_channels = array('pear.php.net', 'pecl.php.net', '__uri');

    /**
     * This variable is used to control the directory values returned
     * @see setInstallRoot();
     * @var string|false
     * @access private
     */
    var $_installRoot = false;

    /**
     * If requested, this will always refer to the registry
     * contained in php_dir
     * @var PEAR_Registry
     */
    var $_registry = array();

    /**
     * @var array
     * @access private
     */
    var $_regInitialized = array();

    /**
     * @var bool
     * @access private
     */
    var $_noRegistry = false;

    /**
     * amount of errors found while parsing config
     * @var integer
     * @access private
     */
    var $_errorsFound = 0;
    var $_lastError = null;

    /**
     * Information about the configuration data.  Stores the type,
     * default value and a documentation string for each configuration
     * value.
     *
     * @var array layer => array(infotype => value, ...)
     */
    var $configuration_info = array(
        // Channels/Internet Access
        'default_channel' => array(
            'type' => 'string',
            'default' => PEAR_CONFIG_DEFAULT_CHANNEL,
            'doc' => 'the default channel to use for all non explicit commands',
            'prompt' => 'Default Channel',
            'group' => 'Internet Access',
            ),
        'preferred_mirror' => array(
            'type' => 'string',
            'default' => PEAR_CONFIG_DEFAULT_CHANNEL,
            'doc' => 'the default server or mirror to use for channel actions',
            'prompt' => 'Default Channel Mirror',
            'group' => 'Internet Access',
            ),
        'remote_config' => array(
            'type' => 'password',
            'default' => '',
            'doc' => 'ftp url of remote configuration file to use for synchronized install',
            'prompt' => 'Remote Configuration File',
            'group' => 'Internet Access',
            ),
        'auto_discover' => array(
            'type' => 'integer',
            'default' => 0,
            'doc' => 'whether to automatically discover new channels',
            'prompt' => 'Auto-discover new Channels',
            'group' => 'Internet Access',
            ),
        // Internet Access
        'master_server' => array(
            'type' => 'string',
            'default' => 'pear.php.net',
            'doc' => 'name of the main PEAR server [NOT USED IN THIS VERSION]',
            'prompt' => 'PEAR server [DEPRECATED]',
            'group' => 'Internet Access',
            ),
        'http_proxy' => array(
            'type' => 'string',
            'default' => PEAR_CONFIG_DEFAULT_HTTP_PROXY,
            'doc' => 'HTTP proxy (host:port) to use when downloading packages',
            'prompt' => 'HTTP Proxy Server Address',
            'group' => 'Internet Access',
            ),
        // File Locations
        'php_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_PHP_DIR,
            'doc' => 'directory where .php files are installed',
            'prompt' => 'PEAR directory',
            'group' => 'File Locations',
            ),
        'ext_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_EXT_DIR,
            'doc' => 'directory where loadable extensions are installed',
            'prompt' => 'PHP extension directory',
            'group' => 'File Locations',
            ),
        'doc_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_DOC_DIR,
            'doc' => 'directory where documentation is installed',
            'prompt' => 'PEAR documentation directory',
            'group' => 'File Locations',
            ),
        'bin_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_BIN_DIR,
            'doc' => 'directory where executables are installed',
            'prompt' => 'PEAR executables directory',
            'group' => 'File Locations',
            ),
        'data_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_DATA_DIR,
            'doc' => 'directory where data files are installed',
            'prompt' => 'PEAR data directory',
            'group' => 'File Locations (Advanced)',
            ),
        'cfg_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_CFG_DIR,
            'doc' => 'directory where modifiable configuration files are installed',
            'prompt' => 'PEAR configuration file directory',
            'group' => 'File Locations (Advanced)',
            ),
        'www_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_WWW_DIR,
            'doc' => 'directory where www frontend files (html/js) are installed',
            'prompt' => 'PEAR www files directory',
            'group' => 'File Locations (Advanced)',
            ),
        'test_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_TEST_DIR,
            'doc' => 'directory where regression tests are installed',
            'prompt' => 'PEAR test directory',
            'group' => 'File Locations (Advanced)',
            ),
        'cache_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_CACHE_DIR,
            'doc' => 'directory which is used for web service cache',
            'prompt' => 'PEAR Installer cache directory',
            'group' => 'File Locations (Advanced)',
            ),
        'temp_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_TEMP_DIR,
            'doc' => 'directory which is used for all temp files',
            'prompt' => 'PEAR Installer temp directory',
            'group' => 'File Locations (Advanced)',
            ),
        'download_dir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_DOWNLOAD_DIR,
            'doc' => 'directory which is used for all downloaded files',
            'prompt' => 'PEAR Installer download directory',
            'group' => 'File Locations (Advanced)',
            ),
        'php_bin' => array(
            'type' => 'file',
            'default' => PEAR_CONFIG_DEFAULT_PHP_BIN,
            'doc' => 'PHP CLI/CGI binary for executing scripts',
            'prompt' => 'PHP CLI/CGI binary',
            'group' => 'File Locations (Advanced)',
            ),
        'php_prefix' => array(
            'type' => 'string',
            'default' => '',
            'doc' => '--program-prefix for php_bin\'s ./configure, used for pecl installs',
            'prompt' => '--program-prefix passed to PHP\'s ./configure',
            'group' => 'File Locations (Advanced)',
            ),
        'php_suffix' => array(
            'type' => 'string',
            'default' => '',
            'doc' => '--program-suffix for php_bin\'s ./configure, used for pecl installs',
            'prompt' => '--program-suffix passed to PHP\'s ./configure',
            'group' => 'File Locations (Advanced)',
            ),
        'php_ini' => array(
            'type' => 'file',
            'default' => '',
            'doc' => 'location of php.ini in which to enable PECL extensions on install',
            'prompt' => 'php.ini location',
            'group' => 'File Locations (Advanced)',
            ),
        // Maintainers
        'username' => array(
            'type' => 'string',
            'default' => '',
            'doc' => '(maintainers) your PEAR account name',
            'prompt' => 'PEAR username (for maintainers)',
            'group' => 'Maintainers',
            ),
        'password' => array(
            'type' => 'password',
            'default' => '',
            'doc' => '(maintainers) your PEAR account password',
            'prompt' => 'PEAR password (for maintainers)',
            'group' => 'Maintainers',
            ),
        // Advanced
        'verbose' => array(
            'type' => 'integer',
            'default' => PEAR_CONFIG_DEFAULT_VERBOSE,
            'doc' => 'verbosity level
0: really quiet
1: somewhat quiet
2: verbose
3: debug',
            'prompt' => 'Debug Log Level',
            'group' => 'Advanced',
            ),
        'preferred_state' => array(
            'type' => 'set',
            'default' => PEAR_CONFIG_DEFAULT_PREFERRED_STATE,
            'doc' => 'the installer will prefer releases with this state when installing packages without a version or state specified',
            'valid_set' => array(
                'stable', 'beta', 'alpha', 'devel', 'snapshot'),
            'prompt' => 'Preferred Package State',
            'group' => 'Advanced',
            ),
        'umask' => array(
            'type' => 'mask',
            'default' => PEAR_CONFIG_DEFAULT_UMASK,
            'doc' => 'umask used when creating files (Unix-like systems only)',
            'prompt' => 'Unix file mask',
            'group' => 'Advanced',
            ),
        'cache_ttl' => array(
            'type' => 'integer',
            'default' => PEAR_CONFIG_DEFAULT_CACHE_TTL,
            'doc' => 'amount of secs where the local cache is used and not updated',
            'prompt' => 'Cache TimeToLive',
            'group' => 'Advanced',
            ),
        'sig_type' => array(
            'type' => 'set',
            'default' => PEAR_CONFIG_DEFAULT_SIG_TYPE,
            'doc' => 'which package signature mechanism to use',
            'valid_set' => array('gpg'),
            'prompt' => 'Package Signature Type',
            'group' => 'Maintainers',
            ),
        'sig_bin' => array(
            'type' => 'string',
            'default' => PEAR_CONFIG_DEFAULT_SIG_BIN,
            'doc' => 'which package signature mechanism to use',
            'prompt' => 'Signature Handling Program',
            'group' => 'Maintainers',
            ),
        'sig_keyid' => array(
            'type' => 'string',
            'default' => '',
            'doc' => 'which key to use for signing with',
            'prompt' => 'Signature Key Id',
            'group' => 'Maintainers',
            ),
        'sig_keydir' => array(
            'type' => 'directory',
            'default' => PEAR_CONFIG_DEFAULT_SIG_KEYDIR,
            'doc' => 'directory where signature keys are located',
            'prompt' => 'Signature Key Directory',
            'group' => 'Maintainers',
            ),
        // __channels is reserved - used for channel-specific configuration
        );

    /**
     * Constructor.
     *
     * @param string file to read user-defined options from
     * @param string file to read system-wide defaults from
     * @param bool   determines whether a registry object "follows"
     *               the value of php_dir (is automatically created
     *               and moved when php_dir is changed)
     * @param bool   if true, fails if configuration files cannot be loaded
     *
     * @access public
     *
     * @see PEAR_Config::singleton
     */
    function PEAR_Config($user_file = '', $system_file = '', $ftp_file = false,
                         $strict = true)
    {
        $this->PEAR();
        PEAR_Installer_Role::initializeConfig($this);
        $sl = DIRECTORY_SEPARATOR;
        if (empty($user_file)) {
            if (OS_WINDOWS) {
                $user_file = PEAR_CONFIG_SYSCONFDIR . $sl . 'pear.ini';
            } else {
                $user_file = getenv('HOME') . $sl . '.pearrc';
            }
        }

        if (empty($system_file)) {
            $system_file = PEAR_CONFIG_SYSCONFDIR . $sl;
            if (OS_WINDOWS) {
                $system_file .= 'pearsys.ini';
            } else {
                $system_file .= 'pear.conf';
            }
        }

        $this->layers = array_keys($this->configuration);
        $this->files['user']   = $user_file;
        $this->files['system'] = $system_file;
        if ($user_file && file_exists($user_file)) {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $this->readConfigFile($user_file, 'user', $strict);
            $this->popErrorHandling();
            if ($this->_errorsFound > 0) {
                return;
            }
        }

        if ($system_file && @file_exists($system_file)) {
            $this->mergeConfigFile($system_file, false, 'system', $strict);
            if ($this->_errorsFound > 0) {
                return;
            }

        }

        if (!$ftp_file) {
            $ftp_file = $this->get('remote_config');
        }

        if ($ftp_file && defined('PEAR_REMOTEINSTALL_OK')) {
            $this->readFTPConfigFile($ftp_file);
        }

        foreach ($this->configuration_info as $key => $info) {
            $this->configuration['default'][$key] = $info['default'];
        }

        $this->_registry['default'] = &new PEAR_Registry($this->configuration['default']['php_dir']);
        $this->_registry['default']->setConfig($this, false);
        $this->_regInitialized['default'] = false;
        //$GLOBALS['_PEAR_Config_instance'] = &$this;
    }

    /**
     * Return the default locations of user and system configuration files
     * @static
     */
    function getDefaultConfigFiles()
    {
        $sl = DIRECTORY_SEPARATOR;
        if (OS_WINDOWS) {
            return array(
                'user'   => PEAR_CONFIG_SYSCONFDIR . $sl . 'pear.ini',
                'system' =>  PEAR_CONFIG_SYSCONFDIR . $sl . 'pearsys.ini'
            );
        }

        return array(
            'user'   => getenv('HOME') . $sl . '.pearrc',
            'system' => PEAR_CONFIG_SYSCONFDIR . $sl . 'pear.conf'
        );
    }

    /**
     * Static singleton method.  If you want to keep only one instance
     * of this class in use, this method will give you a reference to
     * the last created PEAR_Config object if one exists, or create a
     * new object.
     *
     * @param string (optional) file to read user-defined options from
     * @param string (optional) file to read system-wide defaults from
     *
     * @return object an existing or new PEAR_Config instance
     *
     * @access public
     *
     * @see PEAR_Config::PEAR_Config
     */
    function &singleton($user_file = '', $system_file = '', $strict = true)
    {
        if (is_object($GLOBALS['_PEAR_Config_instance'])) {
            return $GLOBALS['_PEAR_Config_instance'];
        }

        $t_conf = &new PEAR_Config($user_file, $system_file, false, $strict);
        if ($t_conf->_errorsFound > 0) {
             return $t_conf->lastError;
        }

        $GLOBALS['_PEAR_Config_instance'] = &$t_conf;
        return $GLOBALS['_PEAR_Config_instance'];
    }

    /**
     * Determine whether any configuration files have been detected, and whether a
     * registry object can be retrieved from this configuration.
     * @return bool
     * @since PEAR 1.4.0a1
     */
    function validConfiguration()
    {
        if ($this->isDefinedLayer('user') || $this->isDefinedLayer('system')) {
            return true;
        }

        return false;
    }

    /**
     * Reads configuration data from a file.  All existing values in
     * the config layer are discarded and replaced with data from the
     * file.
     * @param string file to read from, if NULL or not specified, the
     *               last-used file for the same layer (second param) is used
     * @param string config layer to insert data into ('user' or 'system')
     * @return bool TRUE on success or a PEAR error on failure
     */
    function readConfigFile($file = null, $layer = 'user', $strict = true)
    {
        if (empty($this->files[$layer])) {
            return $this->raiseError("unknown config layer `$layer'");
        }

        if ($file === null) {
            $file = $this->files[$layer];
        }

        $data = $this->_readConfigDataFrom($file);
        if (PEAR::isError($data)) {
            if (!$strict) {
                return true;
            }

            $this->_errorsFound++;
            $this->lastError = $data;

            return $data;
        }

        $this->files[$layer] = $file;
        $this->_decodeInput($data);
        $this->configuration[$layer] = $data;
        $this->_setupChannels();
        if (!$this->_noRegistry && ($phpdir = $this->get('php_dir', $layer, 'pear.php.net'))) {
            $this->_registry[$layer] = &new PEAR_Registry($phpdir);
            $this->_registry[$layer]->setConfig($this, false);
            $this->_regInitialized[$layer] = false;
        } else {
            unset($this->_registry[$layer]);
        }
        return true;
    }

    /**
     * @param string url to the remote config file, like ftp://www.example.com/pear/config.ini
     * @return true|PEAR_Error
     */
    function readFTPConfigFile($path)
    {
        do { // poor man's try
            if (!class_exists('PEAR_FTP')) {
                if (!class_exists('PEAR_Common')) {
                    require_once 'PEAR/Common.php';
                }
                if (PEAR_Common::isIncludeable('PEAR/FTP.php')) {
                    require_once 'PEAR/FTP.php';
                }
            }

            if (!class_exists('PEAR_FTP')) {
                return PEAR::raiseError('PEAR_RemoteInstaller must be installed to use remote config');
            }

            $this->_ftp = &new PEAR_FTP;
            $this->_ftp->pushErrorHandling(PEAR_ERROR_RETURN);
            $e = $this->_ftp->init($path);
            if (PEAR::isError($e)) {
                $this->_ftp->popErrorHandling();
                return $e;
            }

            $tmp = System::mktemp('-d');
            PEAR_Common::addTempFile($tmp);
            $e = $this->_ftp->get(basename($path), $tmp . DIRECTORY_SEPARATOR .
                'pear.ini', false, FTP_BINARY);
            if (PEAR::isError($e)) {
                $this->_ftp->popErrorHandling();
                return $e;
            }

            PEAR_Common::addTempFile($tmp . DIRECTORY_SEPARATOR . 'pear.ini');
            $this->_ftp->disconnect();
            $this->_ftp->popErrorHandling();
            $this->files['ftp'] = $tmp . DIRECTORY_SEPARATOR . 'pear.ini';
            $e = $this->readConfigFile(null, 'ftp');
            if (PEAR::isError($e)) {
                return $e;
            }

            $fail = array();
            foreach ($this->configuration_info as $key => $val) {
                if (in_array($this->getGroup($key),
                      array('File Locations', 'File Locations (Advanced)')) &&
                      $this->getType($key) == 'directory') {
                    // any directory configs must be set for this to work
                    if (!isset($this->configuration['ftp'][$key])) {
                        $fail[] = $key;
                    }
                }
            }

            if (!count($fail)) {
                return true;
            }

            $fail = '"' . implode('", "', $fail) . '"';
            unset($this->files['ftp']);
            unset($this->configuration['ftp']);
            return PEAR::raiseError('ERROR: Ftp configuration file must set all ' .
                'directory configuration variables.  These variables were not set: ' .
                $fail);
        } while (false); // poor man's catch
        unset($this->files['ftp']);
        return PEAR::raiseError('no remote host specified');
    }

    /**
     * Reads the existing configurations and creates the _channels array from it
     */
    function _setupChannels()
    {
        $set = array_flip(array_values($this->_channels));
        foreach ($this->configuration as $layer => $data) {
            $i = 1000;
            if (isset($data['__channels']) && is_array($data['__channels'])) {
                foreach ($data['__channels'] as $channel => $info) {
                    $set[$channel] = $i++;
                }
            }
        }
        $this->_channels = array_values(array_flip($set));
        $this->setChannels($this->_channels);
    }

    function deleteChannel($channel)
    {
        $ch = strtolower($channel);
        foreach ($this->configuration as $layer => $data) {
            if (isset($data['__channels']) && isset($data['__channels'][$ch])) {
                unset($this->configuration[$layer]['__channels'][$ch]);
            }
        }

        $this->_channels = array_flip($this->_channels);
        unset($this->_channels[$ch]);
        $this->_channels = array_flip($this->_channels);
    }

    /**
     * Merges data into a config layer from a file.  Does the same
     * thing as readConfigFile, except it does not replace all
     * existing values in the config layer.
     * @param string file to read from
     * @param bool whether to overwrite existing data (default TRUE)
     * @param string config layer to insert data into ('user' or 'system')
     * @param string if true, errors are returned if file opening fails
     * @return bool TRUE on success or a PEAR error on failure
     */
    function mergeConfigFile($file, $override = true, $layer = 'user', $strict = true)
    {
        if (empty($this->files[$layer])) {
            return $this->raiseError("unknown config layer `$layer'");
        }

        if ($file === null) {
            $file = $this->files[$layer];
        }

        $data = $this->_readConfigDataFrom($file);
        if (PEAR::isError($data)) {
            if (!$strict) {
                return true;
            }

            $this->_errorsFound++;
            $this->lastError = $data;

            return $data;
        }

        $this->_decodeInput($data);
        if ($override) {
            $this->configuration[$layer] =
                PEAR_Config::arrayMergeRecursive($this->configuration[$layer], $data);
        } else {
            $this->configuration[$layer] =
                PEAR_Config::arrayMergeRecursive($data, $this->configuration[$layer]);
        }

        $this->_setupChannels();
        if (!$this->_noRegistry && ($phpdir = $this->get('php_dir', $layer, 'pear.php.net'))) {
            $this->_registry[$layer] = &new PEAR_Registry($phpdir);
            $this->_registry[$layer]->setConfig($this, false);
            $this->_regInitialized[$layer] = false;
        } else {
            unset($this->_registry[$layer]);
        }
        return true;
    }

    /**
     * @param array
     * @param array
     * @return array
     * @static
     */
    function arrayMergeRecursive($arr2, $arr1)
    {
        $ret = array();
        foreach ($arr2 as $key => $data) {
            if (!isset($arr1[$key])) {
                $ret[$key] = $data;
                unset($arr1[$key]);
                continue;
            }
            if (is_array($data)) {
                if (!is_array($arr1[$key])) {
                    $ret[$key] = $arr1[$key];
                    unset($arr1[$key]);
                    continue;
                }
                $ret[$key] = PEAR_Config::arrayMergeRecursive($arr1[$key], $arr2[$key]);
                unset($arr1[$key]);
            }
        }

        return array_merge($ret, $arr1);
    }

    /**
     * Writes data into a config layer from a file.
     *
     * @param string|null file to read from, or null for default
     * @param string config layer to insert data into ('user' or
     *               'system')
     * @param string|null data to write to config file or null for internal data [DEPRECATED]
     * @return bool TRUE on success or a PEAR error on failure
     */
    function writeConfigFile($file = null, $layer = 'user', $data = null)
    {
        $this->_lazyChannelSetup($layer);
        if ($layer == 'both' || $layer == 'all') {
            foreach ($this->files as $type => $file) {
                $err = $this->writeConfigFile($file, $type, $data);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }
            return true;
        }

        if (empty($this->files[$layer])) {
            return $this->raiseError("unknown config file type `$layer'");
        }

        if ($file === null) {
            $file = $this->files[$layer];
        }

        $data = ($data === null) ? $this->configuration[$layer] : $data;
        $this->_encodeOutput($data);
        $opt = array('-p', dirname($file));
        if (!@System::mkDir($opt)) {
            return $this->raiseError("could not create directory: " . dirname($file));
        }

        if (file_exists($file) && is_file($file) && !is_writeable($file)) {
            return $this->raiseError("no write access to $file!");
        }

        $fp = @fopen($file, "w");
        if (!$fp) {
            return $this->raiseError("PEAR_Config::writeConfigFile fopen('$file','w') failed ($php_errormsg)");
        }

        $contents = "#PEAR_Config 0.9\n" . serialize($data);
        if (!@fwrite($fp, $contents)) {
            return $this->raiseError("PEAR_Config::writeConfigFile: fwrite failed ($php_errormsg)");
        }
        return true;
    }

    /**
     * Reads configuration data from a file and returns the parsed data
     * in an array.
     *
     * @param string file to read from
     * @return array configuration data or a PEAR error on failure
     * @access private
     */
    function _readConfigDataFrom($file)
    {
        $fp = false;
        if (file_exists($file)) {
            $fp = @fopen($file, "r");
        }

        if (!$fp) {
            return $this->raiseError("PEAR_Config::readConfigFile fopen('$file','r') failed");
        }

        $size = filesize($file);
        $rt = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);
        fclose($fp);
        $contents = file_get_contents($file);
        if (empty($contents)) {
            return $this->raiseError('Configuration file "' . $file . '" is empty');
        }

        set_magic_quotes_runtime($rt);

        $version = false;
        if (preg_match('/^#PEAR_Config\s+(\S+)\s+/si', $contents, $matches)) {
            $version = $matches[1];
            $contents = substr($contents, strlen($matches[0]));
        } else {
            // Museum config file
            if (substr($contents,0,2) == 'a:') {
                $version = '0.1';
            }
        }

        if ($version && version_compare("$version", '1', '<')) {
            // no '@', it is possible that unserialize
            // raises a notice but it seems to block IO to
            // STDOUT if a '@' is used and a notice is raise
            $data = unserialize($contents);

            if (!is_array($data) && !$data) {
                if ($contents == serialize(false)) {
                    $data = array();
                } else {
                    $err = $this->raiseError("PEAR_Config: bad data in $file");
                    return $err;
                }
            }
            if (!is_array($data)) {
                if (strlen(trim($contents)) > 0) {
                    $error = "PEAR_Config: bad data in $file";
                    $err = $this->raiseError($error);
                    return $err;
                }

                $data = array();
            }
        // add parsing of newer formats here...
        } else {
            $err = $this->raiseError("$file: unknown version `$version'");
            return $err;
        }

        return $data;
    }

    /**
    * Gets the file used for storing the config for a layer
    *
    * @param string $layer 'user' or 'system'
    */
    function getConfFile($layer)
    {
        return $this->files[$layer];
    }

    /**
     * @param string Configuration class name, used for detecting duplicate calls
     * @param array information on a role as parsed from its xml file
     * @return true|PEAR_Error
     * @access private
     */
    function _addConfigVars($class, $vars)
    {
        static $called = array();
        if (isset($called[$class])) {
            return;
        }

        $called[$class] = 1;
        if (count($vars) > 3) {
            return $this->raiseError('Roles can only define 3 new config variables or less');
        }

        foreach ($vars as $name => $var) {
            if (!is_array($var)) {
                return $this->raiseError('Configuration information must be an array');
            }

            if (!isset($var['type'])) {
                return $this->raiseError('Configuration information must contain a type');
            } elseif (!in_array($var['type'],
                    array('string', 'mask', 'password', 'directory', 'file', 'set'))) {
                  return $this->raiseError(
                      'Configuration type must be one of directory, file, string, ' .
                      'mask, set, or password');
            }
            if (!isset($var['default'])) {
                return $this->raiseError(
                    'Configuration information must contain a default value ("default" index)');
            }

            if (is_array($var['default'])) {
                $real_default = '';
                foreach ($var['default'] as $config_var => $val) {
                    if (strpos($config_var, 'text') === 0) {
                        $real_default .= $val;
                    } elseif (strpos($config_var, 'constant') === 0) {
                        if (!defined($val)) {
                            return $this->raiseError(
                                'Unknown constant "' . $val . '" requested in ' .
                                'default value for configuration variable "' .
                                $name . '"');
                        }

                        $real_default .= constant($val);
                    } elseif (isset($this->configuration_info[$config_var])) {
                        $real_default .=
                            $this->configuration_info[$config_var]['default'];
                    } else {
                        return $this->raiseError(
                            'Unknown request for "' . $config_var . '" value in ' .
                            'default value for configuration variable "' .
                            $name . '"');
                    }
                }
                $var['default'] = $real_default;
            }

            if ($var['type'] == 'integer') {
                $var['default'] = (integer) $var['default'];
            }

            if (!isset($var['doc'])) {
                return $this->raiseError(
                    'Configuration information must contain a summary ("doc" index)');
            }

            if (!isset($var['prompt'])) {
                return $this->raiseError(
                    'Configuration information must contain a simple prompt ("prompt" index)');
            }

            if (!isset($var['group'])) {
                return $this->raiseError(
                    'Configuration information must contain a simple group ("group" index)');
            }

            if (isset($this->configuration_info[$name])) {
                return $this->raiseError('Configuration variable "' . $name .
                    '" already exists');
            }

            $this->configuration_info[$name] = $var;
            // fix bug #7351: setting custom config variable in a channel fails
            $this->_channelConfigInfo[] = $name;
        }

        return true;
    }

    /**
     * Encodes/scrambles configuration data before writing to files.
     * Currently, 'password' values will be base64-encoded as to avoid
     * that people spot cleartext passwords by accident.
     *
     * @param array (reference) array to encode values in
     * @return bool TRUE on success
     * @access private
     */
    function _encodeOutput(&$data)
    {
        foreach ($data as $key => $value) {
            if ($key == '__channels') {
                foreach ($data['__channels'] as $channel => $blah) {
                    $this->_encodeOutput($data['__channels'][$channel]);
                }
            }

            if (!isset($this->configuration_info[$key])) {
                continue;
            }

            $type = $this->configuration_info[$key]['type'];
            switch ($type) {
                // we base64-encode passwords so they are at least
                // not shown in plain by accident
                case 'password': {
                    $data[$key] = base64_encode($data[$key]);
                    break;
                }
                case 'mask': {
                    $data[$key] = octdec($data[$key]);
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Decodes/unscrambles configuration data after reading from files.
     *
     * @param array (reference) array to encode values in
     * @return bool TRUE on success
     * @access private
     *
     * @see PEAR_Config::_encodeOutput
     */
    function _decodeInput(&$data)
    {
        if (!is_array($data)) {
            return true;
        }

        foreach ($data as $key => $value) {
            if ($key == '__channels') {
                foreach ($data['__channels'] as $channel => $blah) {
                    $this->_decodeInput($data['__channels'][$channel]);
                }
            }

            if (!isset($this->configuration_info[$key])) {
                continue;
            }

            $type = $this->configuration_info[$key]['type'];
            switch ($type) {
                case 'password': {
                    $data[$key] = base64_decode($data[$key]);
                    break;
                }
                case 'mask': {
                    $data[$key] = decoct($data[$key]);
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Retrieve the default channel.
     *
     * On startup, channels are not initialized, so if the default channel is not
     * pear.php.net, then initialize the config.
     * @param string registry layer
     * @return string|false
     */
    function getDefaultChannel($layer = null)
    {
        $ret = false;
        if ($layer === null) {
            foreach ($this->layers as $layer) {
                if (isset($this->configuration[$layer]['default_channel'])) {
                    $ret = $this->configuration[$layer]['default_channel'];
                    break;
                }
            }
        } elseif (isset($this->configuration[$layer]['default_channel'])) {
            $ret = $this->configuration[$layer]['default_channel'];
        }

        if ($ret == 'pear.php.net' && defined('PEAR_RUNTYPE') && PEAR_RUNTYPE == 'pecl') {
            $ret = 'pecl.php.net';
        }

        if ($ret) {
            if ($ret != 'pear.php.net') {
                $this->_lazyChannelSetup();
            }

            return $ret;
        }

        return PEAR_CONFIG_DEFAULT_CHANNEL;
    }

    /**
     * Returns a configuration value, prioritizing layers as per the
     * layers property.
     *
     * @param string config key
     * @return mixed the config value, or NULL if not found
     * @access public
     */
    function get($key, $layer = null, $channel = false)
    {
        if (!isset($this->configuration_info[$key])) {
            return null;
        }

        if ($key == '__channels') {
            return null;
        }

        if ($key == 'default_channel') {
            return $this->getDefaultChannel($layer);
        }

        if (!$channel) {
            $channel = $this->getDefaultChannel();
        } elseif ($channel != 'pear.php.net') {
            $this->_lazyChannelSetup();
        }
        $channel = strtolower($channel);

        $test = (in_array($key, $this->_channelConfigInfo)) ?
            $this->_getChannelValue($key, $layer, $channel) :
            null;
        if ($test !== null) {
            if ($this->_installRoot) {
                if (in_array($this->getGroup($key),
                      array('File Locations', 'File Locations (Advanced)')) &&
                      $this->getType($key) == 'directory') {
                    return $this->_prependPath($test, $this->_installRoot);
                }
            }
            return $test;
        }

        if ($layer === null) {
            foreach ($this->layers as $layer) {
                if (isset($this->configuration[$layer][$key])) {
                    $test = $this->configuration[$layer][$key];
                    if ($this->_installRoot) {
                        if (in_array($this->getGroup($key),
                              array('File Locations', 'File Locations (Advanced)')) &&
                              $this->getType($key) == 'directory') {
                            return $this->_prependPath($test, $this->_installRoot);
                        }
                    }

                    if ($key == 'preferred_mirror') {
                        $reg = &$this->getRegistry();
                        if (is_object($reg)) {
                            $chan = &$reg->getChannel($channel);
                            if (PEAR::isError($chan)) {
                                return $channel;
                            }

                            if (!$chan->getMirror($test) && $chan->getName() != $test) {
                                return $channel; // mirror does not exist
                            }
                        }
                    }
                    return $test;
                }
            }
        } elseif (isset($this->configuration[$layer][$key])) {
            $test = $this->configuration[$layer][$key];
            if ($this->_installRoot) {
                if (in_array($this->getGroup($key),
                      array('File Locations', 'File Locations (Advanced)')) &&
                      $this->getType($key) == 'directory') {
                    return $this->_prependPath($test, $this->_installRoot);
                }
            }

            if ($key == 'preferred_mirror') {
                $reg = &$this->getRegistry();
                if (is_object($reg)) {
                    $chan = &$reg->getChannel($channel);
                    if (PEAR::isError($chan)) {
                        return $channel;
                    }

                    if (!$chan->getMirror($test) && $chan->getName() != $test) {
                        return $channel; // mirror does not exist
                    }
                }
            }

            return $test;
        }

        return null;
    }

    /**
     * Returns a channel-specific configuration value, prioritizing layers as per the
     * layers property.
     *
     * @param string config key
     * @return mixed the config value, or NULL if not found
     * @access private
     */
    function _getChannelValue($key, $layer, $channel)
    {
        if ($key == '__channels' || $channel == 'pear.php.net') {
            return null;
        }

        $ret = null;
        if ($layer === null) {
            foreach ($this->layers as $ilayer) {
                if (isset($this->configuration[$ilayer]['__channels'][$channel][$key])) {
                    $ret = $this->configuration[$ilayer]['__channels'][$channel][$key];
                    break;
                }
            }
        } elseif (isset($this->configuration[$layer]['__channels'][$channel][$key])) {
            $ret = $this->configuration[$layer]['__channels'][$channel][$key];
        }

        if ($key != 'preferred_mirror') {
            return $ret;
        }


        if ($ret !== null) {
            $reg = &$this->getRegistry($layer);
            if (is_object($reg)) {
                $chan = &$reg->getChannel($channel);
                if (PEAR::isError($chan)) {
                    return $channel;
                }

                if (!$chan->getMirror($ret) && $chan->getName() != $ret) {
                    return $channel; // mirror does not exist
                }
            }

            return $ret;
        }

        if ($channel != $this->getDefaultChannel($layer)) {
            return $channel; // we must use the channel name as the preferred mirror
                             // if the user has not chosen an alternate
        }

        return $this->getDefaultChannel($layer);
    }

    /**
     * Set a config value in a specific layer (defaults to 'user').
     * Enforces the types defined in the configuration_info array.  An
     * integer config variable will be cast to int, and a set config
     * variable will be validated against its legal values.
     *
     * @param string config key
     * @param string config value
     * @param string (optional) config layer
     * @param string channel to set this value for, or null for global value
     * @return bool TRUE on success, FALSE on failure
     */
    function set($key, $value, $layer = 'user', $channel = false)
    {
        if ($key == '__channels') {
            return false;
        }

        if (!isset($this->configuration[$layer])) {
            return false;
        }

        if ($key == 'default_channel') {
            // can only set this value globally
            $channel = 'pear.php.net';
            if ($value != 'pear.php.net') {
                $this->_lazyChannelSetup($layer);
            }
        }

        if ($key == 'preferred_mirror') {
            if ($channel == '__uri') {
                return false; // can't set the __uri pseudo-channel's mirror
            }

            $reg = &$this->getRegistry($layer);
            if (is_object($reg)) {
                $chan = &$reg->getChannel($channel ? $channel : 'pear.php.net');
                if (PEAR::isError($chan)) {
                    return false;
                }

                if (!$chan->getMirror($value) && $chan->getName() != $value) {
                    return false; // mirror does not exist
                }
            }
        }

        if (!isset($this->configuration_info[$key])) {
            return false;
        }

        extract($this->configuration_info[$key]);
        switch ($type) {
            case 'integer':
                $value = (int)$value;
                break;
            case 'set': {
                // If a valid_set is specified, require the value to
                // be in the set.  If there is no valid_set, accept
                // any value.
                if ($valid_set) {
                    reset($valid_set);
                    if ((key($valid_set) === 0 && !in_array($value, $valid_set)) ||
                        (key($valid_set) !== 0 && empty($valid_set[$value])))
                    {
                        return false;
                    }
                }
                break;
            }
        }

        if (!$channel) {
            $channel = $this->get('default_channel', null, 'pear.php.net');
        }

        if (!in_array($channel, $this->_channels)) {
            $this->_lazyChannelSetup($layer);
            $reg = &$this->getRegistry($layer);
            if ($reg) {
                $channel = $reg->channelName($channel);
            }

            if (!in_array($channel, $this->_channels)) {
                return false;
            }
        }

        if ($channel != 'pear.php.net') {
            if (in_array($key, $this->_channelConfigInfo)) {
                $this->configuration[$layer]['__channels'][$channel][$key] = $value;
                return true;
            }

            return false;
        }

        if ($key == 'default_channel') {
            if (!isset($reg)) {
                $reg = &$this->getRegistry($layer);
                if (!$reg) {
                    $reg = &$this->getRegistry();
                }
            }

            if ($reg) {
                $value = $reg->channelName($value);
            }

            if (!$value) {
                return false;
            }
        }

        $this->configuration[$layer][$key] = $value;
        if ($key == 'php_dir' && !$this->_noRegistry) {
            if (!isset($this->_registry[$layer]) ||
                  $value != $this->_registry[$layer]->install_dir) {
                $this->_registry[$layer] = &new PEAR_Registry($value);
                $this->_regInitialized[$layer] = false;
                $this->_registry[$layer]->setConfig($this, false);
            }
        }

        return true;
    }

    function _lazyChannelSetup($uselayer = false)
    {
        if ($this->_noRegistry) {
            return;
        }

        $merge = false;
        foreach ($this->_registry as $layer => $p) {
            if ($uselayer && $uselayer != $layer) {
                continue;
            }

            if (!$this->_regInitialized[$layer]) {
                if ($layer == 'default' && isset($this->_registry['user']) ||
                      isset($this->_registry['system'])) {
                    // only use the default registry if there are no alternatives
                    continue;
                }

                if (!is_object($this->_registry[$layer])) {
                    if ($phpdir = $this->get('php_dir', $layer, 'pear.php.net')) {
                        $this->_registry[$layer] = &new PEAR_Registry($phpdir);
                        $this->_registry[$layer]->setConfig($this, false);
                        $this->_regInitialized[$layer] = false;
                    } else {
                        unset($this->_registry[$layer]);
                        return;
                    }
                }

                $this->setChannels($this->_registry[$layer]->listChannels(), $merge);
                $this->_regInitialized[$layer] = true;
                $merge = true;
            }
        }
    }

    /**
     * Set the list of channels.
     *
     * This should be set via a call to {@link PEAR_Registry::listChannels()}
     * @param array
     * @param bool
     * @return bool success of operation
     */
    function setChannels($channels, $merge = false)
    {
        if (!is_array($channels)) {
            return false;
        }

        if ($merge) {
            $this->_channels = array_merge($this->_channels, $channels);
        } else {
            $this->_channels = $channels;
        }

        foreach ($channels as $channel) {
            $channel = strtolower($channel);
            if ($channel == 'pear.php.net') {
                continue;
            }

            foreach ($this->layers as $layer) {
                if (!isset($this->configuration[$layer]['__channels'])) {
                    $this->configuration[$layer]['__channels'] = array();
                }
                if (!isset($this->configuration[$layer]['__channels'][$channel])
                      || !is_array($this->configuration[$layer]['__channels'][$channel])) {
                    $this->configuration[$layer]['__channels'][$channel] = array();
                }
            }
        }

        return true;
    }

    /**
     * Get the type of a config value.
     *
     * @param string  config key
     *
     * @return string type, one of "string", "integer", "file",
     * "directory", "set" or "password".
     *
     * @access public
     *
     */
    function getType($key)
    {
        if (isset($this->configuration_info[$key])) {
            return $this->configuration_info[$key]['type'];
        }
        return false;
    }

    /**
     * Get the documentation for a config value.
     *
     * @param string  config key
     * @return string documentation string
     *
     * @access public
     *
     */
    function getDocs($key)
    {
        if (isset($this->configuration_info[$key])) {
            return $this->configuration_info[$key]['doc'];
        }

        return false;
    }

    /**
     * Get the short documentation for a config value.
     *
     * @param string  config key
     * @return string short documentation string
     *
     * @access public
     *
     */
    function getPrompt($key)
    {
        if (isset($this->configuration_info[$key])) {
            return $this->configuration_info[$key]['prompt'];
        }

        return false;
    }

    /**
     * Get the parameter group for a config key.
     *
     * @param string  config key
     * @return string parameter group
     *
     * @access public
     *
     */
    function getGroup($key)
    {
        if (isset($this->configuration_info[$key])) {
            return $this->configuration_info[$key]['group'];
        }

        return false;
    }

    /**
     * Get the list of parameter groups.
     *
     * @return array list of parameter groups
     *
     * @access public
     *
     */
    function getGroups()
    {
        $tmp = array();
        foreach ($this->configuration_info as $key => $info) {
            $tmp[$info['group']] = 1;
        }

        return array_keys($tmp);
    }

    /**
     * Get the list of the parameters in a group.
     *
     * @param string $group parameter group
     * @return array list of parameters in $group
     *
     * @access public
     *
     */
    function getGroupKeys($group)
    {
        $keys = array();
        foreach ($this->configuration_info as $key => $info) {
            if ($info['group'] == $group) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Get the list of allowed set values for a config value.  Returns
     * NULL for config values that are not sets.
     *
     * @param string  config key
     * @return array enumerated array of set values, or NULL if the
     *               config key is unknown or not a set
     *
     * @access public
     *
     */
    function getSetValues($key)
    {
        if (isset($this->configuration_info[$key]) &&
            isset($this->configuration_info[$key]['type']) &&
            $this->configuration_info[$key]['type'] == 'set')
        {
            $valid_set = $this->configuration_info[$key]['valid_set'];
            reset($valid_set);
            if (key($valid_set) === 0) {
                return $valid_set;
            }

            return array_keys($valid_set);
        }

        return null;
    }

    /**
     * Get all the current config keys.
     *
     * @return array simple array of config keys
     *
     * @access public
     */
    function getKeys()
    {
        $keys = array();
        foreach ($this->layers as $layer) {
            $test = $this->configuration[$layer];
            if (isset($test['__channels'])) {
                foreach ($test['__channels'] as $channel => $configs) {
                    $keys = array_merge($keys, $configs);
                }
            }

            unset($test['__channels']);
            $keys = array_merge($keys, $test);

        }
        return array_keys($keys);
    }

    /**
     * Remove the a config key from a specific config layer.
     *
     * @param string config key
     * @param string (optional) config layer
     * @param string (optional) channel (defaults to default channel)
     * @return bool TRUE on success, FALSE on failure
     *
     * @access public
     */
    function remove($key, $layer = 'user', $channel = null)
    {
        if ($channel === null) {
            $channel = $this->getDefaultChannel();
        }

        if ($channel !== 'pear.php.net') {
            if (isset($this->configuration[$layer]['__channels'][$channel][$key])) {
                unset($this->configuration[$layer]['__channels'][$channel][$key]);
                return true;
            }
        }

        if (isset($this->configuration[$layer][$key])) {
            unset($this->configuration[$layer][$key]);
            return true;
        }

        return false;
    }

    /**
     * Temporarily remove an entire config layer.  USE WITH CARE!
     *
     * @param string config key
     * @param string (optional) config layer
     * @return bool TRUE on success, FALSE on failure
     *
     * @access public
     */
    function removeLayer($layer)
    {
        if (isset($this->configuration[$layer])) {
            $this->configuration[$layer] = array();
            return true;
        }

        return false;
    }

    /**
     * Stores configuration data in a layer.
     *
     * @param string config layer to store
     * @return bool TRUE on success, or PEAR error on failure
     *
     * @access public
     */
    function store($layer = 'user', $data = null)
    {
        return $this->writeConfigFile(null, $layer, $data);
    }

    /**
     * Tells what config layer that gets to define a key.
     *
     * @param string config key
     * @param boolean return the defining channel
     *
     * @return string|array the config layer, or an empty string if not found.
     *
     *         if $returnchannel, the return is an array array('layer' => layername,
     *         'channel' => channelname), or an empty string if not found
     *
     * @access public
     */
    function definedBy($key, $returnchannel = false)
    {
        foreach ($this->layers as $layer) {
            $channel = $this->getDefaultChannel();
            if ($channel !== 'pear.php.net') {
                if (isset($this->configuration[$layer]['__channels'][$channel][$key])) {
                    if ($returnchannel) {
                        return array('layer' => $layer, 'channel' => $channel);
                    }
                    return $layer;
                }
            }

            if (isset($this->configuration[$layer][$key])) {
                if ($returnchannel) {
                    return array('layer' => $layer, 'channel' => 'pear.php.net');
                }
                return $layer;
            }
        }

        return '';
    }

    /**
     * Tells whether a given key exists as a config value.
     *
     * @param string config key
     * @return bool whether <config key> exists in this object
     *
     * @access public
     */
    function isDefined($key)
    {
        foreach ($this->layers as $layer) {
            if (isset($this->configuration[$layer][$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether a given config layer exists.
     *
     * @param string config layer
     * @return bool whether <config layer> exists in this object
     *
     * @access public
     */
    function isDefinedLayer($layer)
    {
        return isset($this->configuration[$layer]);
    }

    /**
     * Returns the layers defined (except the 'default' one)
     *
     * @return array of the defined layers
     */
    function getLayers()
    {
        $cf = $this->configuration;
        unset($cf['default']);
        return array_keys($cf);
    }

    function apiVersion()
    {
        return '1.1';
    }

    /**
     * @return PEAR_Registry
     */
    function &getRegistry($use = null)
    {
        $layer = $use === null ? 'user' : $use;
        if (isset($this->_registry[$layer])) {
            return $this->_registry[$layer];
        } elseif ($use === null && isset($this->_registry['system'])) {
            return $this->_registry['system'];
        } elseif ($use === null && isset($this->_registry['default'])) {
            return $this->_registry['default'];
        } elseif ($use) {
            $a = false;
            return $a;
        }

        // only go here if null was passed in
        echo "CRITICAL ERROR: Registry could not be initialized from any value";
        exit(1);
    }

    /**
     * This is to allow customization like the use of installroot
     * @param PEAR_Registry
     * @return bool
     */
    function setRegistry(&$reg, $layer = 'user')
    {
        if ($this->_noRegistry) {
            return false;
        }

        if (!in_array($layer, array('user', 'system'))) {
            return false;
        }

        $this->_registry[$layer] = &$reg;
        if (is_object($reg)) {
            $this->_registry[$layer]->setConfig($this, false);
        }

        return true;
    }

    function noRegistry()
    {
        $this->_noRegistry = true;
    }

    /**
     * @return PEAR_REST
     */
    function &getREST($version, $options = array())
    {
        $version = str_replace('.', '', $version);
        if (!class_exists($class = 'PEAR_REST_' . $version)) {
            require_once 'PEAR/REST/' . $version . '.php';
        }

        $remote = &new $class($this, $options);
        return $remote;
    }

    /**
     * The ftp server is set in {@link readFTPConfigFile()}.  It exists only if a
     * remote configuration file has been specified
     * @return PEAR_FTP|false
     */
    function &getFTP()
    {
        if (isset($this->_ftp)) {
            return $this->_ftp;
        }

        $a = false;
        return $a;
    }

    function _prependPath($path, $prepend)
    {
        if (strlen($prepend) > 0) {
            if (OS_WINDOWS && preg_match('/^[a-z]:/i', $path)) {
                if (preg_match('/^[a-z]:/i', $prepend)) {
                    $prepend = substr($prepend, 2);
                } elseif ($prepend{0} != '\\') {
                    $prepend = "\\$prepend";
                }
                $path = substr($path, 0, 2) . $prepend . substr($path, 2);
            } else {
                $path = $prepend . $path;
            }
        }
        return $path;
    }

    /**
     * @param string|false installation directory to prepend to all _dir variables, or false to
     *                     disable
     */
    function setInstallRoot($root)
    {
        if (substr($root, -1) == DIRECTORY_SEPARATOR) {
            $root = substr($root, 0, -1);
        }
        $old = $this->_installRoot;
        $this->_installRoot = $root;
        if (($old != $root) && !$this->_noRegistry) {
            foreach (array_keys($this->_registry) as $layer) {
                if ($layer == 'ftp' || !isset($this->_registry[$layer])) {
                    continue;
                }
                $this->_registry[$layer] =
                    &new PEAR_Registry($this->get('php_dir', $layer, 'pear.php.net'));
                $this->_registry[$layer]->setConfig($this, false);
                $this->_regInitialized[$layer] = false;
            }
        }
    }
}
