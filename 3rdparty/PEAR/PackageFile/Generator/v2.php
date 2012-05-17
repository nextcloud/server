<?php
/**
 * package.xml generation class, package.xml version 2.0
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @author     Stephan Schmidt (original XML_Serializer code)
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: v2.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */
/**
 * file/dir manipulation routines
 */
require_once 'System.php';
require_once 'XML/Util.php';

/**
 * This class converts a PEAR_PackageFile_v2 object into any output format.
 *
 * Supported output formats include array, XML string (using S. Schmidt's
 * XML_Serializer, slightly customized)
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @author     Stephan Schmidt (original XML_Serializer code)
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a1
 */
class PEAR_PackageFile_Generator_v2
{
   /**
    * default options for the serialization
    * @access private
    * @var array $_defaultOptions
    */
    var $_defaultOptions = array(
        'indent'             => ' ',                    // string used for indentation
        'linebreak'          => "\n",                  // string used for newlines
        'typeHints'          => false,                 // automatically add type hin attributes
        'addDecl'            => true,                 // add an XML declaration
        'defaultTagName'     => 'XML_Serializer_Tag',  // tag used for indexed arrays or invalid names
        'classAsTagName'     => false,                 // use classname for objects in indexed arrays
        'keyAttribute'       => '_originalKey',        // attribute where original key is stored
        'typeAttribute'      => '_type',               // attribute for type (only if typeHints => true)
        'classAttribute'     => '_class',              // attribute for class of objects (only if typeHints => true)
        'scalarAsAttributes' => false,                 // scalar values (strings, ints,..) will be serialized as attribute
        'prependAttributes'  => '',                    // prepend string for attributes
        'indentAttributes'   => false,                 // indent the attributes, if set to '_auto', it will indent attributes so they all start at the same column
        'mode'               => 'simplexml',             // use 'simplexml' to use parent name as tagname if transforming an indexed array
        'addDoctype'         => false,                 // add a doctype declaration
        'doctype'            => null,                  // supply a string or an array with id and uri ({@see XML_Util::getDoctypeDeclaration()}
        'rootName'           => 'package',                  // name of the root tag
        'rootAttributes'     => array(
            'version' => '2.0',
            'xmlns' => 'http://pear.php.net/dtd/package-2.0',
            'xmlns:tasks' => 'http://pear.php.net/dtd/tasks-1.0',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://pear.php.net/dtd/tasks-1.0
http://pear.php.net/dtd/tasks-1.0.xsd
http://pear.php.net/dtd/package-2.0
http://pear.php.net/dtd/package-2.0.xsd',
        ),               // attributes of the root tag
        'attributesArray'    => 'attribs',                  // all values in this key will be treated as attributes
        'contentName'        => '_content',                   // this value will be used directly as content, instead of creating a new tag, may only be used in conjuction with attributesArray
        'beautifyFilelist'   => false,
        'encoding' => 'UTF-8',
    );

   /**
    * options for the serialization
    * @access private
    * @var array $options
    */
    var $options = array();

   /**
    * current tag depth
    * @var integer $_tagDepth
    */
    var $_tagDepth = 0;

   /**
    * serilialized representation of the data
    * @var string $_serializedData
    */
    var $_serializedData = null;
    /**
     * @var PEAR_PackageFile_v2
     */
    var $_packagefile;
    /**
     * @param PEAR_PackageFile_v2
     */
    function PEAR_PackageFile_Generator_v2(&$packagefile)
    {
        $this->_packagefile = &$packagefile;
        if (isset($this->_packagefile->encoding)) {
            $this->_defaultOptions['encoding'] = $this->_packagefile->encoding;
        }
    }

    /**
     * @return string
     */
    function getPackagerVersion()
    {
        return '1.9.4';
    }

    /**
     * @param PEAR_Packager
     * @param bool generate a .tgz or a .tar
     * @param string|null temporary directory to package in
     */
    function toTgz(&$packager, $compress = true, $where = null)
    {
        $a = null;
        return $this->toTgz2($packager, $a, $compress, $where);
    }

