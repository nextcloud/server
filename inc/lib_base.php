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
// error_reporting(E_ALL | E_STRICT);
error_reporting(E_ALL); // MDB2 gives loads of strict error, disabling for now

date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// calculate the documentroot
$SERVERROOT=substr(__FILE__,0,-17);
$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',$SERVERROOT);
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$WEBROOT=substr($_SERVER["SCRIPT_NAME"],0,strlen($_SERVER["SCRIPT_NAME"])-strlen($SUBURI));


if($WEBROOT!='' and $WEBROOT[0]!=='/'){
	$WEBROOT='/'.$WEBROOT;
}

// set the right include path
// set_include_path(get_include_path().PATH_SEPARATOR.$SERVERROOT.PATH_SEPARATOR.$SERVERROOT.'/inc'.PATH_SEPARATOR.$SERVERROOT.'/config');

// define default config values
$CONFIG_INSTALLED=false;
$CONFIG_DATADIRECTORY=$SERVERROOT.'/data';
$CONFIG_BACKUPDIRECTORY=$SERVERROOT.'/backup';
$CONFIG_HTTPFORCESSL=false;
$CONFIG_ENABLEBACKUP=false;
$CONFIG_DATEFORMAT='j M Y G:i';
$CONFIG_DBNAME='owncloud';
$CONFIG_DBTYPE='sqlite';

// include the generated configfile
@include_once($SERVERROOT.'/config/config.php');


$CONFIG_DATADIRECTORY_ROOT=$CONFIG_DATADIRECTORY;// store this in a seperate variable so we can change the data directory to jail users.
// redirect to https site if configured
if(isset($CONFIG_HTTPFORCESSL) and $CONFIG_HTTPFORCESSL){
  if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') { 
    $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
    header("Location: $url"); 
    exit; 
  } 
}

// load core libs
oc_require_once('lib_files.php');
oc_require_once('lib_filesystem.php');
oc_require_once('lib_filestorage.php');
oc_require_once('lib_fileobserver.php');
oc_require_once('lib_log.php');
oc_require_once('lib_config.php');
oc_require_once('lib_user.php');
oc_require_once('lib_ocs.php');
@oc_require_once('MDB2.php');
@oc_require_once('MDB2/Schema.php');
oc_require_once('lib_connect.php');
oc_require_once('lib_remotestorage.php');


if(!is_dir($CONFIG_DATADIRECTORY_ROOT)){
	@mkdir($CONFIG_DATADIRECTORY_ROOT) or die("Can't create data directory ($CONFIG_DATADIRECTORY_ROOT), you can usually fix this by setting the owner of '$SERVERROOT' to the user that the web server uses (www-data for debian/ubuntu)");
}
if(OC_USER::isLoggedIn()){
	//jail the user in a seperate data folder
	$CONFIG_DATADIRECTORY=$CONFIG_DATADIRECTORY_ROOT.'/'.$_SESSION['username_clean'];
	if(!is_dir($CONFIG_DATADIRECTORY)){
		mkdir($CONFIG_DATADIRECTORY);
	}
	$rootStorage=new OC_FILESTORAGE_LOCAL(array('datadir'=>$CONFIG_DATADIRECTORY));
	if($CONFIG_ENABLEBACKUP){
		if(!is_dir($CONFIG_BACKUPDIRECTORY)){
			mkdir($CONFIG_BACKUPDIRECTORY);
		}
		if(!is_dir($CONFIG_BACKUPDIRECTORY.'/'.$_SESSION['username_clean'])){
			mkdir($CONFIG_BACKUPDIRECTORY.'/'.$_SESSION['username_clean']);
		}
		$backupStorage=new OC_FILESTORAGE_LOCAL(array('datadir'=>$CONFIG_BACKUPDIRECTORY.'/'.$_SESSION['username_clean']));
		$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
		$rootStorage->addObserver($backup);
	}
	OC_FILESYSTEM::mount($rootStorage,'/');
}



// check if the server is correctly configured for ownCloud
OC_UTIL::checkserver();

