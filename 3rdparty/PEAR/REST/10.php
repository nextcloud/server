<?php
/**
 * PEAR_REST_10
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: 10.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a12
 */

/**
 * For downloading REST xml/txt files
 */
require_once 'PEAR/REST.php';

/**
 * Implement REST 1.0
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a12
 */
class PEAR_REST_10
{
    /**
     * @var PEAR_REST
     */
    var $_rest;
    function PEAR_REST_10($config, $options = array())
    {
        $this->_rest = &new PEAR_REST($config, $options);
    }

    /**
     * Retrieve information about a remote package to be downloaded from a REST server
     *
     * @param string $base The uri to prepend to all REST calls
     * @param array $packageinfo an array of format:
     * <pre>
     *  array(
     *   'package' => 'packagename',
     *   'channel' => 'channelname',
     *  ['state' => 'alpha' (or valid state),]
     *  -or-
     *  ['version' => '1.whatever']
     * </pre>
     * @param string $prefstate Current preferred_state config variable value
     * @param bool $installed the installed version of this package to compare against
     * @return array|false|PEAR_Error see {@link _returnDownloadURL()}
     */
    function getDownloadURL($base, $packageinfo, $prefstate, $installed, $channel = false)
    {
        $states = $this->betterStates($prefstate, true);
        if (!$states) {
            return PEAR::raiseError('"' . $prefstate . '" is not a valid state');
        }

        $channel  = $packageinfo['channel'];
        $package  = $packageinfo['package'];
        $state    = isset($packageinfo['state'])   ? $packageinfo['state']   : null;
        $version  = isset($packageinfo['version']) ? $packageinfo['version'] : null;
        $restFile = $base . 'r/' . strtolower($package) . '/allreleases.xml';

        $info = $this->_rest->retrieveData($restFile, false, false, $channel);
        if (PEAR::isError($info)) {
            return PEAR::raiseError('No releases available for package "' .
                $channel . '/' . $package . '"');
        }

        if (!isset($info['r'])) {
            return false;
        }

        $release = $found = false;
        if (!is_array($info['r']) || !isset($info['r'][0])) {
            $info['r'] = array($info['r']);
        }

        foreach ($info['r'] as $release) {
            if (!isset($this->_rest->_options['force']) && ($installed &&
                  version_compare($release['v'], $installed, '<'))) {
                continue;
            }

            if (isset($state)) {
                // try our preferred state first
                if ($release['s'] == $state) {
                    $found = true;
                    break;
                }
                // see if there is something newer and more stable
                // bug #7221
                if (in_array($release['s'], $this->betterStates($state), true)) {
                    $found = true;
                    break;
                }
            } elseif (isset($version)) {
                if ($release['v'] == $version) {
                    $found = true;
                    break;
                }
            } else {
                if (in_array($release['s'], $states)) {
                    $found = true;
                    break;
                }
            }
        }

        return $this->_returnDownloadURL($base, $package, $release, $info, $found, false, $channel);
    }