    /**
     * Package up both a package.xml and package2.xml for the same release
     * @param PEAR_Packager
     * @param PEAR_PackageFile_v1
     * @param bool generate a .tgz or a .tar
     * @param string|null temporary directory to package in
     */
    function toTgz2(&$packager, &$pf1, $compress = true, $where = null)
    {
        require_once 'Archive/Tar.php';
        if (!$this->_packagefile->isEquivalent($pf1)) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: "' .
                basename($pf1->getPackageFile()) .
                '" is not equivalent to "' . basename($this->_packagefile->getPackageFile())
                . '"');
        }

        if ($where === null) {
            if (!($where = System::mktemp(array('-d')))) {
                return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: mktemp failed');
            }
        } elseif (!@System::mkDir(array('-p', $where))) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: "' . $where . '" could' .
                ' not be created');
        }

        $file = $where . DIRECTORY_SEPARATOR . 'package.xml';
        if (file_exists($file) && !is_file($file)) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: unable to save package.xml as' .
                ' "' . $file  .'"');
        }

        if (!$this->_packagefile->validate(PEAR_VALIDATE_PACKAGING)) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: invalid package.xml');
        }

        $ext = $compress ? '.tgz' : '.tar';
        $pkgver = $this->_packagefile->getPackage() . '-' . $this->_packagefile->getVersion();
        $dest_package = getcwd() . DIRECTORY_SEPARATOR . $pkgver . $ext;
        if (file_exists($dest_package) && !is_file($dest_package)) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: cannot create tgz file "' .
                $dest_package . '"');
        }

        $pkgfile = $this->_packagefile->getPackageFile();
        if (!$pkgfile) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toTgz: package file object must ' .
                'be created from a real file');
        }

        $pkgdir  = dirname(realpath($pkgfile));
        $pkgfile = basename($pkgfile);

        // {{{ Create the package file list
        $filelist = array();
        $i = 0;
        $this->_packagefile->flattenFilelist();
        $contents = $this->_packagefile->getContents();
        if (isset($contents['bundledpackage'])) { // bundles of packages
            $contents = $contents['bundledpackage'];
            if (!isset($contents[0])) {
                $contents = array($contents);
            }

            $packageDir = $where;
            foreach ($contents as $i => $package) {
                $fname = $package;
                $file = $pkgdir . DIRECTORY_SEPARATOR . $fname;
                if (!file_exists($file)) {
                    return $packager->raiseError("File does not exist: $fname");
                }

                $tfile = $packageDir . DIRECTORY_SEPARATOR . $fname;
                System::mkdir(array('-p', dirname($tfile)));
                copy($file, $tfile);
                $filelist[$i++] = $tfile;
                $packager->log(2, "Adding package $fname");
            }
        } else { // normal packages
            $contents = $contents['dir']['file'];
            if (!isset($contents[0])) {
                $contents = array($contents);
            }

            $packageDir = $where;
            foreach ($contents as $i => $file) {
                $fname = $file['attribs']['name'];
                $atts = $file['attribs'];
                $orig = $file;
                $file = $pkgdir . DIRECTORY_SEPARATOR . $fname;
                if (!file_exists($file)) {
                    return $packager->raiseError("File does not exist: $fname");
                }

                $origperms = fileperms($file);
                $tfile = $packageDir . DIRECTORY_SEPARATOR . $fname;
                unset($orig['attribs']);
                if (count($orig)) { // file with tasks
                    // run any package-time tasks
                    $contents = file_get_contents($file);
                    foreach ($orig as $tag => $raw) {
                        $tag = str_replace(
                            array($this->_packagefile->getTasksNs() . ':', '-'),
                            array('', '_'), $tag);
                        $task = "PEAR_Task_$tag";
                        $task = &new $task($this->_packagefile->_config,
                            $this->_packagefile->_logger,
                            PEAR_TASK_PACKAGE);
                        $task->init($raw, $atts, null);
                        $res = $task->startSession($this->_packagefile, $contents, $tfile);
                        if (!$res) {
                            continue; // skip this task
                        }

                        if (PEAR::isError($res)) {
                            return $res;
                        }

                        $contents = $res; // save changes
                        System::mkdir(array('-p', dirname($tfile)));
                        $wp = fopen($tfile, "wb");
                        fwrite($wp, $contents);
                        fclose($wp);
                    }
                }

                if (!file_exists($tfile)) {
                    System::mkdir(array('-p', dirname($tfile)));
                    copy($file, $tfile);
                }

                chmod($tfile, $origperms);
                $filelist[$i++] = $tfile;
                $this->_packagefile->setFileAttribute($fname, 'md5sum', md5_file($tfile), $i - 1);
                $packager->log(2, "Adding file $fname");
            }
        }
            // }}}

        $name       = $pf1 !== null ? 'package2.xml' : 'package.xml';
        $packagexml = $this->toPackageFile($where, PEAR_VALIDATE_PACKAGING, $name);
        if ($packagexml) {
            $tar = new Archive_Tar($dest_package, $compress);
            $tar->setErrorHandling(PEAR_ERROR_RETURN); // XXX Don't print errors
            // ----- Creates with the package.xml file
            $ok = $tar->createModify(array($packagexml), '', $where);
            if (PEAR::isError($ok)) {
                return $packager->raiseError($ok);
            } elseif (!$ok) {
                return $packager->raiseError('PEAR_Packagefile_v2::toTgz(): adding ' . $name .
                    ' failed');
            }

            // ----- Add the content of the package
            if (!$tar->addModify($filelist, $pkgver, $where)) {
                return $packager->raiseError(
                    'PEAR_Packagefile_v2::toTgz(): tarball creation failed');
            }

            // add the package.xml version 1.0
            if ($pf1 !== null) {
                $pfgen = &$pf1->getDefaultGenerator();
                $packagexml1 = $pfgen->toPackageFile($where, PEAR_VALIDATE_PACKAGING, 'package.xml', true);
                if (!$tar->addModify(array($packagexml1), '', $where)) {
                    return $packager->raiseError(
                        'PEAR_Packagefile_v2::toTgz(): adding package.xml failed');
                }
            }

            return $dest_package;
        }
    }

    function toPackageFile($where = null, $state = PEAR_VALIDATE_NORMAL, $name = 'package.xml')
    {
        if (!$this->_packagefile->validate($state)) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toPackageFile: invalid package.xml',
                null, null, null, $this->_packagefile->getValidationWarnings());
        }

        if ($where === null) {
            if (!($where = System::mktemp(array('-d')))) {
                return PEAR::raiseError('PEAR_Packagefile_v2::toPackageFile: mktemp failed');
            }
        } elseif (!@System::mkDir(array('-p', $where))) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toPackageFile: "' . $where . '" could' .
                ' not be created');
        }

        $newpkgfile = $where . DIRECTORY_SEPARATOR . $name;
        $np = @fopen($newpkgfile, 'wb');
        if (!$np) {
            return PEAR::raiseError('PEAR_Packagefile_v2::toPackageFile: unable to save ' .
               "$name as $newpkgfile");
        }
        fwrite($np, $this->toXml($state));
        fclose($np);
        return $newpkgfile;
    }

    function &toV2()
    {
        return $this->_packagefile;
    }

    /**
     * Return an XML document based on the package info (as returned
     * by the PEAR_Common::infoFrom* methods).
     *
     * @return string XML data
     */
    function toXml($state = PEAR_VALIDATE_NORMAL, $options = array())
    {
        $this->_packagefile->setDate(date('Y-m-d'));
        $this->_packagefile->setTime(date('H:i:s'));
        if (!$this->_packagefile->validate($state)) {
            return false;
        }

        if (is_array($options)) {
            $this->options = array_merge($this->_defaultOptions, $options);
        } else {
            $this->options = $this->_defaultOptions;
        }

        $arr = $this->_packagefile->getArray();
        if (isset($arr['filelist'])) {
            unset($arr['filelist']);
        }

        if (isset($arr['_lastversion'])) {
            unset($arr['_lastversion']);
        }

        // Fix the notes a little bit
        if (isset($arr['notes'])) {
            // This trims out the indenting, needs fixing
            $arr['notes'] = "\n" . trim($arr['notes']) . "\n";
        }

        if (isset($arr['changelog']) && !empty($arr['changelog'])) {
            // Fix for inconsistency how the array is filled depending on the changelog release amount
            if (!isset($arr['changelog']['release'][0])) {
                $release = $arr['changelog']['release'];
                unset($arr['changelog']['release']);

                $arr['changelog']['release']    = array();
                $arr['changelog']['release'][0] = $release;
            }

            foreach (array_keys($arr['changelog']['release']) as $key) {
                $c =& $arr['changelog']['release'][$key];
                if (isset($c['notes'])) {
                    // This trims out the indenting, needs fixing
                    $c['notes'] = "\n" . trim($c['notes']) . "\n";
                }
            }
        }

        if ($state ^ PEAR_VALIDATE_PACKAGING && !isset($arr['bundle'])) {
            $use = $this->_recursiveXmlFilelist($arr['contents']['dir']['file']);
            unset($arr['contents']['dir']['file']);
            if (isset($use['dir'])) {
                $arr['contents']['dir']['dir'] = $use['dir'];
            }
            if (isset($use['file'])) {
                $arr['contents']['dir']['file'] = $use['file'];
            }
            $this->options['beautifyFilelist'] = true;
        }

        $arr['attribs']['packagerversion'] = '1.9.4';
        if ($this->serialize($arr, $options)) {
            return $this->_serializedData . "\n";
        }

        return false;
    }


    function _recursiveXmlFilelist($list)
    {
        $dirs = array();
        if (isset($list['attribs'])) {
            $file = $list['attribs']['name'];
            unset($list['attribs']['name']);
            $attributes = $list['attribs'];
            $this->_addDir($dirs, explode('/', dirname($file)), $file, $attributes);
        } else {
            foreach ($list as $a) {
                $file = $a['attribs']['name'];
                $attributes = $a['attribs'];
                unset($a['attribs']);
                $this->_addDir($dirs, explode('/', dirname($file)), $file, $attributes, $a);
            }
        }
        $this->_formatDir($dirs);
        $this->_deFormat($dirs);
        return $dirs;
    }

    function _addDir(&$dirs, $dir, $file = null, $attributes = null, $tasks = null)
    {
        if (!$tasks) {
            $tasks = array();
        }
        if ($dir == array() || $dir == array('.')) {
            $dirs['file'][basename($file)] = $tasks;
            $attributes['name'] = basename($file);
            $dirs['file'][basename($file)]['attribs'] = $attributes;
            return;
        }
        $curdir = array_shift($dir);
        if (!isset($dirs['dir'][$curdir])) {
            $dirs['dir'][$curdir] = array();
        }
        $this->_addDir($dirs['dir'][$curdir], $dir, $file, $attributes, $tasks);
    }

    function _formatDir(&$dirs)
    {
        if (!count($dirs)) {
            return array();
        }
        $newdirs = array();
        if (isset($dirs['dir'])) {
            $newdirs['dir'] = $dirs['dir'];
        }
        if (isset($dirs['file'])) {
            $newdirs['file'] = $dirs['file'];
        }
        $dirs = $newdirs;
        if (isset($dirs['dir'])) {
            uksort($dirs['dir'], 'strnatcasecmp');
            foreach ($dirs['dir'] as $dir => $contents) {
                $this->_formatDir($dirs['dir'][$dir]);
            }
        }
        if (isset($dirs['file'])) {
            uksort($dirs['file'], 'strnatcasecmp');
        };
    }

    function _deFormat(&$dirs)
    {
        if (!count($dirs)) {
            return array();
        }
        $newdirs = array();
        if (isset($dirs['dir'])) {
            foreach ($dirs['dir'] as $dir => $contents) {
                $newdir = array();
                $newdir['attribs']['name'] = $dir;
                $this->_deFormat($contents);
                foreach ($contents as $tag => $val) {
                    $newdir[$tag] = $val;
                }
                $newdirs['dir'][] = $newdir;
            }
            if (count($newdirs['dir']) == 1) {
                $newdirs['dir'] = $newdirs['dir'][0];
            }
        }
        if (isset($dirs['file'])) {
            foreach ($dirs['file'] as $name => $file) {
                $newdirs['file'][] = $file;
            }
            if (count($newdirs['file']) == 1) {
                $newdirs['file'] = $newdirs['file'][0];
            }
        }
        $dirs = $newdirs;
    }

    /**
    * reset all options to default options
    *
    * @access   public
    * @see      setOption(), XML_Unserializer()
    */
    function resetOptions()
    {
        $this->options = $this->_defaultOptions;
    }

   /**
    * set an option
    *
    * You can use this method if you do not want to set all options in the constructor
    *
    * @access   public
    * @see      resetOption(), XML_Serializer()
    */
    function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

   /**
    * sets several options at once
    *
    * You can use this method if you do not want to set all options in the constructor
    *
    * @access   public
    * @see      resetOption(), XML_Unserializer(), setOption()
    */
    function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

   /**
    * serialize data
    *
    * @access   public
    * @param    mixed    $data data to serialize
    * @return   boolean  true on success, pear error on failure
    */
    function serialize($data, $options = null)
    {
        // if options have been specified, use them instead
        // of the previously defined ones
        if (is_array($options)) {
            $optionsBak = $this->options;
            if (isset($options['overrideOptions']) && $options['overrideOptions'] == true) {
                $this->options = array_merge($this->_defaultOptions, $options);
            } else {
                $this->options = array_merge($this->options, $options);
            }
        } else {
            $optionsBak = null;
        }

        //  start depth is zero
        $this->_tagDepth = 0;
        $this->_serializedData = '';
        // serialize an array
        if (is_array($data)) {
            $tagName = isset($this->options['rootName']) ? $this->options['rootName'] : 'array';
            $this->_serializedData .= $this->_serializeArray($data, $tagName, $this->options['rootAttributes']);
        }

        // add doctype declaration
        if ($this->options['addDoctype'] === true) {
            $this->_serializedData = XML_Util::getDoctypeDeclaration($tagName, $this->options['doctype'])
                                   . $this->options['linebreak']
                                   . $this->_serializedData;
        }

        //  build xml declaration
        if ($this->options['addDecl']) {
            $atts = array();
            $encoding = isset($this->options['encoding']) ? $this->options['encoding'] : null;
            $this->_serializedData = XML_Util::getXMLDeclaration('1.0', $encoding)
                                   . $this->options['linebreak']
                                   . $this->_serializedData;
        }


        if ($optionsBak !== null) {
            $this->options = $optionsBak;
        }

        return  true;
    }

   /**
    * get the result of the serialization
    *
    * @access public
    * @return string serialized XML
    */
    function getSerializedData()
    {
        if ($this->_serializedData === null) {
            return  $this->raiseError('No serialized data available. Use XML_Serializer::serialize() first.', XML_SERIALIZER_ERROR_NO_SERIALIZATION);
        }
        return $this->_serializedData;
    }

   /**
    * serialize any value
    *
    * This method checks for the type of the value and calls the appropriate method
    *
    * @access private
    * @param  mixed     $value
    * @param  string    $tagName
    * @param  array     $attributes
    * @return string
    */
    function _serializeValue($value, $tagName = null, $attributes = array())
    {
        if (is_array($value)) {
            $xml = $this->_serializeArray($value, $tagName, $attributes);
        } elseif (is_object($value)) {
            $xml = $this->_serializeObject($value, $tagName);
        } else {
            $tag = array(
                          'qname'      => $tagName,
                          'attributes' => $attributes,
                          'content'    => $value
                        );
            $xml = $this->_createXMLTag($tag);
        }
        return $xml;
    }

   /**
    * serialize an array
    *
    * @access   private
    * @param    array   $array       array to serialize
    * @param    string  $tagName     name of the root tag
    * @param    array   $attributes  attributes for the root tag
    * @return   string  $string      serialized data
    * @uses     XML_Util::isValidName() to check, whether key has to be substituted
    */
    function _serializeArray(&$array, $tagName = null, $attributes = array())
    {
        $_content = null;

        /**
         * check for special attributes
         */
        if ($this->options['attributesArray'] !== null) {
            if (isset($array[$this->options['attributesArray']])) {
                $attributes = $array[$this->options['attributesArray']];
                unset($array[$this->options['attributesArray']]);
            }
            /**
             * check for special content
             */
            if ($this->options['contentName'] !== null) {
                if (isset($array[$this->options['contentName']])) {
                    $_content = $array[$this->options['contentName']];
                    unset($array[$this->options['contentName']]);
                }
            }
        }

        /*
        * if mode is set to simpleXML, check whether
        * the array is associative or indexed
        */
        if (is_array($array) && $this->options['mode'] == 'simplexml') {
            $indexed = true;
            if (!count($array)) {
                $indexed = false;
            }
            foreach ($array as $key => $val) {
                if (!is_int($key)) {
                    $indexed = false;
                    break;
                }
            }

            if ($indexed && $this->options['mode'] == 'simplexml') {
                $string = '';
                foreach ($array as $key => $val) {
                    if ($this->options['beautifyFilelist'] && $tagName == 'dir') {
                        if (!isset($this->_curdir)) {
                            $this->_curdir = '';
                        }
                        $savedir = $this->_curdir;
                        if (isset($val['attribs'])) {
                            if ($val['attribs']['name'] == '/') {
                                $this->_curdir = '/';
                            } else {
                                if ($this->_curdir == '/') {
                                    $this->_curdir = '';
                                }
                                $this->_curdir .= '/' . $val['attribs']['name'];
                            }
                        }
                    }
                    $string .= $this->_serializeValue( $val, $tagName, $attributes);
                    if ($this->options['beautifyFilelist'] && $tagName == 'dir') {
                        $string .= ' <!-- ' . $this->_curdir . ' -->';
                        if (empty($savedir)) {
                            unset($this->_curdir);
                        } else {
                            $this->_curdir = $savedir;
                        }
                    }

                    $string .= $this->options['linebreak'];
                    // do indentation
                    if ($this->options['indent'] !== null && $this->_tagDepth > 0) {
                        $string .= str_repeat($this->options['indent'], $this->_tagDepth);
                    }
                }
                return rtrim($string);
            }
        }

        if ($this->options['scalarAsAttributes'] === true) {
            foreach ($array as $key => $value) {
                if (is_scalar($value) && (XML_Util::isValidName($key) === true)) {
                    unset($array[$key]);
                    $attributes[$this->options['prependAttributes'].$key] = $value;
                }
            }
        }

        // check for empty array => create empty tag
        if (empty($array)) {
            $tag = array(
                            'qname'      => $tagName,
                            'content'    => $_content,
                            'attributes' => $attributes
                        );

        } else {
            $this->_tagDepth++;
            $tmp = $this->options['linebreak'];
            foreach ($array as $key => $value) {
                // do indentation
                if ($this->options['indent'] !== null && $this->_tagDepth > 0) {
                    $tmp .= str_repeat($this->options['indent'], $this->_tagDepth);
                }

                // copy key
                $origKey = $key;
                // key cannot be used as tagname => use default tag
                $valid = XML_Util::isValidName($key);
                if (PEAR::isError($valid)) {
                    if ($this->options['classAsTagName'] && is_object($value)) {
                        $key = get_class($value);
                    } else {
                        $key = $this->options['defaultTagName'];
                    }
                }
                $atts = array();
                if ($this->options['typeHints'] === true) {
                    $atts[$this->options['typeAttribute']] = gettype($value);
                    if ($key !== $origKey) {
                        $atts[$this->options['keyAttribute']] = (string)$origKey;
                    }

                }
                if ($this->options['beautifyFilelist'] && $key == 'dir') {
                    if (!isset($this->_curdir)) {
                        $this->_curdir = '';
                    }
                    $savedir = $this->_curdir;
                    if (isset($value['attribs'])) {
                        if ($value['attribs']['name'] == '/') {
                            $this->_curdir = '/';
                        } else {
                            $this->_curdir .= '/' . $value['attribs']['name'];
                        }
                    }
                }

                if (is_string($value) && $value && ($value{strlen($value) - 1} == "\n")) {
                    $value .= str_repeat($this->options['indent'], $this->_tagDepth);
                }
                $tmp .= $this->_createXMLTag(array(
                                                    'qname'      => $key,
                                                    'attributes' => $atts,
                                                    'content'    => $value )
                                            );
                if ($this->options['beautifyFilelist'] && $key == 'dir') {
                    if (isset($value['attribs'])) {
                        $tmp .= ' <!-- ' . $this->_curdir . ' -->';
                        if (empty($savedir)) {
                            unset($this->_curdir);
                        } else {
                            $this->_curdir = $savedir;
                        }
                    }
                }
                $tmp .= $this->options['linebreak'];
            }

            $this->_tagDepth--;
            if ($this->options['indent']!==null && $this->_tagDepth>0) {
                $tmp .= str_repeat($this->options['indent'], $this->_tagDepth);
            }

            if (trim($tmp) === '') {
                $tmp = null;
            }

            $tag = array(
                'qname'      => $tagName,
                'content'    => $tmp,
                'attributes' => $attributes
            );
        }
        if ($this->options['typeHints'] === true) {
            if (!isset($tag['attributes'][$this->options['typeAttribute']])) {
                $tag['attributes'][$this->options['typeAttribute']] = 'array';
            }
        }

        $string = $this->_createXMLTag($tag, false);
        return $string;
    }

   /**
    * create a tag from an array
    * this method awaits an array in the following format
    * array(
    *       'qname'        => $tagName,
    *       'attributes'   => array(),
    *       'content'      => $content,      // optional
    *       'namespace'    => $namespace     // optional
    *       'namespaceUri' => $namespaceUri  // optional
    *   )
    *
    * @access   private
    * @param    array   $tag tag definition
    * @param    boolean $replaceEntities whether to replace XML entities in content or not
    * @return   string  $string XML tag
    */
    function _createXMLTag($tag, $replaceEntities = true)
    {
        if ($this->options['indentAttributes'] !== false) {
            $multiline = true;
            $indent    = str_repeat($this->options['indent'], $this->_tagDepth);

            if ($this->options['indentAttributes'] == '_auto') {
                $indent .= str_repeat(' ', (strlen($tag['qname'])+2));

            } else {
                $indent .= $this->options['indentAttributes'];
            }
        } else {
            $indent = $multiline = false;
        }

        if (is_array($tag['content'])) {
            if (empty($tag['content'])) {
                $tag['content'] = '';
            }
        } elseif(is_scalar($tag['content']) && (string)$tag['content'] == '') {
            $tag['content'] = '';
        }

        if (is_scalar($tag['content']) || is_null($tag['content'])) {
            if ($this->options['encoding'] == 'UTF-8' &&
                  version_compare(phpversion(), '5.0.0', 'lt')
            ) {
                $tag['content'] = utf8_encode($tag['content']);
            }

            if ($replaceEntities === true) {
                $replaceEntities = XML_UTIL_ENTITIES_XML;
            }

            $tag = XML_Util::createTagFromArray($tag, $replaceEntities, $multiline, $indent, $this->options['linebreak']);
        } elseif (is_array($tag['content'])) {
            $tag = $this->_serializeArray($tag['content'], $tag['qname'], $tag['attributes']);
        } elseif (is_object($tag['content'])) {
            $tag = $this->_serializeObject($tag['content'], $tag['qname'], $tag['attributes']);
        } elseif (is_resource($tag['content'])) {
            settype($tag['content'], 'string');
            $tag = XML_Util::createTagFromArray($tag, $replaceEntities);
        }
        return  $tag;
    }
}