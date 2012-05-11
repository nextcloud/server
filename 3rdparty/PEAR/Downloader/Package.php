<?php
/**
 * PEAR_Downloader_Package
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Package.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */

/**
 * Error code when parameter initialization fails because no releases
 * exist within preferred_state, but releases do exist
 */
define('PEAR_DOWNLOADER_PACKAGE_STATE', -1003);
/**
 * Error code when parameter initialization fails because no releases
 * exist that will work with the existing PHP version
 */
define('PEAR_DOWNLOADER_PACKAGE_PHPVERSION', -1004);

/**
 * Coordinates download parameters and manages their dependencies
 * prior to downloading them.
 *
 * Input can come from three sources:
 *
 * - local files (archives or package.xml)
 * - remote files (downloadable urls)
 * - abstract package names
 *
 * The first two elements are handled cleanly by PEAR_PackageFile, but the third requires
 * accessing pearweb's xml-rpc interface to determine necessary dependencies, and the
 * format returned of dependencies is slightly different from that used in package.xml.
 *
 * This class hides the differences between these elements, and makes automatic
 * dependency resolution a piece of cake.  It also manages conflicts when
 * two classes depend on incompatible dependencies, or differing versions of the same
 * package dependency.  In addition, download will not be attempted if the php version is
 * not supported, PEAR installer version is not supported, or non-PECL extensions are not
 * installed.
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a1
 */
class PEAR_Downloader_Package
{
    /**
     * @var PEAR_Downloader
     */
    var $_downloader;
    /**
     * @var PEAR_Config
     */
    var $_config;
    /**
     * @var PEAR_Registry
     */
    var $_registry;
    /**
     * Used to implement packagingroot properly
     * @var PEAR_Registry
     */
    var $_installRegistry;
    /**
     * @var PEAR_PackageFile_v1|PEAR_PackageFile|v2
     */
    var $_packagefile;
    /**
     * @var array
     */
    var $_parsedname;
    /**
     * @var array
     */
    var $_downloadURL;
    /**
     * @var array
     */
    var $_downloadDeps = array();
    /**
     * @var boolean
     */
    var $_valid = false;
    /**
     * @var boolean
     */
    var $_analyzed = false;
    /**
     * if this or a parent package was invoked with Package-state, this is set to the
     * state variable.
     *
     * This allows temporary reassignment of preferred_state for a parent package and all of
     * its dependencies.
     * @var string|false
     */
    var $_explicitState = false;
    /**
     * If this package is invoked with Package#group, this variable will be true
     */
    var $_explicitGroup = false;
    /**
     * Package type local|url
     * @var string
     */
    var $_type;
    /**
     * Contents of package.xml, if downloaded from a remote channel
     * @var string|false
     * @access private
     */
    var $_rawpackagefile;
    /**
     * @var boolean
     * @access private
     */
    var $_validated = false;

    /**
     * @param PEAR_Downloader
     */
    function PEAR_Downloader_Package(&$downloader)
    {
        $this->_downloader = &$downloader;
        $this->_config = &$this->_downloader->config;
        $this->_registry = &$this->_config->getRegistry();
        $options = $downloader->getOptions();
        if (isset($options['packagingroot'])) {
            $this->_config->setInstallRoot($options['packagingroot']);
            $this->_installRegistry = &$this->_config->getRegistry();
            $this->_config->setInstallRoot(false);
        } else {
            $this->_installRegistry = &$this->_registry;
        }
        $this->_valid = $this->_analyzed = false;
    }

    /**
     * Parse the input and determine whether this is a local file, a remote uri, or an
     * abstract package name.
     *
     * This is the heart of the PEAR_Downloader_Package(), and is used in
     * {@link PEAR_Downloader::download()}
     * @param string
     * @return bool|PEAR_Error
     */
    function initialize($param)
    {
        $origErr = $this->_fromFile($param);
        if ($this->_valid) {
            return true;
        }

        $options = $this->_downloader->getOptions();
        if (isset($options['offline'])) {
            if (PEAR::isError($origErr) && !isset($options['soft'])) {
                foreach ($origErr->getUserInfo() as $userInfo) {
                    if (isset($userInfo['message'])) {
                        $this->_downloader->log(0, $userInfo['message']);
                    }
                }

                $this->_downloader->log(0, $origErr->getMessage());
            }

            return PEAR::raiseError('Cannot download non-local package "' . $param . '"');
        }

        $err = $this->_fromUrl($param);
        if (PEAR::isError($err) || !$this->_valid) {
            if ($this->_type == 'url') {
                if (PEAR::isError($err) && !isset($options['soft'])) {
                    $this->_downloader->log(0, $err->getMessage());
                }

                return PEAR::raiseError("Invalid or missing remote package file");
            }

            $err = $this->_fromString($param);
            if (PEAR::isError($err) || !$this->_valid) {
                if (PEAR::isError($err) && $err->getCode() == PEAR_DOWNLOADER_PACKAGE_STATE) {
                    return false; // instruct the downloader to silently skip
                }

                if (isset($this->_type) && $this->_type == 'local' && PEAR::isError($origErr)) {
                    if (is_array($origErr->getUserInfo())) {
                        foreach ($origErr->getUserInfo() as $err) {
                            if (is_array($err)) {
                                $err = $err['message'];
                            }

                            if (!isset($options['soft'])) {
                                $this->_downloader->log(0, $err);
                            }
                        }
                    }

                    if (!isset($options['soft'])) {
                        $this->_downloader->log(0, $origErr->getMessage());
                    }

                    if (is_array($param)) {
                        $param = $this->_registry->parsedPackageNameToString($param, true);
                    }

                    if (!isset($options['soft'])) {
                        $this->_downloader->log(2, "Cannot initialize '$param', invalid or missing package file");
                    }

                    // Passing no message back - already logged above
                    return PEAR::raiseError();
                }

                if (PEAR::isError($err) && !isset($options['soft'])) {
                    $this->_downloader->log(0, $err->getMessage());
                }

                if (is_array($param)) {
                    $param = $this->_registry->parsedPackageNameToString($param, true);
                }

                if (!isset($options['soft'])) {
                    $this->_downloader->log(2, "Cannot initialize '$param', invalid or missing package file");
                }

                // Passing no message back - already logged above
                return PEAR::raiseError();
            }
        }

        return true;
    }

