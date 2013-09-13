<?php

/**
 * PRODS class
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */

require_once("autoload.inc.php");

class ProdsStreamer
{
 /**
	* current position of the file or dir
	*
	* @access private
	*/
  private $position;
  
  /**
	 * Name of the directory/collection specified in the URI to opendir().
	 *
	 * @access private
	 */
	private $dir;
	
	/**
	 * Name of the file specified in the URI to fopen().
	 *
	 * @access private
	 */
	private $file;

	
	/**
	 * url_stat() handler.
	 *
	 * @access private
	 */
	public function url_stat($path)
	{
		try {
                  $file=ProdsDir::fromURI($path);
                  $conn = RODSConnManager::getConn($file->account);

                  $stats = $this->stat_file($conn, $file->path_str);
                  if (!$stats) {
                    $stats = $this->stat_dir($conn, $file->path_str);
                  }
                  
                  RODSConnManager::releaseConn($conn);
                  
                  return $stats;
                  
		} catch (Exception $e) {
                  trigger_error("Got an exception:$e", E_USER_WARNING);
                  return false;
		}
	}

        /**
         * @param $conn
         * @param $file
         * @return mixed
         */
        private function stat_dir($conn, $path_str) {
                 try {
                   $irods_stats = $conn->getDirStats($path_str);
                   if (!$irods_stats)
                     return false;
                   $stats = array();
                   $stats[0] = $stats['dev'] = 0;
                   $stats[1] = $stats['ino'] = 0;
                   $stats[2] = $stats['mode'] = octdec('040755');
                   $stats[3] = $stats['nlink'] = 1;
                   $stats[4] = $stats['uid'] = 0;
                   $stats[5] = $stats['gid'] = 0;
                   $stats[6] = $stats['rdev'] = -1;
                   $stats[7] = $stats['size'] = 0;
                   $stats[8] = $stats['atime'] = time();
                   $stats[9] = $stats['mtime'] = $irods_stats->mtime;
                   $stats[10] = $stats['ctime'] = $irods_stats->ctime;
                   $stats[11] = $stats['blksize'] = -1;
                   $stats[12] = $stats['blocks'] = -1;
                   return $stats;
                 } catch (Exception $e) {
                   trigger_error("Got an exception: $e", E_USER_WARNING);
                   return false;
                 }
        }
                 
        /**
         * @param $conn
         * @param $file
         * @return mixed
         */
        private function stat_file($conn, $path_str) {
                 try {
                   $irods_stats = $conn->getFileStats($path_str);
                   if (!$irods_stats)
                     return false;
                   $stats = array();
                   $stats[0] = $stats['dev'] = 0;
                   $stats[1] = $stats['ino'] = 0;
                   $stats[2] = $stats['mode'] = octdec('100644');
                   $stats[3] = $stats['nlink'] = 1;
                   $stats[4] = $stats['uid'] = 0;
                   $stats[5] = $stats['gid'] = 0;
                   $stats[6] = $stats['rdev'] = -1;
                   $stats[7] = $stats['size'] = $irods_stats->size;
                   $stats[8] = $stats['atime'] = time();
                   $stats[9] = $stats['mtime'] = $irods_stats->mtime;
                   $stats[10] = $stats['ctime'] = $irods_stats->ctime;
                   $stats[11] = $stats['blksize'] = -1;
                   $stats[12] = $stats['blocks'] = -1;
                   return $stats;
                 } catch (Exception $e) {
                   trigger_error("Got an exception: $e", E_USER_WARNING);
                   return false;
                 }
        }
                 
	/**
	 * mkdir() handler.
	 *
	 * @access private
	 */
	function mkdir ($url, $mode, $options) {
		try {
                  $file=ProdsDir::fromURI($url);
                  $conn = RODSConnManager::getConn($file->account);
                  $conn->mkdir($file->path_str);
                  
                  RODSConnManager::releaseConn($conn);
                  return true;
		} catch (Exception $e) {
                  trigger_error("Got an exception:$e", E_USER_WARNING);
                  return false;
		}
	}

	/**
	 * rmdir() handler
	 *
	 * @param $url
	 * @return bool
	 */
	function rmdir ($url) {
		try {
			$file=ProdsDir::fromURI($url);
			$conn = RODSConnManager::getConn($file->account);
			$conn->rmdir($file->path_str);

			RODSConnManager::releaseConn($conn);
			return true;
		} catch (Exception $e) {
			trigger_error("Got an exception:$e", E_USER_WARNING);
			return false;
		}
	}

	/**
	 * unlink() handler.
	 *
	 * @access private
	 */
	function unlink ($url) {
		try {
                  $file=ProdsDir::fromURI($url);
                  $conn = RODSConnManager::getConn($file->account);
                  if (is_dir($url)) {
                    $conn->rmdir($file->path_str, true, true);
                  } else {
                    $conn->fileUnlink($file->path_str, NULL, true);
                  }
                  
                  RODSConnManager::releaseConn($conn);
                  return true;
		} catch (Exception $e) {
                  trigger_error("Got an exception:$e", E_USER_WARNING);
                  return false;
		}
	}

