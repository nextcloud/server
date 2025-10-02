<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::CSV
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 1997-2008,
 * Vincent Blavet <vincent@phpconcept.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  File_Formats
 * @package   Archive_Tar
 * @author    Vincent Blavet <vincent@phpconcept.net>
 * @copyright 1997-2010 The Authors
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Archive_Tar
 */

// If the PEAR class cannot be loaded via the autoloader,
// then try to require_once it from the PHP include path.
if (!class_exists('PEAR')) {
    require_once 'PEAR.php';
}

define('ARCHIVE_TAR_ATT_SEPARATOR', 90001);
define('ARCHIVE_TAR_END_BLOCK', pack("a512", ''));

if (!function_exists('gzopen') && function_exists('gzopen64')) {
    function gzopen($filename, $mode, $use_include_path = 0)
    {
        return gzopen64($filename, $mode, $use_include_path);
    }
}

if (!function_exists('gztell') && function_exists('gztell64')) {
    function gztell($zp)
    {
        return gztell64($zp);
    }
}

if (!function_exists('gzseek') && function_exists('gzseek64')) {
    function gzseek($zp, $offset, $whence = SEEK_SET)
    {
        return gzseek64($zp, $offset, $whence);
    }
}

/**
 * Creates a (compressed) Tar archive
 *
 * @package Archive_Tar
 * @author  Vincent Blavet <vincent@phpconcept.net>
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version $Revision$
 */
class Archive_Tar extends PEAR
{
    /**
     * @var string Name of the Tar
     */
    public $_tarname = '';

    /**
     * @var boolean if true, the Tar file will be gzipped
     */
    public $_compress = false;

    /**
     * @var string Type of compression : 'none', 'gz', 'bz2' or 'lzma2'
     */
    public $_compress_type = 'none';

    /**
     * @var string Explode separator
     */
    public $_separator = ' ';

    /**
     * @var file descriptor
     */
    public $_file = 0;

    /**
     * @var string Local Tar name of a remote Tar (http:// or ftp://)
     */
    public $_temp_tarname = '';

    /**
     * @var string regular expression for ignoring files or directories
     */
    public $_ignore_regexp = '';

    /**
     * @var object PEAR_Error object
     */
    public $error_object = null;

    /**
     * Format for data extraction
     *
     * @var string
     */
    public $_fmt = '';

    /**
     * @var int Length of the read buffer in bytes
     */
    protected $buffer_length;

    /**
     * Archive_Tar Class constructor. This flavour of the constructor only
     * declare a new Archive_Tar object, identifying it by the name of the
     * tar file.
     * If the compress argument is set the tar will be read or created as a
     * gzip or bz2 compressed TAR file.
     *
     * @param string $p_tarname The name of the tar archive to create
     * @param string $p_compress can be null, 'gz', 'bz2' or 'lzma2'. This
     *               parameter indicates if gzip, bz2 or lzma2 compression
     *               is required.  For compatibility reason the
     *               boolean value 'true' means 'gz'.
     * @param int $buffer_length Length of the read buffer in bytes
     *
     * @return bool
     */
    public function __construct($p_tarname, $p_compress = null, $buffer_length = 512)
    {
        parent::__construct();

        $this->_compress = false;
        $this->_compress_type = 'none';
        if (($p_compress === null) || ($p_compress == '')) {
            if (@file_exists($p_tarname)) {
                if ($fp = @fopen($p_tarname, "rb")) {
                    // look for gzip magic cookie
                    $data = fread($fp, 2);
                    fclose($fp);
                    if ($data == "\37\213") {
                        $this->_compress = true;
                        $this->_compress_type = 'gz';
                        // No sure it's enought for a magic code ....
                    } elseif ($data == "BZ") {
                        $this->_compress = true;
                        $this->_compress_type = 'bz2';
                    } elseif (file_get_contents($p_tarname, false, null, 1, 4) == '7zXZ') {
                        $this->_compress = true;
                        $this->_compress_type = 'lzma2';
                    }
                }
            } else {
                // probably a remote file or some file accessible
                // through a stream interface
                if (substr($p_tarname, -2) == 'gz') {
                    $this->_compress = true;
                    $this->_compress_type = 'gz';
                } elseif ((substr($p_tarname, -3) == 'bz2') ||
                    (substr($p_tarname, -2) == 'bz')
                ) {
                    $this->_compress = true;
                    $this->_compress_type = 'bz2';
                } else {
                    if (substr($p_tarname, -2) == 'xz') {
                        $this->_compress = true;
                        $this->_compress_type = 'lzma2';
                    }
                }
            }
        } else {
            if (($p_compress === true) || ($p_compress == 'gz')) {
                $this->_compress = true;
                $this->_compress_type = 'gz';
            } else {
                if ($p_compress == 'bz2') {
                    $this->_compress = true;
                    $this->_compress_type = 'bz2';
                } else {
                    if ($p_compress == 'lzma2') {
                        $this->_compress = true;
                        $this->_compress_type = 'lzma2';
                    } else {
                        $this->_error(
                            "Unsupported compression type '$p_compress'\n" .
                            "Supported types are 'gz', 'bz2' and 'lzma2'.\n"
                        );
                        return false;
                    }
                }
            }
        }
        $this->_tarname = $p_tarname;
        if ($this->_compress) { // assert zlib or bz2 or xz extension support
            if ($this->_compress_type == 'gz') {
                $extname = 'zlib';
            } else {
                if ($this->_compress_type == 'bz2') {
                    $extname = 'bz2';
                } else {
                    if ($this->_compress_type == 'lzma2') {
                        $extname = 'xz';
                    }
                }
            }

            if (!extension_loaded($extname)) {
                PEAR::loadExtension($extname);
            }
            if (!extension_loaded($extname)) {
                $this->_error(
                    "The extension '$extname' couldn't be found.\n" .
                    "Please make sure your version of PHP was built " .
                    "with '$extname' support.\n"
                );
                return false;
            }
        }


        if (version_compare(PHP_VERSION, "5.5.0-dev") < 0) {
            $this->_fmt = "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/" .
                "a8checksum/a1typeflag/a100link/a6magic/a2version/" .
                "a32uname/a32gname/a8devmajor/a8devminor/a131prefix";
        } else {
            $this->_fmt = "Z100filename/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/" .
                "Z8checksum/Z1typeflag/Z100link/Z6magic/Z2version/" .
                "Z32uname/Z32gname/Z8devmajor/Z8devminor/Z131prefix";
        }


        $this->buffer_length = $buffer_length;
    }

    public function __destruct()
    {
        $this->_close();
        // ----- Look for a local copy to delete
        if ($this->_temp_tarname != '' && (bool) preg_match('/^tar[[:alnum:]]*\.tmp$/', $this->_temp_tarname)) {
            @unlink($this->_temp_tarname);
        }
    }

    /**
     * This method creates the archive file and add the files / directories
     * that are listed in $p_filelist.
     * If a file with the same name exist and is writable, it is replaced
     * by the new tar.
     * The method return false and a PEAR error text.
     * The $p_filelist parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * For each directory added in the archive, the files and
     * sub-directories are also added.
     * See also createModify() method for more details.
     *
     * @param array $p_filelist An array of filenames and directory names, or a
     *              single string with names separated by a single
     *              blank space.
     *
     * @return bool true on success, false on error.
     * @see    createModify()
     */
    public function create($p_filelist)
    {
        return $this->createModify($p_filelist, '', '');
    }

    /**
     * This method add the files / directories that are listed in $p_filelist in
     * the archive. If the archive does not exist it is created.
     * The method return false and a PEAR error text.
     * The files and directories listed are only added at the end of the archive,
     * even if a file with the same name is already archived.
     * See also createModify() method for more details.
     *
     * @param array $p_filelist An array of filenames and directory names, or a
     *              single string with names separated by a single
     *              blank space.
     *
     * @return bool true on success, false on error.
     * @see    createModify()
     * @access public
     */
    public function add($p_filelist)
    {
        return $this->addModify($p_filelist, '', '');
    }

    /**
     * @param string $p_path
     * @param bool $p_preserve
     * @param bool $p_symlinks
     * @return bool
     */
    public function extract($p_path = '', $p_preserve = false, $p_symlinks = true)
    {
        return $this->extractModify($p_path, '', $p_preserve, $p_symlinks);
    }

    /**
     * @return array|int
     */
    public function listContent()
    {
        $v_list_detail = array();

        if ($this->_openRead()) {
            if (!$this->_extractList('', $v_list_detail, "list", '', '')) {
                unset($v_list_detail);
                $v_list_detail = 0;
            }
            $this->_close();
        }

        return $v_list_detail;
    }

