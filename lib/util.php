<?php

/**
 * Class for utility functions
 *
 */
class OC_Util {
	public static $scripts=array();
	public static $styles=array();
	public static $headers=array();
	private static $fsSetup=false;

	// Can be set up
	public static function setupFS( $user = "", $root = "files" ){// configure the initial filesystem based on the configuration
		if(self::$fsSetup){//setting up the filesystem twice can only lead to trouble
			return false;
		}

		$CONFIG_DATADIRECTORY_ROOT = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		$CONFIG_BACKUPDIRECTORY = OC_Config::getValue( "backupdirectory", OC::$SERVERROOT."/backup" );

		// Create root dir
		if(!is_dir($CONFIG_DATADIRECTORY_ROOT)){
			$success=@mkdir($CONFIG_DATADIRECTORY_ROOT);
                        if(!$success) {
				$tmpl = new OC_Template( '', 'error', 'guest' );
				$tmpl->assign('errors',array(1=>array('error'=>"Can't create data directory (".$CONFIG_DATADIRECTORY_ROOT.")",'hint'=>"You can usually fix this by setting the owner of '".OC::$SERVERROOT."' to the user that the web server uses (".OC_Util::checkWebserverUser().")")));
				$tmpl->printPage();
				exit;
  			}
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_User::isLoggedIn()){
			$user = OC_User::getUser();
		}

		if( $user != "" ){ //if we aren't logged in, there is no use to set up the filesystem
			//first set up the local "root" storage and the backupstorage if needed
			$rootStorage=OC_Filesystem::createStorage('local',array('datadir'=>$CONFIG_DATADIRECTORY_ROOT));
// 			if( OC_Config::getValue( "enablebackup", false )){
// 				// This creates the Directorys recursively
// 				if(!is_dir( "$CONFIG_BACKUPDIRECTORY/$user/$root" )){
// 					mkdir( "$CONFIG_BACKUPDIRECTORY/$user/$root", 0755, true );
// 				}
// 				$backupStorage=OC_Filesystem::createStorage('local',array('datadir'=>$CONFIG_BACKUPDIRECTORY));
// 				$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
// 				$rootStorage->addObserver($backup);
// 			}
			OC_Filesystem::mount($rootStorage,'/');

			// TODO add this storage provider in a proper way
			$sharedStorage = OC_Filesystem::createStorage('shared',array('datadir'=>'/'.OC_User::getUser().'/files/Shared'));
			OC_Filesystem::mount($sharedStorage,'/'.OC_User::getUser().'/files/Shared/');

			OC::$CONFIG_DATADIRECTORY = $CONFIG_DATADIRECTORY_ROOT."/$user/$root";
			if( !is_dir( OC::$CONFIG_DATADIRECTORY )){
				mkdir( OC::$CONFIG_DATADIRECTORY, 0755, true );
			}

// TODO: find a cool way for doing this
// 			//set up the other storages according to the system settings
// 			foreach($CONFIG_FILESYSTEM as $storageConfig){
// 				if(OC_Filesystem::hasStorageType($storageConfig['type'])){
// 					$arguments=$storageConfig;
// 					unset($arguments['type']);
// 					unset($arguments['mountpoint']);
// 					$storage=OC_Filesystem::createStorage($storageConfig['type'],$arguments);
// 					if($storage){
// 						OC_Filesystem::mount($storage,$storageConfig['mountpoint']);
// 					}
// 				}
// 			}

			//jail the user into his "home" directory
			OC_Filesystem::chroot("/$user/$root");
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
		return array(1,91,0);
	}

	/**
	 * get the current installed version string of ownCloud
	 * @return string
	 */
	public static function getVersionString(){
		return '2 beta 2';
	}

	/**
	 * add a javascript file
	 *
	 * @param url  $url
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
	 * @param url  $url
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
	 * @param array $attributes array of attrobutes for the element
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
				$systemTimeZone = intval(exec('date +%z'));
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
		if(!is_callable('sqlite_open') and !is_callable('mysql_connect')){
			$errors[]=array('error'=>'No database drivers (sqlite or mysql) installed.<br/>','hint'=>'');//TODO: sane hint
		}
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
		$CONFIG_DBNAME = OC_Config::getValue( "dbname", "owncloud" );
                $serverUser=OC_Util::checkWebserverUser();

		//common hint for all file permissons error messages
		$permissionsHint="Permissions can usually be fixed by setting the owner of the file or directory to the user the web server runs as ($serverUser)";

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

		return $errors;
	}


	/**
	* Try to get the username the httpd server runs on, used in hints
	*/
        public static function checkWebserverUser(){
		$stat=stat($_SERVER['DOCUMENT_ROOT']);
		if(is_callable('posix_getpwuid')){
			$serverUser=posix_getpwuid($stat['uid']);
			$serverUser='\''.$serverUser['name'].'\'';
		}elseif(exec('whoami')){
                	$serverUser=exec('whoami');
                }else{
			$serverUser='\'www-data\' for ubuntu/debian'; //TODO: try to detect the distro and give a guess based on that
		}
                return $serverUser;
        }


	/**
	* Check if the user is logged in, redirects to home if not
	*/
	public static function checkLoggedIn(){
		// Check if we are a user
		if( !OC_User::isLoggedIn()){
			header( 'Location: '.OC_Helper::linkTo( '', 'index.php' , true));
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
			header( 'Location: '.OC_Helper::linkTo( '', 'index.php' , true));
			exit();
		}
	}

	/**
	* Redirect to the user default page
	*/
	public static function redirectToDefaultPage(){
		header( 'Location: '.OC::$WEBROOT.'/'.OC_Appconfig::getValue('core', 'defaultpage', 'files/index.php'));
		exit();
	}
}