    /**
     * Retrieve any non-local packages
     * @return PEAR_PackageFile_v1|PEAR_PackageFile_v2|PEAR_Error
     */
    function &download()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile;
        }

        if (isset($this->_downloadURL['url'])) {
            $this->_isvalid = false;
            $info = $this->getParsedPackage();
            foreach ($info as $i => $p) {
                $info[$i] = strtolower($p);
            }

            $err = $this->_fromUrl($this->_downloadURL['url'],
                $this->_registry->parsedPackageNameToString($this->_parsedname, true));
            $newinfo = $this->getParsedPackage();
            foreach ($newinfo as $i => $p) {
                $newinfo[$i] = strtolower($p);
            }

            if ($info != $newinfo) {
                do {
                    if ($info['channel'] == 'pecl.php.net' && $newinfo['channel'] == 'pear.php.net') {
                        $info['channel'] = 'pear.php.net';
                        if ($info == $newinfo) {
                            // skip the channel check if a pecl package says it's a PEAR package
                            break;
                        }
                    }
                    if ($info['channel'] == 'pear.php.net' && $newinfo['channel'] == 'pecl.php.net') {
                        $info['channel'] = 'pecl.php.net';
                        if ($info == $newinfo) {
                            // skip the channel check if a pecl package says it's a PEAR package
                            break;
                        }
                    }

                    return PEAR::raiseError('CRITICAL ERROR: We are ' .
                        $this->_registry->parsedPackageNameToString($info) . ', but the file ' .
                        'downloaded claims to be ' .
                        $this->_registry->parsedPackageNameToString($this->getParsedPackage()));
                } while (false);
            }

            if (PEAR::isError($err) || !$this->_valid) {
                return $err;
            }
        }

        $this->_type = 'local';
        return $this->_packagefile;
    }

    function &getPackageFile()
    {
        return $this->_packagefile;
    }

    function &getDownloader()
    {
        return $this->_downloader;
    }

    function getType()
    {
        return $this->_type;
    }

    /**
     * Like {@link initialize()}, but operates on a dependency
     */
    function fromDepURL($dep)
    {
        $this->_downloadURL = $dep;
        if (isset($dep['uri'])) {
            $options = $this->_downloader->getOptions();
            if (!extension_loaded("zlib") || isset($options['nocompress'])) {
                $ext = '.tar';
            } else {
                $ext = '.tgz';
            }

            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $err = $this->_fromUrl($dep['uri'] . $ext);
            PEAR::popErrorHandling();
            if (PEAR::isError($err)) {
                if (!isset($options['soft'])) {
                    $this->_downloader->log(0, $err->getMessage());
                }

                return PEAR::raiseError('Invalid uri dependency "' . $dep['uri'] . $ext . '", ' .
                    'cannot download');
            }
        } else {
            $this->_parsedname =
                array(
                    'package' => $dep['info']->getPackage(),
                    'channel' => $dep['info']->getChannel(),
                    'version' => $dep['version']
                );
            if (!isset($dep['nodefault'])) {
                $this->_parsedname['group'] = 'default'; // download the default dependency group
                $this->_explicitGroup = false;
            }

            $this->_rawpackagefile = $dep['raw'];
        }
    }

    function detectDependencies($params)
    {
        $options = $this->_downloader->getOptions();
        if (isset($options['downloadonly'])) {
            return;
        }

        if (isset($options['offline'])) {
            $this->_downloader->log(3, 'Skipping dependency download check, --offline specified');
            return;
        }

        $pname = $this->getParsedPackage();
        if (!$pname) {
            return;
        }

        $deps = $this->getDeps();
        if (!$deps) {
            return;
        }

        if (isset($deps['required'])) { // package.xml 2.0
            return $this->_detect2($deps, $pname, $options, $params);
        }

        return $this->_detect1($deps, $pname, $options, $params);
    }

    function setValidated()
    {
        $this->_validated = true;
    }

    function alreadyValidated()
    {
        return $this->_validated;
    }

    /**
     * Remove packages to be downloaded that are already installed
     * @param array of PEAR_Downloader_Package objects
     * @static
     */
    function removeInstalled(&$params)
    {
        if (!isset($params[0])) {
            return;
        }

        $options = $params[0]->_downloader->getOptions();
        if (!isset($options['downloadonly'])) {
            foreach ($params as $i => $param) {
                $package = $param->getPackage();
                $channel = $param->getChannel();
                // remove self if already installed with this version
                // this does not need any pecl magic - we only remove exact matches
                if ($param->_installRegistry->packageExists($package, $channel)) {
                    $packageVersion = $param->_installRegistry->packageInfo($package, 'version', $channel);
                    if (version_compare($packageVersion, $param->getVersion(), '==')) {
                        if (!isset($options['force'])) {
                            $info = $param->getParsedPackage();
                            unset($info['version']);
                            unset($info['state']);
                            if (!isset($options['soft'])) {
                                $param->_downloader->log(1, 'Skipping package "' .
                                    $param->getShortName() .
                                    '", already installed as version ' . $packageVersion);
                            }
                            $params[$i] = false;
                        }
                    } elseif (!isset($options['force']) && !isset($options['upgrade']) &&
                          !isset($options['soft'])) {
                        $info = $param->getParsedPackage();
                        $param->_downloader->log(1, 'Skipping package "' .
                            $param->getShortName() .
                            '", already installed as version ' . $packageVersion);
                        $params[$i] = false;
                    }
                }
            }
        }

        PEAR_Downloader_Package::removeDuplicates($params);
    }

    function _detect2($deps, $pname, $options, $params)
    {
        $this->_downloadDeps = array();
        $groupnotfound = false;
        foreach (array('package', 'subpackage') as $packagetype) {
            // get required dependency group
            if (isset($deps['required'][$packagetype])) {
                if (isset($deps['required'][$packagetype][0])) {
                    foreach ($deps['required'][$packagetype] as $dep) {
                        if (isset($dep['conflicts'])) {
                            // skip any package that this package conflicts with
                            continue;
                        }
                        $ret = $this->_detect2Dep($dep, $pname, 'required', $params);
                        if (is_array($ret)) {
                            $this->_downloadDeps[] = $ret;
                        } elseif (PEAR::isError($ret) && !isset($options['soft'])) {
                            $this->_downloader->log(0, $ret->getMessage());
                        }
                    }
                } else {
                    $dep = $deps['required'][$packagetype];
                    if (!isset($dep['conflicts'])) {
                        // skip any package that this package conflicts with
                        $ret = $this->_detect2Dep($dep, $pname, 'required', $params);
                        if (is_array($ret)) {
                            $this->_downloadDeps[] = $ret;
                        } elseif (PEAR::isError($ret) && !isset($options['soft'])) {
                            $this->_downloader->log(0, $ret->getMessage());
                        }
                    }
                }
            }

            // get optional dependency group, if any
            if (isset($deps['optional'][$packagetype])) {
                $skipnames = array();
                if (!isset($deps['optional'][$packagetype][0])) {
                    $deps['optional'][$packagetype] = array($deps['optional'][$packagetype]);
                }

                foreach ($deps['optional'][$packagetype] as $dep) {
                    $skip = false;
                    if (!isset($options['alldeps'])) {
                        $dep['package'] = $dep['name'];
                        if (!isset($options['soft'])) {
                            $this->_downloader->log(3, 'Notice: package "' .
                              $this->_registry->parsedPackageNameToString($this->getParsedPackage(),
                                    true) . '" optional dependency "' .
                                $this->_registry->parsedPackageNameToString(array('package' =>
                                    $dep['name'], 'channel' => 'pear.php.net'), true) .
                                '" will not be automatically downloaded');
                        }
                        $skipnames[] = $this->_registry->parsedPackageNameToString($dep, true);
                        $skip = true;
                        unset($dep['package']);
                    }

                    $ret = $this->_detect2Dep($dep, $pname, 'optional', $params);
                    if (PEAR::isError($ret) && !isset($options['soft'])) {
                        $this->_downloader->log(0, $ret->getMessage());
                    }

                    if (!$ret) {
                        $dep['package'] = $dep['name'];
                        $skip = count($skipnames) ?
                            $skipnames[count($skipnames) - 1] : '';
                        if ($skip ==
                              $this->_registry->parsedPackageNameToString($dep, true)) {
                            array_pop($skipnames);
                        }
                    }

                    if (!$skip && is_array($ret)) {
                        $this->_downloadDeps[] = $ret;
                    }
                }

                if (count($skipnames)) {
                    if (!isset($options['soft'])) {
                        $this->_downloader->log(1, 'Did not download optional dependencies: ' .
                            implode(', ', $skipnames) .
                            ', use --alldeps to download automatically');
                    }
                }
            }

            // get requested dependency group, if any
            $groupname = $this->getGroup();
            $explicit  = $this->_explicitGroup;
            if (!$groupname) {
                if (!$this->canDefault()) {
                    continue;
                }

                $groupname = 'default'; // try the default dependency group
            }

            if ($groupnotfound) {
                continue;
            }

            if (isset($deps['group'])) {
                if (isset($deps['group']['attribs'])) {
                    if (strtolower($deps['group']['attribs']['name']) == strtolower($groupname)) {
                        $group = $deps['group'];
                    } elseif ($explicit) {
                        if (!isset($options['soft'])) {
                            $this->_downloader->log(0, 'Warning: package "' .
                                $this->_registry->parsedPackageNameToString($pname, true) .
                                '" has no dependency ' . 'group named "' . $groupname . '"');
                        }

                        $groupnotfound = true;
                        continue;
                    }
                } else {
                    $found = false;
                    foreach ($deps['group'] as $group) {
                        if (strtolower($group['attribs']['name']) == strtolower($groupname)) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        if ($explicit) {
                            if (!isset($options['soft'])) {
                                $this->_downloader->log(0, 'Warning: package "' .
                                    $this->_registry->parsedPackageNameToString($pname, true) .
                                    '" has no dependency ' . 'group named "' . $groupname . '"');
                            }
                        }

                        $groupnotfound = true;
                        continue;
                    }
                }
            }

            if (isset($group) && isset($group[$packagetype])) {
                if (isset($group[$packagetype][0])) {
                    foreach ($group[$packagetype] as $dep) {
                        $ret = $this->_detect2Dep($dep, $pname, 'dependency group "' .
                            $group['attribs']['name'] . '"', $params);
                        if (is_array($ret)) {
                            $this->_downloadDeps[] = $ret;
                        } elseif (PEAR::isError($ret) && !isset($options['soft'])) {
                            $this->_downloader->log(0, $ret->getMessage());
                        }
                    }
                } else {
                    $ret = $this->_detect2Dep($group[$packagetype], $pname,
                        'dependency group "' .
                        $group['attribs']['name'] . '"', $params);
                    if (is_array($ret)) {
                        $this->_downloadDeps[] = $ret;
                    } elseif (PEAR::isError($ret) && !isset($options['soft'])) {
                        $this->_downloader->log(0, $ret->getMessage());
                    }
                }
            }
        }
    }

    function _detect2Dep($dep, $pname, $group, $params)
    {
        if (isset($dep['conflicts'])) {
            return true;
        }

        $options = $this->_downloader->getOptions();
        if (isset($dep['uri'])) {
            return array('uri' => $dep['uri'], 'dep' => $dep);;
        }

        $testdep = $dep;
        $testdep['package'] = $dep['name'];
        if (PEAR_Downloader_Package::willDownload($testdep, $params)) {
            $dep['package'] = $dep['name'];
            if (!isset($options['soft'])) {
                $this->_downloader->log(2, $this->getShortName() . ': Skipping ' . $group .
                    ' dependency "' .
                    $this->_registry->parsedPackageNameToString($dep, true) .
                    '", will be installed');
            }
            return false;
        }

        $options = $this->_downloader->getOptions();
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        if ($this->_explicitState) {
            $pname['state'] = $this->_explicitState;
        }

        $url = $this->_downloader->_getDepPackageDownloadUrl($dep, $pname);
        if (PEAR::isError($url)) {
            PEAR::popErrorHandling();
            return $url;
        }

        $dep['package'] = $dep['name'];
        $ret = $this->_analyzeDownloadURL($url, 'dependency', $dep, $params, $group == 'optional' &&
            !isset($options['alldeps']), true);
        PEAR::popErrorHandling();
        if (PEAR::isError($ret)) {
            if (!isset($options['soft'])) {
                $this->_downloader->log(0, $ret->getMessage());
            }

            return false;
        }

        // check to see if a dep is already installed and is the same or newer
        if (!isset($dep['min']) && !isset($dep['max']) && !isset($dep['recommended'])) {
            $oper = 'has';
        } else {
            $oper = 'gt';
        }

        // do not try to move this before getDepPackageDownloadURL
        // we can't determine whether upgrade is necessary until we know what
        // version would be downloaded
        if (!isset($options['force']) && $this->isInstalled($ret, $oper)) {
            $version = $this->_installRegistry->packageInfo($dep['name'], 'version', $dep['channel']);
            $dep['package'] = $dep['name'];
            if (!isset($options['soft'])) {
                $this->_downloader->log(3, $this->getShortName() . ': Skipping ' . $group .
                    ' dependency "' .
                $this->_registry->parsedPackageNameToString($dep, true) .
                    '" version ' . $url['version'] . ', already installed as version ' .
                    $version);
            }

            return false;
        }

        if (isset($dep['nodefault'])) {
            $ret['nodefault'] = true;
        }

        return $ret;
    }

    function _detect1($deps, $pname, $options, $params)
    {
        $this->_downloadDeps = array();
        $skipnames = array();
        foreach ($deps as $dep) {
            $nodownload = false;
            if (isset ($dep['type']) && $dep['type'] === 'pkg') {
                $dep['channel'] = 'pear.php.net';
                $dep['package'] = $dep['name'];
                switch ($dep['rel']) {
                    case 'not' :
                        continue 2;
                    case 'ge' :
                    case 'eq' :
                    case 'gt' :
                    case 'has' :
                        $group = (!isset($dep['optional']) || $dep['optional'] == 'no') ?
                            'required' :
                            'optional';
                        if (PEAR_Downloader_Package::willDownload($dep, $params)) {
                            $this->_downloader->log(2, $this->getShortName() . ': Skipping ' . $group
                                . ' dependency "' .
                                $this->_registry->parsedPackageNameToString($dep, true) .
                                '", will be installed');
                            continue 2;
                        }
                        $fakedp = new PEAR_PackageFile_v1;
                        $fakedp->setPackage($dep['name']);
                        // skip internet check if we are not upgrading (bug #5810)
                        if (!isset($options['upgrade']) && $this->isInstalled(
                              $fakedp, $dep['rel'])) {
                            $this->_downloader->log(2, $this->getShortName() . ': Skipping ' . $group
                                . ' dependency "' .
                                $this->_registry->parsedPackageNameToString($dep, true) .
                                '", is already installed');
                            continue 2;
                        }
                }

                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                if ($this->_explicitState) {
                    $pname['state'] = $this->_explicitState;
                }

                $url = $this->_downloader->_getDepPackageDownloadUrl($dep, $pname);
                $chan = 'pear.php.net';
                if (PEAR::isError($url)) {
                    // check to see if this is a pecl package that has jumped
                    // from pear.php.net to pecl.php.net channel
                    if (!class_exists('PEAR_Dependency2')) {
                        require_once 'PEAR/Dependency2.php';
                    }

                    $newdep = PEAR_Dependency2::normalizeDep($dep);
                    $newdep = $newdep[0];
                    $newdep['channel'] = 'pecl.php.net';
                    $chan = 'pecl.php.net';
                    $url = $this->_downloader->_getDepPackageDownloadUrl($newdep, $pname);
                    $obj = &$this->_installRegistry->getPackage($dep['name']);
                    if (PEAR::isError($url)) {
                        PEAR::popErrorHandling();
                        if ($obj !== null && $this->isInstalled($obj, $dep['rel'])) {
                            $group = (!isset($dep['optional']) || $dep['optional'] == 'no') ?
                                'required' :
                                'optional';
                            $dep['package'] = $dep['name'];
                            if (!isset($options['soft'])) {
                                $this->_downloader->log(3, $this->getShortName() .
                                    ': Skipping ' . $group . ' dependency "' .
                                    $this->_registry->parsedPackageNameToString($dep, true) .
                                    '", already installed as version ' . $obj->getVersion());
                            }
                            $skip = count($skipnames) ?
                                $skipnames[count($skipnames) - 1] : '';
                            if ($skip ==
                                  $this->_registry->parsedPackageNameToString($dep, true)) {
                                array_pop($skipnames);
                            }
                            continue;
                        } else {
                            if (isset($dep['optional']) && $dep['optional'] == 'yes') {
                                $this->_downloader->log(2, $this->getShortName() .
                                    ': Skipping optional dependency "' .
                                    $this->_registry->parsedPackageNameToString($dep, true) .
                                    '", no releases exist');
                                continue;
                            } else {
                                return $url;
                            }
                        }
                    }
                }

                PEAR::popErrorHandling();
                if (!isset($options['alldeps'])) {
                    if (isset($dep['optional']) && $dep['optional'] == 'yes') {
                        if (!isset($options['soft'])) {
                            $this->_downloader->log(3, 'Notice: package "' .
                                $this->getShortName() .
                                '" optional dependency "' .
                                $this->_registry->parsedPackageNameToString(
                                    array('channel' => $chan, 'package' =>
                                    $dep['name']), true) .
                                '" will not be automatically downloaded');
                        }
                        $skipnames[] = $this->_registry->parsedPackageNameToString(
                                array('channel' => $chan, 'package' =>
                                $dep['name']), true);
                        $nodownload = true;
                    }
                }

                if (!isset($options['alldeps']) && !isset($options['onlyreqdeps'])) {
                    if (!isset($dep['optional']) || $dep['optional'] == 'no') {
                        if (!isset($options['soft'])) {
                            $this->_downloader->log(3, 'Notice: package "' .
                                $this->getShortName() .
                                '" required dependency "' .
                                $this->_registry->parsedPackageNameToString(
                                    array('channel' => $chan, 'package' =>
                                    $dep['name']), true) .
                                '" will not be automatically downloaded');
                        }
                        $skipnames[] = $this->_registry->parsedPackageNameToString(
                                array('channel' => $chan, 'package' =>
                                $dep['name']), true);
                        $nodownload = true;
                    }
                }

                // check to see if a dep is already installed
                // do not try to move this before getDepPackageDownloadURL
                // we can't determine whether upgrade is necessary until we know what
                // version would be downloaded
                if (!isset($options['force']) && $this->isInstalled(
                        $url, $dep['rel'])) {
                    $group = (!isset($dep['optional']) || $dep['optional'] == 'no') ?
                        'required' :
                        'optional';
                    $dep['package'] = $dep['name'];
                    if (isset($newdep)) {
                        $version = $this->_installRegistry->packageInfo($newdep['name'], 'version', $newdep['channel']);
                    } else {
                        $version = $this->_installRegistry->packageInfo($dep['name'], 'version');
                    }

                    $dep['version'] = $url['version'];
                    if (!isset($options['soft'])) {
                        $this->_downloader->log(3, $this->getShortName() . ': Skipping ' . $group .
                            ' dependency "' .
                            $this->_registry->parsedPackageNameToString($dep, true) .
                            '", already installed as version ' . $version);
                    }

                    $skip = count($skipnames) ?
                        $skipnames[count($skipnames) - 1] : '';
                    if ($skip ==
                          $this->_registry->parsedPackageNameToString($dep, true)) {
                        array_pop($skipnames);
                    }

                    continue;
                }

                if ($nodownload) {
                    continue;
                }

                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                if (isset($newdep)) {
                    $dep = $newdep;
                }

                $dep['package'] = $dep['name'];
                $ret = $this->_analyzeDownloadURL($url, 'dependency', $dep, $params,
                    isset($dep['optional']) && $dep['optional'] == 'yes' &&
                    !isset($options['alldeps']), true);
                PEAR::popErrorHandling();
                if (PEAR::isError($ret)) {
                    if (!isset($options['soft'])) {
                        $this->_downloader->log(0, $ret->getMessage());
                    }
                    continue;
                }

                $this->_downloadDeps[] = $ret;
            }
        }

        if (count($skipnames)) {
            if (!isset($options['soft'])) {
                $this->_downloader->log(1, 'Did not download dependencies: ' .
                    implode(', ', $skipnames) .
                    ', use --alldeps or --onlyreqdeps to download automatically');
            }
        }
    }

    function setDownloadURL($pkg)
    {
        $this->_downloadURL = $pkg;
    }

    /**
     * Set the package.xml object for this downloaded package
     *
     * @param PEAR_PackageFile_v1|PEAR_PackageFile_v2 $pkg
     */
    function setPackageFile(&$pkg)
    {
        $this->_packagefile = &$pkg;
    }

    function getShortName()
    {
        return $this->_registry->parsedPackageNameToString(array('channel' => $this->getChannel(),
            'package' => $this->getPackage()), true);
    }

    function getParsedPackage()
    {
        if (isset($this->_packagefile) || isset($this->_parsedname)) {
            return array('channel' => $this->getChannel(),
                'package' => $this->getPackage(),
                'version' => $this->getVersion());
        }

        return false;
    }

    function getDownloadURL()
    {
        return $this->_downloadURL;
    }

    function canDefault()
    {
        if (isset($this->_downloadURL) && isset($this->_downloadURL['nodefault'])) {
            return false;
        }

        return true;
    }

    function getPackage()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getPackage();
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->getPackage();
        }

        return false;
    }

    /**
     * @param PEAR_PackageFile_v1|PEAR_PackageFile_v2
     */
    function isSubpackage(&$pf)
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->isSubpackage($pf);
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->isSubpackage($pf);
        }

        return false;
    }

    function getPackageType()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getPackageType();
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->getPackageType();
        }

        return false;
    }

    function isBundle()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getPackageType() == 'bundle';
        }

        return false;
    }

    function getPackageXmlVersion()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getPackagexmlVersion();
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->getPackagexmlVersion();
        }

        return '1.0';
    }

    function getChannel()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getChannel();
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->getChannel();
        }

        return false;
    }

    function getURI()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getURI();
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->getURI();
        }

        return false;
    }

    function getVersion()
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->getVersion();
        } elseif (isset($this->_downloadURL['version'])) {
            return $this->_downloadURL['version'];
        }

        return false;
    }

    function isCompatible($pf)
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->isCompatible($pf);
        } elseif (isset($this->_downloadURL['info'])) {
            return $this->_downloadURL['info']->isCompatible($pf);
        }

        return true;
    }

    function setGroup($group)
    {
        $this->_parsedname['group'] = $group;
    }

    function getGroup()
    {
        if (isset($this->_parsedname['group'])) {
            return $this->_parsedname['group'];
        }

        return '';
    }

    function isExtension($name)
    {
        if (isset($this->_packagefile)) {
            return $this->_packagefile->isExtension($name);
        } elseif (isset($this->_downloadURL['info'])) {
            if ($this->_downloadURL['info']->getPackagexmlVersion() == '2.0') {
                return $this->_downloadURL['info']->getProvidesExtension() == $name;
            }

            return false;
        }

        return false;
    }

    function getDeps()
    {
        if (isset($this->_packagefile)) {
            $ver = $this->_packagefile->getPackagexmlVersion();
            if (version_compare($ver, '2.0', '>=')) {
                return $this->_packagefile->getDeps(true);
            }

            return $this->_packagefile->getDeps();
        } elseif (isset($this->_downloadURL['info'])) {
            $ver = $this->_downloadURL['info']->getPackagexmlVersion();
            if (version_compare($ver, '2.0', '>=')) {
                return $this->_downloadURL['info']->getDeps(true);
            }

            return $this->_downloadURL['info']->getDeps();
        }

        return array();
    }

    /**
     * @param array Parsed array from {@link PEAR_Registry::parsePackageName()} or a dependency
     *                     returned from getDepDownloadURL()
     */
    function isEqual($param)
    {
        if (is_object($param)) {
            $channel = $param->getChannel();
            $package = $param->getPackage();
            if ($param->getURI()) {
                $param = array(
                    'channel' => $param->getChannel(),
                    'package' => $param->getPackage(),
                    'version' => $param->getVersion(),
                    'uri' => $param->getURI(),
                );
            } else {
                $param = array(
                    'channel' => $param->getChannel(),
                    'package' => $param->getPackage(),
                    'version' => $param->getVersion(),
                );
            }
        } else {
            if (isset($param['uri'])) {
                if ($this->getChannel() != '__uri') {
                    return false;
                }
                return $param['uri'] == $this->getURI();
            }

            $package = isset($param['package']) ? $param['package'] : $param['info']->getPackage();
            $channel = isset($param['channel']) ? $param['channel'] : $param['info']->getChannel();
            if (isset($param['rel'])) {
                if (!class_exists('PEAR_Dependency2')) {
                    require_once 'PEAR/Dependency2.php';
                }

                $newdep = PEAR_Dependency2::normalizeDep($param);
                $newdep = $newdep[0];
            } elseif (isset($param['min'])) {
                $newdep = $param;
            }
        }

        if (isset($newdep)) {
            if (!isset($newdep['min'])) {
                $newdep['min'] = '0';
            }

            if (!isset($newdep['max'])) {
                $newdep['max'] = '100000000000000000000';
            }

            // use magic to support pecl packages suddenly jumping to the pecl channel
            // we need to support both dependency possibilities
            if ($channel == 'pear.php.net' && $this->getChannel() == 'pecl.php.net') {
                if ($package == $this->getPackage()) {
                    $channel = 'pecl.php.net';
                }
            }
            if ($channel == 'pecl.php.net' && $this->getChannel() == 'pear.php.net') {
                if ($package == $this->getPackage()) {
                    $channel = 'pear.php.net';
                }
            }

            return (strtolower($package) == strtolower($this->getPackage()) &&
                $channel == $this->getChannel() &&
                version_compare($newdep['min'], $this->getVersion(), '<=') &&
                version_compare($newdep['max'], $this->getVersion(), '>='));
        }

        // use magic to support pecl packages suddenly jumping to the pecl channel
        if ($channel == 'pecl.php.net' && $this->getChannel() == 'pear.php.net') {
            if (strtolower($package) == strtolower($this->getPackage())) {
                $channel = 'pear.php.net';
            }
        }

        if (isset($param['version'])) {
            return (strtolower($package) == strtolower($this->getPackage()) &&
                $channel == $this->getChannel() &&
                $param['version'] == $this->getVersion());
        }

        return strtolower($package) == strtolower($this->getPackage()) &&
            $channel == $this->getChannel();
    }

    function isInstalled($dep, $oper = '==')
    {
        if (!$dep) {
            return false;
        }

        if ($oper != 'ge' && $oper != 'gt' && $oper != 'has' && $oper != '==') {
            return false;
        }

        if (is_object($dep)) {
            $package = $dep->getPackage();
            $channel = $dep->getChannel();
            if ($dep->getURI()) {
                $dep = array(
                    'uri' => $dep->getURI(),
                    'version' => $dep->getVersion(),
                );
            } else {
                $dep = array(
                    'version' => $dep->getVersion(),
                );
            }
        } else {
            if (isset($dep['uri'])) {
                $channel = '__uri';
                $package = $dep['dep']['name'];
            } else {
                $channel = $dep['info']->getChannel();
                $package = $dep['info']->getPackage();
            }
        }

        $options = $this->_downloader->getOptions();
        $test    = $this->_installRegistry->packageExists($package, $channel);
        if (!$test && $channel == 'pecl.php.net') {
            // do magic to allow upgrading from old pecl packages to new ones
            $test = $this->_installRegistry->packageExists($package, 'pear.php.net');
            $channel = 'pear.php.net';
        }

        if ($test) {
            if (isset($dep['uri'])) {
                if ($this->_installRegistry->packageInfo($package, 'uri', '__uri') == $dep['uri']) {
                    return true;
                }
            }

            if (isset($options['upgrade'])) {
                $packageVersion = $this->_installRegistry->packageInfo($package, 'version', $channel);
                if (version_compare($packageVersion, $dep['version'], '>=')) {
                    return true;
                }

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Detect duplicate package names with differing versions
     *
     * If a user requests to install Date 1.4.6 and Date 1.4.7,
     * for instance, this is a logic error.  This method
     * detects this situation.
     *
     * @param array $params array of PEAR_Downloader_Package objects
     * @param array $errorparams empty array
     * @return array array of stupid duplicated packages in PEAR_Downloader_Package obejcts
     */
    function detectStupidDuplicates($params, &$errorparams)
    {
        $existing = array();
        foreach ($params as $i => $param) {
            $package = $param->getPackage();
            $channel = $param->getChannel();
            $group   = $param->getGroup();
            if (!isset($existing[$channel . '/' . $package])) {
                $existing[$channel . '/' . $package] = array();
            }

            if (!isset($existing[$channel . '/' . $package][$group])) {
                $existing[$channel . '/' . $package][$group] = array();
            }

            $existing[$channel . '/' . $package][$group][] = $i;
        }

        $indices = array();
        foreach ($existing as $package => $groups) {
            foreach ($groups as $group => $dupes) {
                if (count($dupes) > 1) {
                    $indices = $indices + $dupes;
                }
            }
        }

        $indices = array_unique($indices);
        foreach ($indices as $index) {
            $errorparams[] = $params[$index];
        }

        return count($errorparams);
    }

    /**
     * @param array
     * @param bool ignore install groups - for final removal of dupe packages
     * @static
     */
    function removeDuplicates(&$params, $ignoreGroups = false)
    {
        $pnames = array();
        foreach ($params as $i => $param) {
            if (!$param) {
                continue;
            }

            if ($param->getPackage()) {
                $group = $ignoreGroups ? '' : $param->getGroup();
                $pnames[$i] = $param->getChannel() . '/' .
                    $param->getPackage() . '-' . $param->getVersion() . '#' . $group;
            }
        }

        $pnames = array_unique($pnames);
        $unset  = array_diff(array_keys($params), array_keys($pnames));
        $testp  = array_flip($pnames);
        foreach ($params as $i => $param) {
            if (!$param) {
                $unset[] = $i;
                continue;
            }

            if (!is_a($param, 'PEAR_Downloader_Package')) {
                $unset[] = $i;
                continue;
            }

            $group = $ignoreGroups ? '' : $param->getGroup();
            if (!isset($testp[$param->getChannel() . '/' . $param->getPackage() . '-' .
                  $param->getVersion() . '#' . $group])) {
                $unset[] = $i;
            }
        }

        foreach ($unset as $i) {
            unset($params[$i]);
        }

        $ret = array();
        foreach ($params as $i => $param) {
            $ret[] = &$params[$i];
        }

        $params = array();
        foreach ($ret as $i => $param) {
            $params[] = &$ret[$i];
        }
    }

    function explicitState()
    {
        return $this->_explicitState;
    }

    function setExplicitState($s)
    {
        $this->_explicitState = $s;
    }

    /**
     * @static
     */
    function mergeDependencies(&$params)
    {
        $bundles = $newparams = array();
        foreach ($params as $i => $param) {
            if (!$param->isBundle()) {
                continue;
            }

            $bundles[] = $i;
            $pf = &$param->getPackageFile();
            $newdeps = array();
            $contents = $pf->getBundledPackages();
            if (!is_array($contents)) {
                $contents = array($contents);
            }

            foreach ($contents as $file) {
                $filecontents = $pf->getFileContents($file);
                $dl = &$param->getDownloader();
                $options = $dl->getOptions();
                if (PEAR::isError($dir = $dl->getDownloadDir())) {
                    return $dir;
                }

                $fp = @fopen($dir . DIRECTORY_SEPARATOR . $file, 'wb');
                if (!$fp) {
                    continue;
                }

                // FIXME do symlink check

                fwrite($fp, $filecontents, strlen($filecontents));
                fclose($fp);
                if ($s = $params[$i]->explicitState()) {
                    $obj->setExplicitState($s);
                }

                $obj = &new PEAR_Downloader_Package($params[$i]->getDownloader());
                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                if (PEAR::isError($dir = $dl->getDownloadDir())) {
                    PEAR::popErrorHandling();
                    return $dir;
                }

                $e = $obj->_fromFile($a = $dir . DIRECTORY_SEPARATOR . $file);
                PEAR::popErrorHandling();
                if (PEAR::isError($e)) {
                    if (!isset($options['soft'])) {
                        $dl->log(0, $e->getMessage());
                    }
                    continue;
                }

                $j = &$obj;
                if (!PEAR_Downloader_Package::willDownload($j,
                      array_merge($params, $newparams)) && !$param->isInstalled($j)) {
                    $newparams[] = &$j;
                }
            }
        }

        foreach ($bundles as $i) {
            unset($params[$i]); // remove bundles - only their contents matter for installation
        }

        PEAR_Downloader_Package::removeDuplicates($params); // strip any unset indices
        if (count($newparams)) { // add in bundled packages for install
            foreach ($newparams as $i => $unused) {
                $params[] = &$newparams[$i];
            }
            $newparams = array();
        }

        foreach ($params as $i => $param) {
            $newdeps = array();
            foreach ($param->_downloadDeps as $dep) {
                $merge = array_merge($params, $newparams);
                if (!PEAR_Downloader_Package::willDownload($dep, $merge)
                    && !$param->isInstalled($dep)
                ) {
                    $newdeps[] = $dep;
                } else {
                    //var_dump($dep);
                    // detect versioning conflicts here
                }
            }

            // convert the dependencies into PEAR_Downloader_Package objects for the next time around
            $params[$i]->_downloadDeps = array();
            foreach ($newdeps as $dep) {
                $obj = &new PEAR_Downloader_Package($params[$i]->getDownloader());
                if ($s = $params[$i]->explicitState()) {
                    $obj->setExplicitState($s);
                }

                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                $e = $obj->fromDepURL($dep);
                PEAR::popErrorHandling();
                if (PEAR::isError($e)) {
                    if (!isset($options['soft'])) {
                        $obj->_downloader->log(0, $e->getMessage());
                    }
                    continue;
                }

                $e = $obj->detectDependencies($params);
                if (PEAR::isError($e)) {
                    if (!isset($options['soft'])) {
                        $obj->_downloader->log(0, $e->getMessage());
                    }
                }

                $j = &$obj;
                $newparams[] = &$j;
            }
        }

        if (count($newparams)) {
            foreach ($newparams as $i => $unused) {
                $params[] = &$newparams[$i];
            }
            return true;
        }

        return false;
    }


    /**
     * @static
     */
    function willDownload($param, $params)
    {
        if (!is_array($params)) {
            return false;
        }

        foreach ($params as $obj) {
            if ($obj->isEqual($param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * For simpler unit-testing
     * @param PEAR_Config
     * @param int
     * @param string
     */
    function &getPackagefileObject(&$c, $d)
    {
        $a = &new PEAR_PackageFile($c, $d);
        return $a;
    }

    /**
     * This will retrieve from a local file if possible, and parse out
     * a group name as well.  The original parameter will be modified to reflect this.
     * @param string|array can be a parsed package name as well
     * @access private
     */
    function _fromFile(&$param)
    {
        $saveparam = $param;
        if (is_string($param)) {
            if (!@file_exists($param)) {
                $test = explode('#', $param);
                $group = array_pop($test);
                if (@file_exists(implode('#', $test))) {
                    $this->setGroup($group);
                    $param = implode('#', $test);
                    $this->_explicitGroup = true;
                }
            }

            if (@is_file($param)) {
                $this->_type = 'local';
                $options = $this->_downloader->getOptions();
                $pkg = &$this->getPackagefileObject($this->_config, $this->_downloader->_debug);
                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                $pf = &$pkg->fromAnyFile($param, PEAR_VALIDATE_INSTALLING);
                PEAR::popErrorHandling();
                if (PEAR::isError($pf)) {
                    $this->_valid = false;
                    $param = $saveparam;
                    return $pf;
                }
                $this->_packagefile = &$pf;
                if (!$this->getGroup()) {
                    $this->setGroup('default'); // install the default dependency group
                }
                return $this->_valid = true;
            }
        }
        $param = $saveparam;
        return $this->_valid = false;
    }

    function _fromUrl($param, $saveparam = '')
    {
        if (!is_array($param) && (preg_match('#^(http|https|ftp)://#', $param))) {
            $options = $this->_downloader->getOptions();
            $this->_type = 'url';
            $callback = $this->_downloader->ui ?
                array(&$this->_downloader, '_downloadCallback') : null;
            $this->_downloader->pushErrorHandling(PEAR_ERROR_RETURN);
            if (PEAR::isError($dir = $this->_downloader->getDownloadDir())) {
                $this->_downloader->popErrorHandling();
                return $dir;
            }

            $this->_downloader->log(3, 'Downloading "' . $param . '"');
            $file = $this->_downloader->downloadHttp($param, $this->_downloader->ui,
                $dir, $callback, null, false, $this->getChannel());
            $this->_downloader->popErrorHandling();
            if (PEAR::isError($file)) {
                if (!empty($saveparam)) {
                    $saveparam = ", cannot download \"$saveparam\"";
                }
                $err = PEAR::raiseError('Could not download from "' . $param .
                    '"' . $saveparam . ' (' . $file->getMessage() . ')');
                    return $err;
            }

            if ($this->_rawpackagefile) {
                require_once 'Archive/Tar.php';
                $tar = &new Archive_Tar($file);
                $packagexml = $tar->extractInString('package2.xml');
                if (!$packagexml) {
                    $packagexml = $tar->extractInString('package.xml');
                }

                if (str_replace(array("\n", "\r"), array('',''), $packagexml) !=
                      str_replace(array("\n", "\r"), array('',''), $this->_rawpackagefile)) {
                    if ($this->getChannel() != 'pear.php.net') {
                        return PEAR::raiseError('CRITICAL ERROR: package.xml downloaded does ' .
                            'not match value returned from xml-rpc');
                    }

                    // be more lax for the existing PEAR packages that have not-ok
                    // characters in their package.xml
                    $this->_downloader->log(0, 'CRITICAL WARNING: The "' .
                        $this->getPackage() . '" package has invalid characters in its ' .
                        'package.xml.  The next version of PEAR may not be able to install ' .
                        'this package for security reasons.  Please open a bug report at ' .
                        'http://pear.php.net/package/' . $this->getPackage() . '/bugs');
                }
            }

            // whew, download worked!
            $pkg = &$this->getPackagefileObject($this->_config, $this->_downloader->debug);

            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $pf = &$pkg->fromAnyFile($file, PEAR_VALIDATE_INSTALLING);
            PEAR::popErrorHandling();
            if (PEAR::isError($pf)) {
                if (is_array($pf->getUserInfo())) {
                    foreach ($pf->getUserInfo() as $err) {
                        if (is_array($err)) {
                            $err = $err['message'];
                        }

                        if (!isset($options['soft'])) {
                            $this->_downloader->log(0, "Validation Error: $err");
                        }
                    }
                }

                if (!isset($options['soft'])) {
                    $this->_downloader->log(0, $pf->getMessage());
                }

                ///FIXME need to pass back some error code that we can use to match with to cancel all further operations
                /// At least stop all deps of this package from being installed
                $out = $saveparam ? $saveparam : $param;
                $err = PEAR::raiseError('Download of "' . $out . '" succeeded, but it is not a valid package archive');
                $this->_valid = false;
                return $err;
            }

            $this->_packagefile = &$pf;
            $this->setGroup('default'); // install the default dependency group
            return $this->_valid = true;
        }

        return $this->_valid = false;
    }

    /**
     *
     * @param string|array pass in an array of format
     *                     array(
     *                      'package' => 'pname',
     *                     ['channel' => 'channame',]
     *                     ['version' => 'version',]
     *                     ['state' => 'state',])
     *                     or a string of format [channame/]pname[-version|-state]
     */
    function _fromString($param)
    {
        $options = $this->_downloader->getOptions();
        $channel = $this->_config->get('default_channel');
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        $pname = $this->_registry->parsePackageName($param, $channel);
        PEAR::popErrorHandling();
        if (PEAR::isError($pname)) {
            if ($pname->getCode() == 'invalid') {
                $this->_valid = false;
                return false;
            }

            if ($pname->getCode() == 'channel') {
                $parsed = $pname->getUserInfo();
                if ($this->_downloader->discover($parsed['channel'])) {
                    if ($this->_config->get('auto_discover')) {
                        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                        $pname = $this->_registry->parsePackageName($param, $channel);
                        PEAR::popErrorHandling();
                    } else {
                        if (!isset($options['soft'])) {
                            $this->_downloader->log(0, 'Channel "' . $parsed['channel'] .
                                '" is not initialized, use ' .
                                '"pear channel-discover ' . $parsed['channel'] . '" to initialize' .
                                'or pear config-set auto_discover 1');
                        }
                    }
                }

                if (PEAR::isError($pname)) {
                    if (!isset($options['soft'])) {
                        $this->_downloader->log(0, $pname->getMessage());
                    }

                    if (is_array($param)) {
                        $param = $this->_registry->parsedPackageNameToString($param);
                    }

                    $err = PEAR::raiseError('invalid package name/package file "' . $param . '"');
                    $this->_valid = false;
                    return $err;
                }
            } else {
                if (!isset($options['soft'])) {
                    $this->_downloader->log(0, $pname->getMessage());
                }

                $err = PEAR::raiseError('invalid package name/package file "' . $param . '"');
                $this->_valid = false;
                return $err;
            }
        }

        if (!isset($this->_type)) {
            $this->_type = 'rest';
        }

        $this->_parsedname    = $pname;
        $this->_explicitState = isset($pname['state']) ? $pname['state'] : false;
        $this->_explicitGroup = isset($pname['group']) ? true : false;

        $info = $this->_downloader->_getPackageDownloadUrl($pname);
        if (PEAR::isError($info)) {
            if ($info->getCode() != -976 && $pname['channel'] == 'pear.php.net') {
                // try pecl
                $pname['channel'] = 'pecl.php.net';
                if ($test = $this->_downloader->_getPackageDownloadUrl($pname)) {
                    if (!PEAR::isError($test)) {
                        $info = PEAR::raiseError($info->getMessage() . ' - package ' .
                            $this->_registry->parsedPackageNameToString($pname, true) .
                            ' can be installed with "pecl install ' . $pname['package'] .
                            '"');
                    } else {
                        $pname['channel'] = 'pear.php.net';
                    }
                } else {
                    $pname['channel'] = 'pear.php.net';
                }
            }

            return $info;
        }

        $this->_rawpackagefile = $info['raw'];
        $ret = $this->_analyzeDownloadURL($info, $param, $pname);
        if (PEAR::isError($ret)) {
            return $ret;
        }

        if ($ret) {
            $this->_downloadURL = $ret;
            return $this->_valid = (bool) $ret;
        }
    }

    /**
     * @param array output of package.getDownloadURL
     * @param string|array|object information for detecting packages to be downloaded, and
     *                            for errors
     * @param array name information of the package
     * @param array|null packages to be downloaded
     * @param bool is this an optional dependency?
     * @param bool is this any kind of dependency?
     * @access private
     */
    function _analyzeDownloadURL($info, $param, $pname, $params = null, $optional = false,
                                 $isdependency = false)
    {
        if (!is_string($param) && PEAR_Downloader_Package::willDownload($param, $params)) {
            return false;
        }

        if ($info === false) {
            $saveparam = !is_string($param) ? ", cannot download \"$param\"" : '';

            // no releases exist
            return PEAR::raiseError('No releases for package "' .
                $this->_registry->parsedPackageNameToString($pname, true) . '" exist' . $saveparam);
        }

        if (strtolower($info['info']->getChannel()) != strtolower($pname['channel'])) {
            $err = false;
            if ($pname['channel'] == 'pecl.php.net') {
                if ($info['info']->getChannel() != 'pear.php.net') {
                    $err = true;
                }
            } elseif ($info['info']->getChannel() == 'pecl.php.net') {
                if ($pname['channel'] != 'pear.php.net') {
                    $err = true;
                }
            } else {
                $err = true;
            }

            if ($err) {
                return PEAR::raiseError('SECURITY ERROR: package in channel "' . $pname['channel'] .
                    '" retrieved another channel\'s name for download! ("' .
                    $info['info']->getChannel() . '")');
            }
        }

        $preferred_state = $this->_config->get('preferred_state');
        if (!isset($info['url'])) {
            $package_version = $this->_registry->packageInfo($info['info']->getPackage(),
            'version', $info['info']->getChannel());
            if ($this->isInstalled($info)) {
                if ($isdependency && version_compare($info['version'], $package_version, '<=')) {
                    // ignore bogus errors of "failed to download dependency"
                    // if it is already installed and the one that would be
                    // downloaded is older or the same version (Bug #7219)
                    return false;
                }
            }

            if ($info['version'] === $package_version) {
                if (!isset($options['soft'])) {
                    $this->_downloader->log(1, 'WARNING: failed to download ' . $pname['channel'] .
                        '/' . $pname['package'] . '-' . $package_version. ', additionally the suggested version' .
                        ' (' . $package_version . ') is the same as the locally installed one.');
                }

                return false;
            }

            if (version_compare($info['version'], $package_version, '<=')) {
                if (!isset($options['soft'])) {
                    $this->_downloader->log(1, 'WARNING: failed to download ' . $pname['channel'] .
                        '/' . $pname['package'] . '-' . $package_version . ', additionally the suggested version' .
                        ' (' . $info['version'] . ') is a lower version than the locally installed one (' . $package_version . ').');
                }

                return false;
            }

            $instead =  ', will instead download version ' . $info['version'] .
                        ', stability "' . $info['info']->getState() . '"';
            // releases exist, but we failed to get any
            if (isset($this->_downloader->_options['force'])) {
                if (isset($pname['version'])) {
                    $vs = ', version "' . $pname['version'] . '"';
                } elseif (isset($pname['state'])) {
                    $vs = ', stability "' . $pname['state'] . '"';
                } elseif ($param == 'dependency') {
                    if (!class_exists('PEAR_Common')) {
                        require_once 'PEAR/Common.php';
                    }

                    if (!in_array($info['info']->getState(),
                          PEAR_Common::betterStates($preferred_state, true))) {
                        if ($optional) {
                            // don't spit out confusing error message
                            return $this->_downloader->_getPackageDownloadUrl(
                                array('package' => $pname['package'],
                                      'channel' => $pname['channel'],
                                      'version' => $info['version']));
                        }
                        $vs = ' within preferred state "' . $preferred_state .
                            '"';
                    } else {
                        if (!class_exists('PEAR_Dependency2')) {
                            require_once 'PEAR/Dependency2.php';
                        }

                        if ($optional) {
                            // don't spit out confusing error message
                            return $this->_downloader->_getPackageDownloadUrl(
                                array('package' => $pname['package'],
                                      'channel' => $pname['channel'],
                                      'version' => $info['version']));
                        }
                        $vs = PEAR_Dependency2::_getExtraString($pname);
                        $instead = '';
                    }
                } else {
                    $vs = ' within preferred state "' . $preferred_state . '"';
                }

                if (!isset($options['soft'])) {
                    $this->_downloader->log(1, 'WARNING: failed to download ' . $pname['channel'] .
                        '/' . $pname['package'] . $vs . $instead);
                }

                // download the latest release
                return $this->_downloader->_getPackageDownloadUrl(
                    array('package' => $pname['package'],
                          'channel' => $pname['channel'],
                          'version' => $info['version']));
            } else {
                if (isset($info['php']) && $info['php']) {
                    $err = PEAR::raiseError('Failed to download ' .
                        $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'],
                                  'package' => $pname['package']),
                                true) .
                        ', latest release is version ' . $info['php']['v'] .
                        ', but it requires PHP version "' .
                        $info['php']['m'] . '", use "' .
                        $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'], 'package' => $pname['package'],
                            'version' => $info['php']['v'])) . '" to install',
                            PEAR_DOWNLOADER_PACKAGE_PHPVERSION);
                    return $err;
                }

                // construct helpful error message
                if (isset($pname['version'])) {
                    $vs = ', version "' . $pname['version'] . '"';
                } elseif (isset($pname['state'])) {
                    $vs = ', stability "' . $pname['state'] . '"';
                } elseif ($param == 'dependency') {
                    if (!class_exists('PEAR_Common')) {
                        require_once 'PEAR/Common.php';
                    }

                    if (!in_array($info['info']->getState(),
                          PEAR_Common::betterStates($preferred_state, true))) {
                        if ($optional) {
                            // don't spit out confusing error message, and don't die on
                            // optional dep failure!
                            return $this->_downloader->_getPackageDownloadUrl(
                                array('package' => $pname['package'],
                                      'channel' => $pname['channel'],
                                      'version' => $info['version']));
                        }
                        $vs = ' within preferred state "' . $preferred_state . '"';
                    } else {
                        if (!class_exists('PEAR_Dependency2')) {
                            require_once 'PEAR/Dependency2.php';
                        }

                        if ($optional) {
                            // don't spit out confusing error message, and don't die on
                            // optional dep failure!
                            return $this->_downloader->_getPackageDownloadUrl(
                                array('package' => $pname['package'],
                                      'channel' => $pname['channel'],
                                      'version' => $info['version']));
                        }
                        $vs = PEAR_Dependency2::_getExtraString($pname);
                    }
                } else {
                    $vs = ' within preferred state "' . $this->_downloader->config->get('preferred_state') . '"';
                }

                $options = $this->_downloader->getOptions();
                // this is only set by the "download-all" command
                if (isset($options['ignorepreferred_state'])) {
                    $err = PEAR::raiseError(
                        'Failed to download ' . $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'], 'package' => $pname['package']),
                                true)
                         . $vs .
                        ', latest release is version ' . $info['version'] .
                        ', stability "' . $info['info']->getState() . '", use "' .
                        $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'], 'package' => $pname['package'],
                            'version' => $info['version'])) . '" to install',
                            PEAR_DOWNLOADER_PACKAGE_STATE);
                    return $err;
                }

                // Checks if the user has a package installed already and checks the release against
                // the state against the installed package, this allows upgrades for packages
                // with lower stability than the preferred_state
                $stability = $this->_registry->packageInfo($pname['package'], 'stability', $pname['channel']);
                if (!$this->isInstalled($info)
                    || !in_array($info['info']->getState(), PEAR_Common::betterStates($stability['release'], true))
                ) {
                    $err = PEAR::raiseError(
                        'Failed to download ' . $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'], 'package' => $pname['package']),
                                true)
                         . $vs .
                        ', latest release is version ' . $info['version'] .
                        ', stability "' . $info['info']->getState() . '", use "' .
                        $this->_registry->parsedPackageNameToString(
                            array('channel' => $pname['channel'], 'package' => $pname['package'],
                            'version' => $info['version'])) . '" to install');
                    return $err;
                }
            }
        }

        if (isset($info['deprecated']) && $info['deprecated']) {
            $this->_downloader->log(0,
                'WARNING: "' .
                    $this->_registry->parsedPackageNameToString(
                            array('channel' => $info['info']->getChannel(),
                                  'package' => $info['info']->getPackage()), true) .
                '" is deprecated in favor of "' .
                    $this->_registry->parsedPackageNameToString($info['deprecated'], true) .
                '"');
        }

        return $info;
    }
}