// listen for login or logout actions
OC_USER::logoutlisener();
$loginresult=OC_USER::loginlisener();

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
  public static function addScript($url){
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
  public static function checkServer(){
    global $SERVERROOT;
    global $CONFIG_DATADIRECTORY_ROOT;
    global $CONFIG_BACKUPDIRECTORY;
    global $CONFIG_ENABLEBACKUP;
    global $CONFIG_INSTALLED;
    $error='';
    if(!is_callable('sqlite_open') and !is_callable('mysql_connect')){
		$error.='No database drivers (sqlite or mysql) installed.<br/>';
    }
    global $CONFIG_DBTYPE;
    global $CONFIG_DBNAME;
    if($CONFIG_DBTYPE=='sqlite'){
		$file=$SERVERROOT.'/'.$CONFIG_DBNAME;
		if(file_exists($file)){
			$prems=substr(decoct(fileperms($file)),-3);
			if(substr($prems,2,1)!='0'){
				@chmod($file,0660);
				clearstatcache();
				$prems=substr(decoct(fileperms($file)),-3);
				if(substr($prems,2,1)!='0'){
					$error.='SQLite database file ('.$file.') is readable from the web<br/>';
				}
			}
		}
	}
	$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
	if(substr($prems,-1)!='0'){
		chmodr($CONFIG_DATADIRECTORY_ROOT,0770);
		clearstatcache();
		$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
		if(substr($prems,2,1)!='0'){
			$error.='Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') is readable from the web<br/>';
		}
	}
	if($CONFIG_ENABLEBACKUP){
		$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
		if(substr($prems,-1)!='0'){
			chmodr($CONFIG_BACKUPDIRECTORY,0770);
			clearstatcache();
			$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
			if(substr($prems,2,1)!='0'){
				$error.='Data directory ('.$CONFIG_BACKUPDIRECTORY.') is readable from the web<br/>';
			}
		}
	}
	if($error){
		die($error);
	}
    
  }

  /**
   * show the header of the web GUI
   *
   */
  public static function showHeader(){
    global $CONFIG_ADMINLOGIN;
    global $WEBROOT;
    oc_require('templates/header.php');;
  }
  
  /**
   * check if we need to use the layout optimized for smaller screen, currently only checks for iPhone/Android
   * @return bool
   */
	public static function hasSmallScreen(){
		$userAgent=strtolower($_SERVER['HTTP_USER_AGENT']);
		if(strpos($userAgent,'android') or strpos($userAgent,'iphone') or strpos($userAgent,'ipod')){//todo, add support for more devices
			return true;
		}
		return false;
	}

  /**
   * show the footer of the web GUI
   *
   */
  public static function showFooter(){
    global $CONFIG_FOOTEROWNERNAME;
    global $CONFIG_FOOTEROWNEREMAIL;
    oc_require('templates/footer.php');;
  }

  /**
   * add an navigationentry to the main navigation
   *
   * @param name $name
   * @param url  $url
   */
  public static function addNavigationEntry($name,$url) {
    $entry=array();
    $entry['name']=$name;
    $entry['url']=$url;
    OC_UTIL::$NAVIGATION[]=$entry;
  }

  /**
   * show the main navigation
   *
   */
  public static function showNavigation(){
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
	if(OC_USER::ingroup($_SESSION['username'],'admin')){
		if($_SERVER['SCRIPT_NAME']==$WEBROOT.'/admin/index.php') echo('<td class="navigationitemselected"><a href="'.$WEBROOT.'/admin">Admin Panel</a></td>'); else echo('<td class="navigationitem"><a href="'.$WEBROOT.'/admin">Admin Panel</a></td>');
	}
    echo('<td class="navigationitem"><a href="?logoutbutton=1">Logout</a></td>');
    echo('</tr></table>');
  }


  /**
   * show the loginform
   *
   */
  public static function showLoginForm(){
    global $loginresult;
    oc_require('templates/loginform.php');
  }


  /**
   * show an icon for a filetype
   *
   */
  public static function showIcon($filetype){
    global $WEBROOT;
    if($filetype=='dir'){ echo('<td><img src="'.$WEBROOT.'/img/icons/folder.png" width="16" height="16"></td>');
    }elseif($filetype=='foo'){ echo('<td>foo</td>');
    }else{ echo('<td><img src="'.$WEBROOT.'/img/icons/other.png" width="16" height="16"></td>');
    }
  }

	/**
	 * Load the plugins
	 */
	public static function loadPlugins() {
		global $CONFIG_LOADPLUGINS;
		global $SERVERROOT;

		$CONFIG_LOADPLUGINS = 'all';
		if ( 'all' !== $CONFIG_LOADPLUGINS ) {
			$plugins = explode(' ', $CONFIG_LOADPLUGINS);
		} else {
			$plugins = array();
			$fd = opendir($SERVERROOT . '/plugins');
			while ( false !== ($filename = readdir($fd)) ) {
				if ( $filename<>'.' AND $filename<>'..' AND ('.' != substr($filename, 0, 1)) ) {
					$plugins[] = $filename;
				}
			}
			closedir($fd);
		}
		if ( isset($plugins[0]) ) {
			foreach ( $plugins as $plugin ) {
				oc_require_once('/plugins/' . $plugin . '/lib_' . $plugin . '.php');
			}
		}
	}

}


