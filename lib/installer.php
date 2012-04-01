<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
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
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class provides the functionality needed to install, update and remove plugins/apps
 */
class OC_Installer{
	/**
	 * @brief Installs an app
	 * @param $data array with all information
	 * @returns integer
	 *
	 * This function installs an app. All information needed are passed in the
	 * associative array $data.
	 * The following keys are required:
	 *   - source: string, can be "path" or "http"
	 *
	 * One of the following keys is required:
	 *   - path: path to the file containing the app
	 *   - href: link to the downloadable file containing the app
	 *
	 * The following keys are optional:
	 *   - pretend: boolean, if set true the system won't do anything
	 *   - noinstall: boolean, if true appinfo/install.php won't be loaded
	 *   - inactive: boolean, if set true the appconfig/app.sample.php won't be
	 *     renamed
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# unzipping it
	 *   -# installing the database at appinfo/database.xml
	 *   -# including appinfo/install.php
	 *   -# setting the installed version
	 *
	 * It is the task of oc_app_install to create the tables and do whatever is
	 * needed to get the app working.
	 */
	public static function installApp( $data = array()){
		if(!isset($data['source'])){
			OC_Log::write('core','No source specified when installing app',OC_Log::ERROR);
			return false;
		}
		
		//download the file if necesary
		if($data['source']=='http'){
			$path=tempnam(get_temp_dir(),'oc_installer_');
			if(!isset($data['href'])){
				OC_Log::write('core','No href specified when installing app from http',OC_Log::ERROR);
				return false;
			}
			copy($data['href'],$path);
		}else{
			if(!isset($data['path'])){
				OC_Log::write('core','No path specified when installing app from local file',OC_Log::ERROR);
				return false;
			}
			$path=$data['path'];
		}
		
		//extract the archive in a temporary folder
		$extractDir=tempnam(get_temp_dir(),'oc_installer_uncompressed_');
		unlink($extractDir);
		mkdir($extractDir);
		$zip = new ZipArchive;
		if($zip->open($path)===true){
			$zip->extractTo($extractDir);
			$zip->close();
		} else {
			OC_Log::write('core','Failed to open archive when installing app',OC_Log::ERROR);
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http'){
				unlink($path);
			}
			return false;
		}
		