    /**
     * This method creates the archive file and add the files / directories
     * that are listed in $p_filelist.
     * If the file already exists and is writable, it is replaced by the
     * new tar. It is a create and not an add. If the file exists and is
     * read-only or is a directory it is not replaced. The method return
     * false and a PEAR error text.
     * The $p_filelist parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * The path indicated in $p_remove_dir will be removed from the
     * memorized path of each file / directory listed when this path
     * exists. By default nothing is removed (empty path '')
     * The path indicated in $p_add_dir will be added at the beginning of
     * the memorized path of each file / directory listed. However it can
     * be set to empty ''. The adding of a path is done after the removing
     * of path.
     * The path add/remove ability enables the user to prepare an archive
     * for extraction in a different path than the origin files are.
     * See also addModify() method for file adding properties.
     *
     * @param array $p_filelist An array of filenames and directory names,
     *                             or a single string with names separated by
     *                             a single blank space.
     * @param string $p_add_dir A string which contains a path to be added
     *                             to the memorized path of each element in
     *                             the list.
     * @param string $p_remove_dir A string which contains a path to be
     *                             removed from the memorized path of each
     *                             element in the list, when relevant.
     *
     * @return boolean true on success, false on error.
     * @see addModify()
     */
    public function createModify($p_filelist, $p_add_dir, $p_remove_dir = '')
    {
        $v_result = true;

        if (!$this->_openWrite()) {
            return false;
        }

        if ($p_filelist != '') {
            if (is_array($p_filelist)) {
                $v_list = $p_filelist;
            } elseif (is_string($p_filelist)) {
                $v_list = explode($this->_separator, $p_filelist);
            } else {
                $this->_cleanFile();
                $this->_error('Invalid file list');
                return false;
            }

            $v_result = $this->_addList($v_list, $p_add_dir, $p_remove_dir);
        }

        if ($v_result) {
            $this->_writeFooter();
            $this->_close();
        } else {
            $this->_cleanFile();
        }

        return $v_result;
    }

    /**
     * This method add the files / directories listed in $p_filelist at the
     * end of the existing archive. If the archive does not yet exists it
     * is created.
     * The $p_filelist parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * The path indicated in $p_remove_dir will be removed from the
     * memorized path of each file / directory listed when this path
     * exists. By default nothing is removed (empty path '')
     * The path indicated in $p_add_dir will be added at the beginning of
     * the memorized path of each file / directory listed. However it can
     * be set to empty ''. The adding of a path is done after the removing
     * of path.
     * The path add/remove ability enables the user to prepare an archive
     * for extraction in a different path than the origin files are.
     * If a file/dir is already in the archive it will only be added at the
     * end of the archive. There is no update of the existing archived
     * file/dir. However while extracting the archive, the last file will
     * replace the first one. This results in a none optimization of the
     * archive size.
     * If a file/dir does not exist the file/dir is ignored. However an
     * error text is send to PEAR error.
     * If a file/dir is not readable the file/dir is ignored. However an
     * error text is send to PEAR error.
     *
     * @param array $p_filelist An array of filenames and directory
     *                             names, or a single string with names
     *                             separated by a single blank space.
     * @param string $p_add_dir A string which contains a path to be
     *                             added to the memorized path of each
     *                             element in the list.
     * @param string $p_remove_dir A string which contains a path to be
     *                             removed from the memorized path of
     *                             each element in the list, when
     *                             relevant.
     *
     * @return bool true on success, false on error.
     */
    public function addModify($p_filelist, $p_add_dir, $p_remove_dir = '')
    {
        $v_result = true;

        if (!$this->_isArchive()) {
            $v_result = $this->createModify(
                $p_filelist,
                $p_add_dir,
                $p_remove_dir
            );
        } else {
            if (is_array($p_filelist)) {
                $v_list = $p_filelist;
            } elseif (is_string($p_filelist)) {
                $v_list = explode($this->_separator, $p_filelist);
            } else {
                $this->_error('Invalid file list');
                return false;
            }

            $v_result = $this->_append($v_list, $p_add_dir, $p_remove_dir);
        }

        return $v_result;
    }

    /**
     * This method add a single string as a file at the
     * end of the existing archive. If the archive does not yet exists it
     * is created.
     *
     * @param string $p_filename A string which contains the full
     *                           filename path that will be associated
     *                           with the string.
     * @param string $p_string The content of the file added in
     *                           the archive.
     * @param bool|int $p_datetime A custom date/time (unix timestamp)
     *                           for the file (optional).
     * @param array $p_params An array of optional params:
     *                               stamp => the datetime (replaces
     *                                   datetime above if it exists)
     *                               mode => the permissions on the
     *                                   file (600 by default)
     *                               type => is this a link?  See the
     *                                   tar specification for details.
     *                                   (default = regular file)
     *                               uid => the user ID of the file
     *                                   (default = 0 = root)
     *                               gid => the group ID of the file
     *                                   (default = 0 = root)
     *
     * @return bool true on success, false on error.
     */
    public function addString($p_filename, $p_string, $p_datetime = false, $p_params = array())
    {
        $p_stamp = @$p_params["stamp"] ? $p_params["stamp"] : ($p_datetime ? $p_datetime : time());
        $p_mode = @$p_params["mode"] ? $p_params["mode"] : 0600;
        $p_type = @$p_params["type"] ? $p_params["type"] : "";
        $p_uid = @$p_params["uid"] ? $p_params["uid"] : "";
        $p_gid = @$p_params["gid"] ? $p_params["gid"] : "";
        $v_result = true;

        if (!$this->_isArchive()) {
            if (!$this->_openWrite()) {
                return false;
            }
            $this->_close();
        }

        if (!$this->_openAppend()) {
            return false;
        }

        // Need to check the get back to the temporary file ? ....
        $v_result = $this->_addString($p_filename, $p_string, $p_datetime, $p_params);

        $this->_writeFooter();

        $this->_close();

        return $v_result;
    }

    /**
     * This method extract all the content of the archive in the directory
     * indicated by $p_path. When relevant the memorized path of the
     * files/dir can be modified by removing the $p_remove_path path at the
     * beginning of the file/dir path.
     * While extracting a file, if the directory path does not exists it is
     * created.
     * While extracting a file, if the file already exists it is replaced
     * without looking for last modification date.
     * While extracting a file, if the file already exists and is write
     * protected, the extraction is aborted.
     * While extracting a file, if a directory with the same name already
     * exists, the extraction is aborted.
     * While extracting a directory, if a file with the same name already
     * exists, the extraction is aborted.
     * While extracting a file/directory if the destination directory exist
     * and is write protected, or does not exist but can not be created,
     * the extraction is aborted.
     * If after extraction an extracted file does not show the correct
     * stored file size, the extraction is aborted.
     * When the extraction is aborted, a PEAR error text is set and false
     * is returned. However the result can be a partial extraction that may
     * need to be manually cleaned.
     *
     * @param string $p_path The path of the directory where the
     *                               files/dir need to by extracted.
     * @param string $p_remove_path Part of the memorized path that can be
     *                               removed if present at the beginning of
     *                               the file/dir path.
     * @param boolean $p_preserve Preserve user/group ownership of files
     * @param boolean $p_symlinks Allow symlinks.
     *
     * @return boolean true on success, false on error.
     * @see    extractList()
     */
    public function extractModify($p_path, $p_remove_path, $p_preserve = false, $p_symlinks = true)
    {
        $v_result = true;
        $v_list_detail = array();

        if ($v_result = $this->_openRead()) {
            $v_result = $this->_extractList(
                $p_path,
                $v_list_detail,
                "complete",
                0,
                $p_remove_path,
                $p_preserve,
                $p_symlinks
            );
            $this->_close();
        }

        return $v_result;
    }

    /**
     * This method extract from the archive one file identified by $p_filename.
     * The return value is a string with the file content, or NULL on error.
     *
     * @param string $p_filename The path of the file to extract in a string.
     *
     * @return a string with the file content or NULL.
     */
    public function extractInString($p_filename)
    {
        if ($this->_openRead()) {
            $v_result = $this->_extractInString($p_filename);
            $this->_close();
        } else {
            $v_result = null;
        }

        return $v_result;
    }

