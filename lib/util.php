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

	// Can be set up
	public static function setupFS( $user = "", $root = "files" ){ // configure the initial filesystem based on the configuration
		if(self::$fsSetup){ //setting up the filesystem twice can only lead to trouble
			return false;
		}

		$CONFIG_DATADIRECTORY_ROOT = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		$CONFIG_BACKUPDIRECTORY = OC_Config::getValue( "backupdirectory", OC::$SERVERROOT."/backup" );

		// Check if config folder is writable.
		if(!is_writable(OC::$SERVERROOT."/config/")) {
			$tmpl = new OC_Template( '', 'error', 'guest' );
			$tmpl->assign('errors',array(1=>array('error'=>"Can't write into config directory 'config'",'hint'=>"You can usually fix this by giving the webserver user write access to the config directory in owncloud")));
			$tmpl->printPage();
			exit;
		}

		// Check if apps folder is writable.
		if(OC_Config::getValue('writable_appsdir', true) && !is_writable(OC::$SERVERROOT."/apps/")) {
			$tmpl = new OC_Template( '', 'error', 'guest' );
			$tmpl->assign('errors',array(1=>array('error'=>"Can't write into apps directory 'apps'",'hint'=>"You can usually fix this by giving the webserver user write access to the config directory in owncloud")));
			$tmpl->printPage();
			exit;
		}
		
		
		// Create root dir.
		if(!is_dir($CONFIG_DATADIRECTORY_ROOT)){
			$success=@mkdir($CONFIG_DATADIRECTORY_ROOT);
            if(!$success) {
				$tmpl = new OC_Template( '', 'error', 'guest' );
				$tmpl->assign('errors',array(1=>array('error'=>"Can't create data directory (".$CONFIG_DATADIRECTORY_ROOT.")",'hint'=>"You can usually fix this by giving the webserver write access to the ownCloud directory '".OC::$SERVERROOT."' (in a terminal, use the command 'chown -R www-data:www-data /path/to/your/owncloud/install/data' ")));
				$tmpl->printPage();
				exit;
  			}
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_User::isLoggedIn()){
			$user = OC_User::getUser();
		}

		//first set up the local "root" storage
		if(!self::$rootMounted){
			OC_Filesystem::mount('OC_Filestorage_Local',array('datadir'=>$CONFIG_DATADIRECTORY_ROOT),'/');
			self::$rootMounted=true;
		}
		if( $user != "" ){ //if we aren't logged in, there is no use to set up the filesystem

			OC::$CONFIG_DATADIRECTORY = $CONFIG_DATADIRECTORY_ROOT."/$user/$root";
			if( !is_dir( OC::$CONFIG_DATADIRECTORY )){
				mkdir( OC::$CONFIG_DATADIRECTORY, 0755, true );
			}

			//jail the user into his "home" directory
			OC_Filesystem::init('/'.$user.'/'.$root);
			$quotaProxy=new OC_FileProxy_Quota();
			OC_FileProxy::register($quotaProxy);
			self::$fsSetup=true;
		}
	}

	public static function tearDownFS(){
		OC_Filesystem::tearDown();
		self::$fsSetup=false;
	}

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion(){
		return array(4,00,7);
	}

	/**
	 * get the current installed version string of ownCloud
	 * @return string
	 */
	public static function getVersionString(){
		return '4.0.7';
	}

        /**
         * get the current installed edition of ownCloud. There is the community edition that just returns an empty string and the enterprise edition that returns "Enterprise".
         * @return string
         */
        public static function getEditionString(){
                return '';
        }

	/**
	 * add a javascript file
	 *
	 * @param appid  $application
	 * @param filename  $file
	 */
	public static function addScript( $application, $file = null ){
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		if( !empty( $application )){
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
	public static function addStyle( $application, $file = null ){
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		if( !empty( $application )){
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
	public static function addHeader( $tag, $attributes, $text=''){
		self::$headers[]=array('tag'=>$tag,'attributes'=>$attributes,'text'=>$text);
	}

   /**
     * formats a timestamp in the "right" way
     *
     * @param int timestamp $timestamp
     * @param bool dateOnly option to ommit time from the result
     */
    public static function formatDate( $timestamp,$dateOnly=false){
		if(isset($_SESSION['timezone'])){//adjust to clients timezone if we know it
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
	public static function checkServer(){
		$CONFIG_DATADIRECTORY_ROOT = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		$CONFIG_BACKUPDIRECTORY = OC_Config::getValue( "backupdirectory", OC::$SERVERROOT."/backup" );
		$CONFIG_INSTALLED = OC_Config::getValue( "installed", false );
		$errors=array();

		//check for database drivers
		if(!(is_callable('sqlite_open') or class_exists('SQLite3')) and !is_callable('mysql_connect') and !is_callable('pg_connect')){
			$errors[]=array('error'=>'No database drivers (sqlite, mysql, or postgresql) installed.<br/>','hint'=>'');//TODO: sane hint
		}
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
		$CONFIG_DBNAME = OC_Config::getValue( "dbname", "owncloud" );

		//common hint for all file permissons error messages
		$permissionsHint="Permissions can usually be fixed by giving the webserver write access to the ownCloud directory";

		//check for correct file permissions
		if(!stristr(PHP_OS, 'WIN')){
                	$permissionsModHint="Please change the permissions to 0770 so that the directory cannot be listed by other users.";
			$prems=substr(decoct(@fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
			if(substr($prems,-1)!='0'){
				OC_Helper::chmodr($CONFIG_DATADIRECTORY_ROOT,0770);
				clearstatcache();
				$prems=substr(decoct(@fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
				if(substr($prems,2,1)!='0'){
					$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') is readable for other users<br/>','hint'=>$permissionsModHint);
				}
			}
			if( OC_Config::getValue( "enablebackup", false )){
				$prems=substr(decoct(@fileperms($CONFIG_BACKUPDIRECTORY)),-3);
				if(substr($prems,-1)!='0'){
					OC_Helper::chmodr($CONFIG_BACKUPDIRECTORY,0770);
					clearstatcache();
					$prems=substr(decoct(@fileperms($CONFIG_BACKUPDIRECTORY)),-3);
					if(substr($prems,2,1)!='0'){
						$errors[]=array('error'=>'Data directory ('.$CONFIG_BACKUPDIRECTORY.') is readable for other users<br/>','hint'=>$permissionsModHint);
					}
				}
			}
		}else{
			//TODO: permissions checks for windows hosts
		}
		if(is_dir($CONFIG_DATADIRECTORY_ROOT) and !is_writable($CONFIG_DATADIRECTORY_ROOT)){
			$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') not writable by ownCloud<br/>','hint'=>$permissionsHint);
		}

		// check if all required php modules are present
		if(!class_exists('ZipArchive')){
			$errors[]=array('error'=>'PHP module zip not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}

		if(!function_exists('mb_detect_encoding')){
			$errors[]=array('error'=>'PHP module mb multibyte not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}
		if(!function_exists('ctype_digit')){
			$errors[]=array('error'=>'PHP module ctype is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}
		if(!function_exists('json_encode')){
			$errors[]=array('error'=>'PHP module JSON is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}
		if(!function_exists('imagepng')){
			$errors[]=array('error'=>'PHP module GD is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}
		if(floatval(phpversion())<5.3){
			$errors[]=array('error'=>'PHP 5.3 is required.<br/>','hint'=>'Please ask your server administrator to update PHP to version 5.3 or higher. PHP 5.2 is no longer supported by ownCloud and the PHP community.');
		}
		if(!defined('PDO::ATTR_DRIVER_NAME')){
			$errors[]=array('error'=>'PHP PDO module is not installed.<br/>','hint'=>'Please ask your server administrator to install the module.');
		}

		return $errors;
	}

	public static function displayLoginPage($parameters = array()){
		if(isset($_COOKIE["username"])){
			$parameters["username"] = $_COOKIE["username"];
		} else {
			$parameters["username"] = '';
		}
		$sectoken=rand(1000000,9999999);
		$_SESSION['sectoken']=$sectoken;
		$parameters["sectoken"] = $sectoken;
		OC_Template::printGuestPage("", "login", $parameters);
	}


	/**
	* Check if the app is enabled, redirects to home if not
	*/
	public static function checkAppEnabled($app){
		if( !OC_App::isEnabled($app)){
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	* Check if the user is logged in, redirects to home if not. With
	* redirect URL parameter to the request URI.
	*/
	public static function checkLoggedIn(){
		// Check if we are a user
		if( !OC_User::isLoggedIn()){
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ).'?redirect_url='.urlencode($_SERVER["REQUEST_URI"]));
			exit();
		}
	}

	/**
	* Check if the user is a admin, redirects to home if not
	*/
	public static function checkAdminUser(){
		// Check if we are a user
		self::checkLoggedIn();
		if( !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	* Redirect to the user default page
	*/
	public static function redirectToDefaultPage(){
		OC_Log::write('core','redirectToDefaultPage',OC_Log::DEBUG);
		if(isset($_REQUEST['redirect_url']) && (substr($_REQUEST['redirect_url'], 0, strlen(OC::$WEBROOT)) == OC::$WEBROOT || $_REQUEST['redirect_url'][0] == '/')) {
			header( 'Location: '.$_REQUEST['redirect_url']);
		}
		else if (isset(OC::$REQUESTEDAPP) && !empty(OC::$REQUESTEDAPP)) {
			header( 'Location: '.OC::$WEBROOT.'/?app='.OC::$REQUESTEDAPP );
		}
		else {
			header( 'Location: '.OC::$WEBROOT.'/'.OC_Appconfig::getValue('core', 'defaultpage', '?app=files'));
		}
		exit();
	}

	/**
	 * @brief Register an get/post call. This is important to prevent CSRF attacks
	 * Todo: Write howto
	 * @return $token Generated token.
	 */
	public static function callRegister(){
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
					if($value+$maxtime<time()){
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
	public static function isCallRegistered(){
		//mamimum time before token exires
		$maxtime=(60*60);  // 1 hour
		if(isset($_GET['requesttoken'])) {
			$token=$_GET['requesttoken'];
		}elseif(isset($_POST['requesttoken'])){
			$token=$_POST['requesttoken'];
		}elseif(isset($_SERVER['HTTP_REQUESTTOKEN'])){
			$token=$_SERVER['HTTP_REQUESTTOKEN'];
		}else{
			//no token found.
			return false;
		}
		if(isset($_SESSION['requesttoken-'.$token])) {
			$timestamp=$_SESSION['requesttoken-'.$token];
			if($timestamp+$maxtime<time()){
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
	public static function callCheck(){
		if(!OC_Util::isCallRegistered()) {
			exit;
		}
	}
	
	/**
	 * @brief Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any string or array of strings before displaying it on a web page.
	 *
	 * @param string or array of strings
	 * @return array with sanitized strings or a single sinitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( &$value ){
		if (is_array($value) || is_object($value)) array_walk_recursive($value,'OC_Util::sanitizeHTML');
		else $value = htmlentities($value, ENT_QUOTES, 'UTF-8'); //Specify encoding for PHP<5.4
		return $value;
	}





        /**
	 * Check if the htaccess file is working buy creating a test file in the data directory and trying to access via http
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
                $url = OC_Helper::serverProtocol(). '://'  . OC_Helper::serverHost() . OC::$WEBROOT.'/data'.$filename;
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

