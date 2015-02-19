<?php

/**
 * Class for utility functions
 *
 */
class OC_Util {
	public static $scripts = array();
	public static $styles = array();
	public static $headers = array();
	private static $rootMounted = false;
	private static $fsSetup = false;

	private static function initLocalStorageRootFS() {
		// mount local file backend as root
		$configDataDirectory = OC_Config::getValue("datadirectory", OC::$SERVERROOT . "/data");
		//first set up the local "root" storage
		\OC\Files\Filesystem::initMounts();
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
		\OC\Files\Filesystem::initMounts();
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
		if ($user == "" && OC_User::isLoggedIn()) {
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

		//check if we are using an object storage
		$objectStore = OC_Config::getValue('objectstore');
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
			\OC\Files\Filesystem::addStorageWrapper('oc_quota', function ($mountPoint, $storage) {
				// set up quota for home storages, even for other users
				// which can happen when using sharing

				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage->instanceOfStorage('\OC\Files\Storage\Home')
					|| $storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')
				) {
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

			$userDir = '/' . $user . '/files';

			//jail the user into his "home" directory
			\OC\Files\Filesystem::init($user, $userDir);

			$fileOperationProxy = new OC_FileProxy_FileOperations();
			OC_FileProxy::register($fileOperationProxy);

			//trigger creation of user home and /files folder
			\OC::$server->getUserFolder($user);

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
	 *
	 * @return boolean
	 */
	public static function isSharingDisabledForUser() {
		if (\OC_Appconfig::getValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$user = \OCP\User::getUser();
			$groupsList = \OC_Appconfig::getValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = explode(',', $groupsList);
			$usersGroups = \OC_Group::getUserGroups($user);
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
	 * @param string $user
	 * @return int Quota bytes
	 */
	public static function getUserQuota($user) {
		$config = \OC::$server->getConfig();
		$userQuota = $config->getUserValue($user, 'files', 'quota', 'default');
		if ($userQuota === 'default') {
			$userQuota = $config->getAppValue('files', 'default_quota', 'none');
		}
		if($userQuota === 'none') {
			return \OCP\Files\FileInfo::SPACE_UNLIMITED;
		}else{
			return OC_Helper::computerFileSize($userQuota);
		}
	}

	/**
	 * copies the skeleton to the users /files
	 *
	 * @param \OC\User\User $user
	 * @param \OCP\Files\Folder $userDirectory
	 */
	public static function copySkeleton(\OC\User\User $user, \OCP\Files\Folder $userDirectory) {

		$skeletonDirectory = \OCP\Config::getSystemValue('skeletondirectory', \OC::$SERVERROOT . '/core/skeleton');

		if (!empty($skeletonDirectory)) {
			\OCP\Util::writeLog(
				'files_skeleton',
				'copying skeleton for '.$user->getUID().' from '.$skeletonDirectory.' to '.$userDirectory->getFullPath('/'),
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
			require 'version.php';
			$session = \OC::$server->getSession();
			/** @var $timestamp int */
			$session->set('OC_Version_Timestamp', $timestamp);
			/** @var $OC_Version string */
			$session->set('OC_Version', $OC_Version);
			/** @var $OC_VersionString string */
			$session->set('OC_VersionString', $OC_VersionString);
			/** @var $OC_Channel string */
			$session->set('OC_Channel', $OC_Channel);
			/** @var $OC_Build string */
			$session->set('OC_Build', $OC_Build);
		}
	}

	/**
	 * generates a path for JS/CSS files. If no application is provided it will create the path for core.
	 *
	 * @param string $application application to get the files from
	 * @param string $directory directory withing this application (css, js, vendor, etc)
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
	 * @return void
	 */
	public static function addScript($application, $file = null) {
		$path = OC_Util::generatePath($application, 'js', $file);
		if (!in_array($path, self::$scripts)) {
			// core js files need separate handling
			if ($application !== 'core' && $file !== null) {
				self::addTranslations($application);
			}
			self::$scripts[] = $path;
		}
	}

	/**
	 * add a javascript file from the vendor sub folder
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @return void
	 */
	public static function addVendorScript($application, $file = null) {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		if (!in_array($path, self::$scripts)) {
			self::$scripts[] = $path;
		}
	}

	/**
	 * add a translation JS file
	 *
	 * @param string $application application id
	 * @param string $languageCode language code, defaults to the current language
	 */
	public static function addTranslations($application, $languageCode = null) {
		if (is_null($languageCode)) {
			$l = new \OC_L10N($application);
			$languageCode = $l->getLanguageCode($application);
		}
		if (!empty($application)) {
			$path = "$application/l10n/$languageCode";
		} else {
			$path = "l10n/$languageCode";
		}
		if (!in_array($path, self::$scripts)) {
			self::$scripts[] = $path;
		}
	}

	/**
	 * add a css file
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @return void
	 */
	public static function addStyle($application, $file = null) {
		$path = OC_Util::generatePath($application, 'css', $file);
		if (!in_array($path, self::$styles)) {
			self::$styles[] = $path;
		}
	}

	/**
	 * add a css file from the vendor sub folder
	 *
	 * @param string $application application id
	 * @param string|null $file filename
	 * @return void
	 */
	public static function addVendorStyle($application, $file = null) {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		if (!in_array($path, self::$styles)) {
			self::$styles[] = $path;
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
		$setup = new OC_Setup($config);
		$availableDatabases = $setup->getSupportedDatabases();
		if (empty($availableDatabases)) {
			$errors[] = array(
				'error' => $l->t('No database drivers (sqlite, mysql, or postgresql) installed.'),
				'hint' => '' //TODO: sane hint
			);
			$webServerRestart = true;
		}

		// Check if config folder is writable.
		if (!is_writable(OC::$configDir) or !is_readable(OC::$configDir)) {
			$errors[] = array(
				'error' => $l->t('Cannot write into "config" directory'),
				'hint' => $l->t('This can usually be fixed by '
					. '%sgiving the webserver write access to the config directory%s.',
					array('<a href="' . \OC_Helper::linkToDocs('admin-dir_permissions') . '" target="_blank">', '</a>'))
			);
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
						array('<a href="' . \OC_Helper::linkToDocs('admin-dir_permissions') . '" target="_blank">', '</a>'))
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
							. '<a href="%s" target="_blank">giving the webserver write access to the root directory</a>.',
							array(OC_Helper::linkToDocs('admin-dir_permissions')))
					);
				}
			} else if (!is_writable($CONFIG_DATADIRECTORY) or !is_readable($CONFIG_DATADIRECTORY)) {
				//common hint for all file permissions error messages
				$permissionsHint = $l->t('Permissions can usually be fixed by '
					. '%sgiving the webserver write access to the root directory%s.',
					array('<a href="' . \OC_Helper::linkToDocs('admin-dir_permissions') . '" target="_blank">', '</a>'));
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
		// If the dependency is not found the missing module name is shown to the EndUser
		$dependencies = array(
			'classes' => array(
				'ZipArchive' => 'zip',
				'DOMDocument' => 'dom',
				'XMLWriter' => 'XMLWriter'
			),
			'functions' => array(
				'xml_parser_create' => 'libxml',
				'mb_detect_encoding' => 'mb multibyte',
				'ctype_digit' => 'ctype',
				'json_encode' => 'JSON',
				'gd_info' => 'GD',
				'gzencode' => 'zlib',
				'iconv' => 'iconv',
				'simplexml_load_string' => 'SimpleXML',
				'hash' => 'HASH Message Digest Framework'
			),
			'defined' => array(
				'PDO::ATTR_DRIVER_NAME' => 'PDO'
			)
		);
		$missingDependencies = array();
		$moduleHint = $l->t('Please ask your server administrator to install the module.');

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

		foreach($missingDependencies as $missingDependency) {
			$errors[] = array(
				'error' => $l->t('PHP module %s not installed.', array($missingDependency)),
				'hint' => $moduleHint
			);
			$webServerRestart = true;
		}

		if (version_compare(phpversion(), '5.4.0', '<')) {
			$errors[] = array(
				'error' => $l->t('PHP %s or higher is required.', '5.4.0'),
				'hint' => $l->t('Please ask your server administrator to update PHP to the latest version.'
					. ' Your PHP version is no longer supported by ownCloud and the PHP community.')
			);
			$webServerRestart = true;
		}

		/**
		 * PHP 5.6 ships with a PHP setting which throws notices by default for a
		 * lot of endpoints. Thus we need to ensure that the value is set to -1
		 *
		 * FIXME: Due to https://github.com/owncloud/core/pull/13593#issuecomment-71178078
		 * this check is disabled for HHVM at the moment. This should get re-evaluated
		 * at a later point.
		 *
		 * @link https://github.com/owncloud/core/issues/13592
		 */
		if(version_compare(phpversion(), '5.6.0', '>=') &&
			!self::runningOnHhvm() &&
			\OC::$server->getIniWrapper()->getNumeric('always_populate_raw_post_data') !== -1) {
			$errors[] = array(
				'error' => $l->t('PHP is configured to populate raw post data. Since PHP 5.6 this will lead to PHP throwing notices for perfectly valid code.'),
				'hint' => $l->t('To fix this issue set <code>always_populate_raw_post_data</code> to <code>-1</code> in your php.ini')
			);
		}

		if (!self::isAnnotationsWorking()) {
			$errors[] = array(
				'error' => $l->t('PHP is apparently setup to strip inline doc blocks. This will make several core apps inaccessible.'),
				'hint' => $l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')
			);
		}

		if ($webServerRestart) {
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
		$dbType = \OC_Config::getValue('dbtype', 'sqlite');
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
				\OCP\Util::logException('core', $e);
				$errors[] = array(
					'error' => $l->t('Error occurred while checking PostgreSQL version'),
					'hint' => $l->t('Please make sure you have PostgreSQL >= 9 or'
						. ' check the logs for more information about the error')
				);
			}
		}
		return $errors;
	}


	/**
	 * check if there are still some encrypted files stored
	 *
	 * @return boolean
	 */
	public static function encryptedFiles() {
		//check if encryption was enabled in the past
		$encryptedFiles = false;
		if (OC_App::isEnabled('files_encryption') === false) {
			$view = new OC\Files\View('/' . OCP\User::getUser());
			$keysPath = '/files_encryption/keys';
			if ($view->is_dir($keysPath)) {
				$dircontent = $view->getDirectoryContent($keysPath);
				if (!empty($dircontent)) {
					$encryptedFiles = true;
				}
			}
		}

		return $encryptedFiles;
	}

	/**
	 * check if a backup from the encryption keys exists
	 *
	 * @return boolean
	 */
	public static function backupKeysExists() {
		//check if encryption was enabled in the past
		$backupExists = false;
		if (OC_App::isEnabled('files_encryption') === false) {
			$view = new OC\Files\View('/' . OCP\User::getUser());
			$backupPath = '/files_encryption/backup.decryptAll';
			if ($view->is_dir($backupPath)) {
				$dircontent = $view->getDirectoryContent($backupPath);
				if (!empty($dircontent)) {
					$backupExists = true;
				}
			}
		}

		return $backupExists;
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
	 * @return bool true if the data directory is valid, false otherwise
	 */
	public static function checkDataDirectoryValidity($dataDirectory) {
		$l = \OC::$server->getL10N('lib');
		$errors = array();
		if (!file_exists($dataDirectory . '/.ocdata')) {
			$errors[] = array(
				'error' => $l->t('Data directory (%s) is invalid', array($dataDirectory)),
				'hint' => $l->t('Please check that the data directory contains a file' .
					' ".ocdata" in its root.')
			);
		}
		return $errors;
	}

	/**
	 * @param array $errors
	 * @param string[] $messages
	 */
	public static function displayLoginPage($errors = array(), $messages = []) {
		$parameters = array();
		foreach ($errors as $value) {
			$parameters[$value] = true;
		}
		$parameters['messages'] = $messages;
		if (!empty($_REQUEST['user'])) {
			$parameters["username"] = $_REQUEST['user'];
			$parameters['user_autofocus'] = false;
		} else {
			$parameters["username"] = '';
			$parameters['user_autofocus'] = true;
		}
		if (isset($_REQUEST['redirect_url'])) {
			$redirectUrl = $_REQUEST['redirect_url'];
			$parameters['redirect_url'] = urlencode($redirectUrl);
		}

		$parameters['alt_login'] = OC_App::getAlternativeLogIns();
		$parameters['rememberLoginAllowed'] = self::rememberLoginAllowed();
		OC_Template::printGuestPage("", "login", $parameters);
	}


	/**
	 * Check if the app is enabled, redirects to home if not
	 *
	 * @param string $app
	 * @return void
	 */
	public static function checkAppEnabled($app) {
		if (!OC_App::isEnabled($app)) {
			header('Location: ' . OC_Helper::linkToAbsolute('', 'index.php'));
			exit();
		}
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
			header('Location: ' . OC_Helper::linkToAbsolute('', 'index.php',
					array('redirect_url' => OC_Request::requestUri())
				));
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
			header('Location: ' . OC_Helper::linkToAbsolute('', 'index.php'));
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
		if (!OC_SubAdmin::isSubAdmin(OC_User::getUser())) {
			header('Location: ' . OC_Helper::linkToAbsolute('', 'index.php'));
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
			$defaultPage = OC_Appconfig::getValue('core', 'defaultpage');
			if ($defaultPage) {
				$location = $urlGenerator->getAbsoluteURL($defaultPage);
			} else {
				$appId = 'files';
				$defaultApps = explode(',', \OCP\Config::getSystemValue('defaultapp', 'files'));
				// find the first app that is enabled for the current user
				foreach ($defaultApps as $defaultApp) {
					$defaultApp = OC_App::cleanAppId(strip_tags($defaultApp));
					if (OC_App::isEnabled($defaultApp)) {
						$appId = $defaultApp;
						break;
					}
				}
				$location = $urlGenerator->getAbsoluteURL('/index.php/apps/' . $appId . '/');
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
		$id = OC_Config::getValue('instanceid', null);
		if (is_null($id)) {
			// We need to guarantee at least one letter in instanceid so it can be used as the session_name
			$id = 'oc' . \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(10, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);
			OC_Config::$object->setValue('instanceid', $id);
		}
		return $id;
	}

	/**
	 * Register an get/post call. Important to prevent CSRF attacks.
	 *
	 * @return string Generated token.
	 * @description
	 * Creates a 'request token' (random) and stores it inside the session.
	 * Ever subsequent (ajax) request must use such a valid token to succeed,
	 * otherwise the request will be denied as a protection against CSRF.
	 * @see OC_Util::isCallRegistered()
	 */
	public static function callRegister() {
		// Check if a token exists
		if (!\OC::$server->getSession()->exists('requesttoken')) {
			// No valid token found, generate a new one.
			$requestToken = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(30);
			\OC::$server->getSession()->set('requesttoken', $requestToken);
		} else {
			// Valid token already exists, send it
			$requestToken = \OC::$server->getSession()->get('requesttoken');
		}
		return ($requestToken);
	}

	/**
	 * Check an ajax get/post call if the request token is valid.
	 *
	 * @return boolean False if request token is not set or is invalid.
	 * @see OC_Util::callRegister()
	 */
	public static function isCallRegistered() {
		return \OC::$server->getRequest()->passesCSRFCheck();
	}

	/**
	 * Check an ajax get/post call if the request token is valid. Exit if not.
	 *
	 * @return void
	 */
	public static function callCheck() {
		if (!OC_Util::isCallRegistered()) {
			exit();
		}
	}

	/**
	 * Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array &$value
	 * @return string|array an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML(&$value) {
		if (is_array($value)) {
			array_walk_recursive($value, 'OC_Util::sanitizeHTML');
		} else {
			//Specify encoding for PHP<5.4
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

	/**
	 * Check if the .htaccess file is working
	 *
	 * @throws OC\HintException If the testfile can't get written.
	 * @return bool
	 * @description Check if the .htaccess file is working by creating a test
	 * file in the data directory and trying to access via http
	 */
	public static function isHtaccessWorking() {
		if (\OC::$CLI || !OC::$server->getConfig()->getSystemValue('check_for_working_htaccess', true)) {
			return true;
		}

		// php dev server does not support htaccess
		if (php_sapi_name() === 'cli-server') {
			return false;
		}

		// testdata
		$fileName = '/htaccesstest.txt';
		$testContent = 'testcontent';

		// creating a test file
		$testFile = OC::$server->getConfig()->getSystemValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

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

		// accessing the file via http
		$url = OC_Helper::makeURLAbsolute(OC::$WEBROOT . '/data' . $fileName);
		$content = self::getUrlContent($url);

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
	 * Check if the ownCloud server can connect to the internet
	 *
	 * @return bool
	 */
	public static function isInternetConnectionWorking() {
		// in case there is no internet connection on purpose return false
		if (self::isInternetConnectionEnabled() === false) {
			return false;
		}

		// in case the connection is via proxy return true to avoid connecting to owncloud.org
		if (OC_Config::getValue('proxy', '') != '') {
			return true;
		}

		// try to connect to owncloud.org to see if http connections to the internet are possible.
		$connected = @fsockopen("www.owncloud.org", 80);
		if ($connected) {
			fclose($connected);
			return true;
		} else {
			// second try in case one server is down
			$connected = @fsockopen("apps.owncloud.com", 80);
			if ($connected) {
				fclose($connected);
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if the connection to the internet is disabled on purpose
	 *
	 * @return string
	 */
	public static function isInternetConnectionEnabled() {
		return \OC_Config::getValue("has_internet_connection", true);
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
	 * Generates a cryptographic secure pseudo-random string
	 *
	 * @param int $length of the random string
	 * @return string
	 * @deprecated Use \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate($length); instead
	 */
	public static function generateRandomBytes($length = 30) {
		return \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate($length, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);
	}

	/**
	 * Checks if a secure random number generator is available
	 *
	 * @return true
	 * @deprecated Function will be removed in the future and does only return true.
	 */
	public static function secureRNGAvailable() {
		return true;
	}

	/**
	 * Get URL content
	 * @param string $url Url to get content
	 * @deprecated Use \OC::$server->getHTTPHelper()->getUrlContent($url);
	 * @throws Exception If the URL does not start with http:// or https://
	 * @return string of the response or false on error
	 * This function get the content of a page via curl, if curl is enabled.
	 * If not, file_get_contents is used.
	 */
	public static function getUrlContent($url) {
		try {
			return \OC::$server->getHTTPHelper()->getUrlContent($url);
		} catch (\Exception $e) {
			throw $e;
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
		$theme = OC_Config::getValue("theme", '');

		if ($theme === '') {
			if (is_dir(OC::$SERVERROOT . '/themes/default')) {
				$theme = 'default';
			}
		}

		return $theme;
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
			if (ini_get('xcache.admin.enable_auth')) {
				OC_Log::write('core', 'XCache opcode cache will not be cleared because "xcache.admin.enable_auth" is enabled.', \OC_Log::WARN);
			} else {
				xcache_clear_cache(XC_TYPE_PHP, 0);
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
	 */
	public static function isValidFileName($file) {
		$trimmed = trim($file);
		if ($trimmed === '') {
			return false;
		}
		if ($trimmed === '.' || $trimmed === '..') {
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
	 */
	public static function needUpgrade(\OCP\IConfig $config) {
		if ($config->getSystemValue('installed', false)) {
			$installedVersion = $config->getSystemValue('version', '0.0.0');
			$currentVersion = implode('.', OC_Util::getVersion());
			if (version_compare($currentVersion, $installedVersion, '>')) {
				return true;
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

	/**
	 * Check if PhpCharset config is UTF-8
	 *
	 * @return string
	 */
	public static function isPhpCharSetUtf8() {
		return strtoupper(ini_get('default_charset')) === 'UTF-8';
	}

}
