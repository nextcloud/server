<?php

/**
 * Class for utility functions
 *
 */
class OC_Util {
	public static $scripts=array();
	public static $styles=array();
	public static $headers=array();
	private static $rootMounted=false;
	private static $fsSetup=false;
	public static $core_styles=array();
	public static $core_scripts=array();

	// Can be set up
	public static function setupFS( $user = '' ) {// configure the initial filesystem based on the configuration
		if(self::$fsSetup) {//setting up the filesystem twice can only lead to trouble
			return false;
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_User::isLoggedIn()) {
			$user = OC_User::getUser();
		}

		// the filesystem will finish when $user is not empty,
		// mark fs setup here to avoid doing the setup from loading
		// OC_Filesystem
		if ($user != '') {
			self::$fsSetup=true;
		}

		$CONFIG_DATADIRECTORY = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		//first set up the local "root" storage
		if(!self::$rootMounted) {
			OC_Filesystem::mount('OC_Filestorage_Local',array('datadir'=>$CONFIG_DATADIRECTORY),'/');
			self::$rootMounted=true;
		}

		if( $user != "" ) { //if we aren't logged in, there is no use to set up the filesystem
			$user_dir = '/'.$user.'/files';
			$user_root = OC_User::getHome($user);
			$userdirectory = $user_root . '/files';
			if( !is_dir( $userdirectory )) {
				mkdir( $userdirectory, 0755, true );
			}
			//jail the user into his "home" directory
			OC_Filesystem::mount('OC_Filestorage_Local', array('datadir' => $user_root), $user);
			OC_Filesystem::init($user_dir);
			$quotaProxy=new OC_FileProxy_Quota();
			OC_FileProxy::register($quotaProxy);
			// Load personal mount config
			if (is_file($user_root.'/mount.php')) {
				$mountConfig = include($user_root.'/mount.php');
				if (isset($mountConfig['user'][$user])) {
					foreach ($mountConfig['user'][$user] as $mountPoint => $options) {
						OC_Filesystem::mount($options['class'], $options['options'], $mountPoint);
					}
				}

				$mtime=filemtime($user_root.'/mount.php');
				$previousMTime=OC_Preferences::getValue($user,'files','mountconfigmtime',0);
				if($mtime>$previousMTime) {//mount config has changed, filecache needs to be updated
					OC_FileCache::clear($user);
					OC_Preferences::setValue($user,'files','mountconfigmtime',$mtime);
				}
			}
			OC_Hook::emit('OC_Filesystem', 'setup', array('user' => $user, 'user_dir' => $user_dir));
		}
	}

	public static function tearDownFS() {
		OC_Filesystem::tearDown();
		self::$fsSetup=false;
	}

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion() {
		// hint: We only can count up. So the internal version number of ownCloud 4.5 will be 4,9,0. This is not visible to the user
		return array(4,84,10);
	}

	/**
	 * get the current installed version string of ownCloud
	 * @return string
	 */
	public static function getVersionString() {
		return '4.5 beta 4';
	}

	/**
	 * get the current installed edition of ownCloud. There is the community edition that just returns an empty string and the enterprise edition that returns "Enterprise".
	 * @return string
	 */
	public static function getEditionString() {
			return '';
	}

	/**
	 * add a javascript file
	 *
	 * @param appid  $application
	 * @param filename  $file
	 */
	public static function addScript( $application, $file = null ) {
		if( is_null( $file )) {
			$file = $application;
			$application = "";
		}
		if( !empty( $application )) {
			self::$scripts[] = "$application/js/$file";
		}else{
			self::$scripts[] = "js/$file";
		}
	}

	/**
	 * add a css file
	 *
	 * @param appid  $application
	 * @param filename  $file
	 */
	public static function addStyle( $application, $file = null ) {
		if( is_null( $file )) {
			$file = $application;
			$application = "";
		}
		if( !empty( $application )) {
			self::$styles[] = "$application/css/$file";
		}else{
			self::$styles[] = "css/$file";
		}
	}

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader( $tag, $attributes, $text='') {
		self::$headers[]=array('tag'=>$tag,'attributes'=>$attributes,'text'=>$text);
	}