/**
 * Class for database access
 *
 */
class OC_DB {
	static private $DBConnection=false;
	static private $schema=false;
	static private $affected=0;
	static private $result=false;
	/**
	* connect to the datbase if not already connected
	*/
	public static function connect(){
		global $CONFIG_DBNAME;
		global $CONFIG_DBHOST;
		global $CONFIG_DBUSER;
		global $CONFIG_DBPASSWORD;
		global $CONFIG_DBTYPE;
		global $DOCUMENTROOT;
		global $SERVERROOT;
		if(!self::$DBConnection){
			$options = array(
				'portability' => MDB2_PORTABILITY_ALL,
				'log_line_break' => '<br>',
				'idxname_format' => '%s',
				'debug' => true,
				'quote_identifier' => true,
			);
			if($CONFIG_DBTYPE=='sqlite'){
				$dsn = array(
					'phptype'  => 'sqlite',
					'database' => $SERVERROOT.'/'.$CONFIG_DBNAME,
					'mode'     => '0644',
				);
			}elseif($CONFIG_DBTYPE=='mysql'){
				$dsn = array(
					'phptype'  => 'mysql',
					'username' => $CONFIG_DBUSER,
					'password' => $CONFIG_DBPASSWORD,
					'hostspec' => $CONFIG_DBHOST,
					'database' => $CONFIG_DBNAME,
				);
			}elseif($CONFIG_DBTYPE=='pgsql'){
				$dsn = array(
					'phptype'  => 'pgsql',
					'username' => $CONFIG_DBUSER,
					'password' => $CONFIG_DBPASSWORD,
					'hostspec' => $CONFIG_DBHOST,
					'database' => $CONFIG_DBNAME,
				);
			}
			self::$DBConnection=&MDB2::factory($dsn,$options);
			if (PEAR::isError(self::$DBConnection)) {
				echo('<b>can not connect to database, using '.$CONFIG_DBTYPE.'. ('.self::$DBConnection->getUserInfo().')</center>');
				die(self::$DBConnection->getMessage());
			}
			self::$DBConnection->setFetchMode(MDB2_FETCHMODE_ASSOC);
			self::$schema=&MDB2_Schema::factory(self::$DBConnection);
		}
	}
	
	/**
	* executes a query on the database
	*
	* @param string $cmd
	* @return result-set
	*/
	static function query($cmd){
		global $CONFIG_DBTYPE;
		if(!trim($cmd)){
			return false;
		}
		OC_DB::connect();
		if($CONFIG_DBTYPE=='sqlite'){//fix differences between sql versions
			$cmd=str_replace('`','',$cmd);
		}elseif($CONFIG_DBTYPE=='pgsql'){
			$cmd=str_replace('`','"',$cmd);
		}
		$result=self::$DBConnection->exec($cmd);
		if (PEAR::isError($result)) {
			$entry='DB Error: "'.$result->getMessage().'"<br />';
			$entry.='Offending command was: '.$cmd.'<br />';
            error_log($entry);
			die($entry);
		}else{
			self::$affected=$result;
		}
		self::$result=$result;
		return $result;
	} 
  