    /**
     * This method extract from the archive only the files indicated in the
     * $p_filelist. These files are extracted in the current directory or
     * in the directory indicated by the optional $p_path parameter.
     * If indicated the $p_remove_path can be used in the same way as it is
     * used in extractModify() method.
     *
     * @param array $p_filelist An array of filenames and directory names,
     *                               or a single string with names separated
     *                               by a single blank space.
     * @param string $p_path The path of the directory where the
     *                               files/dir need to by extracted.
     * @param string $p_remove_path Part of the memorized path that can be
     *                               removed if present at the beginning of
     *                               the file/dir path.
     * @param boolean $p_preserve Preserve user/group ownership of files
     * @param boolean $p_symlinks Allow symlinks.
     *
     * @return bool true on success, false on error.
     * @see    extractModify()
     */
    public function extractList($p_filelist, $p_path = '', $p_remove_path = '', $p_preserve = false, $p_symlinks = true)
    {
        $v_result = true;
        $v_list_detail = array();

        if (is_array($p_filelist)) {
            $v_list = $p_filelist;
        } elseif (is_string($p_filelist)) {
            $v_list = explode($this->_separator, $p_filelist);
        } else {
            $this->_error('Invalid string list');
            return false;
        }

        if ($v_result = $this->_openRead()) {
            $v_result = $this->_extractList(
                $p_path,
                $v_list_detail,
                "partial",
                $v_list,
                $p_remove_path,
                $p_preserve,
                $p_symlinks
            );
            $this->_close();
        }

        return $v_result;
    }

    /**
     * This method set specific attributes of the archive. It uses a variable
     * list of parameters, in the format attribute code + attribute values :
     * $arch->setAttribute(ARCHIVE_TAR_ATT_SEPARATOR, ',');
     *
     * @return bool true on success, false on error.
     */
    public function setAttribute()
    {
        $v_result = true;

        // ----- Get the number of variable list of arguments
        if (($v_size = func_num_args()) == 0) {
            return true;
        }

        // ----- Get the arguments
        $v_att_list = func_get_args();

        // ----- Read the attributes
        $i = 0;
        while ($i < $v_size) {

            // ----- Look for next option
            switch ($v_att_list[$i]) {
                // ----- Look for options that request a string value
                case ARCHIVE_TAR_ATT_SEPARATOR :
                    // ----- Check the number of parameters
                    if (($i + 1) >= $v_size) {
                        $this->_error(
                            'Invalid number of parameters for '
                            . 'attribute ARCHIVE_TAR_ATT_SEPARATOR'
                        );
                        return false;
                    }

                    // ----- Get the value
                    $this->_separator = $v_att_list[$i + 1];
                    $i++;
                    break;

                default :
                    $this->_error('Unknown attribute code ' . $v_att_list[$i] . '');
                    return false;
            }

            // ----- Next attribute
            $i++;
        }

        return $v_result;
    }

    /**
     * This method sets the regular expression for ignoring files and directories
     * at import, for example:
     * $arch->setIgnoreRegexp("#CVS|\.svn#");
     *
     * @param string $regexp regular expression defining which files or directories to ignore
     */
    public function setIgnoreRegexp($regexp)
    {
        $this->_ignore_regexp = $regexp;
    }

    /**
     * This method sets the regular expression for ignoring all files and directories
     * matching the filenames in the array list at import, for example:
     * $arch->setIgnoreList(array('CVS', '.svn', 'bin/tool'));
     *
     * @param array $list a list of file or directory names to ignore
     *
     * @access public
     */
    public function setIgnoreList($list)
    {
        $list = str_replace(array('#', '.', '^', '$'), array('\#', '\.', '\^', '\$'), $list);
        $regexp = '#/' . join('$|/', $list) . '#';
        $this->setIgnoreRegexp($regexp);
    }

    /**
     * @param string $p_message
     */
    public function _error($p_message)
    {
        $this->error_object = $this->raiseError($p_message);
    }

    /**
     * @param string $p_message
     */
    public function _warning($p_message)
    {
        $this->error_object = $this->raiseError($p_message);
    }

    /**
     * @param string $p_filename
     * @return bool
     */
    public function _isArchive($p_filename = null)
    {
        if ($p_filename == null) {
            $p_filename = $this->_tarname;
        }
        clearstatcache();
        return @is_file($p_filename) && !@is_link($p_filename);
    }

