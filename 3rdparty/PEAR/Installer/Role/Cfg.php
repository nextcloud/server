<?php
/**
 * PEAR_Installer_Role_Cfg
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  2007-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Cfg.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.7.0
 */

/**
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  2007-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.7.0
 */
class PEAR_Installer_Role_Cfg extends PEAR_Installer_Role_Common
{
    /**
     * @var PEAR_Installer
     */
    var $installer;

    /**
     * the md5 of the original file
     *
     * @var unknown_type
     */
    var $md5 = null;

    /**
     * Do any unusual setup here
     * @param PEAR_Installer
     * @param PEAR_PackageFile_v2
     * @param array file attributes
     * @param string file name
     */
    function setup(&$installer, $pkg, $atts, $file)
    {
        $this->installer = &$installer;
        $reg = &$this->installer->config->getRegistry();
        $package = $reg->getPackage($pkg->getPackage(), $pkg->getChannel());
        if ($package) {
            $filelist = $package->getFilelist();
            if (isset($filelist[$file]) && isset($filelist[$file]['md5sum'])) {
                $this->md5 = $filelist[$file]['md5sum'];
            }
        }
    }

    function processInstallation($pkg, $atts, $file, $tmp_path, $layer = null)
    {
        $test = parent::processInstallation($pkg, $atts, $file, $tmp_path, $layer);
        if (@file_exists($test[2]) && @file_exists($test[3])) {
            $md5 = md5_file($test[2]);
            // configuration has already been installed, check for mods
            if ($md5 !== $this->md5 && $md5 !== md5_file($test[3])) {
                // configuration has been modified, so save our version as
                // configfile-version
                $old = $test[2];
                $test[2] .= '.new-' . $pkg->getVersion();
                // backup original and re-install it
                PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                $tmpcfg = $this->config->get('temp_dir');
                $newloc = System::mkdir(array('-p', $tmpcfg));
                if (!$newloc) {
                    // try temp_dir
                    $newloc = System::mktemp(array('-d'));
                    if (!$newloc || PEAR::isError($newloc)) {
                        PEAR::popErrorHandling();
                        return PEAR::raiseError('Could not save existing configuration file '.
                            $old . ', unable to install.  Please set temp_dir ' .
                            'configuration variable to a writeable location and try again');
                    }
                } else {
                    $newloc = $tmpcfg;
                }

                $temp_file = $newloc . DIRECTORY_SEPARATOR . uniqid('savefile');
                if (!@copy($old, $temp_file)) {
                    PEAR::popErrorHandling();
                    return PEAR::raiseError('Could not save existing configuration file '.
                        $old . ', unable to install.  Please set temp_dir ' .
                        'configuration variable to a writeable location and try again');
                }

                PEAR::popErrorHandling();
                $this->installer->log(0, "WARNING: configuration file $old is being installed as $test[2], you should manually merge in changes to the existing configuration file");
                $this->installer->addFileOperation('rename', array($temp_file, $old, false));
                $this->installer->addFileOperation('delete', array($temp_file));
            }
        }

        return $test;
    }
}