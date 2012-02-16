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
// $Id: Iterator.php,v 1.22 2006/05/06 14:03:41 lsmith Exp $

/**
 * PHP5 Iterator
 *
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Iterator implements Iterator
{
    protected $fetchmode;
    protected $result;
    protected $row;

    // {{{ constructor

    /**
     * Constructor
     */
    public function __construct($result, $fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        $this->result = $result;
        $this->fetchmode = $fetchmode;
    }
    // }}}

    // {{{ seek()

    /**
     * Seek forward to a specific row in a result set
     *
     * @param int number of the row where the data can be found
     *
     * @return void
     * @access public
     */
    public function seek($rownum)
    {
        $this->row = null;
        if ($this->result) {
            $this->result->seek($rownum);
        }
    }
    // }}}

    // {{{ next()

    /**
     * Fetch next row of data
     *
     * @return void
     * @access public
     */
    public function next()
    {
        $this->row = null;
    }
    // }}}

    // {{{ current()

    /**
     * return a row of data
     *
     * @return void
     * @access public
     */
    public function current()
    {
        if (is_null($this->row)) {
            $row = $this->result->fetchRow($this->fetchmode);
            if (PEAR::isError($row)) {
                $row = false;
            }
            $this->row = $row;
        }
        return $this->row;
    }
    // }}}

    // {{{ valid()

    /**
     * Check if the end of the result set has been reached
     *
     * @return bool true/false, false is also returned on failure
     * @access public
     */
    public function valid()
    {
        return (bool)$this->current();
    }
    // }}}

    // {{{ free()

    /**
     * Free the internal resources associated with result.
     *
     * @return bool|MDB2_Error true on success, false|MDB2_Error if result is invalid
     * @access public
     */
    public function free()
    {
        if ($this->result) {
            return $this->result->free();
        }
        $this->result = false;
        $this->row = null;
        return false;
    }
    // }}}

    // {{{ key()

    /**
     * Returns the row number
     *
     * @return int|bool|MDB2_Error true on success, false|MDB2_Error if result is invalid
     * @access public
     */
    public function key()
    {
        if ($this->result) {
            return $this->result->rowCount();
        }
        return false;
    }
    // }}}

    // {{{ rewind()

    /**
     * Seek to the first row in a result set
     *
     * @return void
     * @access public
     */
    public function rewind()
    {
    }
    // }}}

    // {{{ destructor

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->free();
    }
    // }}}
}

/**
 * PHP5 buffered Iterator
 *
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_BufferedIterator extends MDB2_Iterator implements SeekableIterator
{
    // {{{ valid()

    /**
     * Check if the end of the result set has been reached
     *
     * @return bool|MDB2_Error true on success, false|MDB2_Error if result is invalid
     * @access public
     */
    public function valid()
    {
        if ($this->result) {
            return $this->result->valid();
        }
        return false;
    }
    // }}}

    // {{{count()

    /**
     * Returns the number of rows in a result object
     *
     * @return int|MDB2_Error number of rows, false|MDB2_Error if result is invalid
     * @access public
     */
    public function count()
    {
        if ($this->result) {
            return $this->result->numRows();
        }
        return false;
    }
    // }}}

    // {{{ rewind()

    /**
     * Seek to the first row in a result set
     *
     * @return void
     * @access public
     */
    public function rewind()
    {
        $this->seek(0);
    }
    // }}}
}

?>