    /**
     * @return bool
     */
    public function _openWrite()
    {
        if ($this->_compress_type == 'gz' && function_exists('gzopen')) {
            $this->_file = @gzopen($this->_tarname, "wb9");
        } else {
            if ($this->_compress_type == 'bz2' && function_exists('bzopen')) {
                $this->_file = @bzopen($this->_tarname, "w");
            } else {
                if ($this->_compress_type == 'lzma2' && function_exists('xzopen')) {
                    $this->_file = @xzopen($this->_tarname, 'w');
                } else {
                    if ($this->_compress_type == 'none') {
                        $this->_file = @fopen($this->_tarname, "wb");
                    } else {
                        $this->_error(
                            'Unknown or missing compression type ('
                            . $this->_compress_type . ')'
                        );
                        return false;
                    }
                }
            }
        }

        if ($this->_file == 0) {
            $this->_error(
                'Unable to open in write mode \''
                . $this->_tarname . '\''
            );
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _openRead()
    {
        if (strtolower(substr($this->_tarname, 0, 7)) == 'http://') {

            // ----- Look if a local copy need to be done
            if ($this->_temp_tarname == '') {
                $this->_temp_tarname = uniqid('tar') . '.tmp';
                if (!$v_file_from = @fopen($this->_tarname, 'rb')) {
                    $this->_error(
                        'Unable to open in read mode \''
                        . $this->_tarname . '\''
                    );
                    $this->_temp_tarname = '';
                    return false;
                }
                if (!$v_file_to = @fopen($this->_temp_tarname, 'wb')) {
                    $this->_error(
                        'Unable to open in write mode \''
                        . $this->_temp_tarname . '\''
                    );
                    $this->_temp_tarname = '';
                    return false;
                }
                while ($v_data = @fread($v_file_from, 1024)) {
                    @fwrite($v_file_to, $v_data);
                }
                @fclose($v_file_from);
                @fclose($v_file_to);
            }

            // ----- File to open if the local copy
            $v_filename = $this->_temp_tarname;
        } else {
            // ----- File to open if the normal Tar file

            $v_filename = $this->_tarname;
        }

        if ($this->_compress_type == 'gz' && function_exists('gzopen')) {
            $this->_file = @gzopen($v_filename, "rb");
        } else {
            if ($this->_compress_type == 'bz2' && function_exists('bzopen')) {
                $this->_file = @bzopen($v_filename, "r");
            } else {
                if ($this->_compress_type == 'lzma2' && function_exists('xzopen')) {
                    $this->_file = @xzopen($v_filename, "r");
                } else {
                    if ($this->_compress_type == 'none') {
                        $this->_file = @fopen($v_filename, "rb");
                    } else {
                        $this->_error(
                            'Unknown or missing compression type ('
                            . $this->_compress_type . ')'
                        );
                        return false;
                    }
                }
            }
        }

        if ($this->_file == 0) {
            $this->_error('Unable to open in read mode \'' . $v_filename . '\'');
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _openReadWrite()
    {
        if ($this->_compress_type == 'gz') {
            $this->_file = @gzopen($this->_tarname, "r+b");
        } else {
            if ($this->_compress_type == 'bz2') {
                $this->_error(
                    'Unable to open bz2 in read/write mode \''
                    . $this->_tarname . '\' (limitation of bz2 extension)'
                );
                return false;
            } else {
                if ($this->_compress_type == 'lzma2') {
                    $this->_error(
                        'Unable to open lzma2 in read/write mode \''
                        . $this->_tarname . '\' (limitation of lzma2 extension)'
                    );
                    return false;
                } else {
                    if ($this->_compress_type == 'none') {
                        $this->_file = @fopen($this->_tarname, "r+b");
                    } else {
                        $this->_error(
                            'Unknown or missing compression type ('
                            . $this->_compress_type . ')'
                        );
                        return false;
                    }
                }
            }
        }

        if ($this->_file == 0) {
            $this->_error(
                'Unable to open in read/write mode \''
                . $this->_tarname . '\''
            );
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _close()
    {
        //if (isset($this->_file)) {
        if (is_resource($this->_file)) {
            if ($this->_compress_type == 'gz') {
                @gzclose($this->_file);
            } else {
                if ($this->_compress_type == 'bz2') {
                    @bzclose($this->_file);
                } else {
                    if ($this->_compress_type == 'lzma2') {
                        @xzclose($this->_file);
                    } else {
                        if ($this->_compress_type == 'none') {
                            @fclose($this->_file);
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                . $this->_compress_type . ')'
                            );
                        }
                    }
                }
            }

            $this->_file = 0;
        }

        // ----- Look if a local copy need to be erase
        // Note that it might be interesting to keep the url for a time : ToDo
        if ($this->_temp_tarname != '') {
            @unlink($this->_temp_tarname);
            $this->_temp_tarname = '';
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _cleanFile()
    {
        $this->_close();

        // ----- Look for a local copy
        if ($this->_temp_tarname != '') {
            // ----- Remove the local copy but not the remote tarname
            @unlink($this->_temp_tarname);
            $this->_temp_tarname = '';
        } else {
            // ----- Remove the local tarname file
            @unlink($this->_tarname);
        }
        $this->_tarname = '';

        return true;
    }

    /**
     * @param mixed $p_binary_data
     * @param integer $p_len
     * @return bool
     */
    public function _writeBlock($p_binary_data, $p_len = null)
    {
        if (is_resource($this->_file)) {
            if ($p_len === null) {
                if ($this->_compress_type == 'gz') {
                    @gzputs($this->_file, $p_binary_data);
                } else {
                    if ($this->_compress_type == 'bz2') {
                        @bzwrite($this->_file, $p_binary_data);
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            @xzwrite($this->_file, $p_binary_data);
                        } else {
                            if ($this->_compress_type == 'none') {
                                @fputs($this->_file, $p_binary_data);
                            } else {
                                $this->_error(
                                    'Unknown or missing compression type ('
                                    . $this->_compress_type . ')'
                                );
                            }
                        }
                    }
                }
            } else {
                if ($this->_compress_type == 'gz') {
                    @gzputs($this->_file, $p_binary_data, $p_len);
                } else {
                    if ($this->_compress_type == 'bz2') {
                        @bzwrite($this->_file, $p_binary_data, $p_len);
                    } else {
                        if ($this->_compress_type == 'lzma2') {
                            @xzwrite($this->_file, $p_binary_data, $p_len);
                        } else {
                            if ($this->_compress_type == 'none') {
                                @fputs($this->_file, $p_binary_data, $p_len);
                            } else {
                                $this->_error(
                                    'Unknown or missing compression type ('
                                    . $this->_compress_type . ')'
                                );
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return null|string
     */
    public function _readBlock()
    {
        $v_block = null;
        if (is_resource($this->_file)) {
            if ($this->_compress_type == 'gz') {
                $v_block = @gzread($this->_file, 512);
            } else {
                if ($this->_compress_type == 'bz2') {
                    $v_block = @bzread($this->_file, 512);
                } else {
                    if ($this->_compress_type == 'lzma2') {
                        $v_block = @xzread($this->_file, 512);
                    } else {
                        if ($this->_compress_type == 'none') {
                            $v_block = @fread($this->_file, 512);
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                . $this->_compress_type . ')'
                            );
                        }
                    }
                }
            }
        }
        return $v_block;
    }

    /**
     * @param null $p_len
     * @return bool
     */
    public function _jumpBlock($p_len = null)
    {
        if (is_resource($this->_file)) {
            if ($p_len === null) {
                $p_len = 1;
            }

            if ($this->_compress_type == 'gz') {
                @gzseek($this->_file, gztell($this->_file) + ($p_len * 512));
            } else {
                if ($this->_compress_type == 'bz2') {
                    // ----- Replace missing bztell() and bzseek()
                    for ($i = 0; $i < $p_len; $i++) {
                        $this->_readBlock();
                    }
                } else {
                    if ($this->_compress_type == 'lzma2') {
                        // ----- Replace missing xztell() and xzseek()
                        for ($i = 0; $i < $p_len; $i++) {
                            $this->_readBlock();
                        }
                    } else {
                        if ($this->_compress_type == 'none') {
                            @fseek($this->_file, $p_len * 512, SEEK_CUR);
                        } else {
                            $this->_error(
                                'Unknown or missing compression type ('
                                . $this->_compress_type . ')'
                            );
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function _writeFooter()
    {
        if (is_resource($this->_file)) {
            // ----- Write the last 0 filled block for end of archive
            $v_binary_data = pack('a1024', '');
            $this->_writeBlock($v_binary_data);
        }
        return true;
    }

    /**
     * @param array $p_list
     * @param string $p_add_dir
     * @param string $p_remove_dir
     * @return bool
     */
    public function _addList($p_list, $p_add_dir, $p_remove_dir)
    {
        $v_result = true;
        $v_header = array();

        // ----- Remove potential windows directory separator
        $p_add_dir = $this->_translateWinPath($p_add_dir);
        $p_remove_dir = $this->_translateWinPath($p_remove_dir, false);

        if (!$this->_file) {
            $this->_error('Invalid file descriptor');
            return false;
        }

        if (sizeof($p_list) == 0) {
            return true;
        }

        foreach ($p_list as $v_filename) {
            if (!$v_result) {
                break;
            }

            // ----- Skip the current tar name
            if ($v_filename == $this->_tarname) {
                continue;
            }

            if ($v_filename == '') {
                continue;
            }

            // ----- ignore files and directories matching the ignore regular expression
            if ($this->_ignore_regexp && preg_match($this->_ignore_regexp, '/' . $v_filename)) {
                $this->_warning("File '$v_filename' ignored");
                continue;
            }

            if (!file_exists($v_filename) && !is_link($v_filename)) {
                $this->_warning("File '$v_filename' does not exist");
                continue;
            }

            // ----- Add the file or directory header
            if (!$this->_addFile($v_filename, $v_header, $p_add_dir, $p_remove_dir)) {
                return false;
            }

            if (@is_dir($v_filename) && !@is_link($v_filename)) {
                if (!($p_hdir = opendir($v_filename))) {
                    $this->_warning("Directory '$v_filename' can not be read");
                    continue;
                }
                while (false !== ($p_hitem = readdir($p_hdir))) {
                    if (($p_hitem != '.') && ($p_hitem != '..')) {
                        if ($v_filename != ".") {
                            $p_temp_list[0] = $v_filename . '/' . $p_hitem;
                        } else {
                            $p_temp_list[0] = $p_hitem;
                        }

                        $v_result = $this->_addList(
                            $p_temp_list,
                            $p_add_dir,
                            $p_remove_dir
                        );
                    }
                }

                unset($p_temp_list);
                unset($p_hdir);
                unset($p_hitem);
            }
        }

        return $v_result;
    }

    /**
     * @param string $p_filename
     * @param mixed $p_header
     * @param string $p_add_dir
     * @param string $p_remove_dir
     * @param null $v_stored_filename
     * @return bool
     */
    public function _addFile($p_filename, &$p_header, $p_add_dir, $p_remove_dir, $v_stored_filename = null)
    {
        if (!$this->_file) {
            $this->_error('Invalid file descriptor');
            return false;
        }

        if ($p_filename == '') {
            $this->_error('Invalid file name');
            return false;
        }

        if (is_null($v_stored_filename)) {
            // ----- Calculate the stored filename
            $p_filename = $this->_translateWinPath($p_filename, false);
            $v_stored_filename = $p_filename;

            if (strcmp($p_filename, $p_remove_dir) == 0) {
                return true;
            }

            if ($p_remove_dir != '') {
                if (substr($p_remove_dir, -1) != '/') {
                    $p_remove_dir .= '/';
                }

                if (substr($p_filename, 0, strlen($p_remove_dir)) == $p_remove_dir) {
                    $v_stored_filename = substr($p_filename, strlen($p_remove_dir));
                }
            }

            $v_stored_filename = $this->_translateWinPath($v_stored_filename);
            if ($p_add_dir != '') {
                if (substr($p_add_dir, -1) == '/') {
                    $v_stored_filename = $p_add_dir . $v_stored_filename;
                } else {
                    $v_stored_filename = $p_add_dir . '/' . $v_stored_filename;
                }
            }

            $v_stored_filename = $this->_pathReduction($v_stored_filename);
        }

        if ($this->_isArchive($p_filename)) {
            if (($v_file = @fopen($p_filename, "rb")) == 0) {
                $this->_warning(
                    "Unable to open file '" . $p_filename
                    . "' in binary read mode"
                );
                return true;
            }

            if (!$this->_writeHeader($p_filename, $v_stored_filename)) {
                return false;
            }

            while (($v_buffer = fread($v_file, $this->buffer_length)) != '') {
                $buffer_length = strlen("$v_buffer");
                if ($buffer_length != $this->buffer_length) {
                    $pack_size = ((int)($buffer_length / 512) + ($buffer_length % 512 !== 0 ? 1 : 0)) * 512;
                    $pack_format = sprintf('a%d', $pack_size);
                } else {
                    $pack_format = sprintf('a%d', $this->buffer_length);
                }
                $v_binary_data = pack($pack_format, "$v_buffer");
                $this->_writeBlock($v_binary_data);
            }

            fclose($v_file);
        } else {
            // ----- Only header for dir
            if (!$this->_writeHeader($p_filename, $v_stored_filename)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $p_filename
     * @param string $p_string
     * @param bool $p_datetime
     * @param array $p_params
     * @return bool
     */
    public function _addString($p_filename, $p_string, $p_datetime = false, $p_params = array())
    {
        $p_stamp = @$p_params["stamp"] ? $p_params["stamp"] : ($p_datetime ? $p_datetime : time());
        $p_mode = @$p_params["mode"] ? $p_params["mode"] : 0600;
        $p_type = @$p_params["type"] ? $p_params["type"] : "";
        $p_uid = @$p_params["uid"] ? $p_params["uid"] : 0;
        $p_gid = @$p_params["gid"] ? $p_params["gid"] : 0;
        if (!$this->_file) {
            $this->_error('Invalid file descriptor');
            return false;
        }

        if ($p_filename == '') {
            $this->_error('Invalid file name');
            return false;
        }

        // ----- Calculate the stored filename
        $p_filename = $this->_translateWinPath($p_filename, false);

        // ----- If datetime is not specified, set current time
        if ($p_datetime === false) {
            $p_datetime = time();
        }

        if (!$this->_writeHeaderBlock(
            $p_filename,
            strlen($p_string),
            $p_stamp,
            $p_mode,
            $p_type,
            $p_uid,
            $p_gid
        )
        ) {
            return false;
        }

        $i = 0;
        while (($v_buffer = substr($p_string, (($i++) * 512), 512)) != '') {
            $v_binary_data = pack("a512", $v_buffer);
            $this->_writeBlock($v_binary_data);
        }

        return true;
    }

    /**
     * @param string $p_filename
     * @param string $p_stored_filename
     * @return bool
     */
    public function _writeHeader($p_filename, $p_stored_filename)
    {
        if ($p_stored_filename == '') {
            $p_stored_filename = $p_filename;
        }

        $v_reduced_filename = $this->_pathReduction($p_stored_filename);

        if (strlen($v_reduced_filename) > 99) {
            if (!$this->_writeLongHeader($v_reduced_filename, false)) {
                return false;
            }
        }

        $v_linkname = '';
        if (@is_link($p_filename)) {
            $v_linkname = readlink($p_filename);
        }

        if (strlen($v_linkname) > 99) {
            if (!$this->_writeLongHeader($v_linkname, true)) {
                return false;
            }
        }

        $v_info = lstat($p_filename);
        $v_uid = sprintf("%07s", DecOct($v_info[4]));
        $v_gid = sprintf("%07s", DecOct($v_info[5]));
        $v_perms = sprintf("%07s", DecOct($v_info['mode'] & 000777));
        $v_mtime = sprintf("%011s", DecOct($v_info['mtime']));

        if (@is_link($p_filename)) {
            $v_typeflag = '2';
            $v_size = sprintf("%011s", DecOct(0));
        } elseif (@is_dir($p_filename)) {
            $v_typeflag = "5";
            $v_size = sprintf("%011s", DecOct(0));
        } else {
            $v_typeflag = '0';
            clearstatcache();
            $v_size = sprintf("%011s", DecOct($v_info['size']));
        }

        $v_magic = 'ustar ';
        $v_version = ' ';
        $v_uname = '';
        $v_gname = '';

        if (function_exists('posix_getpwuid')) {
            $userinfo = posix_getpwuid($v_info[4]);
            $groupinfo = posix_getgrgid($v_info[5]);

            if (isset($userinfo['name'])) {
                $v_uname = $userinfo['name'];
            }

            if (isset($groupinfo['name'])) {
                $v_gname = $groupinfo['name'];
            }
        }

        $v_devmajor = '';
        $v_devminor = '';
        $v_prefix = '';

        $v_binary_data_first = pack(
            "a100a8a8a8a12a12",
            $v_reduced_filename,
            $v_perms,
            $v_uid,
            $v_gid,
            $v_size,
            $v_mtime
        );
        $v_binary_data_last = pack(
            "a1a100a6a2a32a32a8a8a155a12",
            $v_typeflag,
            $v_linkname,
            $v_magic,
            $v_version,
            $v_uname,
            $v_gname,
            $v_devmajor,
            $v_devminor,
            $v_prefix,
            ''
        );

        // ----- Calculate the checksum
        $v_checksum = 0;
        // ..... First part of the header
        for ($i = 0; $i < 148; $i++) {
            $v_checksum += ord(substr($v_binary_data_first, $i, 1));
        }
        // ..... Ignore the checksum value and replace it by ' ' (space)
        for ($i = 148; $i < 156; $i++) {
            $v_checksum += ord(' ');
        }
        // ..... Last part of the header
        for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
            $v_checksum += ord(substr($v_binary_data_last, $j, 1));
        }

        // ----- Write the first 148 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_first, 148);

        // ----- Write the calculated checksum
        $v_checksum = sprintf("%06s\0 ", DecOct($v_checksum));
        $v_binary_data = pack("a8", $v_checksum);
        $this->_writeBlock($v_binary_data, 8);

        // ----- Write the last 356 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_last, 356);

        return true;
    }

    /**
     * @param string $p_filename
     * @param int $p_size
     * @param int $p_mtime
     * @param int $p_perms
     * @param string $p_type
     * @param int $p_uid
     * @param int $p_gid
     * @return bool
     */
    public function _writeHeaderBlock(
        $p_filename,
        $p_size,
        $p_mtime = 0,
        $p_perms = 0,
        $p_type = '',
        $p_uid = 0,
        $p_gid = 0
    )
    {
        $p_filename = $this->_pathReduction($p_filename);

        if (strlen($p_filename) > 99) {
            if (!$this->_writeLongHeader($p_filename, false)) {
                return false;
            }
        }

        if ($p_type == "5") {
            $v_size = sprintf("%011s", DecOct(0));
        } else {
            $v_size = sprintf("%011s", DecOct($p_size));
        }

        $v_uid = sprintf("%07s", DecOct($p_uid));
        $v_gid = sprintf("%07s", DecOct($p_gid));
        $v_perms = sprintf("%07s", DecOct($p_perms & 000777));

        $v_mtime = sprintf("%11s", DecOct($p_mtime));

        $v_linkname = '';

        $v_magic = 'ustar ';

        $v_version = ' ';

        if (function_exists('posix_getpwuid')) {
            $userinfo = posix_getpwuid($p_uid);
            $groupinfo = posix_getgrgid($p_gid);

            if ($userinfo === false || $groupinfo === false) {
                $v_uname = '';
                $v_gname = '';
            } else {
                $v_uname = $userinfo['name'];
                $v_gname = $groupinfo['name'];
            }
        } else {
            $v_uname = '';
            $v_gname = '';
        }

        $v_devmajor = '';

        $v_devminor = '';

        $v_prefix = '';

        $v_binary_data_first = pack(
            "a100a8a8a8a12A12",
            $p_filename,
            $v_perms,
            $v_uid,
            $v_gid,
            $v_size,
            $v_mtime
        );
        $v_binary_data_last = pack(
            "a1a100a6a2a32a32a8a8a155a12",
            $p_type,
            $v_linkname,
            $v_magic,
            $v_version,
            $v_uname,
            $v_gname,
            $v_devmajor,
            $v_devminor,
            $v_prefix,
            ''
        );

        // ----- Calculate the checksum
        $v_checksum = 0;
        // ..... First part of the header
        for ($i = 0; $i < 148; $i++) {
            $v_checksum += ord(substr($v_binary_data_first, $i, 1));
        }
        // ..... Ignore the checksum value and replace it by ' ' (space)
        for ($i = 148; $i < 156; $i++) {
            $v_checksum += ord(' ');
        }
        // ..... Last part of the header
        for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
            $v_checksum += ord(substr($v_binary_data_last, $j, 1));
        }

        // ----- Write the first 148 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_first, 148);

        // ----- Write the calculated checksum
        $v_checksum = sprintf("%06s ", DecOct($v_checksum));
        $v_binary_data = pack("a8", $v_checksum);
        $this->_writeBlock($v_binary_data, 8);

        // ----- Write the last 356 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_last, 356);

        return true;
    }

    /**
     * @param string $p_filename
     * @return bool
     */
    public function _writeLongHeader($p_filename, $is_link = false)
    {
        $v_uid = sprintf("%07s", 0);
        $v_gid = sprintf("%07s", 0);
        $v_perms = sprintf("%07s", 0);
        $v_size = sprintf("%'011s", DecOct(strlen($p_filename)));
        $v_mtime = sprintf("%011s", 0);
        $v_typeflag = ($is_link ? 'K' : 'L');
        $v_linkname = '';
        $v_magic = 'ustar ';
        $v_version = ' ';
        $v_uname = '';
        $v_gname = '';
        $v_devmajor = '';
        $v_devminor = '';
        $v_prefix = '';

        $v_binary_data_first = pack(
            "a100a8a8a8a12a12",
            '././@LongLink',
            $v_perms,
            $v_uid,
            $v_gid,
            $v_size,
            $v_mtime
        );
        $v_binary_data_last = pack(
            "a1a100a6a2a32a32a8a8a155a12",
            $v_typeflag,
            $v_linkname,
            $v_magic,
            $v_version,
            $v_uname,
            $v_gname,
            $v_devmajor,
            $v_devminor,
            $v_prefix,
            ''
        );

        // ----- Calculate the checksum
        $v_checksum = 0;
        // ..... First part of the header
        for ($i = 0; $i < 148; $i++) {
            $v_checksum += ord(substr($v_binary_data_first, $i, 1));
        }
        // ..... Ignore the checksum value and replace it by ' ' (space)
        for ($i = 148; $i < 156; $i++) {
            $v_checksum += ord(' ');
        }
        // ..... Last part of the header
        for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
            $v_checksum += ord(substr($v_binary_data_last, $j, 1));
        }

        // ----- Write the first 148 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_first, 148);

        // ----- Write the calculated checksum
        $v_checksum = sprintf("%06s\0 ", DecOct($v_checksum));
        $v_binary_data = pack("a8", $v_checksum);
        $this->_writeBlock($v_binary_data, 8);

        // ----- Write the last 356 bytes of the header in the archive
        $this->_writeBlock($v_binary_data_last, 356);

        // ----- Write the filename as content of the block
        $i = 0;
        while (($v_buffer = substr($p_filename, (($i++) * 512), 512)) != '') {
            $v_binary_data = pack("a512", "$v_buffer");
            $this->_writeBlock($v_binary_data);
        }

        return true;
    }

    /**
     * @param mixed $v_binary_data
     * @param mixed $v_header
     * @return bool
     */
    public function _readHeader($v_binary_data, &$v_header)
    {
        if (strlen($v_binary_data) == 0) {
            $v_header['filename'] = '';
            return true;
        }

        if (strlen($v_binary_data) != 512) {
            $v_header['filename'] = '';
            $this->_error('Invalid block size : ' . strlen($v_binary_data));
            return false;
        }

        if (!is_array($v_header)) {
            $v_header = array();
        }
        // ----- Calculate the checksum
        $v_checksum = 0;
        // ..... First part of the header
        $v_binary_split = str_split($v_binary_data);
        $v_checksum += array_sum(array_map('ord', array_slice($v_binary_split, 0, 148)));
        $v_checksum += array_sum(array_map('ord', array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',)));
        $v_checksum += array_sum(array_map('ord', array_slice($v_binary_split, 156, 512)));


        $v_data = unpack($this->_fmt, $v_binary_data);

        if (strlen($v_data["prefix"]) > 0) {
            $v_data["filename"] = "$v_data[prefix]/$v_data[filename]";
        }

        // ----- Extract the checksum
        $v_data_checksum = trim($v_data['checksum']);
        if (!preg_match('/^[0-7]*$/', $v_data_checksum)) {
            $this->_error(
                'Invalid checksum for file "' . $v_data['filename']
                . '" : ' . $v_data_checksum . ' extracted'
            );
            return false;
        }

        $v_header['checksum'] = OctDec($v_data_checksum);
        if ($v_header['checksum'] != $v_checksum) {
            $v_header['filename'] = '';

            // ----- Look for last block (empty block)
            if (($v_checksum == 256) && ($v_header['checksum'] == 0)) {
                return true;
            }

            $this->_error(
                'Invalid checksum for file "' . $v_data['filename']
                . '" : ' . $v_checksum . ' calculated, '
                . $v_header['checksum'] . ' expected'
            );
            return false;
        }

        // ----- Extract the properties
        $v_header['filename'] = rtrim($v_data['filename'], "\0");
        if ($this->_isMaliciousFilename($v_header['filename'])) {
            $this->_error(
                'Malicious .tar detected, file "' . $v_header['filename'] .
                '" will not install in desired directory tree'
            );
            return false;
        }
        $v_header['mode'] = OctDec(trim($v_data['mode']));
        $v_header['uid'] = OctDec(trim($v_data['uid']));
        $v_header['gid'] = OctDec(trim($v_data['gid']));
        $v_header['size'] = $this->_tarRecToSize($v_data['size']);
        $v_header['mtime'] = OctDec(trim($v_data['mtime']));
        if (($v_header['typeflag'] = $v_data['typeflag']) == "5") {
            $v_header['size'] = 0;
        }
        $v_header['link'] = trim($v_data['link']);
        /* ----- All these fields are removed form the header because
        they do not carry interesting info
        $v_header[magic] = trim($v_data[magic]);
        $v_header[version] = trim($v_data[version]);
        $v_header[uname] = trim($v_data[uname]);
        $v_header[gname] = trim($v_data[gname]);
        $v_header[devmajor] = trim($v_data[devmajor]);
        $v_header[devminor] = trim($v_data[devminor]);
        */

        return true;
    }

    /**
     * Convert Tar record size to actual size
     *
     * @param string $tar_size
     * @return size of tar record in bytes
     */
    private function _tarRecToSize($tar_size)
    {
        /*
         * First byte of size has a special meaning if bit 7 is set.
         *
         * Bit 7 indicates base-256 encoding if set.
         * Bit 6 is the sign bit.
         * Bits 5:0 are most significant value bits.
         */
        $ch = ord($tar_size[0]);
        if ($ch & 0x80) {
            // Full 12-bytes record is required.
            $rec_str = $tar_size . "\x00";

            $size = ($ch & 0x40) ? -1 : 0;
            $size = ($size << 6) | ($ch & 0x3f);

            for ($num_ch = 1; $num_ch < 12; ++$num_ch) {
                $size = ($size * 256) + ord($rec_str[$num_ch]);
            }

            return $size;

        } else {
            return OctDec(trim($tar_size));
        }
    }

    /**
     * Detect and report a malicious file name
     *
     * @param string $file
     *
     * @return bool
     */
    private function _isMaliciousFilename($file)
    {
        if (strpos($file, '://') !== false) {
            return true;
        }
        if (strpos($file, '../') !== false || strpos($file, '..\\') !== false) {
            return true;
        }
        return false;
    }

    /**
     * @param $v_header
     * @return bool
     */
    public function _readLongHeader(&$v_header)
    {
        $v_filename = '';
        $v_filesize = $v_header['size'];
        $n = floor($v_header['size'] / 512);
        for ($i = 0; $i < $n; $i++) {
            $v_content = $this->_readBlock();
            $v_filename .= $v_content;
        }
        if (($v_header['size'] % 512) != 0) {
            $v_content = $this->_readBlock();
            $v_filename .= $v_content;
        }

        // ----- Read the next header
        $v_binary_data = $this->_readBlock();

        if (!$this->_readHeader($v_binary_data, $v_header)) {
            return false;
        }

        $v_filename = rtrim(substr($v_filename, 0, $v_filesize), "\0");
        $v_header['filename'] = $v_filename;
        if ($this->_isMaliciousFilename($v_filename)) {
            $this->_error(
                'Malicious .tar detected, file "' . $v_filename .
                '" will not install in desired directory tree'
            );
            return false;
        }

        return true;
    }

    /**
     * This method extract from the archive one file identified by $p_filename.
     * The return value is a string with the file content, or null on error.
     *
     * @param string $p_filename The path of the file to extract in a string.
     *
     * @return a string with the file content or null.
     */
    private function _extractInString($p_filename)
    {
        $v_result_str = "";

        while (strlen($v_binary_data = $this->_readBlock()) != 0) {
            if (!$this->_readHeader($v_binary_data, $v_header)) {
                return null;
            }

            if ($v_header['filename'] == '') {
                continue;
            }

            switch ($v_header['typeflag']) {
                case 'L':
                    {
                        if (!$this->_readLongHeader($v_header)) {
                            return null;
                        }
                    }
                    break;

                case 'K':
                    {
                        $v_link_header = $v_header;
                        if (!$this->_readLongHeader($v_link_header)) {
                            return null;
                        }
                        $v_header['link'] = $v_link_header['filename'];
                    }
                    break;
            }

            if ($v_header['filename'] == $p_filename) {
                if ($v_header['typeflag'] == "5") {
                    $this->_error(
                        'Unable to extract in string a directory '
                        . 'entry {' . $v_header['filename'] . '}'
                    );
                    return null;
                } else {
                    $n = floor($v_header['size'] / 512);
                    for ($i = 0; $i < $n; $i++) {
                        $v_result_str .= $this->_readBlock();
                    }
                    if (($v_header['size'] % 512) != 0) {
                        $v_content = $this->_readBlock();
                        $v_result_str .= substr(
                            $v_content,
                            0,
                            ($v_header['size'] % 512)
                        );
                    }
                    return $v_result_str;
                }
            } else {
                $this->_jumpBlock(ceil(($v_header['size'] / 512)));
            }
        }

        return null;
    }

    /**
     * @param string $p_path
     * @param string $p_list_detail
     * @param string $p_mode
     * @param string $p_file_list
     * @param string $p_remove_path
     * @param bool $p_preserve
     * @param bool $p_symlinks
     * @return bool
     */
    public function _extractList(
        $p_path,
        &$p_list_detail,
        $p_mode,
        $p_file_list,
        $p_remove_path,
        $p_preserve = false,
        $p_symlinks = true
    )
    {
        $v_result = true;
        $v_nb = 0;
        $v_extract_all = true;
        $v_listing = false;

        $p_path = $this->_translateWinPath($p_path, false);
        if ($p_path == '' || (substr($p_path, 0, 1) != '/'
                && substr($p_path, 0, 3) != "../" && !strpos($p_path, ':'))
        ) {
            $p_path = "./" . $p_path;
        }
        $p_remove_path = $this->_translateWinPath($p_remove_path);

        // ----- Look for path to remove format (should end by /)
        if (($p_remove_path != '') && (substr($p_remove_path, -1) != '/')) {
            $p_remove_path .= '/';
        }
        $p_remove_path_size = strlen($p_remove_path);

        switch ($p_mode) {
            case "complete" :
                $v_extract_all = true;
                $v_listing = false;
                break;
            case "partial" :
                $v_extract_all = false;
                $v_listing = false;
                break;
            case "list" :
                $v_extract_all = false;
                $v_listing = true;
                break;
            default :
                $this->_error('Invalid extract mode (' . $p_mode . ')');
                return false;
        }

        clearstatcache();

        while (strlen($v_binary_data = $this->_readBlock()) != 0) {
            $v_extract_file = false;
            $v_extraction_stopped = 0;

            if (!$this->_readHeader($v_binary_data, $v_header)) {
                return false;
            }

            if ($v_header['filename'] == '') {
                continue;
            }

            switch ($v_header['typeflag']) {
                case 'L':
                    {
                        if (!$this->_readLongHeader($v_header)) {
                            return null;
                        }
                    }
                    break;

                case 'K':
                    {
                        $v_link_header = $v_header;
                        if (!$this->_readLongHeader($v_link_header)) {
                            return null;
                        }
                        $v_header['link'] = $v_link_header['filename'];
                    }
                    break;
            }

            // ignore extended / pax headers
            if ($v_header['typeflag'] == 'x' || $v_header['typeflag'] == 'g') {
                $this->_jumpBlock(ceil(($v_header['size'] / 512)));
                continue;
            }

            if ((!$v_extract_all) && (is_array($p_file_list))) {
                // ----- By default no unzip if the file is not found
                $v_extract_file = false;

                for ($i = 0; $i < sizeof($p_file_list); $i++) {
                    // ----- Look if it is a directory
                    if (substr($p_file_list[$i], -1) == '/') {
                        // ----- Look if the directory is in the filename path
                        if ((strlen($v_header['filename']) > strlen($p_file_list[$i]))
                            && (substr($v_header['filename'], 0, strlen($p_file_list[$i]))
                                == $p_file_list[$i])
                        ) {
                            $v_extract_file = true;
                            break;
                        }
                    } // ----- It is a file, so compare the file names
                    elseif ($p_file_list[$i] == $v_header['filename']) {
                        $v_extract_file = true;
                        break;
                    }
                }
            } else {
                $v_extract_file = true;
            }

            // ----- Look if this file need to be extracted
            if (($v_extract_file) && (!$v_listing)) {
                if (($p_remove_path != '')
                    && (substr($v_header['filename'] . '/', 0, $p_remove_path_size)
                        == $p_remove_path)
                ) {
                    $v_header['filename'] = substr(
                        $v_header['filename'],
                        $p_remove_path_size
                    );
                    if ($v_header['filename'] == '') {
                        continue;
                    }
                }
                if (($p_path != './') && ($p_path != '/')) {
                    while (substr($p_path, -1) == '/') {
                        $p_path = substr($p_path, 0, strlen($p_path) - 1);
                    }

                    if (substr($v_header['filename'], 0, 1) == '/') {
                        $v_header['filename'] = $p_path . $v_header['filename'];
                    } else {
                        $v_header['filename'] = $p_path . '/' . $v_header['filename'];
                    }
                }
                if (file_exists($v_header['filename'])) {
                    if ((@is_dir($v_header['filename']))
                        && ($v_header['typeflag'] == '')
                    ) {
                        $this->_error(
                            'File ' . $v_header['filename']
                            . ' already exists as a directory'
                        );
                        return false;
                    }
                    if (($this->_isArchive($v_header['filename']))
                        && ($v_header['typeflag'] == "5")
                    ) {
                        $this->_error(
                            'Directory ' . $v_header['filename']
                            . ' already exists as a file'
                        );
                        return false;
                    }
                    if (!is_writeable($v_header['filename'])) {
                        $this->_error(
                            'File ' . $v_header['filename']
                            . ' already exists and is write protected'
                        );
                        return false;
                    }
                    if (filemtime($v_header['filename']) > $v_header['mtime']) {
                        // To be completed : An error or silent no replace ?
                    }
                } // ----- Check the directory availability and create it if necessary
                elseif (($v_result
                        = $this->_dirCheck(
                        ($v_header['typeflag'] == "5"
                            ? $v_header['filename']
                            : dirname($v_header['filename']))
                    )) != 1
                ) {
                    $this->_error('Unable to create path for ' . $v_header['filename']);
                    return false;
                }

                if ($v_extract_file) {
                    if ($v_header['typeflag'] == "5") {
                        if (!@file_exists($v_header['filename'])) {
                            if (!@mkdir($v_header['filename'], 0775)) {
                                $this->_error(
                                    'Unable to create directory {'
                                    . $v_header['filename'] . '}'
                                );
                                return false;
                            }
                        }
                    } elseif ($v_header['typeflag'] == "2") {
                        if (!$p_symlinks) {
                            $this->_warning('Symbolic links are not allowed. '
                                . 'Unable to extract {'
                                . $v_header['filename'] . '}'
                            );
                            return false;
                        }
                        $absolute_link = FALSE;
                        $link_depth = 0;
                        if (strpos($v_header['link'], "/") === 0 || strpos($v_header['link'], ':') !== FALSE) {
                          $absolute_link = TRUE;
                        }
                        else {
                            $s_filename = preg_replace('@^' . preg_quote($p_path) . '@', "", $v_header['filename']);
                            $s_linkname = str_replace('\\', '/', $v_header['link']);
                            foreach (explode("/", $s_filename) as $dir) {
                                if ($dir === "..") {
                                    $link_depth--;
                                } elseif ($dir !== "" && $dir !== "." ) {
                                    $link_depth++;
                                }
                            }
                            foreach (explode("/", $s_linkname) as $dir){
                                if ($link_depth <= 0) {
                                    break;
                                }
                                if ($dir === "..") {
                                    $link_depth--;
                                } elseif ($dir !== "" && $dir !== ".") {
                                    $link_depth++;
                                }
                            }
                        }
                        if ($absolute_link || $link_depth <= 0) {
                            $this->_error(
                                 'Out-of-path file extraction {'
                                 . $v_header['filename'] . ' --> ' .
                                 $v_header['link'] . '}'
                            );
                            return false;
                        }
                        if (@file_exists($v_header['filename'])) {
                            @unlink($v_header['filename']);
                        }
                        if (!@symlink($v_header['link'], $v_header['filename'])) {
                            $this->_error(
                                'Unable to extract symbolic link {'
                                . $v_header['filename'] . '}'
                            );
                            return false;
                        }
                    } else {
                        if (($v_dest_file = @fopen($v_header['filename'], "wb")) == 0) {
                            $this->_error(
                                'Error while opening {' . $v_header['filename']
                                . '} in write binary mode'
                            );
                            return false;
                        } else {
                            $n = floor($v_header['size'] / 512);
                            for ($i = 0; $i < $n; $i++) {
                                $v_content = $this->_readBlock();
                                fwrite($v_dest_file, $v_content, 512);
                            }
                            if (($v_header['size'] % 512) != 0) {
                                $v_content = $this->_readBlock();
                                fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
                            }

                            @fclose($v_dest_file);

                            if ($p_preserve) {
                                @chown($v_header['filename'], $v_header['uid']);
                                @chgrp($v_header['filename'], $v_header['gid']);
                            }

                            // ----- Change the file mode, mtime
                            @touch($v_header['filename'], $v_header['mtime']);
                            if ($v_header['mode'] & 0111) {
                                // make file executable, obey umask
                                $mode = fileperms($v_header['filename']) | (~umask() & 0111);
                                @chmod($v_header['filename'], $mode);
                            }
                        }

                        // ----- Check the file size
                        clearstatcache();
                        if (!is_file($v_header['filename'])) {
                            $this->_error(
                                'Extracted file ' . $v_header['filename']
                                . 'does not exist. Archive may be corrupted.'
                            );
                            return false;
                        }

                        $filesize = filesize($v_header['filename']);
                        if ($filesize != $v_header['size']) {
                            $this->_error(
                                'Extracted file ' . $v_header['filename']
                                . ' does not have the correct file size \''
                                . $filesize
                                . '\' (' . $v_header['size']
                                . ' expected). Archive may be corrupted.'
                            );
                            return false;
                        }
                    }
                } else {
                    $this->_jumpBlock(ceil(($v_header['size'] / 512)));
                }
            } else {
                $this->_jumpBlock(ceil(($v_header['size'] / 512)));
            }

            /* TBC : Seems to be unused ...
            if ($this->_compress)
              $v_end_of_file = @gzeof($this->_file);
            else
              $v_end_of_file = @feof($this->_file);
              */

            if ($v_listing || $v_extract_file || $v_extraction_stopped) {
                // ----- Log extracted files
                if (($v_file_dir = dirname($v_header['filename']))
                    == $v_header['filename']
                ) {
                    $v_file_dir = '';
                }
                if ((substr($v_header['filename'], 0, 1) == '/') && ($v_file_dir == '')) {
                    $v_file_dir = '/';
                }

                $p_list_detail[$v_nb++] = $v_header;
                if (is_array($p_file_list) && (count($p_list_detail) == count($p_file_list))) {
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _openAppend()
    {
        if (filesize($this->_tarname) == 0) {
            return $this->_openWrite();
        }

        if ($this->_compress) {
            $this->_close();

            if (!@rename($this->_tarname, $this->_tarname . ".tmp")) {
                $this->_error(
                    'Error while renaming \'' . $this->_tarname
                    . '\' to temporary file \'' . $this->_tarname
                    . '.tmp\''
                );
                return false;
            }

            if ($this->_compress_type == 'gz') {
                $v_temp_tar = @gzopen($this->_tarname . ".tmp", "rb");
            } elseif ($this->_compress_type == 'bz2') {
                $v_temp_tar = @bzopen($this->_tarname . ".tmp", "r");
            } elseif ($this->_compress_type == 'lzma2') {
                $v_temp_tar = @xzopen($this->_tarname . ".tmp", "r");
            }


            if ($v_temp_tar == 0) {
                $this->_error(
                    'Unable to open file \'' . $this->_tarname
                    . '.tmp\' in binary read mode'
                );
                @rename($this->_tarname . ".tmp", $this->_tarname);
                return false;
            }

            if (!$this->_openWrite()) {
                @rename($this->_tarname . ".tmp", $this->_tarname);
                return false;
            }

            if ($this->_compress_type == 'gz') {
                $end_blocks = 0;

                while (!@gzeof($v_temp_tar)) {
                    $v_buffer = @gzread($v_temp_tar, 512);
                    if ($v_buffer == ARCHIVE_TAR_END_BLOCK || strlen($v_buffer) == 0) {
                        $end_blocks++;
                        // do not copy end blocks, we will re-make them
                        // after appending
                        continue;
                    } elseif ($end_blocks > 0) {
                        for ($i = 0; $i < $end_blocks; $i++) {
                            $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                        }
                        $end_blocks = 0;
                    }
                    $v_binary_data = pack("a512", $v_buffer);
                    $this->_writeBlock($v_binary_data);
                }

                @gzclose($v_temp_tar);
            } elseif ($this->_compress_type == 'bz2') {
                $end_blocks = 0;

                while (strlen($v_buffer = @bzread($v_temp_tar, 512)) > 0) {
                    if ($v_buffer == ARCHIVE_TAR_END_BLOCK || strlen($v_buffer) == 0) {
                        $end_blocks++;
                        // do not copy end blocks, we will re-make them
                        // after appending
                        continue;
                    } elseif ($end_blocks > 0) {
                        for ($i = 0; $i < $end_blocks; $i++) {
                            $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                        }
                        $end_blocks = 0;
                    }
                    $v_binary_data = pack("a512", $v_buffer);
                    $this->_writeBlock($v_binary_data);
                }

                @bzclose($v_temp_tar);
            } elseif ($this->_compress_type == 'lzma2') {
                $end_blocks = 0;

                while (strlen($v_buffer = @xzread($v_temp_tar, 512)) > 0) {
                    if ($v_buffer == ARCHIVE_TAR_END_BLOCK || strlen($v_buffer) == 0) {
                        $end_blocks++;
                        // do not copy end blocks, we will re-make them
                        // after appending
                        continue;
                    } elseif ($end_blocks > 0) {
                        for ($i = 0; $i < $end_blocks; $i++) {
                            $this->_writeBlock(ARCHIVE_TAR_END_BLOCK);
                        }
                        $end_blocks = 0;
                    }
                    $v_binary_data = pack("a512", $v_buffer);
                    $this->_writeBlock($v_binary_data);
                }

                @xzclose($v_temp_tar);
            }

            if (!@unlink($this->_tarname . ".tmp")) {
                $this->_error(
                    'Error while deleting temporary file \''
                    . $this->_tarname . '.tmp\''
                );
            }
        } else {
            // ----- For not compressed tar, just add files before the last
            //       one or two 512 bytes block
            if (!$this->_openReadWrite()) {
                return false;
            }

            clearstatcache();
            $v_size = filesize($this->_tarname);

            // We might have zero, one or two end blocks.
            // The standard is two, but we should try to handle
            // other cases.
            fseek($this->_file, $v_size - 1024);
            if (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
                fseek($this->_file, $v_size - 1024);
            } elseif (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
                fseek($this->_file, $v_size - 512);
            }
        }

        return true;
    }

    /**
     * @param $p_filelist
     * @param string $p_add_dir
     * @param string $p_remove_dir
     * @return bool
     */
    public function _append($p_filelist, $p_add_dir = '', $p_remove_dir = '')
    {
        if (!$this->_openAppend()) {
            return false;
        }

        if ($this->_addList($p_filelist, $p_add_dir, $p_remove_dir)) {
            $this->_writeFooter();
        }

        $this->_close();

        return true;
    }

    /**
     * Check if a directory exists and create it (including parent
     * dirs) if not.
     *
     * @param string $p_dir directory to check
     *
     * @return bool true if the directory exists or was created
     */
    public function _dirCheck($p_dir)
    {
        clearstatcache();
        if ((@is_dir($p_dir)) || ($p_dir == '')) {
            return true;
        }

        $p_parent_dir = dirname($p_dir);

        if (($p_parent_dir != $p_dir) &&
            ($p_parent_dir != '') &&
            (!$this->_dirCheck($p_parent_dir))
        ) {
            return false;
        }

        if (!@mkdir($p_dir, 0775)) {
            $this->_error("Unable to create directory '$p_dir'");
            return false;
        }

        return true;
    }

    /**
     * Compress path by changing for example "/dir/foo/../bar" to "/dir/bar",
     * rand emove double slashes.
     *
     * @param string $p_dir path to reduce
     *
     * @return string reduced path
     */
    private function _pathReduction($p_dir)
    {
        $v_result = '';

        // ----- Look for not empty path
        if ($p_dir != '') {
            // ----- Explode path by directory names
            $v_list = explode('/', $p_dir);

            // ----- Study directories from last to first
            for ($i = sizeof($v_list) - 1; $i >= 0; $i--) {
                // ----- Look for current path
                if ($v_list[$i] == ".") {
                    // ----- Ignore this directory
                    // Should be the first $i=0, but no check is done
                } else {
                    if ($v_list[$i] == "..") {
                        // ----- Ignore it and ignore the $i-1
                        $i--;
                    } else {
                        if (($v_list[$i] == '')
                            && ($i != (sizeof($v_list) - 1))
                            && ($i != 0)
                        ) {
                            // ----- Ignore only the double '//' in path,
                            // but not the first and last /
                        } else {
                            $v_result = $v_list[$i] . ($i != (sizeof($v_list) - 1) ? '/'
                                    . $v_result : '');
                        }
                    }
                }
            }
        }

        if (defined('OS_WINDOWS') && OS_WINDOWS) {
            $v_result = strtr($v_result, '\\', '/');
        }

        return $v_result;
    }

    /**
     * @param $p_path
     * @param bool $p_remove_disk_letter
     * @return string
     */
    public function _translateWinPath($p_path, $p_remove_disk_letter = true)
    {
        if (defined('OS_WINDOWS') && OS_WINDOWS) {
            // ----- Look for potential disk letter
            if (($p_remove_disk_letter)
                && (($v_position = strpos($p_path, ':')) != false)
            ) {
                $p_path = substr($p_path, $v_position + 1);
            }
            // ----- Change potential windows directory separator
            if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')) {
                $p_path = strtr($p_path, '\\', '/');
            }
        }
        return $p_path;
    }
}
