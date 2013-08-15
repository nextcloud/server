<?php
/**
 * PRODS file class
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */

require_once("autoload.inc.php");

class ProdsFile extends ProdsPath
{
    public $stats;

    private $rodsconn; //real RODS connection
    private $l1desc; //lvl 1 descriptor on RODS server
    private $conn; //the connection to RODS agent l1desc lives on.
    private $rescname; //resource name.
    private $openmode; //open mode used if file is opened
    private $position; //current position of the file, if opened.

    /**
     * The class constructor
     */
    public function __construct(RODSAccount &$account, $path_str,
                                $verify = false, RODSFileStats $stats = NULL)
    {
        $this->l1desc = -1;
        $this->stats = $stats;

        if ($path_str{strlen($path_str) - 1} == '/') {
            throw new RODSException("Invalid file name '$path_str' ",
                'PERR_USER_INPUT_PATH_ERROR');
        }

        parent::__construct($account, $path_str);
        if ($verify === true) {
            if ($this->exists() === false) {
                throw new RODSException("File '$this' does not exist",
                    'PERR_PATH_DOES_NOT_EXISTS');
            }
        }
    }
  
  
 /**
	* Create a new ProdsFile object from URI string.
	* @param string $path the URI Sting
	* @param boolean $verify whether verify if the path exsits
	* @return a new ProdsDir
	*/
  public static function fromURI($path, $verify=false)
  {
    if (0!=strncmp($path,"rods://",7))
      $path="rods://".$path;
    $url=parse_url($path);
    
    $host=isset($url['host'])?$url['host']:''; 
    $port=isset($url['port'])?$url['port']:'';   
    
    $user='';
    $zone='';
    $authtype='irods';
    if (isset($url['user']))
    {
      if (strstr($url['user'],".")!==false) {
        $user_array=@explode(".",$url['user']);
        if (count($user_array)===3) {
          $user=$user_array[0];
          $zone=$user_array[1];
          $authtype=$user_array[2];
        }
        else {
          $user=$user_array[0];
          $zone=$user_array[1];
        }
      }
      else
        $user=$url['user'];
    }  
    
    $pass=isset($url['pass'])?$url['pass']:'';
    
    $account=new RODSAccount($host, $port, $user, $pass, $zone, '', $authtype);
    
    $path_str=isset($url['path'])?$url['path']:''; 
    
    // treat query and fragment as part of name
    if (isset($url['query'])&&(strlen($url['query'])>0))
      $path_str=$path_str.'?'.$url['query'];
    if (isset($url['fragment'])&&(strlen($url['fragment'])>0))
      $path_str=$path_str.'#'.$url['fragment'];
    
    if (empty($path_str))
      $path_str='/'; 
      
    return (new ProdsFile($account,$path_str,$verify));
  }  
  
 /**
	* Verify if this file exist with server. This function shouldn't be called directly, use {@link exists}
	*/
  protected function verify()
  {
    $conn = RODSConnManager::getConn($this->account);
    $this->path_exists= $conn -> fileExists ($this->path_str);
    RODSConnManager::releaseConn($conn);  
  }
  
 /**
  * get the file stats
  */
  public function getStats()
  {
    $conn = RODSConnManager::getConn($this->account);
    $stats=$conn->getFileStats($this->path_str);
    RODSConnManager::releaseConn($conn); 
    
    if ($stats===false) $this->stats=NULL;
    else $this->stats=$stats;
    return $this->stats;
  }
 
