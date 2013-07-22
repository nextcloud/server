<?php
/**
 * PRODS directory class
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */

require_once("autoload.inc.php");

class ProdsDir extends ProdsPath
{
    /**
     * @var RODSDirStats
     */
    public $stats;

    private $child_dirs;
    private $child_files;
    private $all_children;
    private $position;

    /**
     * Default Constructor.
     *
     * @param RODSAccount account iRODS account used for connection
     * @param string $path_str the path of this dir
     * @param boolean $verify whether verify if the path exsits
     * @param RODSDirStats $stats if the stats for this dir is already known, initilize it here.
     * @return a new ProdsDir
     */
    public function __construct(RODSAccount &$account, $path_str, $verify = false,
                                RODSDirStats $stats = NULL)
    {
        $this->position = 0;
        $this->stats = $stats;
        parent::__construct($account, $path_str);
        if ($verify === true) {
            if ($this->exists() === false) {
                throw new RODSException("Directory '$this' does not exist",
                    'PERR_PATH_DOES_NOT_EXISTS');
            }
        }
    }
  

 /**
	* Create a ProdsDir object from URI string.
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

    return (new ProdsDir($account,$path_str,$verify));
  }

 /**
	* Verify if this dir exist with server. This function shouldn't be called directly, use {@link exists}
	*/
  //protected function verify()
  protected function verify($get_cb=array('RODSConnManager','getConn'),
                            $rel_cb=array('RODSConnManager', 'releaseConn'))
  {
    //$conn = RODSConnManager::getConn($this->account);
    $conn = call_user_func_array($get_cb, array(&$this->account));
    $this->path_exists= $conn -> dirExists ($this->path_str);
    //RODSConnManager::releaseConn($conn);
    call_user_func($rel_cb, $conn);
  }

 /**
  * get next file or directory from the directory, where the internal iterator points to.
	* @return next file or directory from the directory. The file always come first and dir comes later. return false on failure
	*/
  public function getNextChild()
  {
    if (!$this->all_children)
      $this->all_children=$this->getAllChildren();
    if (($this->position>=count($this->all_children))||($this->position<0))
      return false;
    $names=array_keys($this->all_children);
    $ret_val=$this->all_children[$names[$this->position]];
    $this->position++;
    return $ret_val;
  }


 /**
	* Get children files of this dir.
	*
	* @param array $orderby An associated array specifying how to sort the result by attributes. See details in method {@link findFiles};
	* @param int $startingInx starting index of all files. default is 0.
	* @param int $maxresults max results returned. if negative, it returns all rows. default is -1
	* @param int &$total_num_rows number of all results
	* @param boolean $logical_file whether to return only logical files, if false, it returns all replica with resource name, if true, it returns only 1 logical file, with num_replica available in the stats. default is false.
	* @return an array of ProdsFile
	*/
  public function getChildFiles(array $orderby=array(), $startingInx=0,
    $maxresults=-1, &$total_num_rows=-1, $logicalFile=false)
  {
    $terms=array("descendantOnly"=>true,"recursive"=>false, 'logicalFile'=>$logicalFile);
    return $this->findFiles($terms,$total_num_rows,$startingInx,$maxresults,$orderby);
  }

    /**
     * Resets the directory stream to the beginning of the directory.
     */
    public function rewind()
    {
        $this->position = 0;
    }


    /**
     * @return all children (files and dirs) of current dir
     */
    public function getAllChildren()
    {
        $this->all_children = array();
        $this->all_children = array_merge($this->all_children,
            $this->getChildFiles());
        $this->all_children = array_merge($this->all_children,
            $this->getChildDirs());

        return $this->all_children;
    }

    /**
     * Get children directories of this dir.
     * @param $orderby An associated array specifying how to sort the result by attributes. See details in method {@link findDirs};
     * Note that if the current dir is root '/', it will not return '/' as its child, unlike iCommand's current behavior.
     * @return an array of ProdsDir
     */
    public function getChildDirs(array $orderby = array(), $startingInx = 0,
                                 $maxresults = -1, &$total_num_rows = -1)
    {
        $terms = array("descendantOnly" => true, "recursive" => false);
        return $this->findDirs($terms, $total_num_rows, $startingInx, $maxresults, $orderby);
    }
    