    function getDepDownloadURL($base, $xsdversion, $dependency, $deppackage,
                               $prefstate = 'stable', $installed = false, $channel = false)
    {
        $states = $this->betterStates($prefstate, true);
        if (!$states) {
            return PEAR::raiseError('"' . $prefstate . '" is not a valid state');
        }

        $channel  = $dependency['channel'];
        $package  = $dependency['name'];
        $state    = isset($dependency['state'])   ? $dependency['state']   : null;
        $version  = isset($dependency['version']) ? $dependency['version'] : null;
        $restFile = $base . 'r/' . strtolower($package) . '/allreleases.xml';

        $info = $this->_rest->retrieveData($restFile, false, false, $channel);
        if (PEAR::isError($info)) {
            return PEAR::raiseError('Package "' . $deppackage['channel'] . '/' . $deppackage['package']
                . '" dependency "' . $channel . '/' . $package . '" has no releases');
        }

        if (!is_array($info) || !isset($info['r'])) {
            return false;
        }

        $exclude = array();
        $min = $max = $recommended = false;
        if ($xsdversion == '1.0') {
            switch ($dependency['rel']) {
                case 'ge' :
                    $min = $dependency['version'];
                break;
                case 'gt' :
                    $min = $dependency['version'];
                    $exclude = array($dependency['version']);
                break;
                case 'eq' :
                    $recommended = $dependency['version'];
                break;
                case 'lt' :
                    $max = $dependency['version'];
                    $exclude = array($dependency['version']);
                break;
                case 'le' :
                    $max = $dependency['version'];
                break;
                case 'ne' :
                    $exclude = array($dependency['version']);
                break;
            }
        } else {
            $min = isset($dependency['min']) ? $dependency['min'] : false;
            $max = isset($dependency['max']) ? $dependency['max'] : false;
            $recommended = isset($dependency['recommended']) ?
                $dependency['recommended'] : false;
            if (isset($dependency['exclude'])) {
                if (!isset($dependency['exclude'][0])) {
                    $exclude = array($dependency['exclude']);
                }
            }
        }
        $release = $found = false;
        if (!is_array($info['r']) || !isset($info['r'][0])) {
            $info['r'] = array($info['r']);
        }
        foreach ($info['r'] as $release) {
            if (!isset($this->_rest->_options['force']) && ($installed &&
                  version_compare($release['v'], $installed, '<'))) {
                continue;
            }
            if (in_array($release['v'], $exclude)) { // skip excluded versions
                continue;
            }
            // allow newer releases to say "I'm OK with the dependent package"
            if ($xsdversion == '2.0' && isset($release['co'])) {
                if (!is_array($release['co']) || !isset($release['co'][0])) {
                    $release['co'] = array($release['co']);
                }
                foreach ($release['co'] as $entry) {
                    if (isset($entry['x']) && !is_array($entry['x'])) {
                        $entry['x'] = array($entry['x']);
                    } elseif (!isset($entry['x'])) {
                        $entry['x'] = array();
                    }
                    if ($entry['c'] == $deppackage['channel'] &&
                          strtolower($entry['p']) == strtolower($deppackage['package']) &&
                          version_compare($deppackage['version'], $entry['min'], '>=') &&
                          version_compare($deppackage['version'], $entry['max'], '<=') &&
                          !in_array($release['v'], $entry['x'])) {
                        $recommended = $release['v'];
                        break;
                    }
                }
            }
            if ($recommended) {
                if ($release['v'] != $recommended) { // if we want a specific
                    // version, then skip all others
                    continue;
                } else {
                    if (!in_array($release['s'], $states)) {
                        // the stability is too low, but we must return the
                        // recommended version if possible
                        return $this->_returnDownloadURL($base, $package, $release, $info, true, false, $channel);
                    }
                }
            }
            if ($min && version_compare($release['v'], $min, 'lt')) { // skip too old versions
                continue;
            }
            if ($max && version_compare($release['v'], $max, 'gt')) { // skip too new versions
                continue;
            }
            if ($installed && version_compare($release['v'], $installed, '<')) {
                continue;
            }
            if (in_array($release['s'], $states)) { // if in the preferred state...
                $found = true; // ... then use it
                break;
            }
        }
        return $this->_returnDownloadURL($base, $package, $release, $info, $found, false, $channel);
    }