	/**
	 * rename() handler.
	 *
	 * @access private
	 */
        function rename ($url_from, $url_to) {
                try {
                  $file_from=ProdsDir::fromURI($url_from);
                  $file_to=ProdsDir::fromURI($url_to);
                  $conn = RODSConnManager::getConn($file_from->account);

                  if (is_dir($url_from)) {
                    $conn->rename($file_from->path_str, $file_to->path_str, 0);
                  } else {
                    $conn->rename($file_from->path_str, $file_to->path_str, 1);
                  }

                  RODSConnManager::releaseConn($conn);
                  return true;
                } catch (Exception $e) {
                  trigger_error("Got an exception:$e", E_USER_WARNING);
                  return false;
                }
        }

	/**
	 * opendir() handler.
	 *
	 * @access private
	 */
	public function dir_opendir ($path, $options) 
	{
		try {
		  $this->dir=ProdsDir::fromURI($path,true);
		  return true;
		} catch (Exception $e) {
		  trigger_error("Got an exception:$e", E_USER_WARNING);
		  return false;
		}
	}

    /**
     * readdir() handler.
     *
     * @access private
     */
    public function dir_readdir()
    {
        try {
            $child = $this->dir->getNextChild();
            if ($child === false) return false;
            return $child->getName();
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return false;
        }
    }

	/**
	 * fread() and fgets() handler.
	 *
	 * @access private
	 */
	public function stream_read ($count) {
		if (in_array ($this->file->getOpenMode(), array ('w', 'a', 'x'))) {
			return false;
		}
		try {
  		$ret = $this->file->read($count);
  		$this->position=$this->file->tell();
  		return $ret;
  	} catch (Exception $e) {
		  trigger_error("Got an exception:$e", E_USER_WARNING);
		  return false;
		}	
	}

	/**
	 * fwrite() handler.
	 *
	 * @access private
	 */
	public function stream_write ($data) {
		if ($this->file->getOpenMode() =='r') {
			return false;
		}
		try {
  		$ret = $this->file->write($data);
  		$this->position=$this->file->tell();
  		return $ret;
  	} catch (Exception $e) {
		  trigger_error("Got an exception:$e", E_USER_WARNING);
		  return false;
		}	
	}
    /**
     * rewinddir() handler.
     *
     * @access private
     */
    public function dir_rewinddir()
    {
        try {
            $this->dir->rewind();
            return true;
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return false;
        }
    }

    /**
     * closedir() handler.
     *
     * @access private
     */
    public function dir_closedir()
    {
        try {
            $this->dir->rewind();
            return true;
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return false;
        }
    }

    /**
     * fopen() handler.
     *
     * @access private
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {

        // get rid of tailing 'b', if any.
        if (($mode{strlen($mode) - 1} == 'b') && (strlen($mode) > 1))
            $mode = substr($mode, 0, strlen($mode) - 1);
         try {
            $this->file = ProdsFile::fromURI($path);
            $this->file->open($mode);
            return true;
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return false;
        }
    }

	/**
	 * fstat() handler.
	 *
	 * @access private
	 */
	function stream_stat () {
	  
	  try {
	    $stats=$this->file->getStats();
	    return array (
  			-1, -1, -1, -1, -1, -1, $stats->size, time (), $stats->mtime, $stats->ctime, -1, -1,
  			'dev' => -1,
  			'ino' => -1,
  			'mode' => -1,
  			'nlink' => -1,
  			'uid' => -1,
  			'gid' => -1,
  			'rdev' => -1,
  			'size' => $stats->size,
  			'atime' => time (),
  			'mtime' => $stats->mtime,
  			'ctime' => $stats->ctime,
  			'blksize' => -1,
  			'blocks' => -1,
  		);
	  } catch (Exception $e) {
		  trigger_error("Got an exception:$e", E_USER_WARNING);
		  return false;
		} 
	}

	/**
	 * fclose() handler.
	 *
	 * @access private
	 */
	function stream_close () {
		$this->file->close();
		$this->position = 0;
		$this->file = null;
		$this->dir = null;
	}

    /**
     * ftell() handler.
     *
     * @access private
     */
    function stream_tell()
    {
        return $this->position;
    }

    /**
     * feof() handler.
     *
     * @access private
     */
    function stream_eof()
    {
        try {
            $stats = $this->file->getStats();
            return $this->position >= $stats->size;
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return true;
        }
    }

    /**
     * fseek() handler.
     *
     * @access private
     */
    function stream_seek($offset, $whence)
    {
        try {
            $this->file->seek($offset, $whence);
            return true;
        } catch (Exception $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
            return false;
        }
    }

    /**
     * fflush() handler.  Please Note: This method must be called for any
     * changes to be committed to the repository.
     *
     * @access private
     */
    function stream_flush()
    {
        return true;
    }
}
  
stream_wrapper_register('rods', 'ProdsStreamer')
    or die ('Failed to register protocol:rods');
stream_wrapper_register('rods+ticket', 'ProdsStreamer')
    or die ('Failed to register protocol:rods');
