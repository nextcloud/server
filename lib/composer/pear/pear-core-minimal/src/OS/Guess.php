<?php
/**
 * The OS_Guess class
 *
 * PHP versions 4 and 5
 *
 * @category  pear
 * @package   PEAR
 * @author    Stig Bakken <ssb@php.net>
 * @author    Gregory Beaver <cellog@php.net>
 * @copyright 1997-2009 The Authors
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/PEAR
 * @since     File available since PEAR 0.1
 */

// {{{ uname examples

// php_uname() without args returns the same as 'uname -a', or a PHP-custom
// string for Windows.
// PHP versions prior to 4.3 return the uname of the host where PHP was built,
// as of 4.3 it returns the uname of the host running the PHP code.
//
// PC RedHat Linux 7.1:
// Linux host.example.com 2.4.2-2 #1 Sun Apr 8 20:41:30 EDT 2001 i686 unknown
//
// PC Debian Potato:
// Linux host 2.4.17 #2 SMP Tue Feb 12 15:10:04 CET 2002 i686 unknown
//
// PC FreeBSD 3.3:
// FreeBSD host.example.com 3.3-STABLE FreeBSD 3.3-STABLE #0: Mon Feb 21 00:42:31 CET 2000     root@example.com:/usr/src/sys/compile/CONFIG  i386
//
// PC FreeBSD 4.3:
// FreeBSD host.example.com 4.3-RELEASE FreeBSD 4.3-RELEASE #1: Mon Jun 25 11:19:43 EDT 2001     root@example.com:/usr/src/sys/compile/CONFIG  i386
//
// PC FreeBSD 4.5:
// FreeBSD host.example.com 4.5-STABLE FreeBSD 4.5-STABLE #0: Wed Feb  6 23:59:23 CET 2002     root@example.com:/usr/src/sys/compile/CONFIG  i386
//
// PC FreeBSD 4.5 w/uname from GNU shellutils:
// FreeBSD host.example.com 4.5-STABLE FreeBSD 4.5-STABLE #0: Wed Feb  i386 unknown
//
// HP 9000/712 HP-UX 10:
// HP-UX iq B.10.10 A 9000/712 2008429113 two-user license
//
// HP 9000/712 HP-UX 10 w/uname from GNU shellutils:
// HP-UX host B.10.10 A 9000/712 unknown
//
// IBM RS6000/550 AIX 4.3:
// AIX host 3 4 000003531C00
//
// AIX 4.3 w/uname from GNU shellutils:
// AIX host 3 4 000003531C00 unknown
//
// SGI Onyx IRIX 6.5 w/uname from GNU shellutils:
// IRIX64 host 6.5 01091820 IP19 mips
//
// SGI Onyx IRIX 6.5:
// IRIX64 host 6.5 01091820 IP19
//
// SparcStation 20 Solaris 8 w/uname from GNU shellutils:
// SunOS host.example.com 5.8 Generic_108528-12 sun4m sparc
//
// SparcStation 20 Solaris 8:
// SunOS host.example.com 5.8 Generic_108528-12 sun4m sparc SUNW,SPARCstation-20
//
// Mac OS X (Darwin)
// Darwin home-eden.local 7.5.0 Darwin Kernel Version 7.5.0: Thu Aug  5 19:26:16 PDT 2004; root:xnu/xnu-517.7.21.obj~3/RELEASE_PPC  Power Macintosh
//
// Mac OS X early versions
//

// }}}

/* TODO:
 * - define endianness, to allow matchSignature("bigend") etc.
 */

/**
 * Retrieves information about the current operating system
 *
 * This class uses php_uname() to grok information about the current OS
 *
 * @category  pear
 * @package   PEAR
 * @author    Stig Bakken <ssb@php.net>
 * @author    Gregory Beaver <cellog@php.net>
 * @copyright 1997-2020 The Authors
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PEAR
 * @since     Class available since Release 0.1
 */
class OS_Guess
{
    var $sysname;
    var $nodename;
    var $cpu;
    var $release;
    var $extra;

