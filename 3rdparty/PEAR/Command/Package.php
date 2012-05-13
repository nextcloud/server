<?php
/**
 * PEAR_Command_Package (package, package-validate, cvsdiff, cvstag, package-dependencies,
 * sign, makerpm, convert commands)
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Package.php 313024 2011-07-06 19:51:24Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**
 * base class
 */
require_once 'PEAR/Command/Common.php';

/**
 * PEAR commands for login/logout
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 0.1
 */

class PEAR_Command_Package extends PEAR_Command_Common
{
    var $commands = array(
        'package' => array(
            'summary' => 'Build Package',
            'function' => 'doPackage',
            'shortcut' => 'p',
            'options' => array(
                'nocompress' => array(
                    'shortopt' => 'Z',
                    'doc' => 'Do not gzip the package file'
                    ),
                'showname' => array(
                    'shortopt' => 'n',
                    'doc' => 'Print the name of the packaged file.',
                    ),
                ),
            'doc' => '[descfile] [descfile2]
Creates a PEAR package from its description file (usually called
package.xml).  If a second packagefile is passed in, then
the packager will check to make sure that one is a package.xml
version 1.0, and the other is a package.xml version 2.0.  The
package.xml version 1.0 will be saved as "package.xml" in the archive,
and the other as "package2.xml" in the archive"
'
            ),
        'package-validate' => array(
            'summary' => 'Validate Package Consistency',
            'function' => 'doPackageValidate',
            'shortcut' => 'pv',
            'options' => array(),
            'doc' => '
',
            ),
        'cvsdiff' => array(
            'summary' => 'Run a "cvs diff" for all files in a package',
            'function' => 'doCvsDiff',
            'shortcut' => 'cd',
            'options' => array(
                'quiet' => array(
                    'shortopt' => 'q',
                    'doc' => 'Be quiet',
                    ),
                'reallyquiet' => array(
                    'shortopt' => 'Q',
                    'doc' => 'Be really quiet',
                    ),
                'date' => array(
                    'shortopt' => 'D',
                    'doc' => 'Diff against revision of DATE',
                    'arg' => 'DATE',
                    ),
                'release' => array(
                    'shortopt' => 'R',
                    'doc' => 'Diff against tag for package release REL',
                    'arg' => 'REL',
                    ),
                'revision' => array(
                    'shortopt' => 'r',
                    'doc' => 'Diff against revision REV',
                    'arg' => 'REV',
                    ),
                'context' => array(
                    'shortopt' => 'c',
                    'doc' => 'Generate context diff',
                    ),
                'unified' => array(
                    'shortopt' => 'u',
                    'doc' => 'Generate unified diff',
                    ),
                'ignore-case' => array(
                    'shortopt' => 'i',
                    'doc' => 'Ignore case, consider upper- and lower-case letters equivalent',
                    ),
                'ignore-whitespace' => array(
                    'shortopt' => 'b',
                    'doc' => 'Ignore changes in amount of white space',
                    ),
                'ignore-blank-lines' => array(
                    'shortopt' => 'B',
                    'doc' => 'Ignore changes that insert or delete blank lines',
                    ),
                'brief' => array(
                    'doc' => 'Report only whether the files differ, no details',
                    ),
                'dry-run' => array(
                    'shortopt' => 'n',
                    'doc' => 'Don\'t do anything, just pretend',
                    ),
                ),
            'doc' => '<package.xml>
Compares all the files in a package.  Without any options, this
command will compare the current code with the last checked-in code.
Using the -r or -R option you may compare the current code with that
of a specific release.
',
            ),
         'svntag' => array(
             'summary' => 'Set SVN Release Tag',
             'function' => 'doSvnTag',
             'shortcut' => 'sv',
             'options' => array(
                 'quiet' => array(
                     'shortopt' => 'q',
                     'doc' => 'Be quiet',
                     ),
                 'slide' => array(
                     'shortopt' => 'F',
                     'doc' => 'Move (slide) tag if it exists',
                     ),
                 'delete' => array(
                     'shortopt' => 'd',
                     'doc' => 'Remove tag',
                     ),
                 'dry-run' => array(
                     'shortopt' => 'n',
                     'doc' => 'Don\'t do anything, just pretend',
                     ),
                 ),
             'doc' => '<package.xml> [files...]
 Sets a SVN tag on all files in a package.  Use this command after you have
 packaged a distribution tarball with the "package" command to tag what
 revisions of what files were in that release.  If need to fix something
 after running svntag once, but before the tarball is released to the public,
 use the "slide" option to move the release tag.

 to include files (such as a second package.xml, or tests not included in the
 release), pass them as additional parameters.
 ',
             ),
        'cvstag' => array(
            'summary' => 'Set CVS Release Tag',
            'function' => 'doCvsTag',
            'shortcut' => 'ct',
            'options' => array(
                'quiet' => array(
                    'shortopt' => 'q',
                    'doc' => 'Be quiet',
                    ),
                'reallyquiet' => array(
                    'shortopt' => 'Q',
                    'doc' => 'Be really quiet',
                    ),
                'slide' => array(
                    'shortopt' => 'F',
                    'doc' => 'Move (slide) tag if it exists',
                    ),
                'delete' => array(
                    'shortopt' => 'd',
                    'doc' => 'Remove tag',
                    ),
                'dry-run' => array(
                    'shortopt' => 'n',
                    'doc' => 'Don\'t do anything, just pretend',
                    ),
                ),
            'doc' => '<package.xml> [files...]
Sets a CVS tag on all files in a package.  Use this command after you have
packaged a distribution tarball with the "package" command to tag what
revisions of what files were in that release.  If need to fix something
after running cvstag once, but before the tarball is released to the public,
use the "slide" option to move the release tag.

to include files (such as a second package.xml, or tests not included in the
release), pass them as additional parameters.
',
            ),
        'package-dependencies' => array(
            'summary' => 'Show package dependencies',
            'function' => 'doPackageDependencies',
            'shortcut' => 'pd',
            'options' => array(),
            'doc' => '<package-file> or <package.xml> or <install-package-name>
List all dependencies the package has.
Can take a tgz / tar file, package.xml or a package name of an installed package.'
            ),
        'sign' => array(
            'summary' => 'Sign a package distribution file',
            'function' => 'doSign',
            'shortcut' => 'si',
            'options' => array(
                'verbose' => array(
                    'shortopt' => 'v',
                    'doc' => 'Display GnuPG output',
                    ),
            ),
            'doc' => '<package-file>
Signs a package distribution (.tar or .tgz) file with GnuPG.',
            ),
        'makerpm' => array(
            'summary' => 'Builds an RPM spec file from a PEAR package',
            'function' => 'doMakeRPM',
            'shortcut' => 'rpm',
            'options' => array(
                'spec-template' => array(
                    'shortopt' => 't',
                    'arg' => 'FILE',
                    'doc' => 'Use FILE as RPM spec file template'
                    ),
                'rpm-pkgname' => array(
                    'shortopt' => 'p',
                    'arg' => 'FORMAT',
                    'doc' => 'Use FORMAT as format string for RPM package name, %s is replaced
by the PEAR package name, defaults to "PEAR::%s".',
                    ),
                ),
            'doc' => '<package-file>

Creates an RPM .spec file for wrapping a PEAR package inside an RPM
package.  Intended to be used from the SPECS directory, with the PEAR
package tarball in the SOURCES directory:

$ pear makerpm ../SOURCES/Net_Socket-1.0.tgz
Wrote RPM spec file PEAR::Net_Geo-1.0.spec
$ rpm -bb PEAR::Net_Socket-1.0.spec
...
Wrote: /usr/src/redhat/RPMS/i386/PEAR::Net_Socket-1.0-1.i386.rpm
',
            ),
        'convert' => array(
            'summary' => 'Convert a package.xml 1.0 to package.xml 2.0 format',
            'function' => 'doConvert',
            'shortcut' => 'c2',
            'options' => array(
                'flat' => array(
                    'shortopt' => 'f',
                    'doc' => 'do not beautify the filelist.',
                    ),
                ),
            'doc' => '[descfile] [descfile2]
Converts a package.xml in 1.0 format into a package.xml
in 2.0 format.  The new file will be named package2.xml by default,
and package.xml will be used as the old file by default.
This is not the most intelligent conversion, and should only be
used for automated conversion or learning the format.
'
            ),
        );

