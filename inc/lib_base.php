<?php

/**
* ownCloud
*
* @author Frank Karlitschek 
* @copyright 2010 Frank Karlitschek karlitschek@kde.org 
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/


// set some stuff
ob_start();
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// calculate the documentroot
$SERVERROOT=substr(__FILE__,0,-17);
$DOCUMENTROOT=$_SERVER['DOCUMENT_ROOT'];
$count=strlen($DOCUMENTROOT);
$WEBROOT=substr($SERVERROOT,$count);
if($WEBROOT{0}!=='/'){
	$WEBROOT='/'.$WEBROOT;
}

// set the right include path
set_include_path(get_include_path().PATH_SEPARATOR.$SERVERROOT.PATH_SEPARATOR.$SERVERROOT.'/inc'.PATH_SEPARATOR.$SERVERROOT.'/config');

// define default config values
$CONFIG_ADMINLOGIN='';
$CONFIG_ADMINPASSWORD='';
$CONFIG_DATADIRECTORY=$SERVERROOT.'/data';
$CONFIG_HTTPFORCESSL=false;
$CONFIG_DATEFORMAT='j M Y G:i';
$CONFIG_DBNAME='owncloud';
$CONFIG_DBTYPE='sqlite';

// include the generated configfile
@include_once('config.php');

// redirect to https site if configured
if(isset($CONFIG_HTTPFORCESSL) and $CONFIG_HTTPFORCESSL){
  if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') { 
    $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
    header("Location: $url"); 
    exit; 
  } 
}

// load core libs
require_once('lib_files.php');
require_once('lib_log.php');
require_once('lib_config.php');

// load plugins
$CONFIG_LOADPLUGINS='music';
$plugins=explode(' ',$CONFIG_LOADPLUGINS);
if(isset($plugins[0]['url'])) foreach($plugins as $plugin) require_once('plugins/'.$plugin.'/lib_'.$plugin.'.php');


// check if the server is correctly configured for ownCloud
OC_UTIL::checkserver();

// listen for login or logout actions
OC_USER::logoutlisener();
$loginresult=OC_USER::loginlisener();


/**
 * Class for usermanagement
 *
 */
class OC_USER {
  
  /**
   * check if the login button is pressed and logg the user in
   *
   */
  public static function loginlisener(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    if(isset($_POST['loginbutton']) and isset($_POST['password']) and isset($_POST['login'])){
      if($_POST['login']==$CONFIG_ADMINLOGIN and $_POST['password']==$CONFIG_ADMINPASSWORD){
        $_SESSION['username']=$_POST['login'];
        OC_LOG::event($_SESSION['username'],1,'');
        return('');
      }else{
        return('error');
      } 
    }
    return('');
  }

  /**
   * check if the logout button is pressed and logout the user
   *
   */
  public static function logoutlisener(){
    if(isset($_GET['logoutbutton']) && isset($_SESSION['username'])){
      OC_LOG::event($_SESSION['username'],2,'');
      unset($_SESSION['username']);
    }
  }

}


/**
 * Class for utility functions
 *
 */
class OC_UTIL {
  public static $scripts=array();
  
  /**
   * add a javascript file
   *
   * @param url  $url
   */
  public static function addscript($url){
      self::$scripts[]=$url;
  }

  /**
   * array to store all the optional navigation buttons of the plugins
   *
   */
  static private $NAVIGATION = array();


  /**
   * check if the current server configuration is suitable for ownCloud
   *
   */
  public static function checkserver(){
    global $SERVERROOT;
    $f=@fopen($SERVERROOT.'/config/config.php','a+');
    if(!$f) die('Error: Config file (config/config.php) is not writable for the webserver.');
    @fclose($f);
    
  }

  /**
   * show the header of the web GUI
   *
   */
  public static function showheader(){
    global $CONFIG_ADMINLOGIN;
    global $WEBROOT;
    require('templates/header.php');;
  }

  /**
   * show the footer of the web GUI
   *
   */
  public static function showfooter(){
    global $CONFIG_FOOTEROWNERNAME;
    global $CONFIG_FOOTEROWNEREMAIL;
    require('templates/footer.php');;
  }

  /**
   * add an navigationentry to the main navigation
   *
   * @param name $name
   * @param url  $url
   */
  public static function addnavigationentry($name,$url) {
    $entry=array();
    $entry['name']=$name;
    $entry['url']=$url;
    OC_UTIL::$NAVIGATION[]=$entry;
  }