	/**
	 * formats a timestamp in the "right" way
	 *
	 * @param int timestamp $timestamp
	 * @param bool dateOnly option to ommit time from the result
	 */
    public static function formatDate( $timestamp,$dateOnly=false) {
		if(isset($_SESSION['timezone'])) {//adjust to clients timezone if we know it
			$systemTimeZone = intval(date('O'));
			$systemTimeZone=(round($systemTimeZone/100,0)*60)+($systemTimeZone%100);
			$clientTimeZone=$_SESSION['timezone']*60;
			$offset=$clientTimeZone-$systemTimeZone;
			$timestamp=$timestamp+$offset*60;
		}
		$timeformat=$dateOnly?'F j, Y':'F j, Y, H:i';
		return date($timeformat,$timestamp);
    }

	/**
	 * Shows a pagenavi widget where you can jump to different pages.
	 *
	 * @param int $pagecount
	 * @param int $page
	 * @param string $url
	 * @return OC_Template
	 */
	public static function getPageNavi($pagecount,$page,$url) {

		$pagelinkcount=8;
		if ($pagecount>1) {
			$pagestart=$page-$pagelinkcount;
			if($pagestart<0) $pagestart=0;
			$pagestop=$page+$pagelinkcount;
			if($pagestop>$pagecount) $pagestop=$pagecount;

			$tmpl = new OC_Template( '', 'part.pagenavi', '' );
			$tmpl->assign('page',$page);
			$tmpl->assign('pagecount',$pagecount);
			$tmpl->assign('pagestart',$pagestart);
			$tmpl->assign('pagestop',$pagestop);
			$tmpl->assign('url',$url);
			return $tmpl;
		}
	}



