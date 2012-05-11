<?php
/**
 * PEAR_ChannelFile_Parser for parsing channel.xml
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Parser.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */

/**
 * base xml parser class
 */
require_once 'PEAR/XMLParser.php';
require_once 'PEAR/ChannelFile.php';
/**
 * Parser for channel.xml
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.4
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a1
 */
class PEAR_ChannelFile_Parser extends PEAR_XMLParser
{
    var $_config;
    var $_logger;
    var $_registry;

    function setConfig(&$c)
    {
        $this->_config = &$c;
        $this->_registry = &$c->getRegistry();
    }

    function setLogger(&$l)
    {
        $this->_logger = &$l;
    }

    function parse($data, $file)
    {
        if (PEAR::isError($err = parent::parse($data, $file))) {
            return $err;
        }

        $ret = new PEAR_ChannelFile;
        $ret->setConfig($this->_config);
        if (isset($this->_logger)) {
            $ret->setLogger($this->_logger);
        }

        $ret->fromArray($this->_unserializedData);
        // make sure the filelist is in the easy to read format needed
        $ret->flattenFilelist();
        $ret->setPackagefile($file, $archive);
        return $ret;
    }
}