    /**
     * Open a file path (string) exists on RODS server.
     *
     * @param string $mode open mode. Supported modes are:
     * -  'r'     Open for reading only; place the file pointer at the beginning of the file.
     * -  'r+'    Open for reading and writing; place the file pointer at the beginning of the file.
     * -  'w'    Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
     * -  'w+'    Open for reading and writing; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
     * -  'a'    Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
     * -  'a+'    Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
     * -  'x'    Create and open for writing only; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning FALSE and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     * -  'x+'    Create and open for reading and writing; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning FALSE and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     * @param string $rescname. Note that this parameter is required only if the file does not exists (create mode). If the file already exists, and if file resource is unknown or unique or you-dont-care for that file, leave the field, or pass NULL.
     * @param boolean $assum_file_exists. This parameter specifies whether file exists. If the value is false, this mothod will check with RODS server to make sure. If value is true, the check will NOT be done. Default value is false.
     * @param string $filetype. This parameter only make sense when you want to specify the file type, if file does not exists (create mode). If not specified, it defaults to "generic"
     * @param integer $cmode. This parameter is only used for "createmode". It specifies the file mode on physical storage system (RODS vault), in octal 4 digit format. For instance, 0644 is owner readable/writeable, and nothing else. 0777 is all readable, writable, and excutable. If not specified, and the open flag requirs create mode, it defaults to 0644.
     */
    public function open($mode, $rescname = NULL,
                         $assum_file_exists = false, $filetype = 'generic', $cmode = 0644)
    {
        if ($this->l1desc >= 0)
            return;

        if (!empty($rescname))
            $this->rescname = $rescname;

        $this->conn = RODSConnManager::getConn($this->account);
        $this->l1desc = $this->conn->openFileDesc($this->path_str, $mode,
            $this->postion, $rescname, $assum_file_exists, $filetype, $cmode);
        $this->openmode = $mode;
        RODSConnManager::releaseConn($this->conn);
    }

    /**
     * get the file open mode, if opened previously
     * @return string open mode, if not opened, it return NULL
     */
    public function getOpenmode()
    {
        return $this->openmode;
    }

    /**
     * get the file current position, if opened previously
     * @return string open mode, if not opened, it return NULL
     */
    public function tell()
    {
        return $this->position;
    }

    /**
     * unlink the file on server
     * @param string $rescname resource name. Not required if there is no other replica.
     * @param boolean $force flag (true or false) indicating whether force delete or not.
     */
    public function unlink($rescname = NULL, $force = false)
    {
        $conn = RODSConnManager::getConn($this->account);
        $conn->fileUnlink($this->path_str, $rescname, $force);
        RODSConnManager::releaseConn($conn);
    }
    /**
     * close the file descriptor (private) made from RODS server earlier.
     */
    public function close()
    {
        if ($this->l1desc >= 0) {
            while ($this->conn->isIdle() === false) {
                trigger_error("The connection is not available! sleep for a while and retry...",
                    E_USER_WARNING);
                usleep(50);
            }
            $this->conn->lock();
            $this->conn->closeFileDesc($this->l1desc);
            $this->conn->unlock();
            $this->conn = null; //release the connection
            $this->l1desc = -1;
        }
    }

    /**
     * reads up to length bytes from the file. Reading stops when up to length bytes have been read, EOF (end of file) is reached
     *
     * @param int $length up to how many bytes to read.
     * @return the read string.
     */
    public function read($length)
    {
        if ($this->l1desc < 0) {
            throw new RODSException("File '$this' is not opened! l1desc=$this->l1desc",
                'PERR_USER_INPUT_ERROR');
        }

        while ($this->conn->isIdle() === false) {
            trigger_error("The connection is not available! sleep for a while and retry...",
                E_USER_WARNING);
            usleep(50);
        }

        $this->conn->lock();
        $retval = $this->conn->fileRead($this->l1desc, $length);
        $this->position = $this->position + strlen($retval);
        $this->conn->unlock();
        return $retval;
    }

    /**
     * write up to length bytes to the server. this function is binary safe.
     * @param string $string contents to be written.
     * @param int $length up to how many bytes to write.
     * @return the number of bytes written.
     */
    public function write($string, $length = NULL)
    {
        if ($this->l1desc < 0) {
            throw new RODSException("File '$this' is not opened! l1desc=$this->l1desc",
                'PERR_USER_INPUT_ERROR');
        }

        while ($this->conn->isIdle() === false) {
            trigger_error("The connection is not available! sleep for a while and retry...",
                E_USER_WARNING);
            usleep(50);
        }

        $this->conn->lock();
        $retval = $this->conn->fileWrite($this->l1desc, $string, $length);
        $this->position = $this->position + (int)$retval;
        $this->conn->unlock();
        return $retval;
    }