  /**
   * show the main navigation
   *
   */
  public static function shownavigation(){
    global $WEBROOT;
    global $SERVERROOT;
    echo('<table class="center" cellpadding="5" cellspacing="0" border="0"><tr>');
    echo('<td class="navigationitem1"><a href="'.$WEBROOT.'/">'.$_SESSION['username'].'</a></td>');
    if($_SERVER['SCRIPT_NAME']==$WEBROOT.'/index.php') echo('<td class="navigationitemselected"><a href="'.$WEBROOT.'/">Files</a></td>'); else echo('<td class="navigationitem"><a href="'.$WEBROOT.'/">Files</a></td>');

    foreach(OC_UTIL::$NAVIGATION as $NAVI) {
      if(dirname($_SERVER['SCRIPT_NAME'])==$WEBROOT.$NAVI['url']) echo('<td class="navigationitemselected"><a href="'.$WEBROOT.$NAVI['url'].'">'.$NAVI['name'].'</a></td>'); else echo('<td class="navigationitem"><a href="'.$WEBROOT.$NAVI['url'].'">'.$NAVI['name'].'</a></td>');
    }

    if($_SERVER['SCRIPT_NAME']==$WEBROOT.'/log/index.php') echo('<td class="navigationitemselected"><a href="'.$WEBROOT.'/log">Log</a></td>'); else echo('<td class="navigationitem"><a href="'.$WEBROOT.'/log">Log</a></td>');
    if($_SERVER['SCRIPT_NAME']==$WEBROOT.'/settings/index.php') echo('<td class="navigationitemselected"><a href="'.$WEBROOT.'/settings">Settings</a></td>'); else echo('<td class="navigationitem"><a href="'.$WEBROOT.'/settings">Settings</a></td>');
    echo('<td class="navigationitem"><a href="?logoutbutton=1">Logout</a></td>');
    echo('</tr></table>');
  }


  /**
   * show the loginform
   *
   */
  public static function showloginform(){
    global $loginresult;
    require('templates/loginform.php');
  }


  /**
   * show an icon for a filetype
   *
   */
  public static function showicon($filetype){
    global $WEBROOT;
    if($filetype=='dir'){ echo('<td><img src="'.$WEBROOT.'/img/icons/folder.png" width="16" height="16"></td>');
    }elseif($filetype=='foo'){ echo('<td>foo</td>');
    }else{ echo('<td><img src="'.$WEBROOT.'/img/icons/other.png" width="16" height="16"></td>');
    }
  }

}


/**
 * Class for database access
 *
 */
class OC_DB {

  /**
   * executes a query on the database
   *
   * @param string $cmd
   * @return result-set
   */
  static function query($cmd) {
    global $DOCUMENTROOT;
    global $SERVERROOT;
    global $DBConnection;
    global $CONFIG_DBNAME;
    global $CONFIG_DBHOST;
    global $CONFIG_DBUSER;
    global $CONFIG_DBPASSWORD;
    global $CONFIG_DBTYPE;
    if(!isset($DBConnection)) {
      if($CONFIG_DBTYPE=='sqlite'){
          $DBConnection = @new SQLiteDatabase($SERVERROOT.'/'.$CONFIG_DBNAME);
      }elseif($CONFIG_DBTYPE=='mysql'){
          $DBConnection = @new mysqli($CONFIG_DBHOST, $CONFIG_DBUSER, $CONFIG_DBPASSWORD,$CONFIG_DBNAME);
      }
      if (!$DBConnection) {
        @ob_end_clean();
        echo('<b>can not connect to database, using '.$CONFIG_DBTYPE.'.</center>');
        exit();
      }
    }
    $result = @$DBConnection->query($cmd);
    if (!$result) {
      if($CONFIG_DBTYPE=='sqlite'){
        $error=sqlite_error_string($DBConnection->lastError());
      }elseif($CONFIG_DBTYPE=='mysql'){
        print_r($DBConnection);
        $error=$DBConnection->error;
      }
      $entry='DB Error: "'.$error.'"<br />';
      $entry.='Offending command was: '.$cmd.'<br />';
      echo($entry);
    }
    return $result;
  } 
  