    /**
     * Make a new directory under this directory
     * @param string $name full path of the new dir to be made on server
     * @return ProdsDir the new directory just created (or already exists)
     */
    // public function mkdir($name)
    public function mkdir($name,
                          $get_cb = array('RODSConnManager', 'getConn'),
                          $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->mkdir($this->path_str . "/$name");
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        return (new ProdsDir($this->account, $this->path_str . "/$name"));
    }

    /**
     * remove this directory
     * @param boolean $recursive whether recursively delete all child files and child directories recursively.
     * @param boolean $force whether force delete the file/dir. If force delete, all files will be wiped physically. Else, they are moved to trash derectory.
     * @param array   $additional_flags An array of keyval pairs (array) reprenting additional flags passed to the server/client message. Each keyval pair is an array with first element repsenting the key, and second element representing the value (default to ''). Supported keys are:
     * -    'irodsRmTrash' - whether this rm is a rmtrash operation
     * -    'irodsAdminRmTrash' - whether this rm is a rmtrash operation done by admin user
     * @param mixed   $status_update_func It can be an string or array that represents the status update function (see http://us.php.net/manual/en/language.pseudo-types.php#language.types.callback), which can update status based on the server status update. Leave it blank or 'null' if there is no need to update the status. The function will be called with an assossive arry as parameter, supported fields are:
     * - 'filesCnt' - finished number of files from previous update (normally 10 but not the last update)
     * - 'lastObjPath' - last object that was processed.
     * If this function returns 1, progress will be stopped.
     */
    // public function rmdir($recursive=true,$force=false, $additional_flags=array(),
    //  $status_update_func=null)
    public function rmdir($recursive = true, $force = false, $additional_flags = array(),
                          $status_update_func = null,
                          $get_cb = array('RODSConnManager', 'getConn'),
                          $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->rmdir($this->path_str, $recursive, $force, $additional_flags,
            $status_update_func);
        // RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
    }

    /**
     * get the dir stats
     * @param boolean $force_reload If stats already present in the object, and this flag is true, a force reload will be done.
     * @return RODSDirStats the stats object, note that if this object will not refresh unless $force_reload flag is used.
     */
    // public function getStats($force_reload=false)
    public function getStats($force_reload = false,
                             $get_cb = array('RODSConnManager', 'getConn'),
                             $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if (($force_reload === false) && ($this->stats))
            return $this->stats;

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $stats = $conn->getDirStats($this->path_str);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);

