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
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// redirect to https site if configured
if($CONFIG_HTTPFORCESSL){
  if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') { 
    $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
    header("Location: $url"); 
    exit; 
  } 
}

// load core libs
require_once('lib_files.php');
require_once('lib_log.php');

// load plugins
$plugins=explode(' ',$CONFIG_LOADPLUGINS);
if(isset($plugins[0]['url'])) foreach($plugins as $plugin) require_once('plugins/'.$plugin.'/lib_'.$plugin.'.php');


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
      }else{
        echo('error');
      } 
    }
  }

  /**
   * check if the logout button is pressed and logout the user
   *
   */
  public static function logoutlisener(){
    if(isset($_GET['logoutbutton'])){
      OC_LOG::event($_SESSION['username'],2,'');
      if(isset($_SESSION['username'])) unset($_SESSION['username']);
    }
  }

}


/**
 * Class for utility functions
 *
 */
class OC_UTIL {

  /**
   * array to store all the optional navigation buttons of the plugins
   *
   */
  static private $NAVIGATION = array();

  /**
   * show the header of the web GUI
   *
   */
  public static function showheader(){
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
    echo('<table cellpadding="5" cellspacing="0" border="0"><tr>');
    echo('<td class="navigationitem1"><a href="/">'.$_SESSION['username'].'</a> <img src="/img/dots.png" border="0"></td>');
    if($_SERVER['SCRIPT_NAME']=='/index.php') echo('<td class="navigationitemselected"><a href="/">Files</a></td>'); else echo('<td class="navigationitem"><a href="/">Files</a></td>');

    foreach(OC_UTIL::$NAVIGATION as $NAVI) {
      if($_SERVER['SCRIPT_NAME']==$NAVI['url']) echo('<td class="navigationitemselected"><a href="'.$NAVI['url'].'">'.$NAVI['name'].'</a></td>'); else echo('<td class="navigationitem"><a href="'.$NAVI['url'].'">'.$NAVI['name'].'</a></td>');
    }

    if($_SERVER['SCRIPT_NAME']=='/log/index.php') echo('<td class="navigationitemselected"><a href="/log">Log</a></td>'); else echo('<td class="navigationitem"><a href="/log">Log</a></td>');
    if($_SERVER['SCRIPT_NAME']=='/settings/index.php') echo('<td class="navigationitemselected"><a href="/settings">Settings</a></td>'); else echo('<td class="navigationitem"><a href="/settings">Settings</a></td>');
    echo('<td class="navigationitem"><a href="/?logoutbutton=1">Logout</a></td>');
    echo('</tr></table>');
  }


  /**
   * show the loginform
   *
   */
  public static function showloginform(){
    require('templates/loginform.php');;
  }

  /**
   * show an icon for a filetype
   *
   */
  public static function showicon($filetype){
    if($filetype=='dir'){ echo('<td><img src="/img/icons/folder.png" width="16" height="16"></td>');
    }elseif($filetype=='foo'){ echo('<td>foo</td>');
    }else{ echo('<td><img src="/img/icons/other.png" width="16" height="16"></td>');
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
    global $DBConnection;
    global $CONFIG_DBHOST;
    global $CONFIG_DBNAME;
    global $CONFIG_DBUSER;
    global $CONFIG_DBPWD;
    if(!isset($DBConnection)) {
      $DBConnection = @new mysqli($CONFIG_DBHOST, $CONFIG_DBUSER, $CONFIG_DBPWD,$CONFIG_DBNAME);
      if (mysqli_connect_errno()) {
        @ob_end_clean();
        echo('<html><head></head><body class="error"><br /><br /><center><b>can not connect to database.</center></body></html>');
        exit();
      }
    }
    $result = @$DBConnection->query($cmd);
    if (!$result) {
      $entry='DB Error: "'.$DBConnection->error.'"<br />';
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
    return(mysqli_insert_id($DBConnection));
  }

  /**
   * Returning number of rows in a result
   *
   * @param resultset $result
   * @return int
   */
  static function numrows($result) {
    if(!isset($result) or ($result == false)) return 0;
    $num= mysqli_num_rows($result);
    return($num);
  }

  /**
   * Returning number of affected rows
   *
   * @return int
   */
  static function affected_rows() {
    global $DBConnection;
    if(!isset($DBConnection) or ($DBConnection==false)) return 0;
    $num= mysqli_affected_rows($DBConnection);
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
    //return @mysqli_result($result, $i, $field);

    mysqli_data_seek($result,$i);
    if (is_string($field))
    $tmp=mysqli_fetch_array($result,MYSQLI_BOTH);
    else
    $tmp=mysqli_fetch_array($result,MYSQLI_NUM);
    $tmp=$tmp[$field];
    return($tmp);

  }

  /**
   * get data-array from resultset
   *
   * @param resultset $result
   * @return data
   */
  static function fetch_assoc($result) {
    return mysqli_fetch_assoc($result);
  }


  /**
   * Freeing resultset (performance)
   *
   * @param unknown_type $result
   * @return bool
   */
  static function free_result($result) {
    return @mysqli_free_result($result);
  }

}


?>