  /**
   * executes a query on the database and returns the result in an array
   *
   * @param string $cmd
   * @return result-set
   */
	static function select($cmd){
		OC_DB::connect();
		global $CONFIG_DBTYPE;
		if($CONFIG_DBTYPE=='sqlite'){//fix differences between sql versions
			$cmd=str_replace('`','',$cmd);
		}elseif($CONFIG_DBTYPE=='pgsql'){
			$cmd=str_replace('`','"',$cmd);
		}
		$result=self::$DBConnection->queryAll($cmd);
		if (PEAR::isError($result)) {
			$entry='DB Error: "'.$result->getMessage().'"<br />';
			$entry.='Offending command was: '.$cmd.'<br />';
			die($entry);
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
		$queries=explode(';',$cmd);
		foreach($queries as $query){
			OC_DB::query($query);
		}
		return true;
	}


	/**
	* closing a db connection
	*
	* @return bool
	*/
	static function close() {
		self::$DBConnection->disconnect();
		self::$DBConnection=false;
	}


	/**
	* Returning primarykey if last statement was an insert.
	*
	* @return primarykey
	*/
	static function insertid() {
		$id=self::$DBConnection->lastInsertID();
		return $id;
	}

	/**
	* Returning number of rows in a result
	*
	* @param resultset $result
	* @return int
	*/
	static function numrows($result) {
		$result->numRows();
	}
	/**
	* Returning number of affected rows
	*
	* @return int
	*/
	static function affected_rows() {
		return self::$affected;
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
		$tmp=$result->fetchRow(MDB2_FETCHMODE_ASSOC,$i);
		$tmp=$tmp[$field];
		return($tmp);
	}

	/**
	* get data-array from resultset
	*
	* @param resultset $result
	* @return data
	*/
	static function fetch_assoc($result){
		return $result->fetchRow(MDB2_FETCHMODE_ASSOC);
	}
	
	/**
	* Freeing resultset (performance)
	*
	* @param unknown_type $result
	* @return bool
	*/
	static function free_result() {
		if(self::$result){
			self::$result->free();
			self::$result=false;
		}
	}
	
	static public function disconnect(){
		if(self::$DBConnection){
			self::$DBConnection->disconnect();
			self::$DBConnection=false;
		}
	}
	
	/**
	* escape strings so they can be used in queries
	*
	* @param string string
	* @return string
	*/
	static function escape($string){
		OC_DB::connect();
		return self::$DBConnection->escape($string);
	}
	
	static function getDbStructure($file){
		OC_DB::connect();
		$definition = self::$schema->getDefinitionFromDatabase();
		$dump_options = array(
			'output_mode' => 'file',
			'output' => $file,
			'end_of_line' => "\n"
		);
		self::$schema->dumpDatabase($definition, $dump_options, MDB2_SCHEMA_DUMP_STRUCTURE);
	}
	
	static function createDbFromStructure($file){
		OC_DB::connect();
		global $CONFIG_DBNAME;
		global $CONFIG_DBTABLEPREFIX;
		$content=file_get_contents($file);
		$file2=tempnam(sys_get_temp_dir(),'oc_db_scheme_');
		echo $content;
		$content=str_replace('*dbname*',$CONFIG_DBNAME,$content);
		$content=str_replace('*dbprefix*',$CONFIG_DBTABLEPREFIX,$content);
		echo $content;
		file_put_contents($file2,$content);
		$definition=@self::$schema->parseDatabaseDefinitionFile($file2);
		unlink($file2);
		if($definition instanceof MDB2_Schema_Error){
			die($definition->getMessage() . ': ' . $definition->getUserInfo());
		}
		$ret=@self::$schema->createDatabase($definition);
		if($ret instanceof MDB2_Error) {
			die ($ret->getMessage() . ': ' . $ret->getUserInfo());
		}else{
			return true;
		}
	}
}


//custom require/include functions because not all hosts allow us to set the include path
function oc_require($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;
	if(is_file($file)){
		return require($file);
	}elseif(is_file($SERVERROOT.'/'.$file)){
		return require($SERVERROOT.'/'.$file);
	}elseif(is_file($SERVERROOT.'/inc/'.$file)){
		return require($SERVERROOT.'/inc/'.$file);
	}
}

function oc_require_once($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;
	if(is_file($file)){
		return require_once($file);
	}elseif(is_file($SERVERROOT.'/'.$file)){
		return require_once($SERVERROOT.'/'.$file);
	}elseif(is_file($SERVERROOT.'/inc/'.$file)){
		return require_once($SERVERROOT.'/inc/'.$file);
	}
}

function oc_include($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;
	if(is_file($file)){
		return include($file);
	}elseif(is_file($SERVERROOT.'/'.$file)){
		return include($SERVERROOT.'/'.$file);
	}elseif(is_file($SERVERROOT.'/inc/'.$file)){
		return include($SERVERROOT.'/inc/'.$file);
	}
}

function oc_include_once($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;
	if(is_file($SERVERROOT.'/'.$file)){
		return include_once($SERVERROOT.'/'.$file);
	}elseif(is_file($SERVERROOT.'/inc/'.$file)){
		return include_once($SERVERROOT.'/inc/'.$file);
	}elseif(is_file($file)){
		return include_once($file);
	}
}

function chmodr($path, $filemode) { 
// 	echo "$path<br/>";
	if (!is_dir($path)) 
		return chmod($path, $filemode); 
	$dh = opendir($path); 
	while (($file = readdir($dh)) !== false) { 
		if($file != '.' && $file != '..') { 
			$fullpath = $path.'/'.$file; 
			if(is_link($fullpath)) 
				return FALSE; 
			elseif(!is_dir($fullpath) && !chmod($fullpath, $filemode)) 
					return FALSE; 
			elseif(!chmodr($fullpath, $filemode)) 
				return FALSE; 
		} 
	} 
	closedir($dh); 
	if(chmod($path, $filemode)) 
		return TRUE; 
	else 
		return FALSE; 
}

?>
