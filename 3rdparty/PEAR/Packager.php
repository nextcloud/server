<?php
/**
 * PEAR_Packager for generating releases
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V. V. Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Packager.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * base class
 */
require_once 'PEAR/Common.php';
require_once 'PEAR/PackageFile.php';
require_once 'System.php';

/**
 * Administration class used to make a PEAR release tarball.
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 0.1
 */
class PEAR_Packager extends PEAR_Common
{
    /**
     * @var PEAR_Registry
     */
    var $_registry;

    function package($pkgfile = null, $compress = true, $pkg2 = null)
    {
        // {{{ validate supplied package.xml file
        if (empty($pkgfile)) {
            $pkgfile = 'package.xml';
        }

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $pkg  = &new PEAR_PackageFile($this->config, $this->debug);
        $pf   = &$pkg->fromPackageFile($pkgfile, PEAR_VALIDATE_NORMAL);
        $main = &$pf;
        PEAR::staticPopErrorHandling();
        if (PEAR::isError($pf)) {
            if (is_array($pf->getUserInfo())) {
                foreach ($pf->getUserInfo() as $error) {
                    $this->log(0, 'Error: ' . $error['message']);
                }
            }

            $this->log(0, $pf->getMessage());
            return $this->raiseError("Cannot package, errors in package file");
        }

        foreach ($pf->getValidationWarnings() as $warning) {
            $this->log(1, 'Warning: ' . $warning['message']);
        }

        // }}}
        if ($pkg2) {
            $this->log(0, 'Attempting to process the second package file');
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $pf2 = &$pkg->fromPackageFile($pkg2, PEAR_VALIDATE_NORMAL);
            PEAR::staticPopErrorHandling();
            if (PEAR::isError($pf2)) {
                if (is_array($pf2->getUserInfo())) {
                    foreach ($pf2->getUserInfo() as $error) {
                        $this->log(0, 'Error: ' . $error['message']);
                    }
                }
                $this->log(0, $pf2->getMessage());
                return $this->raiseError("Cannot package, errors in second package file");
            }

            foreach ($pf2->getValidationWarnings() as $warning) {
                $this->log(1, 'Warning: ' . $warning['message']);
            }

            if ($pf2->getPackagexmlVersion() == '2.0' ||
                  $pf2->getPackagexmlVersion() == '2.1'
            ) {
                $main  = &$pf2;
                $other = &$pf;
            } else {
                $main  = &$pf;
                $other = &$pf2;
            }

            if ($main->getPackagexmlVersion() != '2.0' &&
                  $main->getPackagexmlVersion() != '2.1') {
                return PEAR::raiseError('Error: cannot package two package.xml version 1.0, can ' .
                    'only package together a package.xml 1.0 and package.xml 2.0');
            }

            if ($other->getPackagexmlVersion() != '1.0') {
                return PEAR::raiseError('Error: cannot package two package.xml version 2.0, can ' .
                    'only package together a package.xml 1.0 and package.xml 2.0');
            }
        }

        $main->setLogger($this);
        if (!$main->validate(PEAR_VALIDATE_PACKAGING)) {
            foreach ($main->getValidationWarnings() as $warning) {
                $this->log(0, 'Error: ' . $warning['message']);
            }
            return $this->raiseError("Cannot package, errors in package");
        }

        foreach ($main->getValidationWarnings() as $warning) {
            $this->log(1, 'Warning: ' . $warning['message']);
        }

        if ($pkg2) {
            $other->setLogger($this);
            $a = false;
            if (!$other->validate(PEAR_VALIDATE_NORMAL) || $a = !$main->isEquivalent($other)) {
                foreach ($other->getValidationWarnings() as $warning) {
                    $this->log(0, 'Error: ' . $warning['message']);
                }

                foreach ($main->getValidationWarnings() as $warning) {
                    $this->log(0, 'Error: ' . $warning['message']);
                }

                if ($a) {
                    return $this->raiseError('The two package.xml files are not equivalent!');
                }

                return $this->raiseError("Cannot package, errors in package");
            }

            foreach ($other->getValidationWarnings() as $warning) {
                $this->log(1, 'Warning: ' . $warning['message']);
            }

            $gen = &$main->getDefaultGenerator();
            $tgzfile = $gen->toTgz2($this, $other, $compress);
            if (PEAR::isError($tgzfile)) {
                return $tgzfile;
            }

            $dest_package = basename($tgzfile);
            $pkgdir       = dirname($pkgfile);

            // TAR the Package -------------------------------------------
            $this->log(1, "Package $dest_package done");
            if (file_exists("$pkgdir/CVS/Root")) {
                $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $pf->getVersion());
                $cvstag = "RELEASE_$cvsversion";
                $this->log(1, 'Tag the released code with "pear cvstag ' .
                    $main->getPackageFile() . '"');
                $this->log(1, "(or set the CVS tag $cvstag by hand)");
            } elseif (file_exists("$pkgdir/.svn")) {
                $svnversion = preg_replace('/[^a-z0-9]/i', '.', $pf->getVersion());
                $svntag = $pf->getName() . "-$svnversion";
                $this->log(1, 'Tag the released code with "pear svntag ' .
                    $main->getPackageFile() . '"');
                $this->log(1, "(or set the SVN tag $svntag by hand)");
            }
        } else { // this branch is executed for single packagefile packaging
            $gen = &$pf->getDefaultGenerator();
            $tgzfile = $gen->toTgz($this, $compress);
            if (PEAR::isError($tgzfile)) {
                $this->log(0, $tgzfile->getMessage());
                return $this->raiseError("Cannot package, errors in package");
            }

            $dest_package = basename($tgzfile);
            $pkgdir       = dirname($pkgfile);

            // TAR the Package -------------------------------------------
            $this->log(1, "Package $dest_package done");
            if (file_exists("$pkgdir/CVS/Root")) {
                $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $pf->getVersion());
                $cvstag = "RELEASE_$cvsversion";
                $this->log(1, "Tag the released code with `pear cvstag $pkgfile'");
                $this->log(1, "(or set the CVS tag $cvstag by hand)");
            } elseif (file_exists("$pkgdir/.svn")) {
                $svnversion = preg_replace('/[^a-z0-9]/i', '.', $pf->getVersion());
                $svntag = $pf->getName() . "-$svnversion";
                $this->log(1, "Tag the released code with `pear svntag $pkgfile'");
                $this->log(1, "(or set the SVN tag $svntag by hand)");
            }
        }

        return $dest_package;
    }
}