    var $output;

    /**
     * PEAR_Command_Package constructor.
     *
     * @access public
     */
    function PEAR_Command_Package(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    function _displayValidationResults($err, $warn, $strict = false)
    {
        foreach ($err as $e) {
            $this->output .= "Error: $e\n";
        }
        foreach ($warn as $w) {
            $this->output .= "Warning: $w\n";
        }
        $this->output .= sprintf('Validation: %d error(s), %d warning(s)'."\n",
                                       sizeof($err), sizeof($warn));
        if ($strict && count($err) > 0) {
            $this->output .= "Fix these errors and try again.";
            return false;
        }
        return true;
    }

    function &getPackager()
    {
        if (!class_exists('PEAR_Packager')) {
            require_once 'PEAR/Packager.php';
        }
        $a = &new PEAR_Packager;
        return $a;
    }

    function &getPackageFile($config, $debug = false)
    {
        if (!class_exists('PEAR_Common')) {
            require_once 'PEAR/Common.php';
        }
        if (!class_exists('PEAR_PackageFile')) {
            require_once 'PEAR/PackageFile.php';
        }
        $a = &new PEAR_PackageFile($config, $debug);
        $common = new PEAR_Common;
        $common->ui = $this->ui;
        $a->setLogger($common);
        return $a;
    }

    function doPackage($command, $options, $params)
    {
        $this->output = '';
        $pkginfofile = isset($params[0]) ? $params[0] : 'package.xml';
        $pkg2 = isset($params[1]) ? $params[1] : null;
        if (!$pkg2 && !isset($params[0]) && file_exists('package2.xml')) {
            $pkg2 = 'package2.xml';
        }

        $packager = &$this->getPackager();
        $compress = empty($options['nocompress']) ? true : false;
        $result   = $packager->package($pkginfofile, $compress, $pkg2);
        if (PEAR::isError($result)) {
            return $this->raiseError($result);
        }

        // Don't want output, only the package file name just created
        if (isset($options['showname'])) {
            $this->output = $result;
        }

        if ($this->output) {
            $this->ui->outputData($this->output, $command);
        }

        return true;
    }

    function doPackageValidate($command, $options, $params)
    {
        $this->output = '';
        if (count($params) < 1) {
            $params[0] = 'package.xml';
        }

        $obj = &$this->getPackageFile($this->config, $this->_debug);
        $obj->rawReturn();
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $info = $obj->fromTgzFile($params[0], PEAR_VALIDATE_NORMAL);
        if (PEAR::isError($info)) {
            $info = $obj->fromPackageFile($params[0], PEAR_VALIDATE_NORMAL);
        } else {
            $archive = $info->getArchiveFile();
            $tar = &new Archive_Tar($archive);
            $tar->extract(dirname($info->getPackageFile()));
            $info->setPackageFile(dirname($info->getPackageFile()) . DIRECTORY_SEPARATOR .
                $info->getPackage() . '-' . $info->getVersion() . DIRECTORY_SEPARATOR .
                basename($info->getPackageFile()));
        }

        PEAR::staticPopErrorHandling();
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $valid = false;
        if ($info->getPackagexmlVersion() == '2.0') {
            if ($valid = $info->validate(PEAR_VALIDATE_NORMAL)) {
                $info->flattenFileList();
                $valid = $info->validate(PEAR_VALIDATE_PACKAGING);
            }
        } else {
            $valid = $info->validate(PEAR_VALIDATE_PACKAGING);
        }

        $err = $warn = array();
        if ($errors = $info->getValidationWarnings()) {
            foreach ($errors as $error) {
                if ($error['level'] == 'warning') {
                    $warn[] = $error['message'];
                } else {
                    $err[] = $error['message'];
                }
            }
        }

        $this->_displayValidationResults($err, $warn);
        $this->ui->outputData($this->output, $command);
        return true;
    }

    function doSvnTag($command, $options, $params)
    {
        $this->output = '';
        $_cmd = $command;
        if (count($params) < 1) {
            $help = $this->getHelp($command);
            return $this->raiseError("$command: missing parameter: $help[0]");
        }

        $packageFile = realpath($params[0]);
        $dir = dirname($packageFile);
        $dir = substr($dir, strrpos($dir, DIRECTORY_SEPARATOR) + 1);
        $obj  = &$this->getPackageFile($this->config, $this->_debug);
        $info = $obj->fromAnyFile($packageFile, PEAR_VALIDATE_NORMAL);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $err = $warn = array();
        if (!$info->validate()) {
            foreach ($info->getValidationWarnings() as $error) {
                if ($error['level'] == 'warning') {
                    $warn[] = $error['message'];
                } else {
                    $err[] = $error['message'];
                }
            }
        }

        if (!$this->_displayValidationResults($err, $warn, true)) {
            $this->ui->outputData($this->output, $command);
            return $this->raiseError('SVN tag failed');
        }

        $version    = $info->getVersion();
        $package    = $info->getName();
        $svntag     = "$package-$version";

        if (isset($options['delete'])) {
            return $this->_svnRemoveTag($version, $package, $svntag, $packageFile, $options);
        }

        $path = $this->_svnFindPath($packageFile);

        // Check if there are any modified files
        $fp = popen('svn st --xml ' . dirname($packageFile), "r");
        $out = '';
        while ($line = fgets($fp, 1024)) {
            $out .= rtrim($line)."\n";
        }
        pclose($fp);

        if (!isset($options['quiet']) && strpos($out, 'item="modified"')) {
            $params = array(array(
                'name' => 'modified',
                'type' => 'yesno',
                'default' => 'no',
                'prompt' => 'You have files in your SVN checkout (' . $path['from']  . ') that have been modified but not commited, do you still want to tag ' . $version . '?',
            ));
            $answers = $this->ui->confirmDialog($params);

            if (!in_array($answers['modified'], array('y', 'yes', 'on', '1'))) {
                return true;
            }
        }

        if (isset($options['slide'])) {
            $this->_svnRemoveTag($version, $package, $svntag, $packageFile, $options);
        }

        // Check if tag already exists
        $releaseTag = $path['local']['base'] . 'tags' . DIRECTORY_SEPARATOR . $svntag;
        $existsCommand = 'svn ls ' . $path['base'] . 'tags/';

        $fp = popen($existsCommand, "r");
        $out = '';
        while ($line = fgets($fp, 1024)) {
            $out .= rtrim($line)."\n";
        }
        pclose($fp);

        if (in_array($svntag . DIRECTORY_SEPARATOR, explode("\n", $out))) {
            $this->ui->outputData($this->output, $command);
            return $this->raiseError('SVN tag ' . $svntag . ' for ' . $package . ' already exists.');
        } elseif (file_exists($path['local']['base'] . 'tags') === false) {
            return $this->raiseError('Can not locate the tags directory at ' . $path['local']['base'] . 'tags');
        } elseif (is_writeable($path['local']['base'] . 'tags') === false) {
            return $this->raiseError('Can not write to the tag directory at ' . $path['local']['base'] . 'tags');
        } else {
            $makeCommand = 'svn mkdir ' . $releaseTag;
            $this->output .= "+ $makeCommand\n";
            if (empty($options['dry-run'])) {
                // We need to create the tag dir.
                $fp = popen($makeCommand, "r");
                $out = '';
                while ($line = fgets($fp, 1024)) {
                    $out .= rtrim($line)."\n";
                }
                pclose($fp);
                $this->output .= "$out\n";
            }
        }

        $command = 'svn';
        if (isset($options['quiet'])) {
            $command .= ' -q';
        }

        $command .= ' copy --parents ';

        $dir   = dirname($packageFile);
        $dir   = substr($dir, strrpos($dir, DIRECTORY_SEPARATOR) + 1);
        $files = array_keys($info->getFilelist());
        if (!in_array(basename($packageFile), $files)) {
            $files[] = basename($packageFile);
        }

        array_shift($params);
        if (count($params)) {
            // add in additional files to be tagged (package files and such)
            $files = array_merge($files, $params);
        }

        $commands = array();
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $file = $dir . DIRECTORY_SEPARATOR . $file;
            }
            $commands[] = $command . ' ' . escapeshellarg($file) . ' ' .
                          escapeshellarg($releaseTag . DIRECTORY_SEPARATOR . $file);
        }

