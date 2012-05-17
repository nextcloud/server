<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 */

require_once 'MDB2.php';

/**
 * MDB2_LOB: user land stream wrapper implementation for LOB support
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_LOB
{
    /**
     * contains the key to the global MDB2 instance array of the associated
     * MDB2 instance
     *
     * @var integer
     * @access protected
     */
    var $db_index;

    /**
     * contains the key to the global MDB2_LOB instance array of the associated
     * MDB2_LOB instance
     *
     * @var integer
     * @access protected
     */
    var $lob_index;

    // {{{ stream_open()

    /**
     * open stream
     *
     * @param string specifies the URL that was passed to fopen()
     * @param string the mode used to open the file
     * @param int holds additional flags set by the streams API
     * @param string not used
     *
     * @return bool
     * @access public
     */
    function stream_open($path, $mode, $options, &$opened_path)
    {
        if (!preg_match('/^rb?\+?$/', $mode)) {
            return false;
        }
        $url = parse_url($path);
        if (empty($url['host'])) {
            return false;
        }
        $this->db_index = (int)$url['host'];
        if (!isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            return false;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $this->lob_index = (int)$url['user'];
        if (!isset($db->datatype->lobs[$this->lob_index])) {
            return false;
        }
        return true;
    }
    // }}}

    // {{{ stream_read()

    /**
     * read stream
     *
     * @param int number of bytes to read
     *
     * @return string
     * @access public
     */
    function stream_read($count)
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
            $db->datatype->_retrieveLOB($db->datatype->lobs[$this->lob_index]);

            $data = $db->datatype->_readLOB($db->datatype->lobs[$this->lob_index], $count);
            $length = strlen($data);
            if ($length == 0) {
                $db->datatype->lobs[$this->lob_index]['endOfLOB'] = true;
            }
            $db->datatype->lobs[$this->lob_index]['position'] += $length;
            return $data;
        }
    }
    // }}}

    // {{{ stream_write()

    /**
     * write stream, note implemented
     *
     * @param string data
     *
     * @return int
     * @access public
     */
    function stream_write($data)
    {
        return 0;
    }
    // }}}

    // {{{ stream_tell()

    /**
     * return the current position
     *
     * @return int current position
     * @access public
     */
    function stream_tell()
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
            return $db->datatype->lobs[$this->lob_index]['position'];
        }
    }
    // }}}

    // {{{ stream_eof()

    /**
     * Check if stream reaches EOF
     *
     * @return bool
     * @access public
     */
    function stream_eof()
    {
        if (!isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            return true;
        }

        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $result = $db->datatype->_endOfLOB($db->datatype->lobs[$this->lob_index]);
        if (version_compare(phpversion(), "5.0", ">=")
            && version_compare(phpversion(), "5.1", "<")
        ) {
            return !$result;
        }
        return $result;
    }
    // }}}

    // {{{ stream_seek()

    /**
     * Seek stream, not implemented
     *
     * @param int offset
     * @param int whence
     *
     * @return bool
     * @access public
     */
    function stream_seek($offset, $whence)
    {
        return false;
    }
    // }}}

    // {{{ stream_stat()

    /**
     * return information about stream
     *
     * @access public
     */
    function stream_stat()
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
            return array(
              'db_index' => $this->db_index,
              'lob_index' => $this->lob_index,
            );
        }
    }
    // }}}

    // {{{ stream_close()

    /**
     * close stream
     *
     * @access public
     */
    function stream_close()
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
            if (isset($db->datatype->lobs[$this->lob_index])) {
                $db->datatype->_destroyLOB($db->datatype->lobs[$this->lob_index]);
                unset($db->datatype->lobs[$this->lob_index]);
            }
        }
    }
    // }}}
}

// register streams wrapper
if (!stream_wrapper_register("MDB2LOB", "MDB2_LOB")) {
    MDB2::raiseError();
    return false;
}

?>
