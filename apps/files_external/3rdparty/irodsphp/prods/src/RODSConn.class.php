<?php
/**
 * RODS connection class
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package RODSConn
 */


require_once("autoload.inc.php");
require_once("RodsAPINum.inc.php");
require_once("RodsConst.inc.php");

if (!defined("O_RDONLY")) define ("O_RDONLY", 0);
if (!defined("O_WRONLY")) define ("O_WRONLY", 1);
if (!defined("O_RDWR")) define ("O_RDWR", 2);
if (!defined("O_TRUNC")) define ("O_TRUNC", 512);

class RODSConn
{
  private $conn;     // (resource) socket connection to RODS server
  
  private $account;  // RODS user account  
  
  private $idle;
  private $id;
  
  public  $connected;
  
  /**
   * Makes a new connection to RODS server, with supplied user information (name, passwd etc.) 
   * @param string $host hostname 
   * @param string $port port number 
   * @param string $user username 
   * @param string $pass passwd 
   * @param string $zone zonename 
   */
  public function __construct(RODSAccount &$account)
  {
    $this->account=$account;
    $this->connected=false;
    $this->conn=NULL;
    $this->idle=true;
  }
  
  public function __destruct()
  {
    if ($this->connected===true)
      $this->disconnect();
  }
  
  public function equals(RODSConn $other)
  {
    return $this->account->equals($other->account);
  }
  
  public function getSignature()
  {
    return $this->account->getSignature();
  }
  
  public function lock()
  {
    $this->idle=false;
  }
  
  public function unlock()
  {
    $this->idle=true;
  }
  
  public function isIdle()
  {
    return ($this->idle);
  }
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($id)
  {
    $this->id=$id;
  }
  
  public function getAccount()
  {
    return $this->account;
  }
  
