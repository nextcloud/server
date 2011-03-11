<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
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

class OC_APP{
	static private $init = false;
	static private $apps = array();
	static private $adminpages = array();
	static private $navigation = array();
	static private $personalmenu = array();

	/**
	 * @brief loads all apps
	 * @returns true/false
	 *
	 * This function walks through the owncloud directory and loads all apps
	 * it can find. A directory contains an app if the file /appinfo/app.php
	 * exists.
	 */
	public static function loadApps(){
		global $SERVERROOT;

		// Did we allready load everything?
		if( $self::init ){
			return true
		}

		// Get all appinfo
		$dir = opendir( $SERVERROOT );
		while( false !== ( $filename = readdir( $dir ))){
			if( substr( $filename, 0, 1 ) != '.' ){
				if( file_exists( "$SERVERROOT/$filename/appinfo/app.php" )){
					oc_require( "$filename/appinfo/app.php" );
				}
			}
		}
		closedir( $dir );

		$self::init = true;

		// return
		return true;
	}

	/**
	 * @brief makes owncloud aware of this app
	 * @param $data array with all information
	 * @returns true/false
	 *
	 * This function registers the application. $data is an associative array.
	 * The following keys are required:
	 *   - id: id of the application, has to be unique ("addressbook")
	 *   - name: Human readable name ("Addressbook")
	 *   - version: array with Version (major, minor, bugfix) ( array(1, 0, 2))
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     a list of applications. Lower values come first.
	 *
	 */
	public static function register( $data ){
		OC_APP::$apps[] = $data;
	}

	/**
	 * @brief returns information of all apps
	 * @return array with all information
	 *
	 * This function returns all data it got via register().
	 */
	public static function get(){
		return OC_APP::$apps;
	}

	/**
	 * @brief adds an entry to the navigation
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function adds a new entry to the navigation visible to users. $data
	 * is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ("addressbook_index")
	 *   - href: link to the page
	 *   - name: Human readable name ("Addressbook")
	 *
	 * The following keys are optional:
	 *   - icon: path to the icon of the app
	 *   - order: integer, that influences the position of your application in
	 *     the navigation. Lower values come first.
	 */
	public static function addNavigationEntry( $data ){
		// TODO: write function
		OC_APP::$navigation[] = $data;
		return true;
	}

	/**
	 * @brief adds a sub entry to the navigation
	 * @param $parent id of the parent
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function adds a new sub entry to the navigation visible to users.
	 * these entries are visible only if the parent navigation entry is marked
	 * as being active (see activateNavigationEntry()). $data is an associative
	 * array.
	 * The following keys are required:
	 *   - id: unique id for this entry ("addressbook_index")
	 *   - href: link to the page
	 *   - name: Human readable name ("Addressbook")
	 *
	 * The following keys are optional:
	 *   - icon: path to the icon of the app
	 *   - order: integer, that influences the position of your application in
	 *     the navigation. Lower values come first.
	 */
	public static function addNavigationSubEntry( $parent, $data ){
		// TODO: write function
		return true;
	}

	/**
	 * @brief marks a navigation entry as active
	 * @param $id id of the entry
	 * @returns true/false
	 *
	 * This function sets a navigation entry as active and removes the "active"
	 * property from all other entries. The templates can use this for
	 * highlighting the current position of the user.
	 */
	public static function activateNavigationEntry( $id ){
		// TODO: write function
		return true;
	}

	/**
	 * @brief adds an entry to the personal menu
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function adds a new entry to the personal menu visible to users
	 * only. $data is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ("logout")
	 *   - href: link to the page
	 *   - name: Human readable name ("Logout")
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     the personal menu. Lower values come first.
	 */
	public static function addPersonalMenuEntry( $data ){
		// TODO: write function
		OC_APP::$personalmenu[] = $data;
		return true;
	}

	/**
	 * @brief registers an admin page
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function registers a admin page that will be shown in the admin
	 * menu. $data is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ("files_admin")
	 *   - href: link to the admin page
	 *   - name: Human readable name ("Files Administration")
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     the list. Lower values come first.
	 */
	public static function addAdminPage( $data = array()){
		// TODO: write function
		OC_APP::$adminpages[] = $data;
		return true;
	}

	/**
	 * @brief Returns the navigation
	 * @returns associative array
	 *
	 * This function returns an array containing all entries added. The
	 * entries are sorted by the key "order" ascending. Additional to the keys
	 * given for each app the following keys exist:
	 *   - active: boolean, signals if the user is on this navigation entry
	 *   - children: array that is empty if the key "active" is false or
	 *     contains the subentries if the key "active" is true
	 */
	public static function getNavigation(){
		// TODO: write function
		return OC_APP::$navigation;
	}

	/**
	 * @brief Returns the personal menu
	 * @returns associative array
	 *
	 * This function returns an array containing all personal menu entries
	 * added. The entries are sorted by the key "order" ascending.
	 */
	public static function getPersonalMenu(){
		// TODO: write function
		return OC_APP::$personalmenu;
	}

	/**
	 * @brief Returns the admin pages
	 * @returns associative array
	 *
	 * This function returns an array containing all admin pages added. The
	 * entries are sorted by the key "order" ascending.
	 */
	public static function getAdminPages(){
		// TODO: write function
		return OC_APP::$adminpages;
	}

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
	 *   - noinstall: boolean, if true the function oc_app_install will be
	 *     skipped
	 *   - inactive: boolean, if set true the appconfig/app.sample.php won't be
	 *     renamed
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# unzipping it
	 *   -# including appinfo/installer.php
	 *   -# executing "oc_app_install()"
	 *   -# renaming appinfo/app.sample.php to appinfo/app.php
	 *
	 * It is the task of oc_app_install to create the tables and do whatever is
	 * needed to get the app working.
	 */
	public static function installApp( $data = array()){
		// TODO: write function
		return true;
	}

	/**
	 * @brief Installs an application
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
	 *   - noupgrade: boolean, if true the function oc_app_upgrade will be
	 *     skipped
	 *   - keepappinfo: boolean. If set true, the folder appinfo will not be
	 *     deleted, appinfo/app.php will not be replaced by a new version
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# removing the old files
	 *   -# unzipping new file
	 *   -# including appinfo/installer.php
	 *   -# executing "oc_app_upgrade( $options )"
	 *   -# renaming appinfo/app.sample.php to appinfo/app.php
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
	 *   -# including appinfo/installer.php
	 *   -# executing "oc_app_uninstall( $options )"
	 *   -# removing the files
	 *
	 * The function will not delete preferences, tables and the configuration,
	 * this has to be done by the function oc_app_uninstall().
	 */
	public static function removeApp( $name, $options = array()){
		// TODO: write function
		return true;
	}
}
?>