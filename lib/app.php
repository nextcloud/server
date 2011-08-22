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
	static private $navigation = array();
	static private $settingsForms = array();
	static private $adminForms = array();
	static private $personalForms = array();

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
		foreach( array('files', 'settings') as $app ){
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
		if(!OC_Installer::isInstalled($app)){
			OC_Installer::installShippedApp($app);
		}
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
	 * @brief Returns the Settings Navigation
	 * @returns associative array
	 *
	 * This function returns an array containing all settings pages added. The
	 * entries are sorted by the key 'order' ascending.
	 */
	public static function getSettingsNavigation(){
		$l=new OC_L10N('core');
		$admin=array(
			array( "id" => "core_users", "order" => 2, "href" => OC_Helper::linkTo( "settings", "users.php" ), "name" => $l->t("Users"), "icon" => OC_Helper::imagePath( "settings", "users.svg" )),
			array( "id" => "core_apps", "order" => 3, "href" => OC_Helper::linkTo( "settings", "apps.php?installed" ), "name" => $l->t("Apps"), "icon" => OC_Helper::imagePath( "settings", "apps.svg" )),
		);
		$settings=array(
			array( "id" => "help", "order" => 1000, "href" => OC_Helper::linkTo( "settings", "help.php" ), "name" => $l->t("Help"), "icon" => OC_Helper::imagePath( "settings", "help.svg" )),
			array( "id" => "settings", "order" => 1, "href" => OC_Helper::linkTo( "settings", "personal.php" ), "name" => $l->t("Personal"), "icon" => OC_Helper::imagePath( "settings", "personal.svg" ))
		);
		if( OC_Group::inGroup( $_SESSION["user_id"], "admin" )){
			$settings=array_merge($admin,$settings);
		}
		$navigation = self::proceedNavigation($settings);
		return $navigation;
	}

	/// This is private as well. It simply works, so don't ask for more details
	private static function proceedNavigation( $list ){
		foreach( $list as &$naventry ){
			$naventry['subnavigation'] = array();
			if( $naventry['id'] == self::$activeapp ){
				$naventry['active'] = true;
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
			$file=OC::$SERVERROOT.'/apps/'.$appid.'/appinfo/info.xml';
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
		return $navigation;
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
	
	
	/**
	 * get the forms for either settings, admin or personal
	 */
	public static function getForms($type){
		$forms=array();
		switch($type){
			case 'settings':
				$source=self::$settingsForms;
				break;
			case 'admin':
				$source=self::$adminForms;
				break;
			case 'personal':
				$source=self::$personalForms;
				break;
		}
		foreach($source as $form){
			$forms[]=include $form;
		}
		return $forms;
	}
	
	/**
	 * register a settings form to be shown
	 */
	public static function registerSettings($app,$page){
		self::$settingsForms[]='apps/'.$app.'/'.$page.'.php';
	}
	
	/**
	 * register an admin form to be shown
	 */
	public static function registerAdmin($app,$page){
		self::$adminForms[]='apps/'.$app.'/'.$page.'.php';
	}
	
	/**
	 * register a personal form to be shown
	 */
	public static function registerPersonal($app,$page){
		self::$personalForms[]='apps/'.$app.'/'.$page.'.php';
	}
	
	/**
	 * get a list of all apps in the apps folder
	 */
	public static function getAllApps(){
		$apps=array();
		$dh=opendir(OC::$SERVERROOT.'/apps');
		while($file=readdir($dh)){
			if(is_file(OC::$SERVERROOT.'/apps/'.$file.'/appinfo/app.php')){
				$apps[]=$file;
			}
		}
		return $apps;
	}
}
