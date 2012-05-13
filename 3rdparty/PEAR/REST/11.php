<?php
/**
 * PEAR_REST_11 - implement faster list-all/remote-list command
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: 11.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.3
 */

/**
 * For downloading REST xml/txt files
 */
require_once 'PEAR/REST.php';

/**
 * Implement REST 1.1
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.3
 */
class PEAR_REST_11
{
    /**
     * @var PEAR_REST
     */
    var $_rest;

    function PEAR_REST_11($config, $options = array())
    {
        $this->_rest = &new PEAR_REST($config, $options);
    }

    function listAll($base, $dostable, $basic = true, $searchpackage = false, $searchsummary = false, $channel = false)
    {
        $categorylist = $this->_rest->retrieveData($base . 'c/categories.xml', false, false, $channel);
        if (PEAR::isError($categorylist)) {
            return $categorylist;
        }

        $ret = array();
        if (!is_array($categorylist['c']) || !isset($categorylist['c'][0])) {
            $categorylist['c'] = array($categorylist['c']);
        }

        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);

        foreach ($categorylist['c'] as $progress => $category) {
            $category = $category['_content'];
            $packagesinfo = $this->_rest->retrieveData($base .
                'c/' . urlencode($category) . '/packagesinfo.xml', false, false, $channel);

            if (PEAR::isError($packagesinfo)) {
                continue;
            }

            if (!is_array($packagesinfo) || !isset($packagesinfo['pi'])) {
                continue;
            }

            if (!is_array($packagesinfo['pi']) || !isset($packagesinfo['pi'][0])) {
                $packagesinfo['pi'] = array($packagesinfo['pi']);
            }

            foreach ($packagesinfo['pi'] as $packageinfo) {
                if (empty($packageinfo)) {
                    continue;
                }

                $info     = $packageinfo['p'];
                $package  = $info['n'];
                $releases = isset($packageinfo['a']) ? $packageinfo['a'] : false;
                unset($latest);
                unset($unstable);
                unset($stable);
                unset($state);

                if ($releases) {
                    if (!isset($releases['r'][0])) {
                        $releases['r'] = array($releases['r']);
                    }

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
                            $unstable = $release['v'];
                            $state = $release['s'];
                        }

                        if (isset($latest) && !isset($state)) {
                            $state = $release['s'];
                        }

                        if (isset($latest) && isset($stable) && isset($unstable)) {
                            break;
                        }
                    }
                }

                if ($basic) { // remote-list command
                    if (!isset($latest)) {
                        $latest = false;
                    }

                    if ($dostable) {
                        // $state is not set if there are no releases
                        if (isset($state) && $state == 'stable') {
                            $ret[$package] = array('stable' => $latest);
                        } else {
                            $ret[$package] = array('stable' => '-n/a-');
                        }
                    } else {
                        $ret[$package] = array('stable' => $latest);
                    }

                    continue;
                }

                // list-all command
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

                $deps = array();
                if ($latest && isset($packageinfo['deps'])) {
                    if (!is_array($packageinfo['deps']) ||
                          !isset($packageinfo['deps'][0])
                    ) {
                        $packageinfo['deps'] = array($packageinfo['deps']);
                    }

                    $d = false;
                    foreach ($packageinfo['deps'] as $dep) {
                        if ($dep['v'] == $latest) {
                            $d = unserialize($dep['d']);
                        }
                    }

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

                $info = array(
                    'stable'      => $latest,
                    'summary'     => $info['s'],
                    'description' => $info['d'],
                    'deps'        => $deps,
                    'category'    => $info['ca']['_content'],
                    'unstable'    => $unstable,
                    'state'       => $state
                );
                $ret[$package] = $info;
            }
        }

        PEAR::popErrorHandling();
        return $ret;
    }

    /**
     * List all categories of a REST server
     *
     * @param string $base base URL of the server
     * @return array of categorynames
     */
    function listCategories($base, $channel = false)
    {
        $categorylist = $this->_rest->retrieveData($base . 'c/categories.xml', false, false, $channel);
        if (PEAR::isError($categorylist)) {
            return $categorylist;
        }

        if (!is_array($categorylist) || !isset($categorylist['c'])) {
            return array();
        }

        if (isset($categorylist['c']['_content'])) {
            // only 1 category
            $categorylist['c'] = array($categorylist['c']);
        }

        return $categorylist['c'];
    }

    /**
     * List packages in a category of a REST server
     *
     * @param string $base base URL of the server
     * @param string $category name of the category
     * @param boolean $info also download full package info
     * @return array of packagenames
     */
    function listCategory($base, $category, $info = false, $channel = false)
    {
        if ($info == false) {
            $url = '%s'.'c/%s/packages.xml';
        } else {
            $url = '%s'.'c/%s/packagesinfo.xml';
        }
        $url = sprintf($url,
                    $base,
                    urlencode($category));

        // gives '404 Not Found' error when category doesn't exist
        $packagelist = $this->_rest->retrieveData($url, false, false, $channel);
        if (PEAR::isError($packagelist)) {
            return $packagelist;
        }
        if (!is_array($packagelist)) {
            return array();
        }

        if ($info == false) {
            if (!isset($packagelist['p'])) {
                return array();
            }
            if (!is_array($packagelist['p']) ||
                !isset($packagelist['p'][0])) { // only 1 pkg
                $packagelist = array($packagelist['p']);
            } else {
                $packagelist = $packagelist['p'];
            }
            return $packagelist;
        }

        // info == true
        if (!isset($packagelist['pi'])) {
            return array();
        }

        if (!is_array($packagelist['pi']) ||
            !isset($packagelist['pi'][0])) { // only 1 pkg
            $packagelist_pre = array($packagelist['pi']);
        } else {
            $packagelist_pre = $packagelist['pi'];
        }

        $packagelist = array();
        foreach ($packagelist_pre as $i => $item) {
            // compatibility with r/<latest.txt>.xml
            if (isset($item['a']['r'][0])) {
                // multiple releases
                $item['p']['v'] = $item['a']['r'][0]['v'];
                $item['p']['st'] = $item['a']['r'][0]['s'];
            } elseif (isset($item['a'])) {
                // first and only release
                $item['p']['v'] = $item['a']['r']['v'];
                $item['p']['st'] = $item['a']['r']['s'];
            }

            $packagelist[$i] = array('attribs' => $item['p']['r'],
                                     '_content' => $item['p']['n'],
                                     'info' => $item['p']);
        }

        return $packagelist;
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
}
?>