  /**
   * executes multiply queries on the database
   *
   * @param string $cmd
   * @return result-set
   */
  static function multiquery($cmd) {
    global $DOCUMENTROOT;
    global $SERVERROOT;
    global $DBConnection;
    global $CONFIG_DBNAME;
    global $CONFIG_DBTYPE;
    global $CONFIG_DBHOST;
    global $CONFIG_DBUSER;
    global $CONFIG_DBPASSWORD;
    if(!isset($DBConnection)) {
      if($CONFIG_DBTYPE=='sqlite'){
          $DBConnection = new SQLiteDatabase($SERVERROOT.'/'.$CONFIG_DBNAME);
      }elseif($CONFIG_DBTYPE=='mysql'){
          $DBConnection = @new mysqli($CONFIG_DBHOST, $CONFIG_DBUSER, $CONFIG_DBPASSWORD,$CONFIG_DBNAME);
      }
      if (!$DBConnection) {
        @ob_end_clean();
        echo('<b>can not connect to database, using '.$CONFIG_DBTYPE.'.</center>');
        exit();
      }
    }
    if($CONFIG_DBTYPE=='sqlite'){
      $result = @$DBConnection->queryExec($cmd);
    }elseif($CONFIG_DBTYPE=='mysql'){
      $result = @$DBConnection->multi_query($cmd);
    }
    if (!$result) {
      if($CONFIG_DBTYPE=='sqlite'){
        $error=sqlite_error_string($DBConnection->lastError());
      }elseif($CONFIG_DBTYPE=='mysql'){
        $error=$DBConnection->error;
      }
      $entry='DB Error: "'.$error.'"<br />';
      $entry.='Offending command was: '.$cmd.'<br />';
      echo($entry);
    }
    return $result;
  }


  /**
   * closing a db connection
   *
   * @return bool
   */
  static function close() {
    global $CONFIG_DBTYPE;
    global $DBConnection;
    if(isset($DBConnection)) {
      return $DBConnection->close();
    } else {
      return(false);
    }
  }


  /**
   * Returning primarykey if last statement was an insert.
   *
   * @return primarykey
   */
  static function insertid() {
    global $DBConnection;
    global $CONFIG_DBTYPE;
    if($CONFIG_DBTYPE=='sqlite'){
      return $DBConnection->lastInsertRowid();
    }elseif($CONFIG_DBTYPE=='mysql'){
      return(mysqli_insert_id($DBConnection));
    }
  }

  /**
   * Returning number of rows in a result
   *
   * @param resultset $result
   * @return int
   */
  static function numrows($result) {
    if(!isset($result) or ($result == false)) return 0;
    global $CONFIG_DBTYPE;
    if($CONFIG_DBTYPE=='sqlite'){
      $num= $result->numRows();
    }elseif($CONFIG_DBTYPE=='mysql'){
      $num= mysqli_num_rows($result);
    }
    return($num);
  }

  /**
   * Returning number of affected rows
   *
   * @return int
   */
  static function affected_rows() {
    global $DBConnection;
    global $CONFIG_DBTYPE;
    if(!isset($DBConnection) or ($DBConnection==false)) return 0;
    if($CONFIG_DBTYPE=='sqlite'){
      $num= $DBConnection->changes();
    }elseif($CONFIG_DBTYPE=='mysql'){
      $num= mysqli_affected_rows($DBConnection);
    }
    return($num);
  }

  /**
   * get a field from the resultset
   *
   * @param resultset $result
   * @param int $i
   * @param int $field
   * @return unknown
   */
  static function result($result, $i, $field) {
    global $CONFIG_DBTYPE;
    if($CONFIG_DBTYPE=='sqlite'){
      $result->seek($i);
      $tmp=$result->fetch();
    }elseif($CONFIG_DBTYPE=='mysql'){
      mysqli_data_seek($result,$i);
      if (is_string($field))
        $tmp=mysqli_fetch_array($result,MYSQLI_BOTH);
      else
        $tmp=mysqli_fetch_array($result,MYSQLI_NUM);
    }
    $tmp=$tmp[$field];
    return($tmp);
    return($tmp);
  }

  /**
   * get data-array from resultset
   *
   * @param resultset $result
   * @return data
   */
  static function fetch_assoc($result) {
    global $CONFIG_DBTYPE;
    if($CONFIG_DBTYPE=='sqlite'){
      return $result->fetch(SQLITE_ASSOC);
    }elseif($CONFIG_DBTYPE=='mysql'){
      return mysqli_fetch_assoc($result);
    }
  }


  /**
   * Freeing resultset (performance)
   *
   * @param unknown_type $result
   * @return bool
   */
  static function free_result($result) {
    global $CONFIG_DBTYPE;
    if($CONFIG_DBTYPE=='sqlite'){
      $result = null;   //No native way to do this
      return true;
    }elseif($CONFIG_DBTYPE=='mysql'){
      return @mysqli_free_result($result);
    }
  }

}


?>
