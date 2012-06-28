<?php

$hasSQLite = (is_callable('sqlite_open') or class_exists('SQLite3'));
$hasMySQL = is_callable('mysql_connect');
$hasPostgreSQL = is_callable('pg_connect');
$datadir = OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data');
$opts = array(
	'hasSQLite' => $hasSQLite,
	'hasMySQL' => $hasMySQL,
	'hasPostgreSQL' => $hasPostgreSQL,
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
		header("Location: ".OC::$WEBROOT.'/');
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
			$error[] = 'Set an admin username.';
		}
		if(empty($options['adminpass'])) {
			$error[] = 'Set an admin password.';
		}
		if(empty($options['directory'])) {
			$error[] = 'Specify a data folder.';
		}

		if($dbtype=='mysql' or $dbtype=='pgsql') { //mysql and postgresql needs more config options
			if($dbtype=='mysql')
				$dbprettyname = 'MySQL';
			else
				$dbprettyname = 'PostgreSQL';

			if(empty($options['dbuser'])) {
				$error[] = "$dbprettyname enter the database username.";
			}
			if(empty($options['dbname'])) {
				$error[] = "$dbprettyname enter the database name.";
			}
			if(empty($options['dbhost'])) {
				$error[] = "$dbprettyname set the database host.";
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

			//generate a random salt that is used to salt the local user passwords
			$salt=mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000).mt_rand(1000,9000);
			OC_Config::setValue('passwordsalt', $salt);

			//write the config file
			OC_Config::setValue('datadirectory', $datadir);
 			OC_Config::setValue('dbtype', $dbtype);
 			OC_Config::setValue('version',implode('.',OC_Util::getVersion()));
			if($dbtype == 'mysql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';
				OC_Config::setValue('dbname', $dbname);
				OC_Config::setValue('dbhost', $dbhost);
				OC_Config::setValue('dbtableprefix', $dbtableprefix);

				//check if the database user has admin right
				$connection = @mysql_connect($dbhost, $dbuser, $dbpass);
				if(!$connection) {
					$error[] = array(
						'error' => 'MySQL username and/or password not valid',
						'hint' => 'You need to enter either an existing account or the administrator.'
					);
					return($error);
				}
				else {
					$oldUser=OC_Config::getValue('dbuser', false);
					$oldPassword=OC_Config::getValue('dbpassword', false);
					
					$query="SELECT user FROM mysql.user WHERE user='$dbuser'"; //this should be enough to check for admin rights in mysql
					if(mysql_query($query, $connection)) {
						//use the admin login data for the new database user

						//add prefix to the mysql user name to prevent collissions
						$dbusername=substr('oc_'.$username,0,16);
						if($dbusername!=$oldUser){
							//hash the password so we don't need to store the admin config in the config file
							$dbpassword=md5(time().$password);

							self::createDBUser($dbusername, $dbpassword, $connection);

							OC_Config::setValue('dbuser', $dbusername);
							OC_Config::setValue('dbpassword', $dbpassword);
						}

						//create the database
						self::createDatabase($dbname, $dbusername, $connection);
					}
					else {
						if($dbuser!=$oldUser){
							OC_Config::setValue('dbuser', $dbuser);
							OC_Config::setValue('dbpassword', $dbpass);
						}

						//create the database
						self::createDatabase($dbname, $dbuser, $connection);
					}

					//fill the database if needed
					$query="select count(*) from information_schema.tables where table_schema='$dbname' AND table_name = '{$dbtableprefix}users';";
					$result = mysql_query($query,$connection);
					if($result){
						$row=mysql_fetch_row($result);
					}
					if(!$result or $row[0]==0) {
						OC_DB::createDbFromStructure('db_structure.xml');
					}
					mysql_close($connection);
				}
			}
			elseif($dbtype == 'pgsql') {
				$dbuser = $options['dbuser'];
				$dbpass = $options['dbpass'];
				$dbname = $options['dbname'];
				$dbhost = $options['dbhost'];
				$dbtableprefix = isset($options['dbtableprefix']) ? $options['dbtableprefix'] : 'oc_';
				OC_CONFIG::setValue('dbname', $dbname);
				OC_CONFIG::setValue('dbhost', $dbhost);
				OC_CONFIG::setValue('dbtableprefix', $dbtableprefix);

				//check if the database user has admin right
				$connection_string = "host=$dbhost dbname=postgres user=$dbuser password=$dbpass";
				$connection = @pg_connect($connection_string);
				if(!$connection) {
					$error[] = array(
						'error' => 'PostgreSQL username and/or password not valid',
						'hint' => 'You need to enter either an existing account or the administrator.'
					);
					return $error;
				}
				else {
					//check for roles creation rights in postgresql
					$query="SELECT 1 FROM pg_roles WHERE rolcreaterole=TRUE AND rolname='$dbuser'";
					$result = pg_query($connection, $query);
					if($result and pg_num_rows($result) > 0) {
						//use the admin login data for the new database user

						//add prefix to the postgresql user name to prevent collissions
						$dbusername='oc_'.$username;
						//create a new password so we don't need to store the admin config in the config file
						$dbpassword=md5(time());
						
						self::pg_createDBUser($dbusername, $dbpassword, $connection);
						
						OC_CONFIG::setValue('dbuser', $dbusername);
						OC_CONFIG::setValue('dbpassword', $dbpassword);

						//create the database
						self::pg_createDatabase($dbname, $dbusername, $connection);
					}
					else {
						OC_CONFIG::setValue('dbuser', $dbuser);
						OC_CONFIG::setValue('dbpassword', $dbpass);

						//create the database
						self::pg_createDatabase($dbname, $dbuser, $connection);
					}

					// the connection to dbname=postgres is not needed anymore
					pg_close($connection);

					// connect to the ownCloud database (dbname=$dbname) an check if it needs to be filled
					$dbuser = OC_CONFIG::getValue('dbuser');
					$dbpass = OC_CONFIG::getValue('dbpassword');
					$connection_string = "host=$dbhost dbname=$dbname user=$dbuser password=$dbpass";
					$connection = @pg_connect($connection_string);
					if(!$connection) {
						$error[] = array(
							'error' => 'PostgreSQL username and/or password not valid',
							'hint' => 'You need to enter either an existing account or the administrator.'
						);
					} else {
						$query = "select count(*) FROM pg_class WHERE relname='{$dbtableprefix}users' limit 1";
						$result = pg_query($connection, $query);
						if($result) {
							$row = pg_fetch_row($result);
						}
						if(!$result or $row[0]==0) {
							OC_DB::createDbFromStructure('db_structure.xml');
						}
					}
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

			//create the user and group
			try {
				OC_User::createUser($username, $password);
			}
			catch(Exception $exception) {
				$error[] = $exception->getMessage();
			}

			if(count($error) == 0) {
				OC_Appconfig::setValue('core', 'installedat',microtime(true));
				OC_Appconfig::setValue('core', 'lastupdatedat',microtime(true));

				OC_Group::createGroup('admin');
				OC_Group::addToGroup($username, 'admin');
				OC_User::login($username, $password);

				//guess what this does
				OC_Installer::installShippedApps();

				//create htaccess files for apache hosts
				if (strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
					self::createHtaccess();
				}

				//and we are done
				OC_Config::setValue('installed', true);
			}
		}

		return $error;
	}

	public static function createDatabase($name,$user,$connection) {
		//we cant use OC_BD functions here because we need to connect as the administrative user.
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
		// we need to create 2 accounts, one for global use and one for local user. if we don't specify the local one,
		// the anonymous user would take precedence when there is one.
		$query = "CREATE USER '$name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
		$query = "CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query, $connection);
	}

	public static function pg_createDatabase($name,$user,$connection) {
		//we cant use OC_BD functions here because we need to connect as the administrative user.
		$e_name = pg_escape_string($name);
		$e_user = pg_escape_string($user);
		$query = "CREATE DATABASE \"$e_name\" OWNER \"$e_user\"";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry='DB Error: "'.pg_last_error($connection).'"<br />';
			$entry.='Offending command was: '.$query.'<br />';
			echo($entry);
		}
		$query = "REVOKE ALL PRIVILEGES ON DATABASE \"$e_name\" FROM PUBLIC";
		$result = pg_query($connection, $query);		
	}

	private static function pg_createDBUser($name,$password,$connection) {
		$e_name = pg_escape_string($name);
		$e_password = pg_escape_string($password);
		$query = "CREATE USER \"$e_name\" CREATEDB PASSWORD '$e_password';";
		$result = pg_query($connection, $query);
		if(!$result) {
			$entry='DB Error: "'.pg_last_error($connection).'"<br />';
			$entry.='Offending command was: '.$query.'<br />';
			echo($entry);
		}
	}

	/**
	 * create .htaccess files for apache hosts
	 */
	private static function createHtaccess() {
		$content = "ErrorDocument 403 ".OC::$WEBROOT."/core/templates/403.php\n";//custom 403 error page
		$content.= "ErrorDocument 404 ".OC::$WEBROOT."/core/templates/404.php\n";//custom 404 error page
		$content.= "<IfModule mod_php5.c>\n";
		$content.= "php_value upload_max_filesize 512M\n";//upload limit
		$content.= "php_value post_max_size 512M\n";
		$content.= "php_value memory_limit 512M\n";
		$content.= "<IfModule env_module>\n";
		$content.= "  SetEnv htaccessWorking true\n";
		$content.= "</IfModule>\n";
		$content.= "</IfModule>\n";
		$content.= "<IfModule mod_rewrite.c>\n";
		$content.= "RewriteEngine on\n";
 		$content.= "RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
		$content.= "RewriteRule ^.well-known/host-meta /public.php?service=host-meta [QSA,L]\n";
		$content.= "RewriteRule ^.well-known/carddav /remote.php/carddav/ [R]\n";
		$content.= "RewriteRule ^.well-known/caldav /remote.php/caldav/ [R]\n";
		$content.= "RewriteRule ^apps/([^/]*)/(.*\.(css|php))$ index.php?app=$1&getfile=$2 [QSA,L]\n";
		$content.= "RewriteRule ^remote/(.*) remote.php [QSA,L]\n";
		$content.= "</IfModule>\n";
		$content.= "Options -Indexes\n";
		@file_put_contents(OC::$SERVERROOT.'/.htaccess', $content); //supress errors in case we don't have permissions for it

		$content = "deny from all\n";
		$content.= "IndexIgnore *";
		file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/.htaccess', $content);
		file_put_contents(OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data').'/index.html', '');
	}
}

?>
