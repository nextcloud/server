<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Birk Borkason <daniel.niccoli@gmail.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author cmeh <cmeh@users.noreply.github.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author helix84 <helix84@centrum.sk>
 * @author Individual IT Services <info@individual-it.net>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Markus Goetz <markus@woboq.com>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Stefan Rado <owncloud@sradonia.net>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class OC_Util {
	public static $scripts = array();
	public static $styles = array();
	public static $headers = array();
	private static $rootMounted = false;
	private static $fsSetup = false;

	protected static function getAppManager() {
		return \OC::$server->getAppManager();
	}

	private static function initLocalStorageRootFS() {
		// mount local file backend as root
		$configDataDirectory = \OC::$server->getSystemConfig()->getValue("datadirectory", OC::$SERVERROOT . "/data");
		//first set up the local "root" storage
		\OC\Files\Filesystem::initMountManager();
		if (!self::$rootMounted) {
			\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', array('datadir' => $configDataDirectory), '/');
			self::$rootMounted = true;
		}
	}

	/**
	 * mounting an object storage as the root fs will in essence remove the
	 * necessity of a data folder being present.
	 * TODO make home storage aware of this and use the object storage instead of local disk access
	 *
	 * @param array $config containing 'class' and optional 'arguments'
	 */
	private static function initObjectStoreRootFS($config) {
		// check misconfiguration
		if (empty($config['class'])) {
			\OCP\Util::writeLog('files', 'No class given for objectstore', \OCP\Util::ERROR);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = array();
		}

		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);
		// mount with plain / root object store implementation
		$config['class'] = '\OC\Files\ObjectStore\ObjectStoreStorage';

		// mount object storage as root
		\OC\Files\Filesystem::initMountManager();
		if (!self::$rootMounted) {
			\OC\Files\Filesystem::mount($config['class'], $config['arguments'], '/');
			self::$rootMounted = true;
		}
	}

	/**
	 * Can be set up
	 *
	 * @param string $user
	 * @return boolean
	 * @description configure the initial filesystem based on the configuration
	 */
	public static function setupFS($user = '') {
		//setting up the filesystem twice can only lead to trouble
		if (self::$fsSetup) {
			return false;
		}

		\OC::$server->getEventLogger()->start('setup_fs', 'Setup filesystem');

		// If we are not forced to load a specific user we load the one that is logged in
		if ($user === null) {
			$user = '';
		} else if ($user == "" && OC_User::isLoggedIn()) {
			$user = OC_User::getUser();
		}

		// load all filesystem apps before, so no setup-hook gets lost
		OC_App::loadApps(array('filesystem'));

		// the filesystem will finish when $user is not empty,
		// mark fs setup here to avoid doing the setup from loading
		// OC_Filesystem
		if ($user != '') {
			self::$fsSetup = true;
		}

		\OC\Files\Filesystem::initMountManager();

		\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
		\OC\Files\Filesystem::addStorageWrapper('mount_options', function ($mountPoint, \OCP\Files\Storage $storage, \OCP\Files\Mount\IMountPoint $mount) {
			if ($storage->instanceOfStorage('\OC\Files\Storage\Common')) {
				/** @var \OC\Files\Storage\Common $storage */
				$storage->setMountOptions($mount->getOptions());
			}
			return $storage;
		});

		\OC\Files\Filesystem::addStorageWrapper('enable_sharing', function ($mountPoint, \OCP\Files\Storage $storage, \OCP\Files\Mount\IMountPoint $mount) {
			if (!$mount->getOption('enable_sharing', true)) {
				return new \OC\Files\Storage\Wrapper\PermissionsMask([
					'storage' => $storage,
					'mask' => \OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_SHARE
				]);
			}
			return $storage;
		});

		// install storage availability wrapper, before most other wrappers
		\OC\Files\Filesystem::addStorageWrapper('oc_availability', function ($mountPoint, $storage) {
			/** @var \OCP\Files\Storage $storage */
			if (!$storage->instanceOfStorage('\OC\Files\Storage\Shared') && !$storage->isLocal()) {
				return new \OC\Files\Storage\Wrapper\Availability(['storage' => $storage]);
			}
			return $storage;
		});

		\OC\Files\Filesystem::addStorageWrapper('oc_encoding', function ($mountPoint, \OCP\Files\Storage $storage, \OCP\Files\Mount\IMountPoint $mount) {
			if ($mount->getOption('encoding_compatibility', false) && !$storage->instanceOfStorage('\OC\Files\Storage\Shared') && !$storage->isLocal()) {
				return new \OC\Files\Storage\Wrapper\Encoding(['storage' => $storage]);
			}
			return $storage;
		});

		\OC\Files\Filesystem::addStorageWrapper('oc_quota', function ($mountPoint, $storage) {
			// set up quota for home storages, even for other users
			// which can happen when using sharing

			/**
			 * @var \OC\Files\Storage\Storage $storage
			 */
			if ($storage->instanceOfStorage('\OC\Files\Storage\Home')
				|| $storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')
			) {
				/** @var \OC\Files\Storage\Home $storage */
				if (is_object($storage->getUser())) {
					$user = $storage->getUser()->getUID();
					$quota = OC_Util::getUserQuota($user);
					if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
						return new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => $quota, 'root' => 'files'));
					}
				}
			}

			return $storage;
		});

		OC_Hook::emit('OC_Filesystem', 'preSetup', array('user' => $user));
		\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(true);

		//check if we are using an object storage
		$objectStore = \OC::$server->getSystemConfig()->getValue('objectstore', null);
		if (isset($objectStore)) {
			self::initObjectStoreRootFS($objectStore);
		} else {
			self::initLocalStorageRootFS();
		}

		if ($user != '' && !OCP\User::userExists($user)) {
			\OC::$server->getEventLogger()->end('setup_fs');
			return false;
		}

		//if we aren't logged in, there is no use to set up the filesystem
		if ($user != "") {

			$userDir = '/' . $user . '/files';

			//jail the user into his "home" directory
			\OC\Files\Filesystem::init($user, $userDir);

			OC_Hook::emit('OC_Filesystem', 'setup', array('user' => $user, 'user_dir' => $userDir));
		}
		\OC::$server->getEventLogger()->end('setup_fs');
		return true;
	}

	/**
	 * check if a password is required for each public link
	 *
	 * @return boolean
	 */
	public static function isPublicLinkPasswordRequired() {
		$appConfig = \OC::$server->getAppConfig();
		$enforcePassword = $appConfig->getValue('core', 'shareapi_enforce_links_password', 'no');
		return ($enforcePassword === 'yes') ? true : false;
	}

	/**
	 * check if sharing is disabled for the current user
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param IUser|null $user
	 * @return bool
	 */
	public static function isSharingDisabledForUser(IConfig $config, IGroupManager $groupManager, $user) {
		if ($config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$groupsList = $config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = json_decode($groupsList);
			if (is_null($excludedGroups)) {
				$excludedGroups = explode(',', $groupsList);
				$newValue = json_encode($excludedGroups);
				$config->setAppValue('core', 'shareapi_exclude_groups_list', $newValue);
			}
			$usersGroups = $groupManager->getUserGroupIds($user);
			if (!empty($usersGroups)) {
				$remainingGroups = array_diff($usersGroups, $excludedGroups);
				// if the user is only in groups which are disabled for sharing then
				// sharing is also disabled for the user
				if (empty($remainingGroups)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * check if share API enforces a default expire date
	 *
	 * @return boolean
	 */
	public static function isDefaultExpireDateEnforced() {
		$isDefaultExpireDateEnabled = \OCP\Config::getAppValue('core', 'shareapi_default_expire_date', 'no');
		$enforceDefaultExpireDate = false;
		if ($isDefaultExpireDateEnabled === 'yes') {
			$value = \OCP\Config::getAppValue('core', 'shareapi_enforce_expire_date', 'no');
			$enforceDefaultExpireDate = ($value === 'yes') ? true : false;
		}

		return $enforceDefaultExpireDate;
	}

	/**
	 * Get the quota of a user
	 *
	 * @param string $userId
	 * @return int Quota bytes
	 */
	public static function getUserQuota($userId) {
		$user = \OC::$server->getUserManager()->get($userId);
		if (is_null($user)) {
			return \OCP\Files\FileInfo::SPACE_UNLIMITED;
		}
		$userQuota = $user->getQuota();
		if($userQuota === 'none') {
			return \OCP\Files\FileInfo::SPACE_UNLIMITED;
		}
		return OC_Helper::computerFileSize($userQuota);
	}

	/**
	 * copies the skeleton to the users /files
	 *
	 * @param String $userId
	 * @param \OCP\Files\Folder $userDirectory
	 */
	public static function copySkeleton($userId, \OCP\Files\Folder $userDirectory) {

		$skeletonDirectory = \OCP\Config::getSystemValue('skeletondirectory', \OC::$SERVERROOT . '/core/skeleton');

		if (!empty($skeletonDirectory)) {
			\OCP\Util::writeLog(
				'files_skeleton',
				'copying skeleton for '.$userId.' from '.$skeletonDirectory.' to '.$userDirectory->getFullPath('/'),
				\OCP\Util::DEBUG
			);
			self::copyr($skeletonDirectory, $userDirectory);
			// update the file cache
			$userDirectory->getStorage()->getScanner()->scan('', \OC\Files\Cache\Scanner::SCAN_RECURSIVE);
		}
	}

	/**
	 * copies a directory recursively by using streams
	 *
	 * @param string $source
	 * @param \OCP\Files\Folder $target
	 * @return void
	 */
	public static function copyr($source, \OCP\Files\Folder $target) {
		$dir = opendir($source);
		while (false !== ($file = readdir($dir))) {
			if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
				if (is_dir($source . '/' . $file)) {
					$child = $target->newFolder($file);
					self::copyr($source . '/' . $file, $child);
				} else {
					$child = $target->newFile($file);
					stream_copy_to_stream(fopen($source . '/' . $file,'r'), $child->fopen('w'));
				}
			}
		}
		closedir($dir);
	}

	/**
	 * @return void
	 */
	public static function tearDownFS() {
		\OC\Files\Filesystem::tearDown();
		self::$fsSetup = false;
		self::$rootMounted = false;
	}

	/**
	 * get the current installed version of ownCloud
	 *
	 * @return array
	 */
	public static function getVersion() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Version');
	}

	/**
	 * get the current installed version string of ownCloud
	 *
	 * @return string
	 */
	public static function getVersionString() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_VersionString');
	}

	/**
	 * @description get the current installed edition of ownCloud. There is the community
	 * edition that just returns an empty string and the enterprise edition
	 * that returns "Enterprise".
	 * @return string
	 */
	public static function getEditionString() {
		if (OC_App::isEnabled('enterprise_key')) {
			return "Enterprise";
		} else {
			return "";
		}

	}

	/**
	 * @description get the update channel of the current installed of ownCloud.
	 * @return string
	 */
	public static function getChannel() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Channel');
	}

	/**
	 * @description get the build number of the current installed of ownCloud.
	 * @return string
	 */
	public static function getBuild() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Build');
	}

	/**
	 * @description load the version.php into the session as cache
	 */
	private static function loadVersion() {
		$timestamp = filemtime(OC::$SERVERROOT . '/version.php');
		if (!\OC::$server->getSession()->exists('OC_Version') or OC::$server->getSession()->get('OC_Version_Timestamp') != $timestamp) {
			require OC::$SERVERROOT . '/version.php';
			$session = \OC::$server->getSession();
			/** @var $timestamp int */
			$session->set('OC_Version_Timestamp', $timestamp);
			/** @var $OC_Version string */
			$session->set('OC_Version', $OC_Version);
			/** @var $OC_VersionString string */
			$session->set('OC_VersionString', $OC_VersionString);
			/** @var $OC_Build string */
			$session->set('OC_Build', $OC_Build);
			
			// Allow overriding update channel
			
			if (\OC::$server->getSystemConfig()->getValue('installed', false)) {
				$channel = \OC::$server->getAppConfig()->getValue('core', 'OC_Channel');
			} else {
				/** @var $OC_Channel string */
				$channel = $OC_Channel;
			}
			
			if (!is_null($channel)) {
				$session->set('OC_Channel', $channel);
			} else {
				/** @var $OC_Channel string */
				$session->set('OC_Channel', $OC_Channel);
			}
		}
	}

	/**
	 * generates a path for JS/CSS files. If no application is provided it will create the path for core.
	 *
	 * @param string $application application to get the files from
	 * @param string $directory directory within this application (css, js, vendor, etc)
	 * @param string $file the file inside of the above folder
	 * @return string the path
	 */
	private static function generatePath($application, $directory, $file) {
		if (is_null($file)) {
			$file = $application;
			$application = "";
		}
		if (!empty($application)) {
			return "$application/$directory/$file";
		} else {
			return "$directory/$file";
		}
	}

	/**
	 * add a javascript file
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @param bool $prepend prepend the Script to the beginning of the list
	 * @return void
	 */
	public static function addScript($application, $file = null, $prepend = false) {
		$path = OC_Util::generatePath($application, 'js', $file);

		// core js files need separate handling
		if ($application !== 'core' && $file !== null) {
			self::addTranslations ( $application );
		}
		self::addExternalResource($application, $prepend, $path, "script");
	}

	/**
	 * add a javascript file from the vendor sub folder
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @param bool $prepend prepend the Script to the beginning of the list
	 * @return void
	 */
	public static function addVendorScript($application, $file = null, $prepend = false) {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		self::addExternalResource($application, $prepend, $path, "script");
	}

	/**
	 * add a translation JS file
	 *
	 * @param string $application application id
	 * @param string $languageCode language code, defaults to the current language
	 * @param bool $prepend prepend the Script to the beginning of the list 
	 */
	public static function addTranslations($application, $languageCode = null, $prepend = false) {
		if (is_null($languageCode)) {
			$languageCode = \OC_L10N::findLanguage($application);
		}
		if (!empty($application)) {
			$path = "$application/l10n/$languageCode";
		} else {
			$path = "l10n/$languageCode";
		}
		self::addExternalResource($application, $prepend, $path, "script");
	}

	/**
	 * add a css file
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @param bool $prepend prepend the Style to the beginning of the list
	 * @return void
	 */
	public static function addStyle($application, $file = null, $prepend = false) {
		$path = OC_Util::generatePath($application, 'css', $file);
		self::addExternalResource($application, $prepend, $path, "style");
	}

	/**
	 * add a css file from the vendor sub folder
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @param bool $prepend prepend the Style to the beginning of the list
	 * @return void
	 */
	public static function addVendorStyle($application, $file = null, $prepend = false) {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		self::addExternalResource($application, $prepend, $path, "style");
	}

	/**
	 * add an external resource css/js file
	 *
	 * @param string $application application id
	 * @param bool $prepend prepend the file to the beginning of the list
	 * @param string $path 
	 * @param string $type (script or style)
	 * @return void
	 */
	private static function addExternalResource($application, $prepend, $path, $type = "script") {

		if ($type === "style") {
			if (!in_array($path, self::$styles)) {
				if ($prepend === true) {
					array_unshift ( self::$styles, $path );
				} else {
					self::$styles[] = $path;
				}
			}
		} elseif ($type === "script") {
			if (!in_array($path, self::$scripts)) {
				if ($prepend === true) {
					array_unshift ( self::$scripts, $path );
				} else {
					self::$scripts [] = $path;
				}
			}
		}
	}

	/**
	 * Add a custom element to the header
	 * If $text is null then the element will be written as empty element.
	 * So use "" to get a closing tag.
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader($tag, $attributes, $text=null) {
		self::$headers[] = array(
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		);
	}

	/**
	 * formats a timestamp in the "right" way
	 *
	 * @param int $timestamp
	 * @param bool $dateOnly option to omit time from the result
	 * @param DateTimeZone|string $timeZone where the given timestamp shall be converted to
	 * @return string timestamp
	 *
	 * @deprecated Use \OC::$server->query('DateTimeFormatter') instead
	 */
	public static function formatDate($timestamp, $dateOnly = false, $timeZone = null) {
		if ($timeZone !== null && !$timeZone instanceof \DateTimeZone) {
			$timeZone = new \DateTimeZone($timeZone);
		}

		/** @var \OC\DateTimeFormatter $formatter */
		$formatter = \OC::$server->query('DateTimeFormatter');
		if ($dateOnly) {
			return $formatter->formatDate($timestamp, 'long', $timeZone);
		}
		return $formatter->formatDateTime($timestamp, 'long', 'long', $timeZone);
	}

	/**
	 * check if the current server configuration is suitable for ownCloud
	 *
	 * @param \OCP\IConfig $config
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer(\OCP\IConfig $config) {
		$l = \OC::$server->getL10N('lib');
		$errors = array();
		$CONFIG_DATADIRECTORY = $config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');

		if (!self::needUpgrade($config) && $config->getSystemValue('installed', false)) {
			// this check needs to be done every time
			$errors = self::checkDataDirectoryValidity($CONFIG_DATADIRECTORY);
		}

		// Assume that if checkServer() succeeded before in this session, then all is fine.
		if (\OC::$server->getSession()->exists('checkServer_succeeded') && \OC::$server->getSession()->get('checkServer_succeeded')) {
			return $errors;
		}

		$webServerRestart = false;
		$setup = new \OC\Setup($config, \OC::$server->getIniWrapper(), \OC::$server->getL10N('lib'),
			new \OC_Defaults(), \OC::$server->getLogger(), \OC::$server->getSecureRandom());

		$urlGenerator = \OC::$server->getURLGenerator();

		$availableDatabases = $setup->getSupportedDatabases();
		if (empty($availableDatabases)) {
			$errors[] = array(
				'error' => $l->t('No database drivers (sqlite, mysql, or postgresql) installed.'),
				'hint' => '' //TODO: sane hint
			);
			$webServerRestart = true;
		}

		// Check if server running on Windows platform
		if(OC_Util::runningOnWindows()) {
			$errors[] = [
				'error' => $l->t('Microsoft Windows Platform is not supported'),
				'hint' => $l->t('Running ownCloud Server on the Microsoft Windows platform is not supported. We suggest you ' .
					'use a Linux server in a virtual machine if you have no option for migrating the server itself. ' .
					'Find Linux packages as well as easy to deploy virtual machine images on <a href="%s">%s</a>. ' .
					'For migrating existing installations to Linux you can find some tips and a migration script ' .
					'in <a href="%s">our documentation</a>.',
					['https://owncloud.org/install/', 'owncloud.org/install/', 'https://owncloud.org/?p=8045'])
			];
		}

		// Check if config folder is writable.
		if(!OC_Helper::isReadOnlyConfigEnabled()) {
			if (!is_writable(OC::$configDir) or !is_readable(OC::$configDir)) {
				$errors[] = array(
					'error' => $l->t('Cannot write into "config" directory'),
					'hint' => $l->t('This can usually be fixed by '
						. '%sgiving the webserver write access to the config directory%s.',
						array('<a href="' . $urlGenerator->linkToDocs('admin-dir_permissions') . '" target="_blank" rel="noreferrer">', '</a>'))
				);
			}
		}

		// Check if there is a writable install folder.
		if ($config->getSystemValue('appstoreenabled', true)) {
			if (OC_App::getInstallPath() === null
				|| !is_writable(OC_App::getInstallPath())
				|| !is_readable(OC_App::getInstallPath())
			) {
				$errors[] = array(
					'error' => $l->t('Cannot write into "apps" directory'),
					'hint' => $l->t('This can usually be fixed by '
						. '%sgiving the webserver write access to the apps directory%s'
						. ' or disabling the appstore in the config file.',
						array('<a href="' . $urlGenerator->linkToDocs('admin-dir_permissions') . '" target="_blank" rel="noreferrer">', '</a>'))
				);
			}
		}
		// Create root dir.
		if ($config->getSystemValue('installed', false)) {
			if (!is_dir($CONFIG_DATADIRECTORY)) {
				$success = @mkdir($CONFIG_DATADIRECTORY);
				if ($success) {
					$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
				} else {
					$errors[] = array(
						'error' => $l->t('Cannot create "data" directory (%s)', array($CONFIG_DATADIRECTORY)),
						'hint' => $l->t('This can usually be fixed by '
							. '<a href="%s" target="_blank" rel="noreferrer">giving the webserver write access to the root directory</a>.',
							array($urlGenerator->linkToDocs('admin-dir_permissions')))
					);
				}
			} else if (!is_writable($CONFIG_DATADIRECTORY) or !is_readable($CONFIG_DATADIRECTORY)) {
				//common hint for all file permissions error messages
				$permissionsHint = $l->t('Permissions can usually be fixed by '
					. '%sgiving the webserver write access to the root directory%s.',
					array('<a href="' . $urlGenerator->linkToDocs('admin-dir_permissions') . '" target="_blank" rel="noreferrer">', '</a>'));
				$errors[] = array(
					'error' => 'Data directory (' . $CONFIG_DATADIRECTORY . ') not writable by ownCloud',
					'hint' => $permissionsHint
				);
			} else {
				$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
			}
		}

		if (!OC_Util::isSetLocaleWorking()) {
			$errors[] = array(
				'error' => $l->t('Setting locale to %s failed',
					array('en_US.UTF-8/fr_FR.UTF-8/es_ES.UTF-8/de_DE.UTF-8/ru_RU.UTF-8/'
						. 'pt_BR.UTF-8/it_IT.UTF-8/ja_JP.UTF-8/zh_CN.UTF-8')),
				'hint' => $l->t('Please install one of these locales on your system and restart your webserver.')
			);
		}

		// Contains the dependencies that should be checked against
		// classes = class_exists
		// functions = function_exists
		// defined = defined
		// ini = ini_get
		// If the dependency is not found the missing module name is shown to the EndUser
		// When adding new checks always verify that they pass on Travis as well
		// for ini settings, see https://github.com/owncloud/administration/blob/master/travis-ci/custom.ini
		$dependencies = array(
			'classes' => array(
				'ZipArchive' => 'zip',
				'DOMDocument' => 'dom',
				'XMLWriter' => 'XMLWriter',
				'XMLReader' => 'XMLReader',
			),
			'functions' => [
				'xml_parser_create' => 'libxml',
				'mb_strcut' => 'mb multibyte',
				'ctype_digit' => 'ctype',
				'json_encode' => 'JSON',
				'gd_info' => 'GD',
				'gzencode' => 'zlib',
				'iconv' => 'iconv',
				'simplexml_load_string' => 'SimpleXML',
				'hash' => 'HASH Message Digest Framework',
				'curl_init' => 'cURL',
			],
			'defined' => array(
				'PDO::ATTR_DRIVER_NAME' => 'PDO'
			),
			'ini' => [
				'default_charset' => 'UTF-8',
			],
		);
		$missingDependencies = array();
		$invalidIniSettings = [];
		$moduleHint = $l->t('Please ask your server administrator to install the module.');

		/**
		 * FIXME: The dependency check does not work properly on HHVM on the moment
		 *        and prevents installation. Once HHVM is more compatible with our
		 *        approach to check for these values we should re-enable those
		 *        checks.
		 */
		$iniWrapper = \OC::$server->getIniWrapper();
		if (!self::runningOnHhvm()) {
			foreach ($dependencies['classes'] as $class => $module) {
				if (!class_exists($class)) {
					$missingDependencies[] = $module;
				}
			}
			foreach ($dependencies['functions'] as $function => $module) {
				if (!function_exists($function)) {
					$missingDependencies[] = $module;
				}
			}
			foreach ($dependencies['defined'] as $defined => $module) {
				if (!defined($defined)) {
					$missingDependencies[] = $module;
				}
			}
			foreach ($dependencies['ini'] as $setting => $expected) {
				if (is_bool($expected)) {
					if ($iniWrapper->getBool($setting) !== $expected) {
						$invalidIniSettings[] = [$setting, $expected];
					}
				}
				if (is_int($expected)) {
					if ($iniWrapper->getNumeric($setting) !== $expected) {
						$invalidIniSettings[] = [$setting, $expected];
					}
				}
				if (is_string($expected)) {
					if (strtolower($iniWrapper->getString($setting)) !== strtolower($expected)) {
						$invalidIniSettings[] = [$setting, $expected];
					}
				}
			}
		}

		foreach($missingDependencies as $missingDependency) {
			$errors[] = array(
				'error' => $l->t('PHP module %s not installed.', array($missingDependency)),
				'hint' => $moduleHint
			);
			$webServerRestart = true;
		}
		foreach($invalidIniSettings as $setting) {
			if(is_bool($setting[1])) {
				$setting[1] = ($setting[1]) ? 'on' : 'off';
			}
			$errors[] = [
				'error' => $l->t('PHP setting "%s" is not set to "%s".', [$setting[0], var_export($setting[1], true)]),
				'hint' =>  $l->t('Adjusting this setting in php.ini will make ownCloud run again')
			];
			$webServerRestart = true;
		}

		/**
		 * The mbstring.func_overload check can only be performed if the mbstring
		 * module is installed as it will return null if the checking setting is
		 * not available and thus a check on the boolean value fails.
		 *
		 * TODO: Should probably be implemented in the above generic dependency
		 *       check somehow in the long-term.
		 */
		if($iniWrapper->getBool('mbstring.func_overload') !== null &&
			$iniWrapper->getBool('mbstring.func_overload') === true) {
			$errors[] = array(
				'error' => $l->t('mbstring.func_overload is set to "%s" instead of the expected value "0"', [$iniWrapper->getString('mbstring.func_overload')]),
				'hint' => $l->t('To fix this issue set <code>mbstring.func_overload</code> to <code>0</code> in your php.ini')
			);
		}

		if(function_exists('xml_parser_create') &&
			version_compare('2.7.0', LIBXML_DOTTED_VERSION) === 1) {
			$errors[] = array(
				'error' => $l->t('libxml2 2.7.0 is at least required. Currently %s is installed.', [LIBXML_DOTTED_VERSION]),
				'hint' => $l->t('To fix this issue update your libxml2 version and restart your web server.')
			);
		}

		if (!self::isAnnotationsWorking()) {
			$errors[] = array(
				'error' => $l->t('PHP is apparently set up to strip inline doc blocks. This will make several core apps inaccessible.'),
				'hint' => $l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')
			);
		}

		if (!\OC::$CLI && $webServerRestart) {
			$errors[] = array(
				'error' => $l->t('PHP modules have been installed, but they are still listed as missing?'),
				'hint' => $l->t('Please ask your server administrator to restart the web server.')
			);
		}

		$errors = array_merge($errors, self::checkDatabaseVersion());

		// Cache the result of this function
		\OC::$server->getSession()->set('checkServer_succeeded', count($errors) == 0);

		return $errors;
	}

	/**
	 * Check the database version
	 *
	 * @return array errors array
	 */
	public static function checkDatabaseVersion() {
		$l = \OC::$server->getL10N('lib');
		$errors = array();
		$dbType = \OC::$server->getSystemConfig()->getValue('dbtype', 'sqlite');
		if ($dbType === 'pgsql') {
			// check PostgreSQL version
			try {
				$result = \OC_DB::executeAudited('SHOW SERVER_VERSION');
				$data = $result->fetchRow();
				if (isset($data['server_version'])) {
					$version = $data['server_version'];
					if (version_compare($version, '9.0.0', '<')) {
						$errors[] = array(
							'error' => $l->t('PostgreSQL >= 9 required'),
							'hint' => $l->t('Please upgrade your database version')
						);
					}
				}
			} catch (\Doctrine\DBAL\DBALException $e) {
				$logger = \OC::$server->getLogger();
				$logger->warning('Error occurred while checking PostgreSQL version, assuming >= 9');
				$logger->logException($e);
			}
		}
		return $errors;
	}

	/**
	 * Check for correct file permissions of data directory
	 *
	 * @param string $dataDirectory
	 * @return array arrays with error messages and hints
	 */
	public static function checkDataDirectoryPermissions($dataDirectory) {
		$l = \OC::$server->getL10N('lib');
		$errors = array();
		if (self::runningOnWindows()) {
			//TODO: permissions checks for windows hosts
		} else {
			$permissionsModHint = $l->t('Please change the permissions to 0770 so that the directory'
				. ' cannot be listed by other users.');
			$perms = substr(decoct(@fileperms($dataDirectory)), -3);
			if (substr($perms, -1) != '0') {
				chmod($dataDirectory, 0770);
				clearstatcache();
				$perms = substr(decoct(@fileperms($dataDirectory)), -3);
				if (substr($perms, 2, 1) != '0') {
					$errors[] = array(
						'error' => $l->t('Data directory (%s) is readable by other users', array($dataDirectory)),
						'hint' => $permissionsModHint
					);
				}
			}
		}
		return $errors;
	}

	/**
	 * Check that the data directory exists and is valid by
	 * checking the existence of the ".ocdata" file.
	 *
	 * @param string $dataDirectory data directory path
	 * @return array errors found
	 */
	public static function checkDataDirectoryValidity($dataDirectory) {
		$l = \OC::$server->getL10N('lib');
		$errors = [];
		if (!self::runningOnWindows() && $dataDirectory[0] !== '/') {
			$errors[] = [
				'error' => $l->t('Data directory (%s) must be an absolute path', [$dataDirectory]),
				'hint' => $l->t('Check the value of "datadirectory" in your configuration')
			];
		}
		if (!file_exists($dataDirectory . '/.ocdata')) {
			$errors[] = [
				'error' => $l->t('Data directory (%s) is invalid', [$dataDirectory]),
				'hint' => $l->t('Please check that the data directory contains a file' .
					' ".ocdata" in its root.')
			];
		}
		return $errors;
	}

	/**
	 * Check if the user is logged in, redirects to home if not. With
	 * redirect URL parameter to the request URI.
	 *
	 * @return void
	 */
	public static function checkLoggedIn() {
		// Check if we are a user
		if (!OC_User::isLoggedIn()) {
			header('Location: ' . \OC::$server->getURLGenerator()->linkToRoute(
						'core.login.showLoginForm',
						[
							'redirect_url' => urlencode(\OC::$server->getRequest()->getRequestUri()),
						]
					)
			);
			exit();
		}
		// Redirect to index page if 2FA challenge was not solved yet
		if (\OC::$server->getTwoFactorAuthManager()->needsSecondFactor()) {
			header('Location: ' . \OCP\Util::linkToAbsolute('', 'index.php'));
			exit();
		}
	}

	/**
	 * Check if the user is a admin, redirects to home if not
	 *
	 * @return void
	 */
	public static function checkAdminUser() {
		OC_Util::checkLoggedIn();
		if (!OC_User::isAdminUser(OC_User::getUser())) {
			header('Location: ' . \OCP\Util::linkToAbsolute('', 'index.php'));
			exit();
		}
	}

	/**
	 * Check if it is allowed to remember login.
	 *
	 * @note Every app can set 'rememberlogin' to 'false' to disable the remember login feature
	 *
	 * @return bool
	 */
	public static function rememberLoginAllowed() {

		$apps = OC_App::getEnabledApps();

		foreach ($apps as $app) {
			$appInfo = OC_App::getAppInfo($app);
			if (isset($appInfo['rememberlogin']) && $appInfo['rememberlogin'] === 'false') {
				return false;
			}

		}
		return true;
	}

	/**
	 * Check if the user is a subadmin, redirects to home if not
	 *
	 * @return null|boolean $groups where the current user is subadmin
	 */
	public static function checkSubAdminUser() {
		OC_Util::checkLoggedIn();
		$userObject = \OC::$server->getUserSession()->getUser();
		$isSubAdmin = false;
		if($userObject !== null) {
			$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
		}

		if (!$isSubAdmin) {
			header('Location: ' . \OCP\Util::linkToAbsolute('', 'index.php'));
			exit();
		}
		return true;
	}

	/**
	 * Returns the URL of the default page
	 * based on the system configuration and
	 * the apps visible for the current user
	 *
	 * @return string URL
	 */
	public static function getDefaultPageUrl() {
		$urlGenerator = \OC::$server->getURLGenerator();
		// Deny the redirect if the URL contains a @
		// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
		if (isset($_REQUEST['redirect_url']) && strpos($_REQUEST['redirect_url'], '@') === false) {
			$location = $urlGenerator->getAbsoluteURL(urldecode($_REQUEST['redirect_url']));
		} else {
			$defaultPage = \OC::$server->getAppConfig()->getValue('core', 'defaultpage');
			if ($defaultPage) {
				$location = $urlGenerator->getAbsoluteURL($defaultPage);
			} else {
				$appId = 'files';
				$defaultApps = explode(',', \OCP\Config::getSystemValue('defaultapp', 'files'));
				// find the first app that is enabled for the current user
				foreach ($defaultApps as $defaultApp) {
					$defaultApp = OC_App::cleanAppId(strip_tags($defaultApp));
					if (static::getAppManager()->isEnabledForUser($defaultApp)) {
						$appId = $defaultApp;
						break;
					}
				}

				if(getenv('front_controller_active') === 'true') {
					$location = $urlGenerator->getAbsoluteURL('/apps/' . $appId . '/');
				} else {
					$location = $urlGenerator->getAbsoluteURL('/index.php/apps/' . $appId . '/');
				}
			}
		}
		return $location;
	}

	/**
	 * Redirect to the user default page
	 *
	 * @return void
	 */
	public static function redirectToDefaultPage() {
		$location = self::getDefaultPageUrl();
		header('Location: ' . $location);
		exit();
	}

	/**
	 * get an id unique for this instance
	 *
	 * @return string
	 */
	public static function getInstanceId() {
		$id = \OC::$server->getSystemConfig()->getValue('instanceid', null);
		if (is_null($id)) {
			// We need to guarantee at least one letter in instanceid so it can be used as the session_name
			$id = 'oc' . \OC::$server->getSecureRandom()->generate(10, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);
			\OC::$server->getSystemConfig()->setValue('instanceid', $id);
		}
		return $id;
	}

	/**
	 * Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array $value
	 * @return string|array an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML($value) {
		if (is_array($value)) {
			$value = array_map(function($value) {
				return self::sanitizeHTML($value);
			}, $value);
		} else {
			// Specify encoding for PHP<5.4
			$value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		return $value;
	}

	/**
	 * Public function to encode url parameters
	 *
	 * This function is used to encode path to file before output.
	 * Encoding is done according to RFC 3986 with one exception:
	 * Character '/' is preserved as is.
	 *
	 * @param string $component part of URI to encode
	 * @return string
	 */
	public static function encodePath($component) {
		$encoded = rawurlencode($component);
		$encoded = str_replace('%2F', '/', $encoded);
		return $encoded;
	}


	public function createHtaccessTestFile(\OCP\IConfig $config) {
		// php dev server does not support htaccess
		if (php_sapi_name() === 'cli-server') {
			return false;
		}

		// testdata
		$fileName = '/htaccesstest.txt';
		// test content containing the string "HTACCESSFAIL"
		$testContent = 'HTACCESSFAIL: This is used for testing whether htaccess is properly enabled to disallow access from the outside. This file can be safely removed.';

		// creating a test file
		$testFile = $config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

		if (file_exists($testFile)) {// already running this test, possible recursive call
			return false;
		}

		$fp = @fopen($testFile, 'w');
		if (!$fp) {
			throw new OC\HintException('Can\'t create test file to check for working .htaccess file.',
				'Make sure it is possible for the webserver to write to ' . $testFile);
		}
		fwrite($fp, $testContent);
		fclose($fp);
	}

	/**
	 * Check if the .htaccess file is working
	 * @param \OCP\IConfig $config
	 * @return bool
	 * @throws Exception
	 * @throws \OC\HintException If the test file can't get written.
	 */
	public function isHtaccessWorking(\OCP\IConfig $config) {

		if (\OC::$CLI || !$config->getSystemValue('check_for_working_htaccess', true)) {
			return true;
		}

		$testContent = $this->createHtaccessTestFile($config);
		if ($testContent === false) {
			return false;
		}

		$fileName = '/htaccesstest.txt';
		$testFile = $config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

		// accessing the file via http
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL(OC::$WEBROOT . '/data' . $fileName);
		try {
			$content = \OC::$server->getHTTPClientService()->newClient()->get($url)->getBody();
		} catch (\Exception $e) {
			$content = false;
		}

		// cleanup
		@unlink($testFile);

		/*
		 * If the content is not equal to test content our .htaccess
		 * is working as required
		 */
		return $content !== $testContent;
	}

	/**
	 * Check if the setlocal call does not work. This can happen if the right
	 * local packages are not available on the server.
	 *
	 * @return bool
	 */
	public static function isSetLocaleWorking() {
		// setlocale test is pointless on Windows
		if (OC_Util::runningOnWindows()) {
			return true;
		}

		\Patchwork\Utf8\Bootup::initLocale();
		if ('' === basename('§')) {
			return false;
		}
		return true;
	}

	/**
	 * Check if it's possible to get the inline annotations
	 *
	 * @return bool
	 */
	public static function isAnnotationsWorking() {
		$reflection = new \ReflectionMethod(__METHOD__);
		$docs = $reflection->getDocComment();

		return (is_string($docs) && strlen($docs) > 50);
	}

	/**
	 * Check if the PHP module fileinfo is loaded.
	 *
	 * @return bool
	 */
	public static function fileInfoLoaded() {
		return function_exists('finfo_open');
	}

	/**
	 * clear all levels of output buffering
	 *
	 * @return void
	 */
	public static function obEnd() {
		while (ob_get_level()) {
			ob_end_clean();
		}
	}

	/**
	 * Checks whether the server is running on Windows
	 *
	 * @return bool true if running on Windows, false otherwise
	 */
	public static function runningOnWindows() {
		return (substr(PHP_OS, 0, 3) === "WIN");
	}

	/**
	 * Checks whether the server is running on Mac OS X
	 *
	 * @return bool true if running on Mac OS X, false otherwise
	 */
	public static function runningOnMac() {
		return (strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN');
	}

	/**
	 * Checks whether server is running on HHVM
	 *
	 * @return bool True if running on HHVM, false otherwise
	 */
	public static function runningOnHhvm() {
		return defined('HHVM_VERSION');
	}

	/**
	 * Handles the case that there may not be a theme, then check if a "default"
	 * theme exists and take that one
	 *
	 * @return string the theme
	 */
	public static function getTheme() {
		$theme = \OC::$server->getSystemConfig()->getValue("theme", '');

		if ($theme === '') {
			if (is_dir(OC::$SERVERROOT . '/themes/default')) {
				$theme = 'default';
			}
		}

		return $theme;
	}

	/**
	 * Clear a single file from the opcode cache
	 * This is useful for writing to the config file
	 * in case the opcode cache does not re-validate files
	 * Returns true if successful, false if unsuccessful:
	 * caller should fall back on clearing the entire cache
	 * with clearOpcodeCache() if unsuccessful
	 *
	 * @param string $path the path of the file to clear from the cache
	 * @return bool true if underlying function returns true, otherwise false
	 */
	public static function deleteFromOpcodeCache($path) {
		$ret = false;
		if ($path) {
			// APC >= 3.1.1
			if (function_exists('apc_delete_file')) {
				$ret = @apc_delete_file($path);
			}
			// Zend OpCache >= 7.0.0, PHP >= 5.5.0
			if (function_exists('opcache_invalidate')) {
				$ret = opcache_invalidate($path);
			}
		}
		return $ret;
	}

	/**
	 * Clear the opcode cache if one exists
	 * This is necessary for writing to the config file
	 * in case the opcode cache does not re-validate files
	 *
	 * @return void
	 */
	public static function clearOpcodeCache() {
		// APC
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}
		// Zend Opcache
		if (function_exists('accelerator_reset')) {
			accelerator_reset();
		}
		// XCache
		if (function_exists('xcache_clear_cache')) {
			if (\OC::$server->getIniWrapper()->getBool('xcache.admin.enable_auth')) {
				\OCP\Util::writeLog('core', 'XCache opcode cache will not be cleared because "xcache.admin.enable_auth" is enabled.', \OCP\Util::WARN);
			} else {
				@xcache_clear_cache(XC_TYPE_PHP, 0);
			}
		}
		// Opcache (PHP >= 5.5)
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
	}

	/**
	 * Normalize a unicode string
	 *
	 * @param string $value a not normalized string
	 * @return bool|string
	 */
	public static function normalizeUnicode($value) {
		if(Normalizer::isNormalized($value)) {
			return $value;
		}

		$normalizedValue = Normalizer::normalize($value);
		if ($normalizedValue === null || $normalizedValue === false) {
			\OC::$server->getLogger()->warning('normalizing failed for "' . $value . '"', ['app' => 'core']);
			return $value;
		}

		return $normalizedValue;
	}

	/**
	 * @param boolean|string $file
	 * @return string
	 */
	public static function basename($file) {
		$file = rtrim($file, '/');
		$t = explode('/', $file);
		return array_pop($t);
	}

	/**
	 * A human readable string is generated based on version, channel and build number
	 *
	 * @return string
	 */
	public static function getHumanVersion() {
		$version = OC_Util::getVersionString() . ' (' . OC_Util::getChannel() . ')';
		$build = OC_Util::getBuild();
		if (!empty($build) and OC_Util::getChannel() === 'daily') {
			$version .= ' Build:' . $build;
		}
		return $version;
	}

	/**
	 * Returns whether the given file name is valid
	 *
	 * @param string $file file name to check
	 * @return bool true if the file name is valid, false otherwise
	 * @deprecated use \OC\Files\View::verifyPath()
	 */
	public static function isValidFileName($file) {
		$trimmed = trim($file);
		if ($trimmed === '') {
			return false;
		}
		if (\OC\Files\Filesystem::isIgnoredDir($trimmed)) {
			return false;
		}
		foreach (str_split($trimmed) as $char) {
			if (strpos(\OCP\Constants::FILENAME_INVALID_CHARS, $char) !== false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check whether the instance needs to perform an upgrade,
	 * either when the core version is higher or any app requires
	 * an upgrade.
	 *
	 * @param \OCP\IConfig $config
	 * @return bool whether the core or any app needs an upgrade
	 * @throws \OC\HintException When the upgrade from the given version is not allowed
	 */
	public static function needUpgrade(\OCP\IConfig $config) {
		if ($config->getSystemValue('installed', false)) {
			$installedVersion = $config->getSystemValue('version', '0.0.0');
			$currentVersion = implode('.', \OCP\Util::getVersion());
			$versionDiff = version_compare($currentVersion, $installedVersion);
			if ($versionDiff > 0) {
				return true;
			} else if ($config->getSystemValue('debug', false) && $versionDiff < 0) {
				// downgrade with debug
				$installedMajor = explode('.', $installedVersion);
				$installedMajor = $installedMajor[0] . '.' . $installedMajor[1];
				$currentMajor = explode('.', $currentVersion);
				$currentMajor = $currentMajor[0] . '.' . $currentMajor[1];
				if ($installedMajor === $currentMajor) {
					// Same major, allow downgrade for developers
					return true;
				} else {
					// downgrade attempt, throw exception
					throw new \OC\HintException('Downgrading is not supported and is likely to cause unpredictable issues (from ' . $installedVersion . ' to ' . $currentVersion . ')');
				}
			} else if ($versionDiff < 0) {
				// downgrade attempt, throw exception
				throw new \OC\HintException('Downgrading is not supported and is likely to cause unpredictable issues (from ' . $installedVersion . ' to ' . $currentVersion . ')');
			}

			// also check for upgrades for apps (independently from the user)
			$apps = \OC_App::getEnabledApps(false, true);
			$shouldUpgrade = false;
			foreach ($apps as $app) {
				if (\OC_App::shouldUpgrade($app)) {
					$shouldUpgrade = true;
					break;
				}
			}
			return $shouldUpgrade;
		} else {
			return false;
		}
	}

}