  public function connect()
  {
    $host=$this->account->host;
    $port=$this->account->port;
    $user=$this->account->user;
    $pass=$this->account->pass;
    $zone=$this->account->zone;
    $auth_type = $this->account->auth_type;

    // if we're going to use PAM, set up the socket context 
    // options for SSL connections when we open the connection
    if (strcasecmp($auth_type, "PAM") == 0) {
      $ssl_opts = array('ssl' => array());
      if (array_key_exists('ssl', $GLOBALS['PRODS_CONFIG'])) {
        $ssl_conf = $GLOBALS['PRODS_CONFIG']['ssl'];
        if (array_key_exists('verify_peer', $ssl_conf)) {
          if (strcasecmp("true", $ssl_conf['verify_peer']) == 0) {
            $ssl_opts['ssl']['verify_peer'] = true;
          }
        }
        if (array_key_exists('allow_self_signed', $ssl_conf)) {
          if (strcasecmp("true", $ssl_conf['allow_self_signed']) == 0) {
            $ssl_opts['ssl']['allow_self_signed'] = true;
          }
        }
        if (array_key_exists('cafile', $ssl_conf)) {
          $ssl_opts['ssl']['cafile'] = $ssl_conf['cafile'];
        }
        if (array_key_exists('capath', $ssl_conf)) {
          $ssl_opts['ssl']['capath'] = $ssl_conf['capath'];
        }
      }
      $ssl_ctx = stream_context_get_default($ssl_opts);
      $sock_timeout = ini_get("default_socket_timeout");
      $conn = @stream_socket_client("tcp://$host:$port", $errno, $errstr,
        $sock_timeout, STREAM_CLIENT_CONNECT, $ssl_ctx);
    }
    else {
      $conn = @fsockopen($host, $port, $errno, $errstr);
    }
    if (!$conn)
      throw new RODSException("Connection to '$host:$port' failed.1: ($errno)$errstr. ",
        "SYS_SOCK_OPEN_ERR");
    $this->conn=$conn;
    
    // connect to RODS server
    $msg=RODSMessage::packConnectMsg($user,$zone);
    fwrite($conn, $msg);
    
    $msg=new RODSMessage();
    $intInfo=$msg->unpack($conn);
    if ($intInfo<0)
    {
      throw new RODSException("Connection to '$host:$port' failed.2. User: $user Zone: $zone",
        $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
    }
    
    // are we doing PAM authentication
    if (strcasecmp($auth_type, "PAM") == 0) 
    {
      // Ask server to turn on SSL
      $req_packet = new RP_sslStartInp();
      $msg=new RODSMessage("RODS_API_REQ_T", $req_packet, 
        $GLOBALS['PRODS_API_NUMS']['SSL_START_AN']);
      fwrite($conn, $msg->pack());
      $msg=new RODSMessage();
      $intInfo=$msg->unpack($conn);
      if ($intInfo<0) 
      {
        throw new RODSException("Connection to '$host:$port' failed.ssl1. User: $user Zone: $zone",
          $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
      }
      // Turn on SSL on our side
      if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new RODSException("Error turning on SSL on connection to server '$host:$port'.");
      }

      // all good ... do the PAM authentication over the encrypted connection
      $req_packet = new RP_pamAuthRequestInp($user, $pass, -1);
      $msg=new RODSMessage("RODS_API_REQ_T", $req_packet,
        $GLOBALS['PRODS_API_NUMS']['PAM_AUTH_REQUEST_AN']);
      fwrite($conn, $msg->pack());
      $msg=new RODSMessage();
      $intInfo=$msg->unpack($conn);
      if ($intInfo<0)
      {
        throw new RODSException("PAM auth failed at server '$host:$port' User: $user Zone: $zone",
          $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
      }
      
      // Update the account object with the temporary password
      // and set the auth_type to irods for this connection
      $pack = $msg->getBody();
      $pass = $this->account->pass = $pack->irodsPamPassword;

      // Done authentication ... turn ask the server to turn off SSL
      $req_packet = new RP_sslEndInp();
      $msg=new RODSMessage("RODS_API_REQ_T", $req_packet, 
        $GLOBALS['PRODS_API_NUMS']['SSL_END_AN']);
      fwrite($conn, $msg->pack());
      $msg=new RODSMessage();
      $intInfo=$msg->unpack($conn);
      if ($intInfo<0) 
      {
        throw new RODSException("Connection to '$host:$port' failed.ssl2. User: $user Zone: $zone",
          $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
      }
      // De-activate SSL on the connection
      stream_socket_enable_crypto($conn, false);

      // nasty hack ... some characters are left over to be read
      // from the socket after the SSL shutdown, and I can't 
      // figure out how to consume them via SSL routines, so I
      // just read them and throw them away. They need to be consumed
      // or later reads get out of sync with the API responses
      $r = array($conn);
      $w = $e = null;
      while (stream_select($r, $w, $e, 0) > 0) {
        $s = fread($conn, 1);
      }

    }
      
    // request authentication
    $msg=new RODSMessage("RODS_API_REQ_T",NULL,
      $GLOBALS['PRODS_API_NUMS']['AUTH_REQUEST_AN']);
    fwrite($conn, $msg->pack());    
    
    // get chalange string
    $msg=new RODSMessage();
    $intInfo=$msg->unpack($conn);
    if ($intInfo<0)
    {
      throw new RODSException("Connection to '$host:$port' failed.3. User: $user Zone: $zone",
        $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
    }
    $pack=$msg->getBody();
    $challenge_b64encoded=$pack->challenge;
    $challenge=base64_decode($challenge_b64encoded);
    
    // encode chalange with passwd
    $pad_pass=str_pad($pass,MAX_PASSWORD_LEN,"\0");
    $pwmd5=md5($challenge.$pad_pass,true);
    for ($i=0;$i<strlen($pwmd5);$i++) //"escape" the string in RODS way...
    {
      if (ord($pwmd5[$i])==0)
      {
        $pwmd5[$i]=chr(1);
      }
    }
    $response=base64_encode($pwmd5);
    
    // set response
    $resp_packet=new RP_authResponseInp($response,$user);
    $msg=new RODSMessage("RODS_API_REQ_T",$resp_packet,
      $GLOBALS['PRODS_API_NUMS']['AUTH_RESPONSE_AN']);
    fwrite($conn, $msg->pack());
    
    // check if we are connected
    // get chalange string
    $msg=new RODSMessage();
    $intInfo=$msg->unpack($conn);
    if ($intInfo<0)
    {
      $this->disconnect();
      throw new RODSException("Connection to '$host:$port' failed.4 (login failed, possible wrong user/passwd). User: $user Pass: $pass Zone: $zone",
        $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
    }
    
    $this->connected=true;
    // use ticket if specified
        if( !empty($this->account->ticket) ) {
            $ticket_packet = new RP_ticketAdminInp('session', $this->account->ticket);
            $msg = new RODSMessage('RODS_API_REQ_T', $ticket_packet, 723);
            fwrite($conn, $msg->pack());

            // get response
            $msg = new RODSMessage();
            $intInfo = $msg->unpack($conn);
            if ($intInfo < 0) {
                $this->disconnect();
                throw new RODSException('Cannot set session ticket.',
                    $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
            }
        }

  }
    /**
     * Close the connection (socket)
     */
    public function disconnect($force = false)
    {
        if (($this->connected === false) && ($force !== true))
            return;

        $msg = new RODSMessage("RODS_DISCONNECT_T");
        fwrite($this->conn, $msg->pack());
        fclose($this->conn);
        $this->connected = false;
    }

    public function createTicket( $object, $permission = 'read', $ticket = '' )
    {
        if ($this->connected === false) {
            throw new RODSException("createTicket needs an active connection, but the connection is currently inactive",
                'PERR_CONN_NOT_ACTIVE');
        }
        if( empty($ticket) )
        {
            // create a 16 characters long ticket
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for ($i = 0; $i < 16; $i++)
                $ticket .= $chars[mt_rand(1, strlen($chars))-1];
        }

        $ticket_packet = new RP_ticketAdminInp('create', $ticket, $permission, $object);
        $msg = new RODSMessage('RODS_API_REQ_T', $ticket_packet, 723);
        fwrite($this->conn, $msg->pack());

        // get response
        $msg = new RODSMessage();
        $intInfo = $msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException('Cannot create ticket "'.$ticket.'" for object "'.$object.'" with permission "'.$permission.'".',
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }

        return $ticket;
    }

    public function deleteTicket( $ticket )
    {
        if ($this->connected === false) {
            throw new RODSException("deleteTicket needs an active connection, but the connection is currently inactive",
                'PERR_CONN_NOT_ACTIVE');
        }
        $ticket_packet = new RP_ticketAdminInp('delete', $ticket);
        $msg = new RODSMessage('RODS_API_REQ_T', $ticket_packet, 723);
        fwrite($this->conn, $msg->pack());

        // get response
        $msg = new RODSMessage();
        $intInfo = $msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException('Cannot delete ticket "'.$ticket.'".',
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * Get a temp password from the server.
     * @param string $key key obtained from server to generate password. If this key is not specified, this function will ask server for a new key.
     * @return string temp password
     */
    public function getTempPassword($key = NULL)
    {
        if ($this->connected === false) {
            throw new RODSException("getTempPassword needs an active connection, but the connection is currently inactive",
                'PERR_CONN_NOT_ACTIVE');
        }
        if (NULL == $key)
            $key = $this->getKeyForTempPassword();

        $auth_str = str_pad($key . $this->account->pass, 100, "\0");
        $pwmd5 = bin2hex(md5($auth_str, true));

        return $pwmd5;
    }


    /**
     * Get a key for temp password from the server. this key can then be hashed together with real password to generate an temp password.
     * @return string key for temp password
     */
    public function getKeyForTempPassword()
    {
        if ($this->connected === false) {
            throw new RODSException("getKeyForTempPassword needs an active connection, but the connection is currently inactive",
                'PERR_CONN_NOT_ACTIVE');
        }
        $msg = new RODSMessage("RODS_API_REQ_T", null,
            $GLOBALS['PRODS_API_NUMS']['GET_TEMP_PASSWORD_AN']);

        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::getKeyForTempPassword has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        return ($msg->getBody()->stringToHashWith);
    }

    /**
     * Get user information
     * @param string username, if not specified, it will use current username instead
     * @return array with fields: id, name, type, zone, dn, info, comment, ctime, mtime. If user not found return empty array.
     */
    public function getUserInfo($user = NULL)
    {
        if (!isset($user))
            $user = $this->account->user;

        // set selected value
        $select_val = array("COL_USER_ID", "COL_USER_NAME", "COL_USER_TYPE",
            "COL_USER_ZONE", "COL_USER_DN", "COL_USER_INFO",
            "COL_USER_COMMENT", "COL_USER_CREATE_TIME", "COL_USER_MODIFY_TIME");
        $cond = array(new RODSQueryCondition("COL_USER_NAME", $user));
        $que_result = $this->genQuery($select_val, $cond);

        if (false === $que_result) {
            return array();
        } else {
            $retval = array();
            $retval['id'] = $que_result["COL_USER_ID"][0];
            $retval['name'] = $que_result["COL_USER_NAME"][0];
            $retval['type'] = $que_result["COL_USER_TYPE"][0];
            // $retval['zone']=$que_result["COL_USER_ZONE"][0]; This can cause confusion if
            // username is same as another federated grid - sometimes multiple records are returned.
            // Changed source to force user to provide a zone until another method is suggested.
            if ($this->account->zone == "") {
                $retval['zone'] = $que_result["COL_USER_ZONE"][0];
            } else {
                $retval['zone'] = $this->account->zone;
            }
            $retval['dn'] = $que_result["COL_USER_DN"][0];
            $retval['info'] = $que_result["COL_USER_INFO"][0];
            $retval['comment'] = $que_result["COL_USER_COMMENT"][0];
            $retval['ctime'] = $que_result["COL_USER_CREATE_TIME"][0];
            $retval['mtime'] = $que_result["COL_USER_MODIFY_TIME"][0];

            return $retval;
        }
    }

    /**
     * Make a new directory
     * @param string $dir input direcotory path string
     */
    public function mkdir($dir)
    {
        $collInp_pk = new RP_CollInp($dir);
        $msg = new RODSMessage("RODS_API_REQ_T", $collInp_pk,
            $GLOBALS['PRODS_API_NUMS']['COLL_CREATE_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CATALOG_ALREADY_HAS_ITEM_BY_THAT_NAME') {
                throw new RODSException("Collection '$dir' Already exists!",
                    $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
            }
            throw new RODSException("RODSConn::mkdir has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * remove a directory
     * @param string  $dirpath input direcotory path string
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
    public function rmdir($dirpath, $recursive = true, $force = false,
                          $additional_flags = array(), $status_update_func = null)
    {
        $options = array();
        if ($force === true) {
            $options["forceFlag"] = "";
        }
        if ($recursive === true) {
            $options["recursiveOpr"] = "";
        }
        foreach ($additional_flags as $flagkey => $flagval) {
            if (!empty($flagkey))
                $options[$flagkey] = $flagval;
        }
        $options_pk = new RP_KeyValPair();
        $options_pk->fromAssocArray($options);

        $collInp_pk = new RP_CollInp($dirpath, $options_pk);
        $msg = new RODSMessage("RODS_API_REQ_T", $collInp_pk,
            $GLOBALS['PRODS_API_NUMS']['RM_COLL_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        while ($msg->getBody() instanceof RP_CollOprStat) {
            if (is_callable($status_update_func)) // call status update function if requested
            {
                $status = call_user_func($status_update_func,
                    array(
                        "filesCnt" => $msg->getBody()->filesCnt,
                        "lastObjPath" => $msg->getBody()->lastObjPath
                    )
                );
                if (false === $status)
                    throw new Exception("status_update_func failed!");
                else if (1 == $status) {
                    return;
                }
            }

            if ($intInfo == 0) //stop here if intinfo =0 (process completed)
                break;
            $this->replyStatusPacket();
            $msg = new RODSMessage();
            $intInfo = (int)$msg->unpack($this->conn);
        }

        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                return;
            }
            throw new RODSException("RODSConn::rmdir has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    // this is a temp work around for status packet reply.
    // in status packet protocol, the server gives a status update packet:
    // SYS_SVR_TO_CLI_COLL_STAT (99999996)
    // and it expects an  integer only SYS_CLI_TO_SVR_COLL_STAT_REPLY (99999997)
    private function replyStatusPacket()
    {
        fwrite($this->conn, pack("N", 99999997));
    }

    /**
     * Get children direcotories of input direcotory path string
     * @param string $dir input direcotory path string
     * @return an array of string, each string is the name of a child directory. This fuction return empty array, if there is no child direcotry found
     */
    public function getChildDir($dir, $startingInx = 0, $maxresults = 500,
                                &$total_num_rows = -1)
    {
        $cond = array(new RODSQueryCondition("COL_COLL_PARENT_NAME", $dir));
        $que_result = $this->genQuery(array("COL_COLL_NAME"), $cond, array(),
            $startingInx, $maxresults, true, array(), 0, $total_num_rows);

        if (false === $que_result) {
            return array();
        } else {
            if ($dir == "/") {
                $result = array();
                foreach ($que_result["COL_COLL_NAME"] as $childdir) {
                    if ($childdir != "/") {
                        $result[] = $childdir;
                    }
                }
                return $result;
            }

            return array_values($que_result["COL_COLL_NAME"]);
        }
    }

    /**
     * Get children direcotories, with basic stats,  of input direcotory path string
     * @param string $dir input direcotory path string
     * @param $orderby An associated array specifying how to sort the result by attributes. Each array key is the attribute, array val is 0 (assendent) or 1 (dessendent). The supported attributes are "name", "owner", "mtime".
     * @return an array of RODSDirStats
     */
    public function getChildDirWithStats($dir, $orderby = array(), $startingInx = 0,
                                         $maxresults = 500, &$total_num_rows = -1)
    {
        // set selected value
        $select_val = array("COL_COLL_NAME", "COL_COLL_ID", "COL_COLL_OWNER_NAME",
            "COL_COLL_OWNER_ZONE", "COL_COLL_CREATE_TIME", "COL_COLL_MODIFY_TIME",
            "COL_COLL_COMMENTS");
        $select_attr = array();

        // set order by
        if (!empty($orderby)) {
            $select_attr = array_fill(0, count($select_val), 1);
            foreach ($orderby as $key => $val) {
                if ($key == "name") {
                    if ($val == 0) $select_attr[0] = ORDER_BY;
                    else $select_attr[0] = ORDER_BY_DESC;
                } else
                    if ($key == "owner") {
                        if ($val == 0) $select_attr[2] = ORDER_BY;
                        else $select_attr[2] = ORDER_BY_DESC;
                    } else
                        if ($key == "mtime") {
                            if ($val == 0) $select_attr[5] = ORDER_BY;
                            else $select_attr[5] = ORDER_BY_DESC;
                        }
            }
        }

        $cond = array(new RODSQueryCondition("COL_COLL_PARENT_NAME", $dir));
        $continueInx = 0;
        $que_result = $this->genQuery($select_val, $cond,
            array(), $startingInx, $maxresults, true,
            $select_attr, $continueInx, $total_num_rows);

        if (false === $que_result) {
            return array();
        } else {
            $ret_val = array();
            for ($i = 0; $i < count($que_result['COL_COLL_ID']); $i++) {
                if ($que_result['COL_COLL_NAME'][$i] != "/") {
                    $ret_val[] = new RODSDirStats(
                        basename($que_result['COL_COLL_NAME'][$i]),
                        $que_result['COL_COLL_OWNER_NAME'][$i],
                        $que_result['COL_COLL_OWNER_ZONE'][$i],
                        $que_result['COL_COLL_MODIFY_TIME'][$i],
                        $que_result['COL_COLL_CREATE_TIME'][$i],
                        $que_result['COL_COLL_ID'][$i],
                        $que_result['COL_COLL_COMMENTS'][$i]
                    );
                }
            }
            return $ret_val;
        }
    }

    /**
     * Get children file of input direcotory path string
     * @param string $dir input direcotory path string
     * @return an array of string, each string is the name of a child file.  This fuction return empty array, if there is no child direcotry found.
     */
    public function getChildFile($dir, $startingInx = 0, $maxresults = 500,
                                 &$total_num_rows = -1)
    {
        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $dir));
        $que_result = $this->genQuery(array("COL_DATA_NAME"), $cond, array(),
            $startingInx, $maxresults, true, array(), 0, $total_num_rows);

        if (false === $que_result) {
            return array();
        } else {
            return array_values($que_result["COL_DATA_NAME"]);
        }
    }

    /**
     * Get children file, with basic stats, of input direcotory path string
     * The stats
     * @param string $dir input direcotory path string
     * @param $orderby An associated array specifying how to sort the result by attributes. Each array key is the attribute, array val is 0 (assendent) or 1 (dessendent). The supported attributes are "name", "size", "owner", "mtime".
     * @return an array of RODSFileStats
     */
    public function getChildFileWithStats($dir, array $orderby = array(),
                                          $startingInx = 0, $maxresults = 500, &$total_num_rows = -1)
    {
        // set selected value
        $select_val = array("COL_DATA_NAME", "COL_D_DATA_ID", "COL_DATA_TYPE_NAME",
            "COL_D_RESC_NAME", "COL_DATA_SIZE", "COL_D_OWNER_NAME",
            "COL_D_CREATE_TIME", "COL_D_MODIFY_TIME");
        $select_attr = array();

        // set order by
        if (!empty($orderby)) {
            $select_attr = array_fill(0, count($select_val), 1);
            foreach ($orderby as $key => $val) {
                if ($key == "name") {
                    if ($val == 0) $select_attr[0] = ORDER_BY;
                    else $select_attr[0] = ORDER_BY_DESC;
                } else
                    if ($key == "size") {
                        if ($val == 0) $select_attr[4] = ORDER_BY;
                        else $select_attr[4] = ORDER_BY_DESC;
                    } else
                        if ($key == "owner") {
                            if ($val == 0) $select_attr[5] = ORDER_BY;
                            else $select_attr[5] = ORDER_BY_DESC;
                        } else
                            if ($key == "mtime") {
                                if ($val == 0) $select_attr[7] = ORDER_BY;
                                else $select_attr[7] = ORDER_BY_DESC;
                            }
            }
        }

        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $dir));
        $continueInx = 0;
        $que_result = $this->genQuery($select_val, $cond,
            array(), $startingInx, $maxresults, true,
            $select_attr, $continueInx, $total_num_rows);


        if (false === $que_result) {
            return array();
        } else {
            $ret_val = array();
            for ($i = 0; $i < count($que_result['COL_D_DATA_ID']); $i++) {
                $ret_val[] = new RODSFileStats(
                    $que_result['COL_DATA_NAME'][$i],
                    $que_result['COL_DATA_SIZE'][$i],
                    $que_result['COL_D_OWNER_NAME'][$i],
                    $que_result['COL_D_MODIFY_TIME'][$i],
                    $que_result['COL_D_CREATE_TIME'][$i],
                    $que_result['COL_D_DATA_ID'][$i],
                    $que_result['COL_DATA_TYPE_NAME'][$i],
                    $que_result['COL_D_RESC_NAME'][$i]
                );
            }
            return $ret_val;
        }
    }

    /**
     * Get basic stats, of input dir path string
     * @param string $dirpath input dir path string
     * @return RODSDirStats. If dir does not exists, return fales.
     */
    public function getDirStats($dirpath)
    {
        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $dirpath));

        $que_result = $this->genQuery(
            array("COL_COLL_NAME", "COL_COLL_ID", "COL_COLL_OWNER_NAME",
                "COL_COLL_OWNER_ZONE", "COL_COLL_CREATE_TIME", "COL_COLL_MODIFY_TIME",
                "COL_COLL_COMMENTS"),
            $cond, array(), 0, 1, false);
        if ($que_result === false) return false;

        $stats = new RODSDirStats(
            basename($que_result['COL_COLL_NAME'][0]),
            $que_result['COL_COLL_OWNER_NAME'][0],
            $que_result['COL_COLL_OWNER_ZONE'][0],
            $que_result['COL_COLL_MODIFY_TIME'][0],
            $que_result['COL_COLL_CREATE_TIME'][0],
            $que_result['COL_COLL_ID'][0],
            $que_result['COL_COLL_COMMENTS'][0]
        );
        return $stats;
    }

    /**
     * Get basic stats, of input file path string
     * @param string $filepath input file path string
     * @return RODSFileStats. If file does not exists, return fales.
     */
    public function getFileStats($filepath)
    {
        $parent = dirname($filepath);
        $filename = basename($filepath);

        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $parent),
            new RODSQueryCondition("COL_DATA_NAME", $filename));

        $que_result = $this->genQuery(
            array("COL_DATA_NAME", "COL_D_DATA_ID", "COL_DATA_TYPE_NAME",
                "COL_D_RESC_NAME", "COL_DATA_SIZE", "COL_D_OWNER_NAME", "COL_D_OWNER_ZONE",
                "COL_D_CREATE_TIME",
                "COL_D_MODIFY_TIME", "COL_D_COMMENTS"),
            $cond, array(), 0, 1, false);
        if ($que_result === false) return false;

        $stats = new RODSFileStats(
            $que_result['COL_DATA_NAME'][0],
            $que_result['COL_DATA_SIZE'][0],
            $que_result['COL_D_OWNER_NAME'][0],
            $que_result['COL_D_OWNER_ZONE'][0],
            $que_result['COL_D_MODIFY_TIME'][0],
            $que_result['COL_D_CREATE_TIME'][0],
            $que_result['COL_D_DATA_ID'][0],
            $que_result['COL_DATA_TYPE_NAME'][0],
            $que_result['COL_D_RESC_NAME'][0],
            $que_result['COL_D_COMMENTS'][0]);
        return $stats;
    }

    /**
     * Check whether a directory (in string) exists on RODS server.
     * @return true/false
     */
    public function dirExists($dir)
    {
        $cond = array(new RODSQueryCondition("COL_COLL_NAME", $dir));
        $que_result = $this->genQuery(array("COL_COLL_ID"), $cond);

        if ($que_result === false)
            return false;
        else
            return true;
    }

    /**
     * Check whether a file (in string) exists on RODS server.
     * @return true/false
     */
    public function fileExists($filepath, $rescname = NULL)
    {
        $parent = dirname($filepath);
        $filename = basename($filepath);
        if (empty($rescname)) {
            $cond = array(new RODSQueryCondition("COL_COLL_NAME", $parent),
                new RODSQueryCondition("COL_DATA_NAME", $filename));
            $que_result = $this->genQuery(array("COL_D_DATA_ID"), $cond);
        } else {
            $cond = array(new RODSQueryCondition("COL_COLL_NAME", $parent),
                new RODSQueryCondition("COL_DATA_NAME", $filename),
                new RODSQueryCondition("COL_D_RESC_NAME", $rescname));
            $que_result = $this->genQuery(array("COL_D_DATA_ID"), $cond);
        }

        if ($que_result === false)
            return false;
        else
            return true;
    }

    /**
     * Replicate file to resources with options.
     * @param string $path_src full path for the source file
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
    public function repl($path_src, $desc_resc, array $options = array())
    {
        require_once(dirname(__FILE__) . "/RODSObjIOOpr.inc.php");
        require_once(dirname(__FILE__) . "/RodsGenQueryKeyWd.inc.php");

        $optype = REPLICATE_OPR;

        $opt_arr = array();
        $opt_arr[$GLOBALS['PRODS_GENQUE_KEYWD']['DEST_RESC_NAME_KW']] = $desc_resc;
        foreach ($options as $option_key => $option_val) {
            switch ($option_key) {
                case 'all':
                    if ($option_val === true)
                        $opt_arr[$GLOBALS['PRODS_GENQUE_KEYWD']['ALL_KW']] = '';
                    break;

                case 'admin':
                    if ($option_val === true)
                        $opt_arr[$GLOBALS['PRODS_GENQUE_KEYWD']['IRODS_ADMIN_KW']] = '';
                    break;

                case 'replNum':
                    $opt_arr[$GLOBALS['PRODS_GENQUE_KEYWD']['REPL_NUM_KW']] = $option_val;
                    break;

                case 'backupMode':
                    if ($option_val === true)
                        $opt_arr[$GLOBALS['PRODS_GENQUE_KEYWD']
                        ['BACKUP_RESC_NAME_KW']] = $desc_resc;
                    break;

                default:
                    throw new RODSException("Option '$option_key'=>'$option_val' is not supported",
                        'PERR_USER_INPUT_ERROR');
            }
        }

        $keyvalpair = new RP_KeyValPair();
        $keyvalpair->fromAssocArray($opt_arr);

        $inp_pk = new RP_DataObjInp($path_src, 0, 0, 0, 0, 0, $optype, $keyvalpair);

        $msg = new RODSMessage("RODS_API_REQ_T", $inp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_REPL_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::repl has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }

        $retpk = $msg->getBody();
        return $retpk->bytesWritten;
    }

    /**
     * Rename path_src to path_dest.
     * @param string $path_src
     * @param string $path_dest
     * @param integer $path_type if 0, then path type is file, if 1, then path type if directory
     * @return true/false
     */
    public function rename($path_src, $path_dest, $path_type)
    {
        require_once(dirname(__FILE__) . "/RODSObjIOOpr.inc.php");

        if ($path_type === 0) {
            $path_type_magic_num = RENAME_DATA_OBJ;
        } else {
            $path_type_magic_num = RENAME_COLL;
        }
        $src_pk = new RP_DataObjInp($path_src, 0, 0, 0, 0, 0, $path_type_magic_num);
        $dest_pk = new RP_DataObjInp($path_dest, 0, 0, 0, 0, 0, $path_type_magic_num);
        $inp_pk = new RP_DataObjCopyInp($src_pk, $dest_pk);
        $msg = new RODSMessage("RODS_API_REQ_T", $inp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_RENAME_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::rename has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * Open a file path (string) exists on RODS server.
     *
     * @param string $path file path
     * @param string $mode open mode. Supported modes are:
     *   'r'     Open for reading only; place the file pointer at the beginning of the file.
     *   'r+'    Open for reading and writing; place the file pointer at the beginning of the file.
     *   'w'    Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
     *   'w+'    Open for reading and writing; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
     *   'a'    Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
     *   'a+'    Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
     *   'x'    Create and open for writing only; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning FALSE and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     *   'x+'    Create and open for reading and writing; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning FALSE and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     * @param postion updated position
     * @param string $rescname. Note that this parameter is required only if the file does not exists (create mode). If the file already exists, and if file resource is unknown or unique or you-dont-care for that file, leave the field, or pass NULL.
     * @param boolean $assum_file_exists. This parameter specifies whether file exists. If the value is false, this mothod will check with RODS server to make sure. If value is true, the check will NOT be done. Default value is false.
     * @param string $filetype. This parameter only make sense when you want to specify the file type, if file does not exists (create mode). If not specified, it defaults to "generic"
     * @param integer $cmode. This parameter is only used for "createmode". It specifies the file mode on physical storage system (RODS vault), in octal 4 digit format. For instance, 0644 is owner readable/writeable, and nothing else. 0777 is all readable, writable, and excutable. If not specified, and the open flag requirs create mode, it defaults to 0644.
     * @return integer level 1 descriptor
     */
    public function openFileDesc($path, $mode, &$position, $rescname = NULL,
                                 $assum_file_exists = false, $filetype = 'generic', $cmode = 0644)
    {
        $create_if_not_exists = false;
        $error_if_exists = false;
        $seek_to_end_of_file = false;
        $position = 0;

        switch ($mode) {
            case 'r':
                $open_flag = O_RDONLY;
                break;
            case 'r+':
                $open_flag = O_RDWR;
                break;
            case 'w':
                $open_flag = O_WRONLY|O_TRUNC;
                $create_if_not_exists = true;
                break;
            case 'w+':
                $open_flag = O_RDWR|O_TRUNC;
                $create_if_not_exists = true;
                break;
            case 'a':
                $open_flag = O_WRONLY;
                $create_if_not_exists = true;
                $seek_to_end_of_file = true;
                break;
            case 'a+':
                $open_flag = O_RDWR;
                $create_if_not_exists = true;
                $seek_to_end_of_file = true;
                break;
            case 'x':
                $open_flag = O_WRONLY;
                $create_if_not_exists = true;
                $error_if_exists = true;
                break;
            case 'x+':
                $open_flag = O_RDWR;
                $create_if_not_exists = true;
                $error_if_exists = true;
                break;
            default:
                throw new RODSException("RODSConn::openFileDesc() does not recognize input mode:'$mode' ",
                    "PERR_USER_INPUT_ERROR");
        }

        if ($assum_file_exists === true)
            $file_exists = true;
        else
            $file_exists = $this->fileExists($path, $rescname);

        if (($error_if_exists) && ($file_exists === true)) {
            throw new RODSException("RODSConn::openFileDesc() expect file '$path' dose not exists with mode '$mode', but the file does exists",
                "PERR_USER_INPUT_ERROR");
        }


        if (($create_if_not_exists) && ($file_exists === false)) // create new file
        {
            $keyValPair_pk = new RP_KeyValPair(2, array("rescName", "dataType"),
                array("$rescname", "$filetype"));
            $dataObjInp_pk = new RP_DataObjInp($path, $cmode, $open_flag, 0, -1, 0, 0,
                $keyValPair_pk);
            $api_num = $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_CREATE_AN'];
        } else // open existing file
        {
            // open the file and get descriptor
            if (isset($rescname)) {
                $keyValPair_pk = new RP_KeyValPair(1, array("rescName"),
                    array("$rescname"));
                $dataObjInp_pk = new RP_DataObjInp
                ($path, 0, $open_flag, 0, -1, 0, 0, $keyValPair_pk);
            } else {
                $dataObjInp_pk = new RP_DataObjInp
                ($path, 0, $open_flag, 0, -1, 0, 0);
            }
            $api_num = $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_OPEN_AN'];
        }

        $msg = new RODSMessage("RODS_API_REQ_T", $dataObjInp_pk, $api_num);
        fwrite($this->conn, $msg->pack()); // send it
        // get value back
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                throw new RODSException("trying to open a file '$path' " .
                        "which does not exists with mode '$mode' ",
                    "PERR_USER_INPUT_ERROR");
            }
            throw new RODSException("RODSConn::openFileDesc has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        $l1desc = $intInfo;

        if ($seek_to_end_of_file === true) {
            $position = $this->fileSeek($l1desc, 0, SEEK_END);
        }

        return $l1desc;
    }

    /**
     * unlink the file on server
     * @param string $path path of the file
     * @param string $rescname resource name. Not required if there is no other replica.
     * @param boolean $force flag (true or false) indicating whether force delete or not.
     *
     */
    public function fileUnlink($path, $rescname = NULL, $force = false)
    {
        $options = array();
        if (isset($rescname)) {
            $options['rescName'] = $rescname;
        }
        if ($force == true) {
            $options['forceFlag'] = "";
        }

        if (!empty($options)) {
            $options_pk = new RP_KeyValPair();
            $options_pk->fromAssocArray($options);
            $dataObjInp_pk = new RP_DataObjInp
            ($path, 0, 0, 0, -1, 0, 0, $options_pk);
        } else {
            $dataObjInp_pk = new RP_DataObjInp
            ($path, 0, 0, 0, -1, 0, 0);
        }

        $msg = new RODSMessage("RODS_API_REQ_T", $dataObjInp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_UNLINK_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        // get value back
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                throw new RODSException("trying to unlink a file '$path' " .
                        "which does not exists",
                    "PERR_USER_INPUT_ERROR");
            }
            throw new RODSException("RODSConn::fileUnlink has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * close the input file descriptor on RODS server.
     *
     * @param int $l1desc level 1 file descriptor
     */
    public function closeFileDesc($l1desc)
    {
        try {
            $dataObjCloseInp_pk = new RP_dataObjCloseInp($l1desc);
            $msg = new RODSMessage("RODS_API_REQ_T", $dataObjCloseInp_pk,
                $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_CLOSE_AN']);
            fwrite($this->conn, $msg->pack()); // send it
            // get value back
            $msg = new RODSMessage();
            $intInfo = (int)$msg->unpack($this->conn);
            if ($intInfo < 0) {
                trigger_error("Got an error from server:$intInfo",
                    E_USER_WARNING);
            }
        } catch (RODSException $e) {
            trigger_error("Got an exception:$e", E_USER_WARNING);
        }
    }

    /**
     * reads up to length bytes from the file pointer referenced by handle. Reading stops when up to length bytes have been read, EOF (end of file) is reached
     *
     * @param int $l1desc level 1 file descriptor
     * @param int $length up to how many bytes to read.
     * @return the read string.
     */
    public function fileRead($l1desc, $length)
    {
        $dataObjReadInp_pk = new RP_dataObjReadInp($l1desc, $length);
        $msg = new RODSMessage("RODS_API_REQ_T", $dataObjReadInp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_READ_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::fileRead has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        return $msg->getBinstr();
    }

    /**
     * writes up to length bytes from the file pointer referenced by handle. returns number of bytes writtne.
     *
     * @param int $l1desc level 1 file descriptor
     * @param string $string contents (binary safe) to be written
     * @param int $length up to how many bytes to read.
     * @return the number of bytes written.
     */
    public function fileWrite($l1desc, $string, $length = NULL)
    {
        if (!isset($length))
            $length = strlen($string);

        $dataObjWriteInp_pk = new RP_dataObjWriteInp($l1desc, $length);
        $msg = new RODSMessage("RODS_API_REQ_T", $dataObjWriteInp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_WRITE_AN'], $string);
        fwrite($this->conn, $msg->pack()); // send header and body msg
        fwrite($this->conn, $string); // send contents
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::fileWrite has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        return $intInfo;
    }

    /**
     *  Sets the file position indicator for the file referenced by l1desc (int descriptor). The new position, measured in bytes from the beginning of the file, is obtained by adding offset to the position specified by whence, whose values are defined as follows:
     *  SEEK_SET - Set position equal to offset bytes.
     *  SEEK_CUR - Set position to current location plus offset.
     *  SEEK_END - Set position to end-of-file plus offset. (To move to a position before the end-of-file, you need to pass a negative value in offset.)
     *  If whence is not specified, it is assumed to be SEEK_SET.
     * @return int the current offset
     */
    public function fileSeek($l1desc, $offset, $whence = SEEK_SET)
    {
        $dataObjReadInp_pk = new RP_fileLseekInp($l1desc, $offset, $whence);
        $msg = new RODSMessage("RODS_API_REQ_T", $dataObjReadInp_pk,
            $GLOBALS['PRODS_API_NUMS']['DATA_OBJ_LSEEK_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::fileSeek has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        $retpk = $msg->getBody();
        return $retpk->offset;
    }

    /**
     * Get metadata for a file, dir, resource or user
     * @param char $pathtype 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param string $name name of the target object. in the case of file and dir, use its full path
     * @return RODSMeta $meta meta data for the target.
     */
    public function getMeta($pathtype, $name)
    {
        switch ($pathtype) {
            case 'd':
                $select = array("COL_META_DATA_ATTR_NAME", "COL_META_DATA_ATTR_VALUE",
                    "COL_META_DATA_ATTR_UNITS", 'COL_META_DATA_ATTR_ID');
                $condition = array(
                    new RODSQueryCondition("COL_COLL_NAME", dirname($name)),
                    new RODSQueryCondition("COL_DATA_NAME", basename($name))
                );
                break;
            case 'c':
                $select = array("COL_META_COLL_ATTR_NAME", "COL_META_COLL_ATTR_VALUE",
                    "COL_META_COLL_ATTR_UNITS", 'COL_META_COLL_ATTR_ID');
                $condition = array(new RODSQueryCondition("COL_COLL_NAME", $name));
                break;
            case 'r':
                $select = array("COL_META_RESC_ATTR_NAME", "COL_META_RESC_ATTR_VALUE",
                    "COL_META_RESC_ATTR_UNITS", 'COL_META_RESC_ATTR_ID');
                $condition = array(new RODSQueryCondition("COL_R_RESC_NAME", $name));
                break;
            case 'u':
                $select = array("COL_META_USER_ATTR_NAME", "COL_META_USER_ATTR_VALUE",
                    "COL_META_USER_ATTR_UNITS", 'COL_META_USER_ATTR_ID');
                $condition = array(new RODSQueryCondition("COL_USER_NAME", $name));
                break;
            default:
                throw new RODSException("RODSConn::getMeta pathtype '$pathtype' is not supported!",
                    'PERR_USER_INPUT_ERROR');
        }

        $genque_result = $this->genQuery($select, $condition);

        if ($genque_result === false) {
            return array();
        }
        $ret_array = array();
        for ($i = 0; $i < count($genque_result[$select[0]]); $i++) {
            $ret_array[$i] = new RODSMeta(
                $genque_result[$select[0]][$i],
                $genque_result[$select[1]][$i],
                $genque_result[$select[2]][$i],
                $genque_result[$select[3]][$i]
            );

        }
        return $ret_array;

    }

    /**
     * Add metadata to a file, dir, resource or user
     * @param char $pathtype 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param string $name name of the target object. in the case of file and dir, use its full path
     * @param RODSMeta $meta meta data to be added.
     */
    public function addMeta($pathtype, $name, RODSMeta $meta)
    {
        $pkt = new RP_ModAVUMetadataInp("add", "-$pathtype", $name, $meta->name,
            $meta->value, $meta->units);
        $msg = new RODSMessage("RODS_API_REQ_T", $pkt,
            $GLOBALS['PRODS_API_NUMS']['MOD_AVU_METADATA_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::addMeta has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * remove metadata to a file, dir, resource or user
     * @param char $pathtype 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param string $name name of the target object. in the case of file and dir, use its full path
     * @param RODSMeta $meta meta data to be removed.
     */
    public function rmMeta($pathtype, $name, RODSMeta $meta)
    {
        $pkt = new RP_ModAVUMetadataInp("rm", "-$pathtype", $name, $meta->name,
            $meta->value, $meta->units);
        $msg = new RODSMessage("RODS_API_REQ_T", $pkt,
            $GLOBALS['PRODS_API_NUMS']['MOD_AVU_METADATA_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::rmMeta has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * remove metadata to a file, dir, resource or user
     * @param char $pathtype 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param string $name name of the target object. in the case of file and dir, use its full path
     * @param integer $metaid id of the metadata to be removed.
     */
    public function rmMetaByID($pathtype, $name, $metaid)
    {
        $pkt = new RP_ModAVUMetadataInp("rmi", "-$pathtype", $name, $metaid);
        $msg = new RODSMessage("RODS_API_REQ_T", $pkt,
            $GLOBALS['PRODS_API_NUMS']['MOD_AVU_METADATA_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) != 'CAT_SUCCESS_BUT_WITH_NO_INFO') {
                throw new RODSException("RODSConn::rmMetaByID has got an error from the server",
                    $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
            }
        }
    }

    /**
     * copy metadata between file, dir, resource or user
     * @param char $pathtype_src source path type 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param char $pathtype_dest destination path type 'd'=file, 'c'=dir, 'r'=resource, 'u'=user
     * @param string $name_src name of the source target object. in the case of file and dir, use its full path
     * @param string $name_dest name of the destination target object. in the case of file and dir, use its full path
     */
    public function cpMeta($pathtype_src, $pathtype_dest, $name_src, $name_dest)
    {
        $pkt = new RP_ModAVUMetadataInp("cp", "-$pathtype_src",
            "-$pathtype_dest", $name_src, $name_dest);
        $msg = new RODSMessage("RODS_API_REQ_T", $pkt,
            $GLOBALS['PRODS_API_NUMS']['MOD_AVU_METADATA_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $msg = new RODSMessage();
        $intInfo = (int)$msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::cpMeta has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
    }

    /**
     * Excute a user defined rule
     * @param string $rule_body body of the rule. Read this tutorial for details about rules: http://www.irods.org/index.php/Executing_user_defined_rules/workflow
     * @param array $inp_params associative array defining input parameter for micro services used in this rule. only string and keyval pair are supported at this time. If the array value is a string, then type is string, if the array value is an RODSKeyValPair object, it will be treated a keyval pair
     * @param array $out_params an array of names (strings)
     * @param array $remotesvr if this rule need to run at remote server, this associative array should have the following keys:
     *    - 'host' remote host name or address
     *    - 'port' remote port
     *    - 'zone' remote zone
     *    if any of the value is empty, this option will be ignored.
     * @param RODSKeyValPair $options an RODSKeyValPair specifying additional options, purpose of this is unknown at the developement time. Leave it alone if you are as clueless as me...
     * @return an associative array. Each array key is the lable, and each array value's type will depend on the type of $out_param, at this moment, only string and RODSKeyValPair are supported
     */
    public function execUserRule($rule_body,
                                 array $inp_params = array(), array $out_params = array(),
                                 array $remotesvr = array(), RODSKeyValPair $options = null)
    {
        $inp_params_packets = array();
        foreach ($inp_params as $inp_param_key => $inp_param_val) {
            if (is_a($inp_param_val, 'RODSKeyValPair')) {
                $inp_params_packets[] = new RP_MsParam($inp_param_key,
                    $inp_param_val->makePacket());
            } else // a string
            {
                $inp_params_packets[] = new RP_MsParam($inp_param_key,
                    new RP_STR($inp_param_val));
            }
        }
        $inp_param_arr_packet = new RP_MsParamArray($inp_params_packets);

        $out_params_desc = implode('%', $out_params);

        if ((isset($remotesvr['host'])) && (isset($remotesvr['port'])) &&
            (isset($remotesvr['zone']))
        ) {
            $remotesvr_packet = new RP_RHostAddr($remotesvr['host'],
                $remotesvr['zone'], $remotesvr['port']);
        } else {
            $remotesvr_packet = new RP_RHostAddr();
        }

        if (!isset($options))
            $options = new RODSKeyValPair();

        $options_packet = $options->makePacket();

        $pkt = new RP_ExecMyRuleInp($rule_body, $remotesvr_packet,
            $options_packet, $out_params_desc, $inp_param_arr_packet);
        $msg = new RODSMessage("RODS_API_REQ_T", $pkt,
            $GLOBALS['PRODS_API_NUMS']['EXEC_MY_RULE_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        $resv_msg = new RODSMessage();
        $intInfo = (int)$resv_msg->unpack($this->conn);
        if ($intInfo < 0) {
            throw new RODSException("RODSConn::execUserRule has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        $retpk = $resv_msg->getBody();
        $param_array = $retpk->MsParam_PI;
        $ret_arr = array();
        foreach ($param_array as $param) {
            if ($param->type == 'STR_PI') {
                $label = $param->label;
                $ret_arr["$label"] = $param->STR_PI->myStr;
            } else
                if ($param->type == 'KeyValPair_PI') {
                    $label = $param->label;
                    $ret_arr["$label"] = RODSKeyValPair::fromPacket($param->KeyValPair_PI);
                } else
                    if ($param->type == 'ExecCmdOut_PI') {
                        $label = $param->label;
                        $exec_ret_val = $param->ExecCmdOut_PI->buf;
                        $ret_arr["$label"] = $exec_ret_val;
                    } else {
                        throw new RODSException("RODSConn::execUserRule got. " .
                                "an unexpected output param with type: '$param->type' \n",
                            "PERR_UNEXPECTED_PACKET_FORMAT");
                    }
        }
        return $ret_arr;
    }

    /**
     * This function is depreciated, and kept only for lagacy reasons!
     * Makes a general query to RODS server. Think it as an SQL. "select foo from sometab where bar = '3'". In this example, foo is specified by "$select", bar and "= '3'" are speficed by condition.
     * @param array $select the fields (names) to be returned/interested. There can not be more than 50 input fields. For example:"COL_COLL_NAME" means collection-name.
     * @param array $condition  Array of RODSQueryCondition. All fields are defined in RodsGenQueryNum.inc.php
     * @param array $condition_kw  Array of RODSQueryCondition. All fields are defined in RodsGenQueryKeyWd.inc.php
     * @param integer $startingInx result start from which row.
     * @param integer $maxresult up to how man rows should the result contain.
     * @param boolean $getallrows whether to retreive all results
     * @param boolean $select_attr attributes (array of int) of each select value. For instance, the attribute can be ORDER_BY (0x400) or ORDER_BY_DESC (0x800) to have the results sorted on the server. The default value is 1 for each attribute. Pass empty array or leave the option if you don't want anything fancy.
     * @param integer $continueInx This index can be used to retrieve rest of results, when there is a overflow of the rows (> 500)
     * @return an associated array, keys are the returning field names, each value is an array of the field values. Also, it returns false (boolean), if no rows are found.
     * Note: This function is very low level. It's not recommended for beginners.
     */
    public function genQuery(array $select, array $condition = array(),
                             array $condition_kw = array(), $startingInx = 0, $maxresults = 500,
                             $getallrows = true, array $select_attr = array(), &$continueInx = 0,
                             &$total_num_rows = -1)
    {
        if (count($select) > 50) {
            trigger_error("genQuery(): Only upto 50 input are supported, rest ignored",
                E_USER_WARNING);
            $select = array_slice($select, 0, 50);
        }

        $GenQueInp_options = 0;
        if ($total_num_rows != -1) {
            $GenQueInp_options = 1;
        }

        require_once("RodsGenQueryNum.inc.php"); //load magic numbers
        require_once("RodsGenQueryKeyWd.inc.php"); //load magic numbers

        // contruct select packet (RP_InxIvalPair $selectInp)
        $select_pk = NULL;
        if (count($select) > 0) {
            if (empty($select_attr))
                $select_attr = array_fill(0, count($select), 1);
            $idx = array();
            foreach ($select as $selval) {
                if (isset($GLOBALS['PRODS_GENQUE_NUMS']["$selval"]))
                    $idx[] = $GLOBALS['PRODS_GENQUE_NUMS']["$selval"];
                else
                    trigger_error("genQuery(): select val '$selval' is not support, ignored",
                        E_USER_WARNING);
            }

            $select_pk = new RP_InxIvalPair(count($select), $idx, $select_attr);
        } else {
            $select_pk = new RP_InxIvalPair();
        }

        foreach ($condition_kw as &$cond_kw) {
            if (isset($GLOBALS['PRODS_GENQUE_KEYWD'][$cond_kw->name]))
                $cond_kw->name = $GLOBALS['PRODS_GENQUE_KEYWD'][$cond_kw->name];
        }

        foreach ($condition as &$cond) {
            if (isset($GLOBALS['PRODS_GENQUE_NUMS'][$cond->name]))
                $cond->name = $GLOBALS['PRODS_GENQUE_NUMS'][$cond->name];
        }

        $condInput = new RP_KeyValPair();
        $condInput->fromRODSQueryConditionArray($condition_kw);

        $sqlCondInp = new RP_InxValPair();
        $sqlCondInp->fromRODSQueryConditionArray($condition);

        // construct RP_GenQueryInp packet
        $genque_input_pk = new RP_GenQueryInp($maxresults, $continueInx, $condInput,
            $select_pk, $sqlCondInp, $GenQueInp_options, $startingInx);

        // contruce a new API request message, with type GEN_QUERY_AN
        $msg = new RODSMessage("RODS_API_REQ_T", $genque_input_pk,
            $GLOBALS['PRODS_API_NUMS']['GEN_QUERY_AN']);
        fwrite($this->conn, $msg->pack()); // send it
        // get value back
        $msg_resv = new RODSMessage();
        $intInfo = $msg_resv->unpack($this->conn);
        if ($intInfo < 0) {
            if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                return false;
            }

            throw new RODSException("RODSConn::genQuery has got an error from the server",
                $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
        }
        $genque_result_pk = $msg_resv->getBody();

        $result_arr = array();
        for ($i = 0; $i < $genque_result_pk->attriCnt; $i++) {
            $sql_res_pk = $genque_result_pk->SqlResult_PI[$i];
            $attri_name = $GLOBALS['PRODS_GENQUE_NUMS_REV'][$sql_res_pk->attriInx];
            $result_arr["$attri_name"] = $sql_res_pk->value;
        }
        if ($total_num_rows != -1)
            $total_num_rows = $genque_result_pk->totalRowCount;


        $more_results = true;
        // if there are more results to be fetched
        while (($genque_result_pk->continueInx > 0) && ($more_results === true)
            && ($getallrows === true)) {
            $msg->getBody()->continueInx = $genque_result_pk->continueInx;
            fwrite($this->conn, $msg->pack()); // re-send it with new continueInx
            // get value back
            $msg_resv = new RODSMessage();
            $intInfo = $msg_resv->unpack($this->conn);
            if ($intInfo < 0) {
                if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                    $more_results = false;
                    break;
                } else
                    throw new RODSException("RODSConn::genQuery has got an error from the server",
                        $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
            }
            $genque_result_pk = $msg_resv->getBody();

            for ($i = 0; $i < $genque_result_pk->attriCnt; $i++) {
                $sql_res_pk = $genque_result_pk->SqlResult_PI[$i];
                $attri_name = $GLOBALS['PRODS_GENQUE_NUMS_REV'][$sql_res_pk->attriInx];
                $result_arr["$attri_name"] =
                    array_merge($result_arr["$attri_name"], $sql_res_pk->value);
            }
        }
        
         // Make sure and close the query if there are any results left.
    if ($genque_result_pk->continueInx > 0) 
    {
      $msg->getBody()->continueInx=$genque_result_pk->continueInx;
      $msg->getBody()->maxRows=-1;  // tells the server to close the query
      fwrite($this->conn, $msg->pack());
      $msg_resv=new RODSMessage();
      $intInfo=$msg_resv->unpack($this->conn);
      if ($intInfo<0)
      {
        throw new RODSException("RODSConn::genQuery has got an error from the server",
          $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
      }
    }

        return $result_arr;
    }

    /**
     * Makes a general query to RODS server. Think it as an SQL. "select foo from sometab where bar = '3'". In this example, foo is specified by "$select", bar and "= '3'" are speficed by condition.
     * @param RODSGenQueSelFlds $select the fields (names) to be returned/interested. There can not be more than 50 input fields. For example:"COL_COLL_NAME" means collection-name.
     * @param RODSGenQueConds $condition  All fields are defined in RodsGenQueryNum.inc.php and RodsGenQueryKeyWd.inc.php
     * @param integer $start result start from which row.
     * @param integer $limit up to how many rows should the result contain. If -1 is passed, all available rows will be returned
     * @return RODSGenQueResults
     * Note: This function is very low level. It's not recommended for beginners.
     */
    public function query(RODSGenQueSelFlds $select, RODSGenQueConds $condition,
                          $start = 0, $limit = -1)
    {
        if (($select->getCount() < 1) || ($select->getCount() > 50)) {
            throw new RODSException("Only 1-50 fields are supported",
                'PERR_USER_INPUT_ERROR');
        }

        // contruct select packet (RP_InxIvalPair $selectInp), and condition packets
        $select_pk = $select->packetize();
        $cond_pk = $condition->packetize();
        $condkw_pk = $condition->packetizeKW();

        // determin max number of results per query
            if (($limit > 0) && ($limit < 500))
                $max_result_per_query = $limit;
            else
                $max_result_per_query = 500;

        $num_fetched_rows = 0;
        $continueInx = 0;
        $results = new RODSGenQueResults();
        do {
            // construct RP_GenQueryInp packet
            $options = 1 | $GLOBALS['PRODS_GENQUE_NUMS']['RETURN_TOTAL_ROW_COUNT'];
            $genque_input_pk = new RP_GenQueryInp($max_result_per_query,
                $continueInx, $condkw_pk, $select_pk, $cond_pk, $options, $start);

            // contruce a new API request message, with type GEN_QUERY_AN
            $msg = new RODSMessage("RODS_API_REQ_T", $genque_input_pk,
                $GLOBALS['PRODS_API_NUMS']['GEN_QUERY_AN']);
            fwrite($this->conn, $msg->pack()); // send it
            // get value back
            $msg_resv = new RODSMessage();
            $intInfo = $msg_resv->unpack($this->conn);
            if ($intInfo < 0) {
                if (RODSException::rodsErrCodeToAbbr($intInfo) == 'CAT_NO_ROWS_FOUND') {
                    break;
                }

                throw new RODSException("RODSConn::query has got an error from the server",
                    $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
            }
            $genque_result_pk = $msg_resv->getBody();
            $num_row_added = $results->addResults($genque_result_pk);
            $continueInx = $genque_result_pk->continueInx;
            $start = $start + $results->getNumRow();
        } while (($continueInx > 0) &&
            (($results->getNumRow() < $limit) || ($limit < 0)));
    

        // Make sure and close the query if there are any results left.
    if ($continueInx > 0) 
    {
      $msg->getBody()->continueInx=$continueInx;
      $msg->getBody()->maxRows=-1;  // tells the server to close the query
      fwrite($this->conn, $msg->pack());
      $msg_resv=new RODSMessage();
      $intInfo=$msg_resv->unpack($this->conn);
      if ($intInfo<0)
      {
        throw new RODSException("RODSConn::query has got an error from the server",
          $GLOBALS['PRODS_ERR_CODES_REV']["$intInfo"]);
      }
    }
        
        return $results;
    }
}

?>