    function __construct($uname = null)
    {
        list($this->sysname,
             $this->release,
             $this->cpu,
             $this->extra,
             $this->nodename) = $this->parseSignature($uname);
    }

    function parseSignature($uname = null)
    {
        static $sysmap = array(
            'HP-UX' => 'hpux',
            'IRIX64' => 'irix',
        );
        static $cpumap = array(
            'i586' => 'i386',
            'i686' => 'i386',
            'ppc' => 'powerpc',
        );
        if ($uname === null) {
            $uname = php_uname();
        }
        $parts = preg_split('/\s+/', trim($uname));
        $n = count($parts);

        $release  = $machine = $cpu = '';
        $sysname  = $parts[0];
        $nodename = $parts[1];
        $cpu      = $parts[$n-1];
        $extra = '';
        if ($cpu == 'unknown') {
            $cpu = $parts[$n - 2];
        }

        switch ($sysname) {
            case 'AIX' :
                $release = "$parts[3].$parts[2]";
                break;
            case 'Windows' :
                $release = $parts[1];
                if ($release == '95/98') {
                    $release = '9x';
                }
                $cpu = 'i386';
                break;
            case 'Linux' :
                $extra = $this->_detectGlibcVersion();
                // use only the first two digits from the kernel version
                $release = preg_replace('/^([0-9]+\.[0-9]+).*/', '\1', $parts[2]);
                break;
            case 'Mac' :
                $sysname = 'darwin';
                $nodename = $parts[2];
                $release = $parts[3];
                $cpu = $this->_determineIfPowerpc($cpu, $parts);
                break;
            case 'Darwin' :
                $cpu = $this->_determineIfPowerpc($cpu, $parts);
                $release = preg_replace('/^([0-9]+\.[0-9]+).*/', '\1', $parts[2]);
                break;
            default:
                $release = preg_replace('/-.*/', '', $parts[2]);
                break;
        }

        if (isset($sysmap[$sysname])) {
            $sysname = $sysmap[$sysname];
        } else {
            $sysname = strtolower($sysname);
        }
        if (isset($cpumap[$cpu])) {
            $cpu = $cpumap[$cpu];
        }
        return array($sysname, $release, $cpu, $extra, $nodename);
    }

    function _determineIfPowerpc($cpu, $parts)
    {
        $n = count($parts);
        if ($cpu == 'Macintosh' && $parts[$n - 2] == 'Power') {
            $cpu = 'powerpc';
        }
        return $cpu;
    }

    function _detectGlibcVersion()
    {
        static $glibc = false;
        if ($glibc !== false) {
            return $glibc; // no need to run this multiple times
        }
        $major = $minor = 0;
        include_once "System.php";

        // Let's try reading possible libc.so.6 symlinks
        $libcs = array(
            '/lib64/libc.so.6',
            '/lib/libc.so.6',
            '/lib/i386-linux-gnu/libc.so.6'
        );
        $versions = array();
        foreach ($libcs as $file) {
            $versions = $this->_readGlibCVersionFromSymlink($file);
            if ($versions != []) {
                list($major, $minor) = $versions;
                break;
            }
        }

        // Use glibc's <features.h> header file to
        // get major and minor version number:
        if (!($major && $minor)) {
            $versions = $this->_readGlibCVersionFromFeaturesHeaderFile();
        }
        if (is_array($versions) && $versions != []) {
            list($major, $minor) = $versions;
        }

        if (!($major && $minor)) {
            return $glibc = '';
        }

        return $glibc = "glibc{$major}.{$minor}";
    }

    function _readGlibCVersionFromSymlink($file)
    {
        $versions = array();
        if (@is_link($file)
            && (preg_match('/^libc-(.*)\.so$/', basename(readlink($file)), $matches))
        ) {
            $versions = explode('.', $matches[1]);
        }
        return $versions;
    }