    /**
     *  Sets the file position for the file. The new position, measured in bytes from the beginning of the file, is obtained by adding offset to the position specified by whence, whose values are defined as follows:
     *  SEEK_SET - Set position equal to offset bytes.
     *  SEEK_CUR - Set position to current location plus offset.
     *  SEEK_END - Set position to end-of-file plus offset. (To move to a position before the end-of-file, you need to pass a negative value in offset.)
     *  If whence is not specified, it is assumed to be SEEK_SET.
     * @return int the current offset
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->l1desc < 0) {
            throw new RODSException("File '$this' is not opened! l1desc=$this->l1desc",
                'PERR_USER_INPUT_ERROR');
        }

        while ($this->conn->isIdle() === false) {
            trigger_error("The connection is not available! sleep for a while and retry...",
                E_USER_WARNING);
            usleep(50);
        }

        $this->conn->lock();
        $retval = $this->conn->fileSeek($this->l1desc, $offset, $whence);
        $this->position = (int)$retval;
        $this->conn->unlock();
        return $retval;
    }

    /**
     * Sets the file position to the beginning of the file stream.
     */
    public function rewind()
    {
        while ($this->conn->isIdle() === false) {
            trigger_error("The connection is not available! sleep for a while and retry...",
                E_USER_WARNING);
            usleep(50);
        }

        $this->seek(0, SEEK_SET);
        $this->position = 0;
    }

    /**
     * get the file descriptor (private) made from RODS server earlier.
     */
    public function getL1desc()
    {
        return $this->l1desc;
    }

    /**
     * Because RODS server can only do file operations in a single connection, a RODS
     * connection is 'reserved' when file is opened, and released when closed.
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * Replicate file to resources with options.
     * @param string $desc_resc destination resource
     * @param array $options an assosive array of options:
     *   - 'all'        (boolean): only meaningful if input resource is a resource group. Replicate to all the resources in the resource group.
     *   - 'backupMode' (boolean): if a good copy already exists in this resource, don't make another copy.
     *   - 'admin'      (boolean): admin user uses this option to backup/replicate other users files
     *   - 'replNum'    (integer): the replica to copy, typically not needed
     *   - 'srcResc'    (string): specifies the source resource of the data object to be replicate, only copies stored in this resource will be replicated. Otherwise, one of the copy will be replicated
     * These options are all 'optional', if omitted, the server will try to do it anyway
     * @return number of bytes written if success, in case of faliure, throw an exception
     */
    public function repl($desc_resc, array $options = array())
    {
        $conn = RODSConnManager::getConn($this->account);
        $bytesWritten = $conn->repl($this->path_str, $desc_resc, $options);
        RODSConnManager::releaseConn($conn);

        return $bytesWritten;
    }