        $this->output .= implode("\n", $commands) . "\n";
        if (empty($options['dry-run'])) {
            foreach ($commands as $command) {
                $fp = popen($command, "r");
                while ($line = fgets($fp, 1024)) {
                    $this->output .= rtrim($line)."\n";
                }
                pclose($fp);
            }
        }

        $command = 'svn ci -m "Tagging the ' . $version  . ' release" ' . $releaseTag . "\n";
        $this->output .= "+ $command\n";
        if (empty($options['dry-run'])) {
            $fp = popen($command, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }

        $this->ui->outputData($this->output, $_cmd);
        return true;
    }

    function _svnFindPath($file)
    {
        $xml = '';
        $command = "svn info --xml $file";
        $fp = popen($command, "r");
        while ($line = fgets($fp, 1024)) {
            $xml .= rtrim($line)."\n";
        }
        pclose($fp);
        $url_tag = strpos($xml, '<url>');
        $url = substr($xml, $url_tag + 5, strpos($xml, '</url>', $url_tag + 5) - ($url_tag + 5));

        $path = array();
        $path['from'] = substr($url, 0, strrpos($url, '/'));
        $path['base'] = substr($path['from'], 0, strrpos($path['from'], '/') + 1);

        // Figure out the local paths - see http://pear.php.net/bugs/17463
        $pos = strpos($file, DIRECTORY_SEPARATOR . 'trunk' . DIRECTORY_SEPARATOR);
        if ($pos === false) {
            $pos = strpos($file, DIRECTORY_SEPARATOR . 'branches' . DIRECTORY_SEPARATOR);
        }
        $path['local']['base'] = substr($file, 0, $pos + 1);

        return $path;
    }