	/**
	 * check if the current server configuration is suitable for ownCloud
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer() {
		$errors=array();

		$web_server_restart= false;
		//check for database drivers
		if(!(is_callable('sqlite_open') or class_exists('SQLite3')) and !is_callable('mysql_connect') and !is_callable('pg_connect')) {
			$errors[]=array('error'=>'No database drivers (sqlite, mysql, or postgresql) installed.<br/>','hint'=>'');//TODO: sane hint
			$web_server_restart= true;
		}

		//common hint for all file permissons error messages
		$permissionsHint="Permissions can usually be fixed by giving the webserver write access to the ownCloud directory";

		// Check if config folder is writable.
		if(!is_writable(OC::$SERVERROOT."/config/") or !is_readable(OC::$SERVERROOT."/config/")) {
			$errors[]=array('error'=>"Can't write into config directory 'config'",'hint'=>"You can usually fix this by giving the webserver user write access to the config directory in owncloud");
		}

		// Check if there is a writable install folder.
		if(OC_Config::getValue('appstoreenabled', true)) {
			if( OC_App::getInstallPath() === null  || !is_writable(OC_App::getInstallPath()) || !is_readable(OC_App::getInstallPath()) ) {
				$errors[]=array('error'=>"Can't write into apps directory",'hint'=>"You can usually fix this by giving the webserver user write access to the apps directory
				in owncloud or disabling the appstore in the config file.");
			}
		}

		$CONFIG_DATADIRECTORY = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		//check for correct file permissions
		if(!stristr(PHP_OS, 'WIN')) {
			$permissionsModHint="Please change the permissions to 0770 so that the directory cannot be listed by other users.";
			$prems=substr(decoct(@fileperms($CONFIG_DATADIRECTORY)),-3);
			if(substr($prems,-1)!='0') {
				OC_Helper::chmodr($CONFIG_DATADIRECTORY,0770);
				clearstatcache();
				$prems=substr(decoct(@fileperms($CONFIG_DATADIRECTORY)),-3);
				if(substr($prems,2,1)!='0') {
					$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY.') is readable for other users<br/>','hint'=>$permissionsModHint);
				}
			}
			if( OC_Config::getValue( "enablebackup", false )) {
				$CONFIG_BACKUPDIRECTORY = OC_Config::getValue( "backupdirectory", OC::$SERVERROOT."/backup" );
				$prems=substr(decoct(@fileperms($CONFIG_BACKUPDIRECTORY)),-3);
				if(substr($prems,-1)!='0') {
					OC_Helper::chmodr($CONFIG_BACKUPDIRECTORY,0770);
					clearstatcache();
					$prems=substr(decoct(@fileperms($CONFIG_BACKUPDIRECTORY)),-3);
					if(substr($prems,2,1)!='0') {
						$errors[]=array('error'=>'Data directory ('.$CONFIG_BACKUPDIRECTORY.') is readable for other users<br/>','hint'=>$permissionsModHint);
					}
				}
			}
		}else{
			//TODO: permissions checks for windows hosts
		}
		// Create root dir.
		if(!is_dir($CONFIG_DATADIRECTORY)) {
			$success=@mkdir($CONFIG_DATADIRECTORY);
			if(!$success) {
				$errors[]=array('error'=>"Can't create data directory (".$CONFIG_DATADIRECTORY.")",'hint'=>"You can usually fix this by giving the webserver write access to the ownCloud directory '".OC::$SERVERROOT."' (in a terminal, use the command 'chown -R www-data:www-data /path/to/your/owncloud/install/data' ");
			}
		} else if(!is_writable($CONFIG_DATADIRECTORY) or !is_readable($CONFIG_DATADIRECTORY)) {
			$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY.') not writable by ownCloud<br/>','hint'=>$permissionsHint);
		}

		// check if all required php modules are present
		if(!class_exists('ZipArchive')) {
			$errors[]=array('error'=>'PHP module zip not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}

		if(!function_exists('mb_detect_encoding')) {
			$errors[]=array('error'=>'PHP module mb multibyte not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}
		if(!function_exists('ctype_digit')) {
			$errors[]=array('error'=>'PHP module ctype is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}
		if(!function_exists('json_encode')) {
			$errors[]=array('error'=>'PHP module JSON is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}
		if(!function_exists('imagepng')) {
			$errors[]=array('error'=>'PHP module GD is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}
		if(!function_exists('gzencode')) {
			$errors[]=array('error'=>'PHP module zlib is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}
		if(floatval(phpversion())<5.3) {
			$errors[]=array('error'=>'PHP 5.3 is required.<br/>','hint'=>'Please ask your server administrator to update PHP to version 5.3 or higher. PHP 5.2 is no longer supported by ownCloud and the PHP community.');
			$web_server_restart= false;
		}
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			$errors[]=array('error'=>'PHP PDO module is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
			$web_server_restart= false;
		}

		if($web_server_restart) {
			$errors[]=array('error'=>'PHP modules have been installed, but they are still listed as missing?<br/>','hint'=>'Please ask your server administrator to restart the web server.');
		}

		return $errors;
	}

	public static function displayLoginPage($display_lostpassword) {
		$parameters = array();
		$parameters['display_lostpassword'] = $display_lostpassword;
		if (!empty($_POST['user'])) {
			$parameters["username"] =
				OC_Util::sanitizeHTML($_POST['user']).'"';
			$parameters['user_autofocus'] = false;
		} else {
			$parameters["username"] = '';
			$parameters['user_autofocus'] = true;
		}
		$sectoken=rand(1000000,9999999);
		$_SESSION['sectoken']=$sectoken;
		$parameters["sectoken"] = $sectoken;
		if (isset($_REQUEST['redirect_url'])) {
			$redirect_url = OC_Util::sanitizeHTML($_REQUEST['redirect_url']);
		} else {
			$redirect_url = $_SERVER['REQUEST_URI'];
		}
		$parameters['redirect_url'] = $redirect_url;
		OC_Template::printGuestPage("", "login", $parameters);
	}


	/**
	* Check if the app is enabled, redirects to home if not
	*/
	public static function checkAppEnabled($app) {
		if( !OC_App::isEnabled($app)) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	* Check if the user is logged in, redirects to home if not. With
	* redirect URL parameter to the request URI.
	*/
	public static function checkLoggedIn() {
		// Check if we are a user
		if( !OC_User::isLoggedIn()) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php', array('redirect_url' => urlencode($_SERVER["REQUEST_URI"]))));
			exit();
		}
	}

