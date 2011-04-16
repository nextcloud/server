<?php

if(isset($_POST['install']) and $_POST['install']=='true'){
	$errors=OC_INSTALLER::install($_POST);
	if(count($errors)>0){
		OC_TEMPLATE::printGuestPage( "", "error", array( "errors" => $errors ));
	}else{
		header( "Location: $WEBROOT");
		exit();
	}
}

class OC_INSTALLER{
	public static function install($options){
		$error=array();
		$dbtype=$options['dbtype'];
		if(empty($options['login'])){
			$error[]=array('error'=>'username not set');
		};
		if(empty($options['pass'])){
			$error[]=array('error'=>'password not set');
		};
		if(empty($options['directory'])){
			$error[]=array('error'=>'data directory not set');
		};
		if($dbtype=='mysql'){//mysql needs more config options
			if(empty($options['dbuser'])){
				$error[]=array('error'=>'database user directory not set');
			};
			if(empty($options['dbpass'])){
				$error[]=array('error'=>'database password directory not set');
			};
			if(empty($options['dbname'])){
				$error[]=array('error'=>'database name directory not set');
			};
			if(empty($options['dbhost'])){
				$error[]=array('error'=>'database host directory not set');
			};
			if(!isset($options['dbtableprefix'])){
				$error[]=array('error'=>'database table prefix directory not set');
			};
		}
		if(count($error)==0){ //no errors, good
			$username=$options['login'];
			$password=$options['pass'];
			$datadir=$options['directory'];
			
			//write the config file
			OC_CONFIG::setValue('datadirectory',$datadir);
			OC_CONFIG::setValue('dbtype',$dbtype);
			if($dbtype=='mysql'){
				$dbuser=$options['dbuser'];
				$dbpass=$options['dbpass'];
				$dbname=$options['dbname'];
				$dbhost=$options['dbhost'];
				$dbtableprefix=$options['dbtableprefix'];
				OC_CONFIG::setValue('dbname',$dbname);
				OC_CONFIG::setValue('dbhost',$dbhost);
				OC_CONFIG::setValue('dbtableprefix',$dbtableprefix);
				
				//check if the database user has admin right
				$connection=mysql_connect($dbhost, $dbuser, $dbpass);
				if(!$connection) {
					$error[]=array('error'=>'mysql username and/or password not valid','you need to enter either an existing account, or the administrative account if you wish to create a new user for ownCloud');
				}else{
					$query="SELECT user FROM mysql.user WHERE user='$dbuser'";//this should be enough to check for admin rights in mysql
					if(mysql_query($query,$connection)){
						//use the admin login data for the new database user
						self::createDBUser($username,$password);
						OC_CONFIG::setValue('dbuser',$username);
						OC_CONFIG::setValue('dbpass',$password);
					}else{
						OC_CONFIG::setValue('dbuser',$dbuser);
						OC_CONFIG::setValue('dbpass',$dbpass);
						
						//create the database
						self::createDatabase($dbname,$dbuser);
					}
				}
				mysql_close($connection);
			}
			OC_USER::createUser($username,$password);
			OC_GROUP::createGroup('admin');
			OC_GROUP::addToGroup($username,'admin');
			OC_CONFIG::setValue('installed',true);
		}
		return $error;
	}
	
	public static function createDatabase($name,$adminUser,$adminPwd){//TODO refactoring this
		$CONFIG_DBHOST=$options['host'];
		$CONFIG_DBNAME=$options['name'];
		$CONFIG_DBUSER=$options['user'];
		$CONFIG_DBPWD=$options['pass'];
		$CONFIG_DBTYPE=$options['type'];
		//we cant user OC_BD functions here because we need to connect as the administrative user.
		$query="CREATE DATABASE IF NOT EXISTS  `$name`";
		$result = mysql_query($query,$connection);
		if (!$result) {
			$entry='DB Error: "'.mysql_error($connection).'"<br />';
			$entry.='Offending command was: '.$query.'<br />';
			echo($entry);
		}
		$query="GRANT ALL PRIVILEGES ON  `$name` . * TO  '$user'";
		$result = mysql_query($query,$connection);
		if (!$result) {
			$entry='DB Error: "'.mysql_error($connection).'"<br />';
			$entry.='Offending command was: '.$query.'<br />';
			echo($entry);
		}
	}
	
	private static function createDBUser($name,$password){
		//we need to create 2 accounts, one for global use and one for local user. if we don't speccify the local one,
				//  the anonymous user would take precedence when there is one.
		$query="CREATE USER 'name'@'localhost' IDENTIFIED BY '$password'";
		$result = mysql_query($query,$connection);
		$query="CREATE USER '$name'@'%' IDENTIFIED BY '$password'";
		$result = mysql_query($query,$connection);
	}
}

?>