    function _svnRemoveTag($version, $package, $tag, $packageFile, $options)
    {
        $command = 'svn';

        if (isset($options['quiet'])) {
            $command .= ' -q';
        }

        $command .= ' remove';
        $command .= ' -m "Removing tag for the ' . $version  . ' release."';

        $path = $this->_svnFindPath($packageFile);
        $command .= ' ' . $path['base'] . 'tags/' . $tag;


        if ($this->config->get('verbose') > 1) {
            $this->output .= "+ $command\n";
        }

        $this->output .= "+ $command\n";
        if (empty($options['dry-run'])) {
            $fp = popen($command, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }

        $this->ui->outputData($this->output, $command);
        return true;
    }

    function doCvsTag($command, $options, $params)
    {
        $this->output = '';
        $_cmd = $command;
        if (count($params) < 1) {
            $help = $this->getHelp($command);
            return $this->raiseError("$command: missing parameter: $help[0]");
        }

        $packageFile = realpath($params[0]);
        $obj  = &$this->getPackageFile($this->config, $this->_debug);
        $info = $obj->fromAnyFile($packageFile, PEAR_VALIDATE_NORMAL);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $err = $warn = array();
        if (!$info->validate()) {
            foreach ($info->getValidationWarnings() as $error) {
                if ($error['level'] == 'warning') {
                    $warn[] = $error['message'];
                } else {
                    $err[] = $error['message'];
                }
            }
        }

        if (!$this->_displayValidationResults($err, $warn, true)) {
            $this->ui->outputData($this->output, $command);
            return $this->raiseError('CVS tag failed');
        }

        $version    = $info->getVersion();
        $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $version);
        $cvstag     = "RELEASE_$cvsversion";
        $files      = array_keys($info->getFilelist());
        $command = 'cvs';
        if (isset($options['quiet'])) {
            $command .= ' -q';
        }

        if (isset($options['reallyquiet'])) {
            $command .= ' -Q';
        }

        $command .= ' tag';
        if (isset($options['slide'])) {
            $command .= ' -F';
        }

        if (isset($options['delete'])) {
            $command .= ' -d';
        }

        $command .= ' ' . $cvstag . ' ' . escapeshellarg($params[0]);
        array_shift($params);
        if (count($params)) {
            // add in additional files to be tagged
            $files = array_merge($files, $params);
        }

        $dir = dirname($packageFile);
        $dir = substr($dir, strrpos($dir, '/') + 1);
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $file = $dir . DIRECTORY_SEPARATOR . $file;
            }
            $command .= ' ' . escapeshellarg($file);
        }

        if ($this->config->get('verbose') > 1) {
            $this->output .= "+ $command\n";
        }

        $this->output .= "+ $command\n";
        if (empty($options['dry-run'])) {
            $fp = popen($command, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }

        $this->ui->outputData($this->output, $_cmd);
        return true;
    }

    function doCvsDiff($command, $options, $params)
    {
        $this->output = '';
        if (sizeof($params) < 1) {
            $help = $this->getHelp($command);
            return $this->raiseError("$command: missing parameter: $help[0]");
        }

        $file = realpath($params[0]);
        $obj  = &$this->getPackageFile($this->config, $this->_debug);
        $info = $obj->fromAnyFile($file, PEAR_VALIDATE_NORMAL);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $err = $warn = array();
        if (!$info->validate()) {
            foreach ($info->getValidationWarnings() as $error) {
                if ($error['level'] == 'warning') {
                    $warn[] = $error['message'];
                } else {
                    $err[] = $error['message'];
                }
            }
        }

        if (!$this->_displayValidationResults($err, $warn, true)) {
            $this->ui->outputData($this->output, $command);
            return $this->raiseError('CVS diff failed');
        }

        $info1 = $info->getFilelist();
        $files = $info1;
        $cmd = "cvs";
        if (isset($options['quiet'])) {
            $cmd .= ' -q';
            unset($options['quiet']);
        }

        if (isset($options['reallyquiet'])) {
            $cmd .= ' -Q';
            unset($options['reallyquiet']);
        }

        if (isset($options['release'])) {
            $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $options['release']);
            $cvstag = "RELEASE_$cvsversion";
            $options['revision'] = $cvstag;
            unset($options['release']);
        }

        $execute = true;
        if (isset($options['dry-run'])) {
            $execute = false;
            unset($options['dry-run']);
        }

        $cmd .= ' diff';
        // the rest of the options are passed right on to "cvs diff"
        foreach ($options as $option => $optarg) {
            $arg = $short = false;
            if (isset($this->commands[$command]['options'][$option])) {
                $arg = $this->commands[$command]['options'][$option]['arg'];
                $short = $this->commands[$command]['options'][$option]['shortopt'];
            }
            $cmd .= $short ? " -$short" : " --$option";
            if ($arg && $optarg) {
                $cmd .= ($short ? '' : '=') . escapeshellarg($optarg);
            }
        }

        foreach ($files as $file) {
            $cmd .= ' ' . escapeshellarg($file['name']);
        }

        if ($this->config->get('verbose') > 1) {
            $this->output .= "+ $cmd\n";
        }

        if ($execute) {
            $fp = popen($cmd, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }

        $this->ui->outputData($this->output, $command);
        return true;
    }

    function doPackageDependencies($command, $options, $params)
    {
        // $params[0] -> the PEAR package to list its information
        if (count($params) !== 1) {
            return $this->raiseError("bad parameter(s), try \"help $command\"");
        }

        $obj = &$this->getPackageFile($this->config, $this->_debug);
        if (is_file($params[0]) || strpos($params[0], '.xml') > 0) {
           $info = $obj->fromAnyFile($params[0], PEAR_VALIDATE_NORMAL);
        } else {
            $reg  = $this->config->getRegistry();
            $info = $obj->fromArray($reg->packageInfo($params[0]));
        }

        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $deps = $info->getDeps();
        if (is_array($deps)) {
            if ($info->getPackagexmlVersion() == '1.0') {
                $data = array(
                    'caption' => 'Dependencies for pear/' . $info->getPackage(),
                    'border' => true,
                    'headline' => array("Required?", "Type", "Name", "Relation", "Version"),
                    );

                foreach ($deps as $d) {
                    if (isset($d['optional'])) {
                        if ($d['optional'] == 'yes') {
                            $req = 'No';
                        } else {
                            $req = 'Yes';
                        }
                    } else {
                        $req = 'Yes';
                    }

                    if (isset($this->_deps_rel_trans[$d['rel']])) {
                        $rel = $this->_deps_rel_trans[$d['rel']];
                    } else {
                        $rel = $d['rel'];
                    }

                    if (isset($this->_deps_type_trans[$d['type']])) {
                        $type = ucfirst($this->_deps_type_trans[$d['type']]);
                    } else {
                        $type = $d['type'];
                    }

                    if (isset($d['name'])) {
                        $name = $d['name'];
                    } else {
                        $name = '';
                    }

                    if (isset($d['version'])) {
                        $version = $d['version'];
                    } else {
                        $version = '';
                    }

                    $data['data'][] = array($req, $type, $name, $rel, $version);
                }
            } else { // package.xml 2.0 dependencies display
                require_once 'PEAR/Dependency2.php';
                $deps = $info->getDependencies();
                $reg = &$this->config->getRegistry();
                if (is_array($deps)) {
                    $d = new PEAR_Dependency2($this->config, array(), '');
                    $data = array(
                        'caption' => 'Dependencies for ' . $info->getPackage(),
                        'border' => true,
                        'headline' => array("Required?", "Type", "Name", 'Versioning', 'Group'),
                        );
                    foreach ($deps as $type => $subd) {
                        $req = ($type == 'required') ? 'Yes' : 'No';
                        if ($type == 'group') {
                            $group = $subd['attribs']['name'];
                        } else {
                            $group = '';
                        }

                        if (!isset($subd[0])) {
                            $subd = array($subd);
                        }

                        foreach ($subd as $groupa) {
                            foreach ($groupa as $deptype => $depinfo) {
                                if ($deptype == 'attribs') {
                                    continue;
                                }

                                if ($deptype == 'pearinstaller') {
                                    $deptype = 'pear Installer';
                                }

                                if (!isset($depinfo[0])) {
                                    $depinfo = array($depinfo);
                                }

                                foreach ($depinfo as $inf) {
                                    $name = '';
                                    if (isset($inf['channel'])) {
                                        $alias = $reg->channelAlias($inf['channel']);
                                        if (!$alias) {
                                            $alias = '(channel?) ' .$inf['channel'];
                                        }
                                        $name = $alias . '/';

                                    }
                                    if (isset($inf['name'])) {
                                        $name .= $inf['name'];
                                    } elseif (isset($inf['pattern'])) {
                                        $name .= $inf['pattern'];
                                    } else {
                                        $name .= '';
                                    }

                                    if (isset($inf['uri'])) {
                                        $name .= ' [' . $inf['uri'] .  ']';
                                    }

                                    if (isset($inf['conflicts'])) {
                                        $ver = 'conflicts';
                                    } else {
                                        $ver = $d->_getExtraString($inf);
                                    }

                                    $data['data'][] = array($req, ucfirst($deptype), $name,
                                        $ver, $group);
                                }
                            }
                        }
                    }
                }
            }

            $this->ui->outputData($data, $command);
            return true;
        }

        // Fallback
        $this->ui->outputData("This package does not have any dependencies.", $command);
    }

    function doSign($command, $options, $params)
    {
        // should move most of this code into PEAR_Packager
        // so it'll be easy to implement "pear package --sign"
        if (count($params) !== 1) {
            return $this->raiseError("bad parameter(s), try \"help $command\"");
        }

        require_once 'System.php';
        require_once 'Archive/Tar.php';

        if (!file_exists($params[0])) {
            return $this->raiseError("file does not exist: $params[0]");
        }

        $obj = $this->getPackageFile($this->config, $this->_debug);
        $info = $obj->fromTgzFile($params[0], PEAR_VALIDATE_NORMAL);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }

        $tar = new Archive_Tar($params[0]);

        $tmpdir = $this->config->get('temp_dir');
        $tmpdir = System::mktemp(' -t "' . $tmpdir . '" -d pearsign');
        if (!$tar->extractList('package2.xml package.xml package.sig', $tmpdir)) {
            return $this->raiseError("failed to extract tar file");
        }

        if (file_exists("$tmpdir/package.sig")) {
            return $this->raiseError("package already signed");
        }

        $packagexml = 'package.xml';
        if (file_exists("$tmpdir/package2.xml")) {
            $packagexml = 'package2.xml';
        }

        if (file_exists("$tmpdir/package.sig")) {
            unlink("$tmpdir/package.sig");
        }

        if (!file_exists("$tmpdir/$packagexml")) {
            return $this->raiseError("Extracted file $tmpdir/$packagexml not found.");
        }

        $input = $this->ui->userDialog($command,
                                       array('GnuPG Passphrase'),
                                       array('password'));
        if (!isset($input[0])) {
            //use empty passphrase
            $input[0] = '';
        }

        $devnull = (isset($options['verbose'])) ? '' : ' 2>/dev/null';
        $gpg = popen("gpg --batch --passphrase-fd 0 --armor --detach-sign --output $tmpdir/package.sig $tmpdir/$packagexml" . $devnull, "w");
        if (!$gpg) {
            return $this->raiseError("gpg command failed");
        }

        fwrite($gpg, "$input[0]\n");
        if (pclose($gpg) || !file_exists("$tmpdir/package.sig")) {
            return $this->raiseError("gpg sign failed");
        }

        if (!$tar->addModify("$tmpdir/package.sig", '', $tmpdir)) {
            return $this->raiseError('failed adding signature to file');
        }

        $this->ui->outputData("Package signed.", $command);
        return true;
    }

    /**
     * For unit testing purposes
     */
    function &getInstaller(&$ui)
    {
        if (!class_exists('PEAR_Installer')) {
            require_once 'PEAR/Installer.php';
        }
        $a = &new PEAR_Installer($ui);
        return $a;
    }

    /**
     * For unit testing purposes
     */
    function &getCommandPackaging(&$ui, &$config)
    {
        if (!class_exists('PEAR_Command_Packaging')) {
            if ($fp = @fopen('PEAR/Command/Packaging.php', 'r', true)) {
                fclose($fp);
                include_once 'PEAR/Command/Packaging.php';
            }
        }

        if (class_exists('PEAR_Command_Packaging')) {
            $a = &new PEAR_Command_Packaging($ui, $config);
        } else {
            $a = null;
        }

        return $a;
    }

    function doMakeRPM($command, $options, $params)
    {

        // Check to see if PEAR_Command_Packaging is installed, and
        // transparently switch to use the "make-rpm-spec" command from it
        // instead, if it does. Otherwise, continue to use the old version
        // of "makerpm" supplied with this package (PEAR).
        $packaging_cmd = $this->getCommandPackaging($this->ui, $this->config);
        if ($packaging_cmd !== null) {
            $this->ui->outputData('PEAR_Command_Packaging is installed; using '.
                'newer "make-rpm-spec" command instead');
            return $packaging_cmd->run('make-rpm-spec', $options, $params);
        }

        $this->ui->outputData('WARNING: "pear makerpm" is no longer available; an '.
          'improved version is available via "pear make-rpm-spec", which '.
          'is available by installing PEAR_Command_Packaging');
        return true;
    }

    function doConvert($command, $options, $params)
    {
        $packagexml    = isset($params[0]) ? $params[0] : 'package.xml';
        $newpackagexml = isset($params[1]) ? $params[1] : dirname($packagexml) .
            DIRECTORY_SEPARATOR . 'package2.xml';
        $pkg = &$this->getPackageFile($this->config, $this->_debug);
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $pf = $pkg->fromPackageFile($packagexml, PEAR_VALIDATE_NORMAL);
        PEAR::staticPopErrorHandling();
        if (PEAR::isError($pf)) {
            if (is_array($pf->getUserInfo())) {
                foreach ($pf->getUserInfo() as $warning) {
                    $this->ui->outputData($warning['message']);
                }
            }
            return $this->raiseError($pf);
        }

        if (is_a($pf, 'PEAR_PackageFile_v2')) {
            $this->ui->outputData($packagexml . ' is already a package.xml version 2.0');
            return true;
        }

        $gen   = &$pf->getDefaultGenerator();
        $newpf = &$gen->toV2();
        $newpf->setPackagefile($newpackagexml);
        $gen = &$newpf->getDefaultGenerator();
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $state = (isset($options['flat']) ? PEAR_VALIDATE_PACKAGING : PEAR_VALIDATE_NORMAL);
        $saved = $gen->toPackageFile(dirname($newpackagexml), $state, basename($newpackagexml));
        PEAR::staticPopErrorHandling();
        if (PEAR::isError($saved)) {
            if (is_array($saved->getUserInfo())) {
                foreach ($saved->getUserInfo() as $warning) {
                    $this->ui->outputData($warning['message']);
                }
            }

            $this->ui->outputData($saved->getMessage());
            return true;
        }

        $this->ui->outputData('Wrote new version 2.0 package.xml to "' . $saved . '"');
        return true;
    }
}