        if ($stats === false) $this->stats = NULL;
        else $this->stats = $stats;
        return $this->stats;
    }
    
    public function getACL()
    {

        $collection = $this->path_str;

        $connLocal = RODSConnManager::getConn($this->account);
        $que_result_coll = $connLocal->genQuery(
            array("COL_COLL_INHERITANCE", "COL_COLL_NAME", "COL_COLL_OWNER_NAME", "COL_COLL_ID"),
            array(new RODSQueryCondition("COL_COLL_NAME", $collection)));
        
        $users['COL_COLL_INHERITANCE'] = (int)($que_result_coll['COL_COLL_INHERITANCE'][0]);

        $que_result_users = $connLocal->genQuery(
            array("COL_DATA_ACCESS_NAME", "COL_DATA_ACCESS_USER_ID"),
            array(new RODSQueryCondition("COL_DATA_ACCESS_DATA_ID", $que_result_coll['COL_COLL_ID'][0])));
        
        for($i=0; $i<sizeof($que_result_users["COL_DATA_ACCESS_USER_ID"]);$i++) {
            $que_result_user_info = $connLocal->genQuery(
                array("COL_USER_NAME", "COL_USER_ZONE"),
                array(new RODSQueryCondition("COL_USER_ID", $que_result_users["COL_DATA_ACCESS_USER_ID"][$i])));
            
            $users['COL_USERS'][] = (object) array(
                "COL_USER_NAME" => $que_result_user_info['COL_USER_NAME'][0],
                "COL_USER_ZONE" => $que_result_user_info['COL_USER_ZONE'][0],
                "COL_DATA_ACCESS_NAME" => $que_result_users['COL_DATA_ACCESS_NAME'][$i]
            );
        }
        
        RODSConnManager::releaseConn($connLocal);
        return $users;
        

    }

    /**
     * get the dir statistics, such as total number of files under this dir
     * @param string $fld Name of the statistics, supported values are:
     * - num_dirs number of directories
     * - num_files number of files
     * @param boolean $recursive wheather recursively through the sub collections, default is true.
     * @return result, an integer value, assosiated with the query.
     */
    //public function queryStatistics($fld, $recursive=true)
    public function queryStatistics($fld, $recursive = true,
                                    $get_cb = array('RODSConnManager', 'getConn'),
                                    $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        $condition = new RODSGenQueConds();
        $select = new RODSGenQueSelFlds();
        $ret_data_index = '';
        switch ($fld) {
            case 'num_dirs' :
                $select->add('COL_COLL_ID', 'count');
                $ret_data_index = 'COL_COLL_ID';
                if ($recursive === true)
                    $condition->add('COL_COLL_NAME', 'like', $this->path_str . '/%');
                else
                    $condition->add('COL_COLL_PARENT_NAME', '=', $this->path_str);
                break;
            case 'num_files' :
                $select->add('COL_D_DATA_ID', 'count');
                $ret_data_index = 'COL_D_DATA_ID';
                if ($recursive === true)
                    $condition->add('COL_COLL_NAME', 'like', $this->path_str . '/%',
                        array(array('op' => '=', 'val' => $this->path_str)));
                else
                    $condition->add('COL_COLL_NAME', '=', $this->path_str);
                break;
            default :
                throw new RODSException("Query field '$fld' not supported!",
                    'PERR_USER_INPUT_ERROR');
        }

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $results = $conn->query($select, $condition);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        $result_values = $results->getValues();

        if (isset($result_values[$ret_data_index][0]))
            return intval($result_values[$ret_data_index][0]);
        else {
            throw new RODSException("Query did not get value back with expected " .
                "index: $ret_data_index!", 'GENERAL_PRODS_ERR');
        }
    }

    /**
     * query metadata, and find matching files.
     * @param array $terms an assositive array of search conditions, supported ones are:
     * -    'name' (string) - partial name of the target (file or dir)
     * -    'descendantOnly' (boolean) - whether to search among this directory's decendents. default is false.
     * -    'recursive'      (boolean) - whether to search recursively, among all decendents and their children. default is false. This option only works when 'descendantOnly' is true
     * -    'logicalFile'    (boolean) - whether to return logical file, instead of all replicas for each file. if true, the resource name for each file will be null, instead, num_replicas will be provided. default is false.
     * -    'smtime'         (int)     - start last-modified-time in unix timestamp. The specified time is included in query, in other words the search can be thought was "mtime >= specified time"
     * -    'emtime'         (int)     - end last-modified-time in unix timestamp. The specified time is not included in query, in other words the search can be thought was "mtime < specified time"
     * -    'owner'          (string)  - owner name of the file
     * -    'rescname'       (string)  - resource name of the file
     * -    'metadata' (array of RODSMeta) - array of metadata.
     * @param int &$total_count This value (passed by reference) returns the total potential count of search results
     * @param int $start starting index of search results.
     * @param int $limit up to how many results to be returned. If negative, give all results back.
     * @param array $sort_flds associative array with following keys:
     * -     'name'      - name of the file or dir
     * -     'size'      - size of the file
     * -     'mtime'     - last modified time
     * -     'ctime'     - creation time
     * -     'owner'     - owner of the file
     * -     'typename'  - file/data type
     * -     'dirname'   - directory/collection name for the file
     * The results are sorted by specified array keys.
     * The possible array value must be boolean: true stands for 'asc' and false stands for 'desc', default is 'asc'
     * @return array of ProdsPath objects (ProdsFile or ProdsDir).
     */
    //public function findFiles(array $terms, &$total_count, $start=0, $limit=-1, array $sort_flds=array())
    public function findFiles(array $terms, &$total_count, $start = 0, $limit = -1,
                              array $sort_flds = array(),
                              $get_cb = array('RODSConnManager', 'getConn'),
                              $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        $flds = array("COL_DATA_NAME" => NULL, "COL_D_DATA_ID" => NULL,
            "COL_DATA_TYPE_NAME" => NULL, "COL_D_RESC_NAME" => NULL,
            "COL_DATA_SIZE" => NULL, "COL_D_OWNER_NAME" => NULL, "COL_D_OWNER_ZONE" => NULL,
            "COL_D_CREATE_TIME" => NULL, "COL_D_MODIFY_TIME" => NULL,
            "COL_COLL_NAME" => NULL, "COL_D_COMMENTS" => NULL);

        foreach ($sort_flds as $sort_fld_key => $sort_fld_val) {
            switch ($sort_fld_key) {
                case 'name':
                    if ($sort_fld_val === false)
                        $flds['COL_DATA_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_DATA_NAME'] = 'order_by_asc';
                    break;

                case 'size':
                    if ($sort_fld_val === false)
                        $flds['COL_DATA_SIZE'] = 'order_by_desc';
                    else
                        $flds['COL_DATA_SIZE'] = 'order_by_asc';
                    break;

                case 'mtime':
                    if ($sort_fld_val === false)
                        $flds['COL_D_MODIFY_TIME'] = 'order_by_desc';
                    else
                        $flds['COL_D_MODIFY_TIME'] = 'order_by_asc';
                    break;

                case 'ctime':
                    if ($sort_fld_val === false)
                        $flds['COL_D_CREATE_TIME'] = 'order_by_desc';
                    else
                        $flds['COL_D_CREATE_TIME'] = 'order_by_asc';
                    break;

                case 'typename':
                    if ($sort_fld_val === false)
                        $flds['COL_DATA_TYPE_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_DATA_TYPE_NAME'] = 'order_by_asc';
                    break;

                case 'owner':
                    if ($sort_fld_val === false)
                        $flds['COL_D_OWNER_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_D_OWNER_NAME'] = 'order_by_asc';
                    break;

                case 'dirname':
                    if ($sort_fld_val === false)
                        $flds['COL_COLL_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_COLL_NAME'] = 'order_by_asc';
                    break;

                default:
                    /*
                    throw new RODSException("Sort field name '$sort_fld_key' is not valid",
                      'PERR_USER_INPUT_ERROR');
                    break;
                    */
            }
        }
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));

        $descendantOnly = false;
        $recursive = false;
        $logicalFile = false;
        $condition = new RODSGenQueConds();
        foreach ($terms as $term_key => $term_val) {
            switch ($term_key) {
                case 'name':
                    //$condition->add('COL_DATA_NAME', 'like', '%'.$term_val.'%');
                    $condition->add('COL_DATA_NAME', 'like', $term_val);
                    break;
                case 'smtime':
                    $condition->add('COL_D_MODIFY_TIME', '>=', $term_val);
                    break;
                case 'emtime':
                    $condition->add('COL_D_MODIFY_TIME', '<', $term_val);
                    break;
                case 'owner':
                    $condition->add('COL_D_OWNER_NAME', '=', $term_val);
                    break;
                case 'ownerzone':
                    $condition->add('COL_D_OWNER_ZONE', '=', $term_val);
                    break;
                case 'rescname':
                    $condition->add('COL_D_RESC_NAME', '=', $term_val);
                    break;
                case 'metadata':
                    $meta_array = $term_val;
                    foreach ($meta_array as $meta) {
                        if (isset($meta->name)) {
                            if ($meta->nameop === 'like') {
                                $condition->add('COL_META_DATA_ATTR_NAME', 'like', '%' . $meta->name . '%');
                            } else if (isset($meta->nameop)) {
                                $condition->add('COL_META_DATA_ATTR_NAME', $meta->nameop, $meta->name);
                            } else {
                                $condition->add('COL_META_DATA_ATTR_NAME', '=', $meta->name);
                            }
                        }
                        if (isset($meta->value)) {
                            if ($meta->op === 'like') {
                                $condition->add('COL_META_DATA_ATTR_VALUE', 'like', '%' . $meta->value . '%');
                            } else if (isset($meta->op)) {
                                $condition->add('COL_META_DATA_ATTR_VALUE', $meta->op, $meta->value);
                            } else {
                                $condition->add('COL_META_DATA_ATTR_VALUE', '=', $meta->value);
                            }
                        }
                        if (isset($meta->unit)) {
                            if ($meta->unitop === 'like') {
                                $condition->add('COL_META_DATA_ATTR_UNIT', 'like', '%' . $meta->unit . '%');
                            } else if (isset($meta->unitop)) {
                                $condition->add('COL_META_DATA_ATTR_UNIT', $meta->unitop, $meta->unit);
                            } else {
                                $condition->add('COL_META_DATA_ATTR_UNIT', '=', $meta->unit);
                            }
                        }
                    }
                    break;

                case 'descendantOnly':
                    if (true === $term_val)
                        $descendantOnly = true;
                    break;

                case 'recursive':
                    if (true === $term_val)
                        $recursive = true;
                    break;

                case 'logicalFile':
                    if (true === $term_val)
                        $logicalFile = true;
                    break;

                default:
                    throw new RODSException("Term field name '$term_key' is not valid",
                        'PERR_USER_INPUT_ERROR');
                    break;
            }
        }

        if ($descendantOnly === true) {
            if ($recursive === true)
                $condition->add('COL_COLL_NAME', 'like', $this->path_str . '/%',
                    array(array('op' => '=', 'val' => $this->path_str)));
            else
                $condition->add('COL_COLL_NAME', '=', $this->path_str);
        }

        if ($logicalFile === true) {
            $select->update('COL_D_RESC_NAME', 'count');
            $select->update('COL_DATA_SIZE', 'max');
            $select->update('COL_D_CREATE_TIME', 'min');
            $select->update('COL_D_MODIFY_TIME', 'max');
        }

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $results = $conn->query($select, $condition, $start, $limit);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);

        $total_count = $results->getTotalCount();
        $result_values = $results->getValues();
        $found = array();
        for ($i = 0; $i < $results->getNumRow(); $i++) {
            $resc_name = ($logicalFile === true) ? NULL : $result_values['COL_D_RESC_NAME'][$i];
            $num_replica = ($logicalFile === true) ? intval($result_values['COL_D_RESC_NAME'][$i]) : NULL;
            $stats = new RODSFileStats(
                $result_values['COL_DATA_NAME'][$i],
                $result_values['COL_DATA_SIZE'][$i],
                $result_values['COL_D_OWNER_NAME'][$i],
                $result_values['COL_D_OWNER_ZONE'][$i],
                $result_values['COL_D_MODIFY_TIME'][$i],
                $result_values['COL_D_CREATE_TIME'][$i],
                $result_values['COL_D_DATA_ID'][$i],
                $result_values['COL_DATA_TYPE_NAME'][$i],
                $resc_name,
                $result_values['COL_D_COMMENTS'][$i],
                $num_replica
            );

            if ($result_values['COL_COLL_NAME'][$i] == '/')
                $full_path = '/' . $result_values['COL_DATA_NAME'][$i];
            else
                $full_path = $result_values['COL_COLL_NAME'][$i] . '/' .
                    $result_values['COL_DATA_NAME'][$i];
            $found[] = new ProdsFile($this->account, $full_path, false, $stats);
        }
        return $found;
    }

    /**
     * query metadata, and find matching diretories.
     * @param array $terms an assositive array of search conditions, supported ones are:
     * -    'name' (string) - partial name of the target (file or dir)
     * -    'descendantOnly' (boolean) - whether to search among this directory's decendents. default is false.
     * -    'recursive'      (boolean) - whether to search recursively, among all decendents and their children. default is false. This option only works when 'descendantOnly' is true
     * -    'smtime'         (int)     - start last-modified-time in unix timestamp. The specified time is included in query, in other words the search can be thought was "mtime >= specified time"
     * -    'emtime'         (int)     - end last-modified-time in unix timestamp. The specified time is not included in query, in other words the search can be thought was "mtime < specified time"
     * -    'owner'          (string)  - owner name of the dir
     * -    'metadata' (array of RODSMeta) - array of metadata.
     * @param int &$total_count This value (passed by reference) returns the total potential count of search results
     * @param int $start starting index of search results.
     * @param int $limit up to how many results to be returned. If negative, give all results back.
     * @param array $sort_flds associative array with following keys:
     * -     'name'      - name of the dir
     * -     'mtime'     - last modified time
     * -     'ctime'     - creation time
     * -     'owner'     - owner of the dir
     * The results are sorted by specified array keys.
     * The possible array value must be boolean: true stands for 'asc' and false stands for 'desc', default is 'asc'
     * @return array of ProdsPath objects (ProdsFile or ProdsDir).
     */
    public function findDirs(array $terms, &$total_count, $start = 0, $limit = -1,
                             array $sort_flds = array())
    {
        $flds = array("COL_COLL_NAME" => NULL, "COL_COLL_ID" => NULL,
            "COL_COLL_OWNER_NAME" => NULL, 'COL_COLL_OWNER_ZONE' => NULL,
            "COL_COLL_CREATE_TIME" => NULL, "COL_COLL_MODIFY_TIME" => NULL,
            "COL_COLL_COMMENTS" => NULL);

        foreach ($sort_flds as $sort_fld_key => $sort_fld_val) {
            switch ($sort_fld_key) {
                case 'name':
                    if ($sort_fld_val === false)
                        $flds['COL_COLL_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_COLL_NAME'] = 'order_by_asc';
                    break;

                case 'mtime':
                    if ($sort_fld_val === false)
                        $flds['COL_COLL_MODIFY_TIME'] = 'order_by_desc';
                    else
                        $flds['COL_COLL_MODIFY_TIME'] = 'order_by_asc';
                    break;

                case 'ctime':
                    if ($sort_fld_val === false)
                        $flds['COL_COLL_CREATE_TIME'] = 'order_by_desc';
                    else
                        $flds['COL_COLL_CREATE_TIME'] = 'order_by_asc';
                    break;

                case 'owner':
                    if ($sort_fld_val === false)
                        $flds['COL_COLL_OWNER_NAME'] = 'order_by_desc';
                    else
                        $flds['COL_COLL_OWNER_NAME'] = 'order_by_asc';
                    break;

                default:
                    /*
                    throw new RODSException("Sort field name '$sort_fld_key' is not valid",
                      'PERR_USER_INPUT_ERROR');
                    */
                    break;
            }
        }
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));

        $descendantOnly = false;
        $recursive = false;
        $condition = new RODSGenQueConds();
        foreach ($terms as $term_key => $term_val) {
            switch ($term_key) {
                case 'name':
                    //$condition->add('COL_COLL_NAME', 'like', '%'.$term_val.'%');
                    $condition->add('COL_COLL_NAME', 'like', $term_val);
                    break;
                case 'smtime':
                    $condition->add('COL_COLL_MODIFY_TIME', '>=', $term_val);
                    break;
                case 'emtime':
                    $condition->add('COL_COLL_MODIFY_TIME', '<', $term_val);
                    break;
                case 'owner':
                    $condition->add('COL_COLL_OWNER_NAME', '=', $term_val);
                    break;
                case 'metadata':
                    $meta_array = $term_val;
                    foreach ($meta_array as $meta) {
                        $condition->add('COL_META_COLL_ATTR_NAME', '=', $meta->name);
                        if (isset($meta->op))
                            $op = $meta->op;
                        else
                            $op = '=';
                        if ($op == 'like')
                            //$value='%'.$meta->value.'%';
                            $value = $meta->value;
                        else
                            $value = $meta->value;
                        $condition->add('COL_META_COLL_ATTR_VALUE', $op, $value);
                    }
                    break;

                case 'descendantOnly':
                    if (true === $term_val)
                        $descendantOnly = true;
                    break;

                case 'recursive':
                    if (true === $term_val)
                        $recursive = true;
                    break;

                default:
                    throw new RODSException("Term field name '$term_key' is not valid",
                        'PERR_USER_INPUT_ERROR');
                    break;
            }
        }

        if ($descendantOnly === true) {
            // eliminate '/' from children, if current path is already root
            if ($this->path_str == '/')
                $condition->add('COL_COLL_NAME', '<>', '/');

            if ($recursive === true)
                $condition->add('COL_COLL_PARENT_NAME', 'like', $this->path_str . '/%',
                    array(array('op' => '=', 'val' => $this->path_str)));
            else
                $condition->add('COL_COLL_PARENT_NAME', '=', $this->path_str);
        }

        $conn = RODSConnManager::getConn($this->account);
        $results = $conn->query($select, $condition, $start, $limit);
        RODSConnManager::releaseConn($conn);

        $total_count = $results->getTotalCount();
        $result_values = $results->getValues();
        $found = array();
        for ($i = 0; $i < $results->getNumRow(); $i++) {
            $full_path = $result_values['COL_COLL_NAME'][$i];
            $acctual_name = basename($result_values['COL_COLL_NAME'][$i]);
            $stats = new RODSDirStats(
                $acctual_name,
                $result_values['COL_COLL_OWNER_NAME'][$i],
                $result_values['COL_COLL_OWNER_ZONE'][$i],
                $result_values['COL_COLL_MODIFY_TIME'][$i],
                $result_values['COL_COLL_CREATE_TIME'][$i],
                $result_values['COL_COLL_ID'][$i],
                $result_values['COL_COLL_COMMENTS'][$i]);

            $found[] = new ProdsDir($this->account, $full_path, false, $stats);
        }
        return $found;
    }
}
