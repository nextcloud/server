<?php
/**
* ownCloud
*
* @author Frank Karlitschek
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
 * Public interface of ownCloud for apps to use.
 * App Class.
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

class App {


	/**
	 * @brief get the user id of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getUser(){
		return \OC_USER::getUser();
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
		return \OC_App::register( $data );
        }


	/**
	 * register an admin form to be shown
	 */
	public static function registerAdmin($app,$page){
		return \OC_App::registerAdmin($app,$page);
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
		return \OC_App::addNavigationEntry($data);
	}


        /**
         * @brief Read app metadata from the info.xml file
         * @param string $appid id of the app or the path of the info.xml file
         * @param boolean path (optional)
         * @returns array
        */
        public static function getAppInfo($appid,$path=false){
		return \OC_App::getAppInfo($appid,$path);
	}


        /**
         * register a personal form to be shown
         */
        public static function registerPersonal($app,$page){
		return \OC_App::registerPersonal($app,$page);
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
        public static function setActiveNavigationEntry($id){
		return \OC_App::setActiveNavigationEntry($id);
	}


        /**
         * @brief checks whether or not an app is enabled
         * @param $app app
         * @returns true/false
         *
         * This function checks whether or not an app is enabled.
         */
        public static function isEnabled( $app ){
		return \OC_App::isEnabled( $app );
	}


        /**
        * Check if the app is enabled, redirects to home if not
        */
        public static function checkAppEnabled($app){
                return \OC_Util::checkAppEnabled( $app );
        }


        /**
         * get the last version of the app, either from appinfo/version or from appinfo/info.xml
         */
        public static function getAppVersion($appid){
		return \OC_App::getAppVersion( $appid );
	}


        /**
         * @param string appid
         * @return OC_FilesystemView
         */
        public static function getStorage($appid){
		return \OC_App::getStorage( $appid );
	}


}


?>