		//load the info.xml file of the app
		if(!is_file($extractDir.'/appinfo/info.xml')){
			OC_Log::write('core','App does not provide an info.xml file',OC_Log::ERROR);
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http'){
				unlink($path);
			}
			return false;
		}
		$info=OC_App::getAppInfo($extractDir.'/appinfo/info.xml',true);
		$basedir=OC::$SERVERROOT.'/apps/'.$info['id'];
		
		//check if an app with the same id is already installed
		if(self::isInstalled( $info['id'] )){
			OC_Log::write('core','App already installed',OC_Log::WARN);
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http'){
				unlink($path);
			}
			return false;
		}

		//check if the destination directory already exists
		if(is_dir($basedir)){
			OC_Log::write('core','App directory already exists',OC_Log::WARN);
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http'){
				unlink($path);
			}
			return false;
		}
		
		if(isset($data['pretent']) and $data['pretent']==true){
			return false;
		}
		
		//copy the app to the correct place
		if(!mkdir($basedir)){
			OC_Log::write('core','Can\'t create app folder ('.$basedir.')',OC_Log::ERROR);
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http'){
				unlink($path);
			}
			return false;
		}
		OC_Helper::copyr($extractDir,$basedir);
		
		//remove temporary files
		OC_Helper::rmdirr($extractDir);
		if($data['source']=='http'){
			unlink($path);
		}
		
		//install the database
		if(is_file($basedir.'/appinfo/database.xml')){
			OC_DB::createDbFromStructure($basedir.'/appinfo/database.xml');
		}
		
		//run appinfo/install.php
		if((!isset($data['noinstall']) or $data['noinstall']==false) and file_exists($basedir.'/appinfo/install.php')){
			include($basedir.'/appinfo/install.php');
		}
		
		//set the installed version
		OC_Appconfig::setValue($info['id'],'installed_version',$info['version']);
		OC_Appconfig::setValue($info['id'],'enabled','no');
		return $info['id'];
	}

	/**
	 * @brief checks whether or not an app is installed
	 * @param $app app
	 * @returns true/false
	 *
	 * Checks whether or not an app is installed, i.e. registered in apps table.
	 */
	public static function isInstalled( $app ){

		if( null == OC_Appconfig::getValue( $app, "installed_version" )){
			return false;
		}

		return true;
	}

	/**
	 * @brief Update an application
	 * @param $data array with all information
	 * @returns integer
	 *
	 * This function installs an app. All information needed are passed in the
	 * associative array $data.
	 * The following keys are required:
	 *   - source: string, can be "path" or "http"
	 *
	 * One of the following keys is required:
	 *   - path: path to the file containing the app
	 *   - href: link to the downloadable file containing the app
	 *
	 * The following keys are optional:
	 *   - pretend: boolean, if set true the system won't do anything
	 *   - noupgrade: boolean, if true appinfo/upgrade.php won't be loaded
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# removing the old files
	 *   -# unzipping new file
	 *   -# including appinfo/upgrade.php
	 *   -# setting the installed version
	 *
	 * upgrade.php can determine the current installed version of the app using "OC_Appconfig::getValue($appid,'installed_version')"
	 */
	public static function upgradeApp( $data = array()){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Removes an app
	 * @param $name name of the application to remove
	 * @param $options array with options
	 * @returns true/false
	 *
	 * This function removes an app. $options is an associative array. The
	 * following keys are optional:ja
	 *   - keeppreferences: boolean, if true the user preferences won't be deleted
	 *   - keepappconfig: boolean, if true the config will be kept
	 *   - keeptables: boolean, if true the database will be kept
	 *   - keepfiles: boolean, if true the user files will be kept
	 *
	 * This function works as follows
	 *   -# including appinfo/remove.php
	 *   -# removing the files
	 *
	 * The function will not delete preferences, tables and the configuration,
	 * this has to be done by the function oc_app_uninstall().
	 */
	public static function removeApp( $name, $options = array()){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Installs shipped apps
	 * @param $enabled
	 *
	 * This function installs all apps found in the 'apps' directory;
	 * If $enabled is true, apps are installed as enabled.
	 * If $enabled is false, apps are installed as disabled.
	 */
	public static function installShippedApps(){
		$dir = opendir( OC::$SERVERROOT."/apps" );
		while( false !== ( $filename = readdir( $dir ))){
			if( substr( $filename, 0, 1 ) != '.' and is_dir(OC::$SERVERROOT."/apps/$filename") ){
				if( file_exists( OC::$SERVERROOT."/apps/$filename/appinfo/app.php" )){
					if(!OC_Installer::isInstalled($filename)){
						$info = OC_Installer::installShippedApp($filename);
						$enabled = isset($info['default_enable']);
						if( $enabled ){
							OC_Appconfig::setValue($filename,'enabled','yes');
						}else{
							OC_Appconfig::setValue($filename,'enabled','no');
						}
					}
				}
			}
		}
		closedir( $dir );
	}

	/**
	 * install an app already placed in the app folder
	 * @param string $app id of the app to install
	 * @returns array see OC_App::getAppInfo
	 */
	public static function installShippedApp($app){
		//install the database
		if(is_file(OC::$SERVERROOT."/apps/$app/appinfo/database.xml")){
			OC_DB::createDbFromStructure(OC::$SERVERROOT."/apps/$app/appinfo/database.xml");
		}

		//run appinfo/install.php
		if(is_file(OC::$SERVERROOT."/apps/$app/appinfo/install.php")){
			include(OC::$SERVERROOT."/apps/$app/appinfo/install.php");
		}
		$info=OC_App::getAppInfo($app);
		OC_Appconfig::setValue($app,'installed_version',$info['version']);
		return $info;
	}
}