    /**
     * Take raw data and return the array needed for processing a download URL
     *
     * @param string $base REST base uri
     * @param string $package Package name
     * @param array $release an array of format array('v' => version, 's' => state)
     *                       describing the release to download
     * @param array $info list of all releases as defined by allreleases.xml
     * @param bool|null $found determines whether the release was found or this is the next
     *                    best alternative.  If null, then versions were skipped because
     *                    of PHP dependency
     * @return array|PEAR_Error
     * @access private
     */
    function _returnDownloadURL($base, $package, $release, $info, $found, $phpversion = false, $channel = false)
    {
        if (!$found) {
            $release = $info['r'][0];
        }

        $packageLower = strtolower($package);
        $pinfo = $this->_rest->retrieveCacheFirst($base . 'p/' . $packageLower . '/' .
            'info.xml', false, false, $channel);
        if (PEAR::isError($pinfo)) {
            return PEAR::raiseError('Package "' . $package .
                '" does not have REST info xml available');
        }

        $releaseinfo = $this->_rest->retrieveCacheFirst($base . 'r/' . $packageLower . '/' .
            $release['v'] . '.xml', false, false, $channel);
        if (PEAR::isError($releaseinfo)) {
            return PEAR::raiseError('Package "' . $package . '" Version "' . $release['v'] .
                '" does not have REST xml available');
        }

        $packagexml = $this->_rest->retrieveCacheFirst($base . 'r/' . $packageLower . '/' .
            'deps.' . $release['v'] . '.txt', false, true, $channel);
        if (PEAR::isError($packagexml)) {
            return PEAR::raiseError('Package "' . $package . '" Version "' . $release['v'] .
                '" does not have REST dependency information available');
        }

        $packagexml = unserialize($packagexml);
        if (!$packagexml) {
            $packagexml = array();
        }

        $allinfo = $this->_rest->retrieveData($base . 'r/' . $packageLower .
            '/allreleases.xml', false, false, $channel);
        if (PEAR::isError($allinfo)) {
            return $allinfo;
        }

        if (!is_array($allinfo['r']) || !isset($allinfo['r'][0])) {
            $allinfo['r'] = array($allinfo['r']);
        }

        $compatible = false;
        foreach ($allinfo['r'] as $release) {
            if ($release['v'] != $releaseinfo['v']) {
                continue;
            }

            if (!isset($release['co'])) {
                break;
            }

            $compatible = array();
            if (!is_array($release['co']) || !isset($release['co'][0])) {
                $release['co'] = array($release['co']);
            }

            foreach ($release['co'] as $entry) {
                $comp = array();
                $comp['name']    = $entry['p'];
                $comp['channel'] = $entry['c'];
                $comp['min']     = $entry['min'];
                $comp['max']     = $entry['max'];
                if (isset($entry['x']) && !is_array($entry['x'])) {
                    $comp['exclude'] = $entry['x'];
                }

                $compatible[] = $comp;
            }

            if (count($compatible) == 1) {
                $compatible = $compatible[0];
            }

            break;
        }

        $deprecated = false;
        if (isset($pinfo['dc']) && isset($pinfo['dp'])) {
            if (is_array($pinfo['dp'])) {
                $deprecated = array('channel' => (string) $pinfo['dc'],
                                    'package' => trim($pinfo['dp']['_content']));
            } else {
                $deprecated = array('channel' => (string) $pinfo['dc'],
                                    'package' => trim($pinfo['dp']));
            }
        }

        $return = array(
            'version'    => $releaseinfo['v'],
            'info'       => $packagexml,
            'package'    => $releaseinfo['p']['_content'],
            'stability'  => $releaseinfo['st'],
            'compatible' => $compatible,
            'deprecated' => $deprecated,
        );

        if ($found) {
            $return['url'] = $releaseinfo['g'];
            return $return;
        }

        $return['php'] = $phpversion;
        return $return;
    }

    function listPackages($base, $channel = false)
    {
        $packagelist = $this->_rest->retrieveData($base . 'p/packages.xml', false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }

        if (!is_array($packagelist) || !isset($packagelist['p'])) {
            return array();
        }

        if (!is_array($packagelist['p'])) {
            $packagelist['p'] = array($packagelist['p']);
        }

        return $packagelist['p'];
    }

    /**
     * List all categories of a REST server
     *
     * @param string $base base URL of the server
     * @return array of categorynames
     */
    function listCategories($base, $channel = false)
    {
        $categories = array();

        // c/categories.xml does not exist;
        // check for every package its category manually
        // This is SLOOOWWWW : ///
        $packagelist = $this->_rest->retrieveData($base . 'p/packages.xml', false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }

        if (!is_array($packagelist) || !isset($packagelist['p'])) {
            $ret = array();
            return $ret;
        }

        if (!is_array($packagelist['p'])) {
            $packagelist['p'] = array($packagelist['p']);
        }

        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        foreach ($packagelist['p'] as $package) {
                $inf = $this->_rest->retrieveData($base . 'p/' . strtolower($package) . '/info.xml', false, false, $channel);
                if (PEAR::isError($inf)) {
                    PEAR::popErrorHandling();
                    return $inf;
                }
                $cat = $inf['ca']['_content'];
                if (!isset($categories[$cat])) {
                    $categories[$cat] = $inf['ca'];
                }
        }

        return array_values($categories);
    }

