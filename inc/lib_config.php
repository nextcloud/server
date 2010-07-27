<?php
class OC_CONFIG{
   /**
   * show the configform
   *
   */
  public static function showConfigForm(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    global $CONFIG_DATADIRECTORY;
    global $CONFIG_HTTPFORCESSL;
    global $CONFIG_DATEFORMAT;
    global $CONFIG_DBNAME;
    oc_require('templates/configform.php');
  }
  
  /**
   * show the configform
   *
   */
  public static function showAdminForm(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    global $CONFIG_DATADIRECTORY;
    global $CONFIG_HTTPFORCESSL;
    global $CONFIG_DATEFORMAT;
    global $CONFIG_DBNAME;
	global $CONFIG_DBTABLEPREFIX;
    global $CONFIG_INSTALLED;
		$allow=false;
		if(!$CONFIG_INSTALLED){
			$allow=true;
		}elseif(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				$allow=true;
			}
		}
    if($allow){
		oc_require('templates/adminform.php');
	}
  }

	public static function createUserLisener(){
		if(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				if(isset($_POST['new_username']) and isset($_POST['new_password'])){
					if(OC_USER::createuser($_POST['new_username'],$_POST['new_password'])){
						return 'user successfully created';
					}else{
						return 'error while trying to create user';
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
	}
	
	public static function createGroupLisener(){
		if(OC_USER::isLoggedIn()){
			if(isset($_POST['creategroup']) and $_POST['creategroup']==1){
				if(OC_USER::creategroup($_POST['groupname'])){
					if(OC_USER::addtogroup($_SESSION['username'],$_POST['groupname'])){
						return 'group successfully created';
					}else{
						return 'error while trying to add user to the new created group';
					}
				}else{
					return 'error while trying to create group';
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	
	/**
   * lisen for configuration changes
   *
   */
	public static function configLisener(){
		if(OC_USER::isLoggedIn()){
			if(isset($_POST['config']) and $_POST['config']==1){
				$error='';
				if(!OC_USER::checkpassword($_SESSION['username'],$_POST['currentpassword'])){
					$error.='wrong password<br />';
				}else{
					if(isset($_POST['changepass']) and $_POST['changepass']==1){
						if(!isset($_POST['password'])     or empty($_POST['password']))     $error.='password not set<br />';
						if(!isset($_POST['password2'])    or empty($_POST['password2']))    $error.='retype password not set<br />';
						if($_POST['password']<>$_POST['password2'] )                        $error.='passwords are not the same<br />';
						if(empty($error)){
							if(!OC_USER::setpassword($_SESSION['username'],$_POST['password'])){
								$error.='error while trying to set password<br />';
							}
						}
					}
				}
				return $error;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * Write the configuration to the `config.php` file
	 *
	 * $configuration contains key/value pairs
	 *   - the key is the option name without the 'CONFIG_' prefix
	 *   - the value is a string, a boolean or a number
	 *
	 * @param array $configuration is an associative array
	 */
	protected static function saveConfiguration($configuration) {
		global $SERVERROOT;

		$configContent = '<?php';
		foreach ( $configuration as $key => $value ) {
			if ( is_string($value) ) {
				$configContent .= "\n\$CONFIG_$key = '$value';";  // e.g. $CONFIG_DBTYPE = 'mysql';
			} else if ( is_bool($value) ) {
				$value = $value ? 'true' : 'false';
				$configContent .= "\n\$CONFIG_$key = $value;";  // e.g. $CONFIG_INSTALLED = true;
			} else if ( is_numeric($value) ) {
				$configContent .= "\n\$CONFIG_$key = $value;";  // e.g. $CONFIG_PI = 3.14;
			}
		}
		$filename = "$SERVERROOT/config/config.php";
		file_put_contents($filename, $configContent);
	}
	
	/**
	* lisen for admin configuration changes and write it to the file
	*4bd0be1185e76
	*/
	public static function writeAdminLisener(){
		global $CONFIG_INSTALLED;
		$allow=false;
		if(!$CONFIG_INSTALLED){
			$allow=true;
		}elseif(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				$allow=true;
			}
		}
		if($allow){
			global $DOCUMENTROOT;
			global $SERVERROOT;
			global $WEBROOT;
			global $CONFIG_DBHOST;
			global $CONFIG_DBNAME;
			global $CONFIG_DBTABLEPREFIX;
			global $CONFIG_INSTALLED;
			global $CONFIG_DBUSER;
			global $CONFIG_DBPASSWORD;
			global $CONFIG_DBTYPE;
			global $CONFIG_ADMINLOGIN;
			global $CONFIG_ADMINPASSWORD;
			if(isset($_POST['set_config'])){
				
				//checkdata
				$error='';
				$FIRSTRUN=!$CONFIG_INSTALLED;
				if(!$FIRSTRUN){
					if(!OC_USER::login($_SESSION['username'],$_POST['currentpassword'])){
					$error.='wrong password<br />';
					}
				}
				
				if((!isset($_POST['adminlogin'])        or empty($_POST['adminlogin'])) and $FIRSTRUN)        $error.='admin login not set<br />';
				if((!isset($_POST['adminpassword'])     or empty($_POST['adminpassword'])) and $FIRSTRUN)     $error.='admin password not set<br />';
				if((!isset($_POST['adminpassword2'])    or empty($_POST['adminpassword2'])) and $FIRSTRUN)    $error.='retype admin password not set<br />';
				if(!isset($_POST['datadirectory'])     or empty($_POST['datadirectory']))     $error.='data directory not set<br />';
				if(!isset($_POST['dateformat'])        or empty($_POST['dateformat']))        $error.='dateformat not set<br />';
				if(!isset($_POST['dbname'])            or empty($_POST['dbname']))            $error.='databasename not set<br />';
				if($FIRSTRUN and $_POST['adminpassword']<>$_POST['adminpassword2'] )                        $error.='admin passwords are not the same<br />';
				$dbtype=$_POST['dbtype'];
				if($dbtype=='mysql'){
					if(!isset($_POST['dbhost'])            or empty($_POST['dbhost']))            $error.='database host not set<br />';
					if(!isset($_POST['dbuser'])            or empty($_POST['dbuser']))            $error.='database user not set<br />';
					if($_POST['dbpassword']<>$_POST['dbpassword2'] )                        $error.='database passwords are not the same<br />';
					
				}
				if(isset($_POST['enablebackup']) and $_POST['enablebackup']==1){
					if(!isset($_POST['backupdirectory'])     or empty($_POST['backupdirectory']))     $error.='backup directory not set<br />';
				}
				if(!$FIRSTRUN){
					if(!isset($_POST['adminpassword']) or empty($_POST['adminpassword'])){
						$_POST['adminpassword']=$CONFIG_ADMINPASSWORD;
					}
					if(!isset($_POST['dbpassword']) or empty($_POST['dbpassword'])){
						$_POST['dbpassword']=$CONFIG_DBPASSWORD;
					}
				}
				if(!is_dir($_POST['datadirectory'])){
					try{
						mkdir($_POST['datadirectory']);
					}catch(Exception $e){
						$error.='error while trying to create data directory<br/>';
					}
				}
				if(empty($error)) {
					if($CONFIG_DBTYPE!=$dbtype or $FIRSTRUN){
						//create/fill database
						$CONFIG_DBTYPE=$dbtype;
						$CONFIG_DBNAME=$_POST['dbname'];
						if($dbtype!='sqlite'){
							$CONFIG_DBTABLEPREFIX=$_POST['dbtableprefix'];
							$CONFIG_DBHOST=$_POST['dbhost'];
							$CONFIG_DBUSER=$_POST['dbuser'];
							$CONFIG_DBPASSWORD=$_POST['dbpassword'];
						}else{
							$_POST['dbtableprefix']='';
							$CONFIG_DBTABLEPREFIX='';
						}
						try{
							if(isset($_POST['createdatabase']) and $CONFIG_DBTYPE!='sqlite'){
								self::createdatabase($_POST['dbadminuser'],$_POST['dbadminpwd']);
							}
						}catch(Exception $e){
							$error.='error while trying to create the database<br/>';
						}
						if($CONFIG_DBTYPE=='sqlite'){
							$f=@fopen($SERVERROOT.'/'.$CONFIG_DBNAME,'a+');
							if(!$f){
								$error.='path of sqlite database not writable by server<br/>';
							}
							OC_DB::disconnect();
							unlink($SERVERROOT.'/'.$CONFIG_DBNAME);
						}
						try{
							if(isset($_POST['filldb'])){
								self::filldatabase();
							}
						}catch(Exception $e){
							$error.='error while trying to fill the database<br/>';
						}
						if($CONFIG_DBTYPE=='sqlite'){
							OC_DB::disconnect();
						}
					}
					if($FIRSTRUN){
						if(!OC_USER::createuser($_POST['adminlogin'],$_POST['adminpassword']) && !OC_USER::login($_POST['adminlogin'],$_POST['adminpassword'])){
							$error.='error while trying to create the admin user<br/>';
						}
						if(OC_USER::getgroupid('admin')==0){
							if(!OC_USER::creategroup('admin')){
								$error.='error while trying to create the admin group<br/>';
							}
						}
						if(!OC_USER::addtogroup($_POST['adminlogin'],'admin')){
							$error.='error while trying to add the admin user to the admin group<br/>';
						}
					}
					// Build the configuration array
					$config = array();
					$config['INSTALLED'] = true;
					$config['DATADIRECTORY'] = $_POST['datadirectory'];
					$config['HTTPFORCESSL'] = isset($_POST['forcessl']);
					// Backup configuration
					$config['ENABLEBACKUP'] = isset($_POST['enablebackup']);
					if ( $config['ENABLEBACKUP'] AND (1 == $_POST['enablebackup']) )
						$config['BACKUPDIRECTORY'] = $_POST['backupdirectory'];
					$config['DATEFORMAT'] = $_POST['dateformat'];
					// DB Configuration
					$config['DBTYPE'] = $dbtype;
					$config['DBNAME'] = $_POST['dbname'];
					$config['DBTABLEPREFIX'] = $_POST['dbtableprefix'];
					if ( 'sqlite' != $dbtype ) {
						$config['DBHOST'] = $_POST['dbhost'];
						$config['DBUSER'] = $_POST['dbuser'];
						$config['DBPASSWORD'] = $_POST['dbpassword'];
					}

					if( empty($error) ) {
						header("Location: $WEBROOT/");
						try {
							// Write the configuration array to `/config/config.php`
							self::saveConfiguration($config);
						} catch ( Exception $e ) {
							$error.='error while trying to save the configuration file<br/>';
							return $error;
						}
					} else {
						return $error;
					}
				}
				return($error);
			}
		}
	}
	
	/**
	* Fills the database with the initial tables
	* Note: while the AUTO_INCREMENT function is not supported by SQLite
	*    the same effect can be achieved by accessing the SQLite pseudo-column
	*    "rowid"
	*/
	private static function fillDatabase(){
		global $SERVERROOT;
		OC_DB::createDBFromStructure($SERVERROOT.'/db_structure.xml');
	}
	
	/**
	* Create the database and user
	* @param string adminUser
	* @param string adminPwd
	*
	*/
	private static function createDatabase($adminUser,$adminPwd){
		global $CONFIG_DBHOST;
		global $CONFIG_DBNAME;
		global $CONFIG_DBUSER;
		global $CONFIG_DBPWD;
		global $CONFIG_DBTYPE;
		//we cant user OC_BD functions here because we need to connect as the administrative user.
		if($CONFIG_DBTYPE=='mysql'){
			$connection = @new mysqli($CONFIG_DBHOST, $adminUser, $adminPwd);
			if (mysqli_connect_errno()) {
				@ob_end_clean();
				echo('<html><head></head><body bgcolor="#F0F0F0"><br /><br /><center><b>can not connect to database as administrative user.</center></body></html>');
				exit();
			}
			$query="SELECT user FROM mysql.user WHERE user='{$_POST['dbuser']}';";
			$result = @$connection->query($query);
			if (!$result) {
				$entry='DB Error: "'.$connection->error.'"<br />';
				$entry.='Offending command was: '.$query.'<br />';
				echo($entry);
			}
			if($result->num_rows==0){
				$query="CREATE USER '{$_POST['dbuser']}' IDENTIFIED BY  '{$_POST['dbpassword']}';";
			}else{
				$query='';
			}
			$query.="CREATE DATABASE IF NOT EXISTS  `{$_POST['dbname']}`;";
			$query.="GRANT ALL PRIVILEGES ON  `{$_POST['dbname']}` . * TO  '{$_POST['dbuser']}';";
			$result = @$connection->multi_query($query);
			if (!$result) {
				$entry='DB Error: "'.$connection->error.'"<br />';
				$entry.='Offending command was: '.$query.'<br />';
				echo($entry);
			}
			$connection->close();
		}elseif($CONFIG_DBTYPE=='pgsql'){
			$connection = pg_connect("user='$adminUser' host='$CONFIG_DBHOST' password='$adminPwd'");
			$query="CREATE USER {$_POST['dbuser']} WITH PASSWORD '{$_POST['dbpassword']}' CREATEDB;";
			$result = pg_exec($connection, $query);
			$query="select count(*) from pg_catalog.pg_database where datname = '{$_POST['dbname']}';";
			$result = pg_exec($connection, $query);
			if(pg_result($result,0,0)==0){
				$query="CREATE DATABASE {$_POST['dbname']};";
				$result = pg_exec($connection, $query);
				$query="ALTER DATABASE {$_POST['dbname']} OWNER TO {$_POST['dbuser']};";
				$result = pg_exec($connection, $query);
			}
		}
	}
}
?>


