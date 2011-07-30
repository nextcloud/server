<?php

$hasSQLite = (is_callable('sqlite_open') or class_exists('SQLite3'));
$hasMySQL = is_callable('mysql_connect');
$datadir = OC_Config::getValue('datadir', $SERVERROOT.'/data');
$opts = array(
	'hasSQLite' => $hasSQLite,
	'hasMySQL' => $hasMySQL,
	'directory' => $datadir,
	'errors' => array(),
);

if(isset($_POST['install']) AND $_POST['install']=='true') {
	// We have to launch the installation process :
	$e = OC_Setup::install($_POST);
	$errors = array('errors' => $e);
	
	if(count($e) > 0) {
		//OC_Template::printGuestPage("", "error", array("errors" => $errors));
		$options = array_merge($_POST, $opts, $errors);
		OC_Template::printGuestPage("", "installation", $options);
	}
	else {
		header("Location: ".$WEBROOT.'/');
		exit();
	}
}
else {
	OC_Template::printGuestPage("", "installation", $opts);
}

class OC_Setup {
	public static function install($options) {
		$error = array();
		$dbtype = $options['dbtype'];
		
		if(empty($options['adminlogin'])) {
			$error[] = 'STEP 1 : admin login is not set.';
		}
		if(empty($options['adminpass'])) {
			$error[] = 'STEP 1 : admin password is not set.';
		}
		if(empty($options['directory'])) {
			$error[] = 'STEP 2 : data directory path is not set.';
		}
		if($dbtype=='mysql') { //mysql needs more config options
			if(empty($options['dbuser'])) {
				$error[] = 'STEP 3 : MySQL database user is not set.';
			}
			if(empty($options['dbpass'])) {
				$error[] = 'STEP 3 : MySQL database password is not set.';
			}
			if(empty($options['dbname'])) {
				$error[] = 'STEP 3 : MySQL database name is not set.';
			}
			if(empty($options['dbhost'])) {
				$error[] = 'STEP 3 : MySQL database host is not set.';
			}
			if(!isset($options['dbtableprefix'])) {
				$error[] = 'STEP 3 : MySQL database table prefix is not set.';
			}
		}

		if(count($error) == 0) { //no errors, good
			$username = htmlspecialchars_decode($options['adminlogin']);
			$password = htmlspecialchars_decode($options['adminpass']);
			$datadir = htmlspecialchars_decode($options['directory']);
			
			//use sqlite3 when available, otherise sqlite2 will be used.
			if($dbtype=='sqlite' and class_exists('SQLite3')){
				$dbtype='sqlite3';
			}

			//write the config file
			OC_Config::setValue('datadirectory', $datadir);
 			OC_Config::setValue('dbtype', $dbtype);
 			OC_Config::setValue('version',implode('.',OC_Util::getVersion()));
			if($dbtype == 'mysql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = $options['dbtableprefix'];
				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				//check if the database user has admin right
				$connection = @mysql_connect($dbhost, $dbuser, $dbpass);
				if(!$connection) {
					$error[] = array(
						'error' => 'mysql username and/or password not valid',
						'hint' => 'you need to enter either an existing account, or the administrative account if you wish to create a new user for ownCloud'
					);
				}
				else {
					$query="SELECT user FROM mysql.user WHERE user='$dbuser'"; //this should be enough to check for admin rights in mysql
					if(mysql_query($query, $connection)) {
						//use the admin login data for the new database user

						//add prefix to the mysql user name to prevent collissions
						$dbusername='oc_mysql_'.$username;
						//hash the password so we don't need to store the admin config in the config file
						$dbpassword=md5(time().$password);
						
						self::createDBUser($dbusername, $dbpassword, $connection);
						
						OC_Config::setValue('dbuser', $dbusername);
						OC_Config::setValue('dbpassword', $dbpassword);

						//create the database
						self::createDatabase($dbname, $dbusername, $connection);
					}
					else {
						OC_Config::setValue('dbuser', $dbuser);
						OC_Config::setValue('dbpassword', $dbpass);

						//create the database
						self::createDatabase($dbname, $dbuser, $connection);
					}

					//fill the database if needed
					$query="SELECT * FROM $dbname.{$dbtableprefix}users";
					$result = mysql_query($query,$connection);
					if(!$result) {
						OC_DB::createDbFromStructure('db_structure.xml');
					}
					mysql_close($connection);
				}
			}
			else {
				//delete the old sqlite database first, might cause infinte loops otherwise
				if(file_exists("$datadir/owncloud.db")){
					unlink("$datadir/owncloud.db");
				}
				//in case of sqlite, we can always fill the database
				OC_DB::createDbFromStructure('db_structure.xml');
			}

			if(count($error) == 0) {
				//create the user and group
				OC_User::createUser($username, $password);
				OC_Group::createGroup('admin');
				OC_Group::addToGroup($username, 'admin');

				//guess what this does
				OC_Installer::installShippedApps(true);

				//create htaccess files for apache hosts
				self::createHtaccess(); //TODO detect if apache is used

				//and we are done
				OC_Config::setValue('installed', true);
			}
		}

		return $error;
	}

	public static function createDatabase($name,$user,$connection) {
		//we cant user OC_BD functions here because we need to connect as the administrative user.
		$query = "CREATE DATABASE IF NOT EXISTS  `$name`";
		$result = mysql_query($query, $connection);
		if(!$result) {
			$entry='DB Error: "'.mysql_error($connection).'"<br />';
			$entry.='Offending command was: '.$query.'<br />';
			echo($entry);
		}
		$query="GRANT ALL PRIVILEGES ON  `$name` . * TO  '$user'";
		$result = mysql_query($query, $connection); //this query will fail if there aren't the right permissons, ignore the error
	}

	private static function createDBUser($name,$password,$connection) {
		// we need to create 2 accounts, one for global use and one for local user. if we don't speccify the local one,
		// the anonymous user would take precedence when there is one.
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
	}

	/**
	 * create .htaccess files for apache hosts
	 */
	private static function createHtaccess() {
		global $SERVERROOT;
		global $WEBROOT;
		$content = "ErrorDocument 404 /$WEBROOT/core/templates/404.php\n";//custom 404 error page
		$content.= "php_value upload_max_filesize 20M\n";//upload limit
		$content.= "php_value post_max_size 20M\n";
		$content.= "SetEnv htaccessWorking true\n";
		$content.= "Options -Indexes\n";
		@file_put_contents($SERVERROOT.'/.htaccess', $content); //supress errors in case we don't have permissions for it

		$content = "deny from all";
		file_put_contents(OC_Config::getValue('datadirectory', $SERVERROOT.'/data').'/.htaccess', $content);
	}
}

?>
