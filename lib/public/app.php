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

/**
 * This class provides functions to manage apps in ownCloud
 */
class App {
        /**
         * @brief Makes owncloud aware of this app
         * @brief This call is deprecated and not necessary to use.
         * @param $data array with all information
         * @returns true/false
         *
         * @deprecated this method is deprecated
         * Do not call it anymore
         * It'll remain in our public API for compatibility reasons
         *
         */
        public static function register( $data ){
		return \OC_App::register( $data );
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
		return \OC_App::addNavigationEntry( $data );
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
		return \OC_App::setActiveNavigationEntry( $id );
	}


        /**
         * @brief Register a Configuration Screen that should appear in the personal settings section.
         * @param $app string appid
         * @param $page string page to be included
        */
        public static function registerPersonal( $app, $page ){
		return \OC_App::registerPersonal( $app, $page );
	}


	/**
         * @brief Register a Configuration Screen that should appear in the Admin section.
         * @param $app string appid
         * @param $page string page to be included
	 */
	public static function registerAdmin( $app, $page ){
		return \OC_App::registerAdmin( $app, $page );
	}


        /**
         * @brief Read app metadata from the info.xml file
         * @param string $app id of the app or the path of the info.xml file
         * @param boolean path (optional)
         * @returns array
        */
        public static function getAppInfo( $app, $path=false ){
		return \OC_App::getAppInfo( $app, $path);
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
         * @brief Check if the app is enabled, redirects to home if not 
         * @param $app app
         * @returns true/false
        */
        public static function checkAppEnabled( $app ){
                return \OC_Util::checkAppEnabled( $app );
        }


        /**
         * @brief Get the last version of the app, either from appinfo/version or from appinfo/info.xml 
         * @param $app app
         * @returns true/false
         */
        public static function getAppVersion( $app ){
		return \OC_App::getAppVersion( $app );
	}



}


?>