    /**
     * List a category of a REST server
     *
     * @param string $base base URL of the server
     * @param string $category name of the category
     * @param boolean $info also download full package info
     * @return array of packagenames
     */
    function listCategory($base, $category, $info = false, $channel = false)
    {
        // gives '404 Not Found' error when category doesn't exist
        $packagelist = $this->_rest->retrieveData($base.'c/'.urlencode($category).'/packages.xml', false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }

        if (!is_array($packagelist) || !isset($packagelist['p'])) {
            return array();
        }

        if (!is_array($packagelist['p']) ||
            !isset($packagelist['p'][0])) { // only 1 pkg
            $packagelist = array($packagelist['p']);
        } else {
            $packagelist = $packagelist['p'];
        }

        if ($info == true) {
            // get individual package info
            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            foreach ($packagelist as $i => $packageitem) {
                $url = sprintf('%s'.'r/%s/latest.txt',
                        $base,
                        strtolower($packageitem['_content']));
                $version = $this->_rest->retrieveData($url, false, false, $channel);
                if (PEAR::isError($version)) {
                    break; // skipit
                }
                $url = sprintf('%s'.'r/%s/%s.xml',
                        $base,
                        strtolower($packageitem['_content']),
                        $version);
                $info = $this->_rest->retrieveData($url, false, false, $channel);
                if (PEAR::isError($info)) {
                    break; // skipit
                }
                $packagelist[$i]['info'] = $info;
            }
            PEAR::popErrorHandling();
        }

        return $packagelist;
    }


    function listAll($base, $dostable, $basic = true, $searchpackage = false, $searchsummary = false, $channel = false)
    {
        $packagelist = $this->_rest->retrieveData($base . 'p/packages.xml', false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }
        if ($this->_rest->config->get('verbose') > 0) {
            $ui = &PEAR_Frontend::singleton();
            $ui->log('Retrieving data...0%', true);
        }
        $ret = array();
        if (!is_array($packagelist) || !isset($packagelist['p'])) {
            return $ret;
        }
        if (!is_array($packagelist['p'])) {
            $packagelist['p'] = array($packagelist['p']);
        }

        // only search-packagename = quicksearch !
        if ($searchpackage && (!$searchsummary || empty($searchpackage))) {
            $newpackagelist = array();
            foreach ($packagelist['p'] as $package) {
                if (!empty($searchpackage) && stristr($package, $searchpackage) !== false) {
                    $newpackagelist[] = $package;
                }
            }
            $packagelist['p'] = $newpackagelist;
        }
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        $next = .1;
        foreach ($packagelist['p'] as $progress => $package) {
            if ($this->_rest->config->get('verbose') > 0) {
                if ($progress / count($packagelist['p']) >= $next) {
                    if ($next == .5) {
                        $ui->log('50%', false);
                    } else {
                        $ui->log('.', false);
                    }
                    $next += .1;
                }
            }

            if ($basic) { // remote-list command
                if ($dostable) {
                    $latest = $this->_rest->retrieveData($base . 'r/' . strtolower($package) .
                        '/stable.txt', false, false, $channel);
                } else {
                    $latest = $this->_rest->retrieveData($base . 'r/' . strtolower($package) .
                        '/latest.txt', false, false, $channel);
                }
                if (PEAR::isError($latest)) {
                    $latest = false;
                }
                $info = array('stable' => $latest);
            } else { // list-all command
                $inf = $this->_rest->retrieveData($base . 'p/' . strtolower($package) . '/info.xml', false, false, $channel);
                if (PEAR::isError($inf)) {
                    PEAR::popErrorHandling();
                    return $inf;
                }
                if ($searchpackage) {
                    $found = (!empty($searchpackage) && stristr($package, $searchpackage) !== false);
                    if (!$found && !(isset($searchsummary) && !empty($searchsummary)
                        && (stristr($inf['s'], $searchsummary) !== false
                            || stristr($inf['d'], $searchsummary) !== false)))
                    {
                        continue;
                    };
                }
                $releases = $this->_rest->retrieveData($base . 'r/' . strtolower($package) .
                    '/allreleases.xml', false, false, $channel);
                if (PEAR::isError($releases)) {
                    continue;
                }
                if (!isset($releases['r'][0])) {
                    $releases['r'] = array($releases['r']);
                }
                unset($latest);
                unset($unstable);
                unset($stable);
                unset($state);
                foreach ($releases['r'] as $release) {
                    if (!isset($latest)) {
                        if ($dostable && $release['s'] == 'stable') {
                            $latest = $release['v'];
                            $state = 'stable';
                        }
                        if (!$dostable) {
                            $latest = $release['v'];
                            $state = $release['s'];
                        }
                    }
                    if (!isset($stable) && $release['s'] == 'stable') {
                        $stable = $release['v'];
                        if (!isset($unstable)) {
                            $unstable = $stable;
                        }
                    }
                    if (!isset($unstable) && $release['s'] != 'stable') {
                        $latest = $unstable = $release['v'];
                        $state = $release['s'];
                    }
                    if (isset($latest) && !isset($state)) {
                        $state = $release['s'];
                    }
                    if (isset($latest) && isset($stable) && isset($unstable)) {
                        break;
                    }
                }
                $deps = array();
                if (!isset($unstable)) {
                    $unstable = false;
                    $state = 'stable';
                    if (isset($stable)) {
                        $latest = $unstable = $stable;
                    }
                } else {
                    $latest = $unstable;
                }
                if (!isset($latest)) {
                    $latest = false;
                }
                if ($latest) {
                    $d = $this->_rest->retrieveCacheFirst($base . 'r/' . strtolower($package) . '/deps.' .
                        $latest . '.txt', false, false, $channel);
                    if (!PEAR::isError($d)) {
                        $d = unserialize($d);
                        if ($d) {
                            if (isset($d['required'])) {
                                if (!class_exists('PEAR_PackageFile_v2')) {
                                    require_once 'PEAR/PackageFile/v2.php';
                                }
                                if (!isset($pf)) {
                                    $pf = new PEAR_PackageFile_v2;
                                }
                                $pf->setDeps($d);
                                $tdeps = $pf->getDeps();
                            } else {
                                $tdeps = $d;
                            }
                            foreach ($tdeps as $dep) {
                                if ($dep['type'] !== 'pkg') {
                                    continue;
                                }
                                $deps[] = $dep;
                            }
                        }
                    }
                }
                if (!isset($stable)) {
                    $stable = '-n/a-';
                }
                if (!$searchpackage) {
                    $info = array('stable' => $latest, 'summary' => $inf['s'], 'description' =>
                        $inf['d'], 'deps' => $deps, 'category' => $inf['ca']['_content'],
                        'unstable' => $unstable, 'state' => $state);
                } else {
                    $info = array('stable' => $stable, 'summary' => $inf['s'], 'description' =>
                        $inf['d'], 'deps' => $deps, 'category' => $inf['ca']['_content'],
                        'unstable' => $unstable, 'state' => $state);
                }
            }
            $ret[$package] = $info;
        }
        PEAR::popErrorHandling();
        return $ret;
    }

