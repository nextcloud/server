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

/**
 * This class manages the apps. It allows them to register and integrate in the
 * owncloud ecosystem. Furthermore, this class is responsible for installing,
 * upgrading and removing apps.
 */
class OC_App{
	static private $init = false;
	static private $apps = array();
	static private $activeapp = '';
	static private $adminpages = array();
	static private $settingspages = array();
	static private $navigation = array();
	static private $subnavigation = array();

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
		if( self::$init ){
			return true;
		}

		// Our very own core apps are hardcoded
		foreach( array( 'admin', 'files', 'log', 'help', 'settings' ) as $app ){
			require( $app.'/appinfo/app.php' );
		}

		// The rest comes here
		$apps = OC_Appconfig::getApps();
		foreach( $apps as $app ){
			if( self::isEnabled( $app )){
				if(is_file($SERVERROOT.'/apps/'.$app.'/appinfo/app.php')){
					require( 'apps/'.$app.'/appinfo/app.php' );
				}
			}
		}

		self::$init = true;

		// return
		return true;
	}

	/**
	 * @brief checks whether or not an app is enabled
	 * @param $app app
	 * @returns true/false
	 *
	 * This function checks whether or not an app is enabled.
	 */
	public static function isEnabled( $app ){
		if( 'yes' == OC_Appconfig::getValue( $app, 'enabled' )){
			return true;
		}

		return false;
	}

	/**
	 * @brief enables an app
	 * @param $app app
	 * @returns true/false
	 *
	 * This function set an app as enabled in appconfig.
	 */
	public static function enable( $app ){
		OC_Appconfig::setValue( $app, 'enabled', 'yes' );
	}

	/**
	 * @brief enables an app
	 * @param $app app
	 * @returns true/false
	 *
	 * This function set an app as enabled in appconfig.
	 */
	public static function disable( $app ){
		OC_Appconfig::setValue( $app, 'enabled', 'no' );
	}

	/**
	 * @brief makes owncloud aware of this app
	 * @param $data array with all information
	 * @returns true/false
	 *
	 * This function registers the application. $data is an associative array.
	 * The following keys are required:
	 *   - id: id of the application, has to be unique ('addressbook')
	 *   - name: Human readable name ('Addressbook')
	 *   - version: array with Version (major, minor, bugfix) ( array(1, 0, 2))
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     a list of applications. Lower values come first.
	 *
	 */
	public static function register( $data ){
		OC_App::$apps[] = $data;
	}

	/**
	 * @brief returns information of all apps
	 * @return array with all information
	 *
	 * This function returns all data it got via register().
	 */
	public static function get(){
		// TODO: write function
		return OC_App::$apps;
	}

	/**
	 * @brief adds an entry to the navigation
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function adds a new entry to the navigation visible to users. $data
	 * is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ('addressbook_index')
	 *   - href: link to the page
	 *   - name: Human readable name ('Addressbook')
	 *
	 * The following keys are optional:
	 *   - icon: path to the icon of the app
	 *   - order: integer, that influences the position of your application in
	 *     the navigation. Lower values come first.
	 */
	public static function addNavigationEntry( $data ){
		$data['active']=false;
		if(!isset($data['icon'])){
			$data['icon']='';
		}
		OC_App::$navigation[] = $data;
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
	 *   - id: unique id for this entry ('addressbook_index')
	 *   - href: link to the page
	 *   - name: Human readable name ('Addressbook')
	 *
	 * The following keys are optional:
	 *   - icon: path to the icon of the app
	 *   - order: integer, that influences the position of your application in
	 *     the navigation. Lower values come first.
	 */
	public static function addNavigationSubEntry( $parent, $data ){
		$data['active']=false;
		if(!isset($data['icon'])){
			$data['icon']='';
		}
		if( !array_key_exists( $parent, self::$subnavigation )){
			self::$subnavigation[$parent] = array();
		}
		self::$subnavigation[$parent][] = $data;
		return true;
	}

	/**
	 * @brief marks a navigation entry as active
	 * @param $id id of the entry
	 * @returns true/false
	 *
	 * This function sets a navigation entry as active and removes the 'active'
	 * property from all other entries. The templates can use this for
	 * highlighting the current position of the user.
	 */
	public static function setActiveNavigationEntry( $id ){
		self::$activeapp = $id;
		return true;
	}

	/**
	 * @brief gets the active Menu entry
	 * @returns id or empty string
	 *
	 * This function returns the id of the active navigation entry (set by
	 * setActiveNavigationEntry
	 */
	public static function getActiveNavigationEntry(){
		return self::$activeapp;
	}

	/**
	 * @brief registers an admin page
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function registers a admin page that will be shown in the admin
	 * menu. $data is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ('files_admin')
	 *   - href: link to the admin page
	 *   - name: Human readable name ('Files Administration')
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     the list. Lower values come first.
	 */
	public static function addAdminPage( $data = array()){
		// TODO: write function
		OC_App::$adminpages[] = $data;
		return true;
	}

	/**
	 * @brief registers a settings page
	 * @param $data array containing the data
	 * @returns true/false
	 *
	 * This function registers a settings page. $data is an associative array.
	 * The following keys are required:
	 *   - app: app the settings belong to ('files')
	 *   - id: unique id for this entry ('files_public')
	 *   - href: link to the admin page
	 *   - name: Human readable name ('Public files')
	 *
	 * The following keys are optional:
	 *   - order: integer, that influences the position of your application in
	 *     the list. Lower values come first.
	 *
	 * For the main settings page of an app, the keys 'app' and 'id' have to be
	 * the same.
	 */
	public static function addSettingsPage( $data = array()){
		// TODO: write function
		OC_App::$settingspages[] = $data;
		return true;
	}

	/**
	 * @brief Returns the navigation
	 * @returns associative array
	 *
	 * This function returns an array containing all entries added. The
	 * entries are sorted by the key 'order' ascending. Additional to the keys
	 * given for each app the following keys exist:
	 *   - active: boolean, signals if the user is on this navigation entry
	 *   - children: array that is empty if the key 'active' is false or
	 *     contains the subentries if the key 'active' is true
	 */
	public static function getNavigation(){
		$navigation = self::proceedNavigation( self::$navigation );
		$navigation = self::addSubNavigation( $navigation );
		return $navigation;
	}

	/**
	 * @brief Returns the Settings Navigation
	 * @returns associative array
	 *
	 * This function returns an array containing all settings pages added. The
	 * entries are sorted by the key 'order' ascending.
	 */
	public static function getSettingsNavigation(){
		$navigation = self::proceedNavigation( self::$settingspages );
		$navigation = self::addSubNavigation( $navigation );

		return $navigation;
	}

	/**
	 * @brief Returns the admin navigation
	 * @returns associative array
	 *
	 * This function returns an array containing all admin pages added. The
	 * entries are sorted by the key 'order' ascending.
	 */
	public static function getAdminNavigation(){
		$navigation = self::proceedNavigation( self::$adminpages );
		$navigation = self::addSubNavigation( $navigation );

		return $navigation;
	}

	/// Private foo
	private static function addSubNavigation( $list ){
		if(isset(self::$subnavigation[self::$activeapp])){
			$subNav=self::$subnavigation[self::$activeapp];
			foreach( $list as &$naventry ){
				if( $naventry['id'] == self::$activeapp ){
					$naventry['active'] = true;
					$naventry['subnavigation'] = $subNav;
				}
			}
		}else{
			foreach(self::$subnavigation as $parent=>$entries){
				$activeParent=false;
				foreach($entries as &$subNav){
					$subNav['active']=$subNav['id'] == self::$activeapp;
					if($subNav['active']){
						$activeParent=true;
					}
				}
				if($activeParent){
					foreach( $list as &$naventry ){
						if( $naventry['id'] == $parent ){
							$naventry['active'] = true;
							$naventry['subnavigation'] = $entries;
						}
					}
				}
			}
		}

		return $list;
	}

	/// This is private as well. It simply works, so don't ask for more details
	private static function proceedNavigation( $list ){
		foreach( $list as &$naventry ){
			$naventry['subnavigation'] = array();
			if( $naventry['id'] == self::$activeapp ){
				$naventry['active'] = true;
				if( array_key_exists( $naventry['id'], self::$subnavigation )){
					$naventry['subnavigation'] = self::$subnavigation[$naventry['id']];
				}
			}
			else{
				$naventry['active'] = false;
			}
		} unset( $naventry );

		usort( $list, create_function( '$a, $b', 'if( $a["order"] == $b["order"] ){return 0;}elseif( $a["order"] < $b["order"] ){return -1;}else{return 1;}' ));

		return $list;
	}
	
	/**
	 * @brief Read app metadata from the info.xml file
	 * @param string $appid id of the app or the path of the info.xml file
	 * @returns array
	*/
	public static function getAppInfo($appid){
		if(is_file($appid)){
			$file=$appid;
		}else{
			$file='apps/'.$appid.'/appinfo/info.xml';
			if(!is_file($file)){
				return array();
			}
		}
		$data=array();
		$content=file_get_contents($file);
		$xml = new SimpleXMLElement($content);
		$data['info']=array();
		foreach($xml->children() as $child){
			$data[$child->getName()]=(string)$child;
		}
		return $data;
	}
	
	/**
	 * get the id of loaded app
	 * @return string
	 */
	public static function getCurrentApp(){
		global $WEBROOT;
		$script=substr($_SERVER["SCRIPT_NAME"],strlen($WEBROOT)+1);
		$topFolder=substr($script,0,strpos($script,'/'));
		if($topFolder=='apps'){
			$length=strlen($topFolder);
			return substr($script,$length+1,strpos($script,'/',$length+1)-$length-1);
		}else{
			return $topFolder;
		}
	}
}
