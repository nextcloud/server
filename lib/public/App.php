<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * App Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides functions to manage apps in ownCloud
 * @since 4.0.0
 */
class App {

	/**
	 * Adds an entry to the navigation
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
	 *
	 * @param array $data containing the data
	 * @return boolean
	 *
	 * @deprecated 8.1.0 Use \OC::$server->getNavigationManager()->add() instead to
	 * register a closure, this helps to speed up all requests against ownCloud
	 * @since 4.0.0
	 */
	public static function addNavigationEntry($data) {
		\OC::$server->getNavigationManager()->add($data);
		return true;
	}

	/**
	 * Marks a navigation entry as active
	 * @param string $id id of the entry
	 * @return boolean
	 *
	 * This function sets a navigation entry as active and removes the 'active'
	 * property from all other entries. The templates can use this for
	 * highlighting the current position of the user.
	 *
	 * @deprecated 8.1.0 Use \OC::$server->getNavigationManager()->setActiveEntry() instead
	 * @since 4.0.0
	 */
	public static function setActiveNavigationEntry( $id ) {
		\OC::$server->getNavigationManager()->setActiveEntry($id);
		return true;
	}

	/**
	 * Register a Configuration Screen that should appear in the personal settings section.
	 * @param string $app appid
	 * @param string $page page to be included
	 * @return void
	 * @since 4.0.0
	*/
	public static function registerPersonal( $app, $page ) {
		\OC_App::registerPersonal( $app, $page );
	}

	/**
	 * Register a Configuration Screen that should appear in the Admin section.
	 * @param string $app string appid
	 * @param string $page string page to be included
	 * @return void
	 * @since 4.0.0
	 */
	public static function registerAdmin( $app, $page ) {
		\OC_App::registerAdmin( $app, $page );
	}

	/**
	 * Read app metadata from the info.xml file
	 * @param string $app id of the app or the path of the info.xml file
	 * @param boolean $path (optional)
	 * @return array|null
	 * @deprecated 14.0.0 ise \OC::$server->getAppManager()->getAppInfo($appId)
	 * @since 4.0.0
	*/
	public static function getAppInfo( $app, $path=false ) {
		return \OC_App::getAppInfo( $app, $path);
	}

	/**
	 * checks whether or not an app is enabled
	 * @param string $app
	 * @return boolean
	 *
	 * This function checks whether or not an app is enabled.
	 * @since 4.0.0
	 * @deprecated 13.0.0 use \OC::$server->getAppManager()->isEnabledForUser($appId)
	 */
	public static function isEnabled( $app ) {
		return \OC::$server->getAppManager()->isEnabledForUser( $app );
	}

	/**
	 * Check if the app is enabled, redirects to home if not
	 * @param string $app
	 * @return void
	 * @since 4.0.0
	 * @deprecated 9.0.0 ownCloud core will handle disabled apps and redirects to valid URLs
	*/
	public static function checkAppEnabled( $app ) {
	}

	/**
	 * Get the last version of the app from appinfo/info.xml
	 * @param string $app
	 * @return string
	 * @since 4.0.0
	 * @deprecated 14.0.0 use \OC::$server->getAppManager()->getAppVersion($appId)
	 */
	public static function getAppVersion( $app ) {
		return \OC::$server->getAppManager()->getAppVersion($app);
	}
}