    /**
     * get replica information for this file
     * @return array of array, each child array is a associative and contains:
     *   [repl_num]      : replica number
     *   [chk_sum]       : checksum of the file
     *   [size]          : size of the file (replica)
     *   [resc_name]     : resource name
     *   [resc_repl_status] : replica status (dirty bit), whether this replica is dirty (modifed), and requirs synchs to other replicas.
     *   [resc_grp_name] : resource group name
     *   [resc_type]     : resource type name
     *   [resc_class]    : resource class name
     *   [resc_loc]      : resource location
     *   [resc_freespace]: resource freespace
     *   [data_status]   : data status
     *   [ctime]         : data creation time (unix timestamp)
     *   [mtime]         : data last modified time (unix timestamp)
     */
    public function getReplInfo()
    {
        $select = new RODSGenQueSelFlds(
            array("COL_DATA_REPL_NUM", "COL_D_DATA_CHECKSUM", 'COL_DATA_SIZE',
                "COL_D_RESC_NAME", "COL_D_RESC_GROUP_NAME",
                "COL_D_DATA_STATUS", "COL_D_CREATE_TIME",
                "COL_D_MODIFY_TIME", 'COL_R_TYPE_NAME', 'COL_R_CLASS_NAME',
                'COL_R_LOC', 'COL_R_FREE_SPACE', 'COL_D_REPL_STATUS')
        );
        $condition = new RODSGenQueConds(
            array("COL_COLL_NAME", "COL_DATA_NAME"),
            array("=", "="),
            array($this->parent_path, $this->name)
        );

        $conn = RODSConnManager::getConn($this->account);
        $que_result = $conn->query($select, $condition);
        RODSConnManager::releaseConn($conn);

        $ret_arr = array();
        for ($i = 0; $i < $que_result->getNumRow(); $i++) {
            $ret_arr_row = array();
            $que_result_val = $que_result->getValues();
            $ret_arr_row['repl_num'] = $que_result_val['COL_DATA_REPL_NUM'][$i];
            $ret_arr_row['chk_sum'] = $que_result_val['COL_D_DATA_CHECKSUM'][$i];
            $ret_arr_row['size'] = $que_result_val['COL_DATA_SIZE'][$i];
            $ret_arr_row['resc_name'] = $que_result_val['COL_D_RESC_NAME'][$i];
            $ret_arr_row['resc_grp_name'] = $que_result_val['COL_D_RESC_GROUP_NAME'][$i];
            $ret_arr_row['data_status'] = $que_result_val['COL_D_DATA_STATUS'][$i];
            $ret_arr_row['ctime'] = $que_result_val['COL_D_CREATE_TIME'][$i];
            $ret_arr_row['mtime'] = $que_result_val['COL_D_MODIFY_TIME'][$i];
            $ret_arr_row['resc_type'] = $que_result_val['COL_R_TYPE_NAME'][$i];
            $ret_arr_row['resc_class'] = $que_result_val['COL_R_CLASS_NAME'][$i];
            $ret_arr_row['resc_loc'] = $que_result_val['COL_R_LOC'][$i];
            $ret_arr_row['resc_freespace'] = $que_result_val['COL_R_FREE_SPACE'][$i];
            $ret_arr_row['resc_repl_status'] = $que_result_val['COL_D_REPL_STATUS'][$i];
            $ret_arr[] = $ret_arr_row;
        }
        return $ret_arr;
    }
    
        /**
     * Get ACL (users and their rights on a file)
     * @param string $filepath input file path string
     * @return RODSFileStats. If file does not exists, return fales.
     */
    public function getACL()
    {

        $filepath = $this->path_str;
        $parent = dirname($filepath);
        $filename = basename($filepath);

//        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $parent),
//            new RODSQueryCondition("COL_DATA_NAME", $filename));
        $cond = array(new RODSQueryCondition("COL_DATA_NAME", $filename),
            new RODSQueryCondition("COL_COLL_NAME", $parent));

        $connLocal = RODSConnManager::getConn($this->account);
        $que_result = $connLocal->genQuery(
              array("COL_USER_NAME", "COL_USER_ZONE", "COL_DATA_ACCESS_NAME"),
            $cond, array());
        RODSConnManager::releaseConn($connLocal);
        if ($que_result === false) return false;


        for($i=0; $i < sizeof($que_result['COL_USER_NAME']); $i++) {
            $users[] = (object) array(
                "COL_USER_NAME" => $que_result['COL_USER_NAME'][$i],
                "COL_USER_ZONE" => $que_result['COL_USER_ZONE'][$i],
                "COL_DATA_ACCESS_NAME" => $que_result['COL_DATA_ACCESS_NAME'][$i]
            );
        }
        return $users;
    }
}   
    
    
    
    