    function listLatestUpgrades($base, $pref_state, $installed, $channel, &$reg)
    {
        $packagelist = $this->_rest->retrieveData($base . 'p/packages.xml', false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }

        $ret = array();
        if (!is_array($packagelist) || !isset($packagelist['p'])) {
            return $ret;
        }

        if (!is_array($packagelist['p'])) {
            $packagelist['p'] = array($packagelist['p']);
        }

        foreach ($packagelist['p'] as $package) {
            if (!isset($installed[strtolower($package)])) {
                continue;
            }

            $inst_version = $reg->packageInfo($package, 'version', $channel);
            $inst_state   = $reg->packageInfo($package, 'release_state', $channel);
            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $info = $this->_rest->retrieveData($base . 'r/' . strtolower($package) .
                '/allreleases.xml', false, false, $channel);
            PEAR::popErrorHandling();
            if (PEAR::isError($info)) {
                continue; // no remote releases
            }

            if (!isset($info['r'])) {
                continue;
            }

            $release = $found = false;
            if (!is_array($info['r']) || !isset($info['r'][0])) {
                $info['r'] = array($info['r']);
            }

            // $info['r'] is sorted by version number
            usort($info['r'], array($this, '_sortReleasesByVersionNumber'));
            foreach ($info['r'] as $release) {
                if ($inst_version && version_compare($release['v'], $inst_version, '<=')) {
                    // not newer than the one installed
                    break;
                }

                // new version > installed version
                if (!$pref_state) {
                    // every state is a good state
                    $found = true;
                    break;
                } else {
                    $new_state = $release['s'];
                    // if new state >= installed state: go
                    if (in_array($new_state, $this->betterStates($inst_state, true))) {
                        $found = true;
                        break;
                    } else {
                        // only allow to lower the state of package,
                        // if new state >= preferred state: go
                        if (in_array($new_state, $this->betterStates($pref_state, true))) {
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (!$found) {
                continue;
            }

            $relinfo = $this->_rest->retrieveCacheFirst($base . 'r/' . strtolower($package) . '/' .
                $release['v'] . '.xml', false, false, $channel);
            if (PEAR::isError($relinfo)) {
                return $relinfo;
            }

            $ret[$package] = array(
                'version'  => $release['v'],
                'state'    => $release['s'],
                'filesize' => $relinfo['f'],
            );
        }

        return $ret;
    }

    function packageInfo($base, $package, $channel = false)
    {
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        $pinfo = $this->_rest->retrieveData($base . 'p/' . strtolower($package) . '/info.xml', false, false, $channel);
        if (PEAR::isError($pinfo)) {
            PEAR::popErrorHandling();
            return PEAR::raiseError('Unknown package: "' . $package . '" in channel "' . $channel . '"' . "\n". 'Debug: ' .
                $pinfo->getMessage());
        }

        $releases = array();
        $allreleases = $this->_rest->retrieveData($base . 'r/' . strtolower($package) .
            '/allreleases.xml', false, false, $channel);
        if (!PEAR::isError($allreleases)) {
            if (!class_exists('PEAR_PackageFile_v2')) {
                require_once 'PEAR/PackageFile/v2.php';
            }

            if (!is_array($allreleases['r']) || !isset($allreleases['r'][0])) {
                $allreleases['r'] = array($allreleases['r']);
            }

            $pf = new PEAR_PackageFile_v2;
            foreach ($allreleases['r'] as $release) {
                $ds = $this->_rest->retrieveCacheFirst($base . 'r/' . strtolower($package) . '/deps.' .
                    $release['v'] . '.txt', false, false, $channel);
                if (PEAR::isError($ds)) {
                    continue;
                }

                if (!isset($latest)) {
                    $latest = $release['v'];
                }

                $pf->setDeps(unserialize($ds));
                $ds = $pf->getDeps();
                $info = $this->_rest->retrieveCacheFirst($base . 'r/' . strtolower($package)
                    . '/' . $release['v'] . '.xml', false, false, $channel);

                if (PEAR::isError($info)) {
                    continue;
                }

                $releases[$release['v']] = array(
                    'doneby' => $info['m'],
                    'license' => $info['l'],
                    'summary' => $info['s'],
                    'description' => $info['d'],
                    'releasedate' => $info['da'],
                    'releasenotes' => $info['n'],
                    'state' => $release['s'],
                    'deps' => $ds ? $ds : array(),
                );
            }
        } else {
            $latest = '';
        }

        PEAR::popErrorHandling();
        if (isset($pinfo['dc']) && isset($pinfo['dp'])) {
            if (is_array($pinfo['dp'])) {
                $deprecated = array('channel' => (string) $pinfo['dc'],
                                    'package' => trim($pinfo['dp']['_content']));
            } else {
                $deprecated = array('channel' => (string) $pinfo['dc'],
                                    'package' => trim($pinfo['dp']));
            }
        } else {
            $deprecated = false;
        }

        if (!isset($latest)) {
            $latest = '';
        }

        return array(
            'name' => $pinfo['n'],
            'channel' => $pinfo['c'],
            'category' => $pinfo['ca']['_content'],
            'stable' => $latest,
            'license' => $pinfo['l'],
            'summary' => $pinfo['s'],
            'description' => $pinfo['d'],
            'releases' => $releases,
            'deprecated' => $deprecated,
            );
    }

    /**
     * Return an array containing all of the states that are more stable than
     * or equal to the passed in state
     *
     * @param string Release state
     * @param boolean Determines whether to include $state in the list
     * @return false|array False if $state is not a valid release state
     */
    function betterStates($state, $include = false)
    {
        static $states = array('snapshot', 'devel', 'alpha', 'beta', 'stable');
        $i = array_search($state, $states);
        if ($i === false) {
            return false;
        }

        if ($include) {
            $i--;
        }

        return array_slice($states, $i + 1);
    }

    /**
     * Sort releases by version number
     *
     * @access private
     */
    function _sortReleasesByVersionNumber($a, $b)
    {
        if (version_compare($a['v'], $b['v'], '=')) {
            return 0;
        }

        if (version_compare($a['v'], $b['v'], '>')) {
            return -1;
        }

        if (version_compare($a['v'], $b['v'], '<')) {
            return 1;
        }
    }
}