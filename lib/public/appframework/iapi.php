<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OCP\AppFramework;


/**
 * A few very basic and frequently used API functions are combined in here
 */
interface IApi {

	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 */
	function getAppName();


	/**
	 * Creates a new navigation entry
	 * @param array $entry containing: id, name, order, icon and href key
	 */
	function addNavigationEntry(array $entry);


	/**
	 * Gets the userid of the current user
	 * @return string the user id of the current user
	 */
	function getUserId();


	/**
	 * Sets the current navigation entry to the currently running app
	 */
	function activateNavigationEntry();


	/**
	 * Adds a new javascript file
	 * @param string $scriptName the name of the javascript in js/ without the suffix
	 * @param string $appName the name of the app, defaults to the current one
	 */
	function addScript($scriptName, $appName = null);


	/**
	 * Adds a new css file
	 * @param string $styleName the name of the css file in css/without the suffix
	 * @param string $appName the name of the app, defaults to the current one
	 */
	function addStyle($styleName, $appName = null);


	/**
	 * shorthand for addScript for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	function add3rdPartyScript($name);


	/**
	 * shorthand for addStyle for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	function add3rdPartyStyle($name);

	/**
	 * Looks up a system-wide defined value
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	function getSystemValue($key);

	/**
	 * Sets a new system-wide value
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 */
	function setSystemValue($key, $value);


	/**
	 * Looks up an app-specific defined value
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	function getAppValue($key, $appName = null);


	/**
	 * Writes a new app-specific value
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 */
	function setAppValue($key, $value, $appName = null);


	/**
	 * Shortcut for setting a user defined value
	 * @param string $key the key under which the value is being stored
	 * @param string $value the value that you want to store
	 * @param string $userId the userId of the user that we want to store the value under, defaults to the current one
	 */
	function setUserValue($key, $value, $userId = null);


	/**
	 * Shortcut for getting a user defined value
	 * @param string $key the key under which the value is being stored
	 * @param string $userId the userId of the user that we want to store the value under, defaults to the current one
	 */
	function getUserValue($key, $userId = null);

	/**
	 * Returns the translation object
	 * @return \OC_L10N the translation object
	 *
	 * FIXME: returns private object / should be retrieved from teh ServerContainer
	 */
	function getTrans();


	/**
	 * Used to abstract the owncloud database access away
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @return \OCP\DB a query object
	 *
	 * FIXME: returns non public interface / object
	 */
	function prepareQuery($sql, $limit=null, $offset=null);


	/**
	 * Used to get the id of the just inserted element
	 * @param string $tableName the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 *
	 * FIXME: move to db object
	 */
	function getInsertId($tableName);


	/**
	 * Returns the URL for a route
	 * @param string $routeName the name of the route
	 * @param array $arguments an array with arguments which will be filled into the url
	 * @return string the url
	 */
	function linkToRoute($routeName, $arguments=array());


	/**
	 * Returns an URL for an image or file
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 */
	function linkTo($file, $appName=null);


	/**
	 * Returns the link to an image, like link to but only with prepending img/
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 */
	function imagePath($file, $appName = null);


	/**
	 * Makes an URL absolute
	 * @param string $url the url
	 * @return string the absolute url
	 *
	 * FIXME: function should live in Request / Response
	 */
	function getAbsoluteURL($url);


	/**
	 * links to a file
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 * @deprecated replaced with linkToRoute()
	 * @return string the url
	 */
	function linkToAbsolute($file, $appName = null);


	/**
	 * Checks if an app is enabled
	 * @param string $appName the name of an app
	 * @return bool true if app is enabled
	 */
	public function isAppEnabled($appName);


	/**
	 * Writes a function into the error log
	 * @param string $msg the error message to be logged
	 * @param int $level the error level
	 *
	 * FIXME: add logger instance to ServerContainer
	 */
	function log($msg, $level = null);


	/**
	 * Returns a template
	 * @param string $templateName the name of the template
	 * @param string $renderAs how it should be rendered
	 * @param string $appName the name of the app
	 * @return \OCP\Template a new template
	 */
	function getTemplate($templateName, $renderAs='user', $appName=null);
}