    function _readGlibCVersionFromFeaturesHeaderFile()
    {
        $features_header_file = '/usr/include/features.h';
        if (!(@file_exists($features_header_file)
            && @is_readable($features_header_file))
        ) {
            return array();
        }
        if (!@file_exists('/usr/bin/cpp') || !@is_executable('/usr/bin/cpp')) {
            return $this-_parseFeaturesHeaderFile($features_header_file);
        } // no cpp

        return $this->_fromGlibCTest();
    }

    function _parseFeaturesHeaderFile($features_header_file)
    {
        $features_file = fopen($features_header_file, 'rb');
        while (!feof($features_file)) {
            $line = fgets($features_file, 8192);
            if (!$this->_IsADefinition($line)) {
                continue;
            }
            if (strpos($line, '__GLIBC__')) {
                // major version number #define __GLIBC__ version
                $line = preg_split('/\s+/', $line);
                $glibc_major = trim($line[2]);
                if (isset($glibc_minor)) {
                    break;
                }
                continue;
            }

            if (strpos($line, '__GLIBC_MINOR__')) {
                // got the minor version number
                // #define __GLIBC_MINOR__ version
                $line = preg_split('/\s+/', $line);
                $glibc_minor = trim($line[2]);
                if (isset($glibc_major)) {
                    break;
                }
            }
        }
        fclose($features_file);
        if (!isset($glibc_major) || !isset($glibc_minor)) {
            return array();
        }
        return array(trim($glibc_major), trim($glibc_minor));
    }

    function _IsADefinition($line)
    {
        if ($line === false) {
            return false;
        }
        return strpos(trim($line), '#define') !== false;
    }

    function _fromGlibCTest()
    {
        $major = null;
        $minor = null;

        $tmpfile = System::mktemp("glibctest");
        $fp = fopen($tmpfile, "w");
        fwrite($fp, "#include <features.h>\n__GLIBC__ __GLIBC_MINOR__\n");
        fclose($fp);
        $cpp = popen("/usr/bin/cpp $tmpfile", "r");
        while ($line = fgets($cpp, 1024)) {
            if ($line[0] == '#' || trim($line) == '') {
                continue;
            }

            if (list($major, $minor) = explode(' ', trim($line))) {
                break;
            }
        }
        pclose($cpp);
        unlink($tmpfile);
        if ($major !== null && $minor !== null) {
            return [$major, $minor];
        }
    }

    function getSignature()
    {
        if (empty($this->extra)) {
            return "{$this->sysname}-{$this->release}-{$this->cpu}";
        }
        return "{$this->sysname}-{$this->release}-{$this->cpu}-{$this->extra}";
    }

    function getSysname()
    {
        return $this->sysname;
    }

    function getNodename()
    {
        return $this->nodename;
    }

    function getCpu()
    {
        return $this->cpu;
    }

    function getRelease()
    {
        return $this->release;
    }

    function getExtra()
    {
        return $this->extra;
    }

    function matchSignature($match)
    {
        $fragments = is_array($match) ? $match : explode('-', $match);
        $n = count($fragments);
        $matches = 0;
        if ($n > 0) {
            $matches += $this->_matchFragment($fragments[0], $this->sysname);
        }
        if ($n > 1) {
            $matches += $this->_matchFragment($fragments[1], $this->release);
        }
        if ($n > 2) {
            $matches += $this->_matchFragment($fragments[2], $this->cpu);
        }
        if ($n > 3) {
            $matches += $this->_matchFragment($fragments[3], $this->extra);
        }
        return ($matches == $n);
    }

    function _matchFragment($fragment, $value)
    {
        if (strcspn($fragment, '*?') < strlen($fragment)) {
            $expression = str_replace(
                array('*', '?', '/'),
                array('.*', '.', '\\/'),
                $fragment
            );
            $reg = '/^' . $expression . '\\z/';
            return preg_match($reg, $value);
        }
        return ($fragment == '*' || !strcasecmp($fragment, $value));
    }
}
/*
 * Local Variables:
 * indent-tabs-mode: nil
 * c-basic-offset: 4
 * End:
 */
