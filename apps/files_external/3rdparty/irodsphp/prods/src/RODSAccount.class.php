<?php
/**#@+
 * Constants
 */
/**
 * Maximum lengh of password string. Only up to this lengh of password are submitted to RODS server.
 */
define("MAX_PASSWORD_LEN", 50);
/**#@-*/

require_once("autoload.inc.php");

class RODSAccount
{
 /**#@+
  * @var string 
  */
  public $user;
  public $pass;
  public $host;
  public $port;
  public $zone; 
  public $default_resc;
  public $auth_type;
  public $ticket;
  /**#@-*/
  
  public function __construct($host, $port, $user, $pass, $zone="", 
    $default_resc="", $auth_type="irods",$ticket = '')
  {
    $this->host=$host;
    $this->port=$port;
    $this->user=$user;
    $this->pass=$pass;
    $this->zone=$zone;
    $this->default_resc=$default_resc;
    $this->auth_type=$auth_type;
     $this->ticket = $ticket;
  }
  
 /**
	* Create a RODSAccount object from URI string.
	* @param string $uri 
	* @return a new RODSAccount object
	*/
  public static function fromURI($uri)
  {
    $url=parse_url($uri);
    
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
    
    return (new RODSAccount($host, $port, $user, $pass, $zone, "", $authtype,$ticket = ''));
  }
  
  

    public function equals(RODSAccount $other)
    {
        if (!isset($other))
            return false;

        if (($this->host == $other->host) &&
            ($this->port == $other->port) &&
            ($this->user == $other->user)
        ) {
            $ret_val = true;
        } else
            $ret_val = false;

        //echo ( "$this->host,$this->port,$this->user vs. $other->host,$other->port,$other->user = $ret_val");
        //flush();
        return $ret_val;
    }

    public function getSignature()
    {
        return (bin2hex(md5("$this->user.$this->zone:this->pass@$this->host:$this->port.$this->ticket", TRUE)));
    }

    public function __toString()
    {
        return "$this->user.$this->zone:(password hidden)@$this->host:$this->port";
    }

    public function toURI()
    {
        return ($this->user .
            (empty($this->zone) ? '' : '.' . $this->zone) .
            "@" . $this->host . ":" . $this->port);
    }

    /**
     * Get user information
     * @param string username, if not specified, it will use current username instead
     * @return array with fields: id, name, type, zone, dn, info, comment, ctime, mtime. If user not found return empty array.
     */
    public function getUserInfo($username = NULL,
                                $get_cb = array('RODSConnManager', 'getConn'),
                                $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        //$conn = RODSConnManager::getConn($this);
        $conn = call_user_func_array($get_cb, array(&$this));
        //TODO: Overcome fear of passing $this by reference or stop passing $this by reference
        $userinfo = $conn->getUserInfo($username);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        if ((!empty($userinfo)) && (!empty($userinfo['zone'])))
            $this->zone = $userinfo['zone'];
        return $userinfo;
    }

    /**
     * Get a temp password for current user
     * @return string of temp password
     */
    public function getTempPassword($get_cb = array('RODSConnManager', 'getConn'),
                                    $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        //$conn = RODSConnManager::getConn($this);
        $conn = call_user_func_array($get_cb, array(&$this));
        //TODO: Overcome fear of passing $this by reference or stop passing $this by reference
        $temppass = $conn->getTempPassword();
        // RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        return $temppass;
    }

    /**
     * Get user's home directory
     * @param string init_path, if specified, it will overwrite the default path
     * @return ProdsDir User's home directory
     */
    public function getUserHomeDir($init_path = NULL)
    {
        if (empty($this->zone))
            $this->getUserInfo();
        if (isset($init_path)) {
            $dir = new ProdsDir($this, $init_path);
            if ($dir->exists()) {
                return $dir;
            }
        }
        return new ProdsDir($this, "/$this->zone/home/$this->user");
    }

    /**
     * Get user's home directory URI
     * @param string init_path, if specified, it will overwrite the default path
     * @return String User's home
     */
    public function getUserHomeDirURI($init_path = NULL)
    {
        $dir = $this->getUserHomeDir($init_path);
        return $dir->toURI();
    }

    /**
     * Get user's trash directory
     * @return ProdsDir User's trash dir
     */
    public function getUserTrashDir()
    {
        if (empty($this->zone))
            $this->getUserInfo();
        return new ProdsDir($this, "/$this->zone/trash/home/$this->user");
    }

    /**
     * Get user's trash directory URI
     * @return String User's trash URI
     */
    public function getUserTrashDirURI()
    {
        $dir = $this->getUserTrashDir();
        return $dir->toURI();
    }
}