	/**
	* Check if the user is a admin, redirects to home if not
	*/
	public static function checkAdminUser() {
		// Check if we are a user
		self::checkLoggedIn();
		if( !OC_Group::inGroup( OC_User::getUser(), 'admin' )) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	* Check if the user is a subadmin, redirects to home if not
	* @return array $groups where the current user is subadmin
	*/
	public static function checkSubAdminUser() {
		// Check if we are a user
		self::checkLoggedIn();
		if(OC_Group::inGroup(OC_User::getUser(),'admin')) {
			return true;
		}
		if(!OC_SubAdmin::isSubAdmin(OC_User::getUser())) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
		return true;
	}

	/**
	* Redirect to the user default page
	*/
	public static function redirectToDefaultPage() {
		if(isset($_REQUEST['redirect_url']) && (substr($_REQUEST['redirect_url'], 0, strlen(OC::$WEBROOT)) == OC::$WEBROOT || $_REQUEST['redirect_url'][0] == '/')) {
			$location = $_REQUEST['redirect_url'];
		}
		else if (isset(OC::$REQUESTEDAPP) && !empty(OC::$REQUESTEDAPP)) {
			$location = OC_Helper::linkToAbsolute( OC::$REQUESTEDAPP, 'index.php' );
		}
		else {
			$defaultpage = OC_Appconfig::getValue('core', 'defaultpage');
			if ($defaultpage) {
				$location = OC_Helper::makeURLAbsolute(OC::$WEBROOT.'/'.$defaultpage);
			}
			else {
				$location = OC_Helper::linkToAbsolute( 'files', 'index.php' );
			}
		}
		OC_Log::write('core', 'redirectToDefaultPage: '.$location, OC_Log::DEBUG);
		header( 'Location: '.$location );
		exit();
	}

	/**
	 * get an id unqiue for this instance
	 * @return string
	 */
	public static function getInstanceId() {
		$id=OC_Config::getValue('instanceid',null);
		if(is_null($id)) {
			$id=uniqid();
			OC_Config::setValue('instanceid',$id);
		}
		return $id;
	}

	/**
	 * @brief Register an get/post call. This is important to prevent CSRF attacks
	 * Todo: Write howto
	 * @return $token Generated token.
	 */
	public static function callRegister() {
		//mamimum time before token exires
		$maxtime=(60*60);  // 1 hour

		// generate a random token.
		$token=mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000);

		// store the token together with a timestamp in the session.
		$_SESSION['requesttoken-'.$token]=time();

		// cleanup old tokens garbage collector
		// only run every 20th time so we don't waste cpu cycles
		if(rand(0,20)==0) {
			foreach($_SESSION as $key=>$value) {
				// search all tokens in the session
				if(substr($key,0,12)=='requesttoken') {
					if($value+$maxtime<time()) {
						// remove outdated tokens
						unset($_SESSION[$key]);
					}
				}
			}
		}
		// return the token
		return($token);
	}


	/**
	 * @brief Check an ajax get/post call if the request token is valid.
	 * @return boolean False if request token is not set or is invalid.
	 */
	public static function isCallRegistered() {
		//mamimum time before token exires
		$maxtime=(60*60);  // 1 hour
		if(isset($_GET['requesttoken'])) {
			$token=$_GET['requesttoken'];
		}elseif(isset($_POST['requesttoken'])) {
			$token=$_POST['requesttoken'];
		}elseif(isset($_SERVER['HTTP_REQUESTTOKEN'])) {
			$token=$_SERVER['HTTP_REQUESTTOKEN'];
		}else{
			//no token found.
			return false;
		}
		if(isset($_SESSION['requesttoken-'.$token])) {
			$timestamp=$_SESSION['requesttoken-'.$token];
			if($timestamp+$maxtime<time()) {
				return false;
			}else{
				//token valid
				return true;
			}
		}else{
			return false;
		}
	}

	/**
	 * @brief Check an ajax get/post call if the request token is valid. exit if not.
	 * Todo: Write howto
	 */
	public static function callCheck() {
		if(!OC_Util::isCallRegistered()) {
			exit;
		}
	}

	/**
	 * @brief Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string or array of strings
	 * @return array with sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( &$value ) {
		if (is_array($value) || is_object($value)) array_walk_recursive($value,'OC_Util::sanitizeHTML');
		else $value = htmlentities($value, ENT_QUOTES, 'UTF-8'); //Specify encoding for PHP<5.4
		return $value;
	}


	/**
	 * Check if the htaccess file is working by creating a test file in the data directory and trying to access via http
	 */
	public static function ishtaccessworking() {
		// testdata
		$filename='/htaccesstest.txt';
		$testcontent='testcontent';

		// creating a test file
		$testfile = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" ).'/'.$filename;
		$fp = @fopen($testfile, 'w');
		@fwrite($fp, $testcontent);
		@fclose($fp);

		// accessing the file via http
		$url = OC_Helper::makeURLAbsolute(OC::$WEBROOT.'/data'.$filename);
		$fp = @fopen($url, 'r');
		$content=@fread($fp, 2048);
		@fclose($fp);

		// cleanup
		@unlink($testfile);

		// does it work ?
		if($content==$testcontent) {
			return(false);
		}else{
			return(true);
		}
	}

}
