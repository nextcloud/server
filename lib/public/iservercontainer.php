<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
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
 * Server container interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;


/**
 * Class IServerContainer
 * @package OCP
 *
 * This container holds all ownCloud services
 */
interface IServerContainer {

	/**
	 * The contacts manager will act as a broker between consumers for contacts information and
	 * providers which actual deliver the contact information.
	 *
	 * @return \OCP\Contacts\IManager
	 */
	function getContactsManager();

	/**
	 * The current request object holding all information about the request currently being processed
	 * is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest|null
	 */
	function getRequest();

	/**
	 * Returns the preview manager which can create preview images for a given file
	 *
	 * @return \OCP\IPreview
	 */
	function getPreviewManager();

	/**
	 * Returns the tag manager which can get and set tags for different object types
	 *
	 * @see \OCP\ITagManager::load()
	 * @return \OCP\ITagManager
	 */
	function getTagManager();

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return \OCP\Files\Folder
	 */
	function getRootFolder();

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 */
	function getUserFolder($userId = null);

	/**
	 * Returns an app-specific view in ownClouds data directory
	 *
	 * @return \OCP\Files\Folder
	 */
	function getAppFolder();

	/**
	 * Returns a user manager
	 *
	 * @return \OCP\IUserManager
	 */
	function getUserManager();

	/**
	 * Returns a group manager
	 *
	 * @return \OCP\IGroupManager
	 */
	function getGroupManager();

	/**
	 * Returns the user session
	 *
	 * @return \OCP\IUserSession
	 */
	function getUserSession();

	/**
	 * Returns the navigation manager
	 *
	 * @return \OCP\INavigationManager
	 */
	function getNavigationManager();

	/**
	 * Returns the config manager
	 *
	 * @return \OCP\IConfig
	 */
	function getConfig();

	/**
	 * Returns a Crypto instance
	 *
	 * @return \OCP\Security\ICrypto
	 */
	function getCrypto();

	/**
	 * Returns a Hasher instance
	 *
	 * @return \OCP\Security\IHasher
	 */
	function getHasher();

	/**
	 * Returns an instance of the db facade
	 * @return \OCP\IDb
	 */
	function getDb();


	/**
	 * Returns the app config manager
	 *
	 * @return \OCP\IAppConfig
	 */
	function getAppConfig();

	/**
	 * get an L10N instance
	 * @param string $app appid
	 * @param string $lang
	 * @return \OCP\IL10N
	 */
	function getL10N($app, $lang = null);

	/**
	 * Returns the URL generator
	 *
	 * @return \OCP\IURLGenerator
	 */
	function getURLGenerator();

	/**
	 * Returns the Helper
	 *
	 * @return \OCP\IHelper
	 */
	function getHelper();

	/**
	 * Returns an ICache instance
	 *
	 * @return \OCP\ICache
	 */
	function getCache();

	/**
	 * Returns an \OCP\CacheFactory instance
	 *
	 * @return \OCP\ICacheFactory
	 */
	function getMemCacheFactory();

	/**
	 * Returns the current session
	 *
	 * @return \OCP\ISession
	 */
	function getSession();

	/**
	 * Returns the activity manager
	 *
	 * @return \OCP\Activity\IManager
	 */
	function getActivityManager();

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 */
	function getDatabaseConnection();

	/**
	 * Returns an avatar manager, used for avatar functionality
	 *
	 * @return \OCP\IAvatarManager
	 */
	function getAvatarManager();

	/**
	 * Returns an job list for controlling background jobs
	 *
	 * @return \OCP\BackgroundJob\IJobList
	 */
	function getJobList();

	/**
	 * Returns a logger instance
	 *
	 * @return \OCP\ILogger
	 */
	function getLogger();

	/**
	 * Returns a router for generating and matching urls
	 *
	 * @return \OCP\Route\IRouter
	 */
	function getRouter();

	/**
	 * Returns a search instance
	 *
	 * @return \OCP\ISearch
	 */
	function getSearch();

	/**
	 * Get the certificate manager for the user
	 *
	 * @param \OCP\IUser $user (optional) if not specified the current loggedin user is used
	 * @return \OCP\ICertificateManager
	 */
	function getCertificateManager($user = null);
	
	/**
	 * Create a new event source
	 *
	 * @return \OCP\IEventSource
	 */
	function createEventSource();

	/**
	 * Returns an instance of the HTTP helper class
	 * @return \OC\HTTPHelper
	 */
	function getHTTPHelper();

	/**
	 * Get the active event logger
	 *
	 * @return \OCP\Diagnostics\IEventLogger
	 */
	function getEventLogger();

	/**
	 * Get the active query logger
	 *
	 * The returned logger only logs data when debug mode is enabled
	 *
	 * @return \OCP\Diagnostics\IQueryLogger
	 */
	function getQueryLogger();

	/**
	 * Get the manager for temporary files and folders
	 *
	 * @return \OCP\ITempManager
	 */
	function getTempManager();

	/**
	 * Get the app manager
	 *
	 * @return \OCP\App\IAppManager
	 */
	function getAppManager();

	/**
	 * Get the webroot
	 *
	 * @return string
	 */
	function getWebRoot();

	/**
	 * @return \OCP\Files\Config\IMountProviderCollection
	 */
	function getMountProviderCollection();

	/**
	 * Get the IniWrapper
	 *
	 * @return \bantu\IniGetWrapper\IniGetWrapper
	 */
	 function getIniWrapper();
}
