<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use bantu\IniGetWrapper\IniGetWrapper;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OC\Files\SetupManager;
use OCP\Files\Template\ITemplateManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

class OC_Util {
	public static $scripts = [];
	public static $styles = [];
	public static $headers = [];

	/** @var array Local cache of version.php */
	private static $versionCache = null;

	protected static function getAppManager() {
		return \OC::$server->getAppManager();
	}

	/**
	 * Setup the file system
	 *
	 * @description configure the initial filesystem based on the configuration
	 * @suppress PhanDeprecatedFunction
	 * @suppress PhanAccessMethodInternal
	 */
	public static function setupFS(?string $user = ''): bool {
		// If we are not forced to load a specific user we load the one that is logged in
		if ($user === '') {
			$userObject = Server::get(\OCP\IUserSession::class)->getUser();
		} else {
			$userObject = Server::get(\OCP\IUserManager::class)->get($user);
		}

		/** @var SetupManager $setupManager */
		$setupManager = Server::get(SetupManager::class);

		if ($userObject) {
			$setupManager->setupForUser($userObject);
		} else {
			$setupManager->setupRoot();
		}
		return true;
	}

	/**
	 * Check if a password is required for each public link
	 *
	 * @suppress PhanDeprecatedFunction
	 */
	public static function isPublicLinkPasswordRequired(bool $checkGroupMembership = true): bool {
		/** @var IManager $shareManager */
		$shareManager = Server::get(IManager::class);
		return $shareManager->shareApiLinkEnforcePassword($checkGroupMembership);
	}

	/**
	 * check if sharing is disabled for the current user
	 *
	 */
	public static function isSharingDisabledForUser(IConfig $config, IGroupManager $groupManager, ?IUser $user): bool {
		/** @var IManager $shareManager */
		$shareManager = Server::get(IManager::class);
		$userId = $user ? $user->getUID() : null;
		return $shareManager->sharingDisabledForUser($userId);
	}

	/**
	 * check if share API enforces a default expire date
	 *
	 * @suppress PhanDeprecatedFunction
	 */
	public static function isDefaultExpireDateEnforced(): bool {
		/** @var IManager $shareManager */
		$shareManager = Server::get(IManager::class);
		return $shareManager->shareApiLinkDefaultExpireDateEnforced();
	}

	/**
	 * Get the quota of a user
	 *
	 */
	public static function getUserQuota(?IUser $user): int|float|false {
		if (is_null($user)) {
			return \OCP\Files\FileInfo::SPACE_UNLIMITED;
		}
		$userQuota = $user->getQuota();
		if ($userQuota === 'none') {
			return \OCP\Files\FileInfo::SPACE_UNLIMITED;
		}
		return OC_Helper::computerFileSize($userQuota);
	}

	/**
	 * copies the skeleton to the users /files
	 *
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @suppress PhanDeprecatedFunction
	 */
	public static function copySkeleton(string $userId, \OCP\Files\Folder $userDirectory): void {
		/** @var LoggerInterface $logger */
		$logger = Server::get(LoggerInterface::class);
		$config = Server::get(IConfig::class);

		$plainSkeletonDirectory = $config->getSystemValueString('skeletondirectory', \OC::$SERVERROOT . '/core/skeleton');
		$userLang = Server::get(IFactory::class)->findLanguage();
		$skeletonDirectory = str_replace('{lang}', $userLang, $plainSkeletonDirectory);

		if (!file_exists($skeletonDirectory)) {
			$dialectStart = strpos($userLang, '_');
			if ($dialectStart !== false) {
				$skeletonDirectory = str_replace('{lang}', substr($userLang, 0, $dialectStart), $plainSkeletonDirectory);
			}
			if ($dialectStart === false || !file_exists($skeletonDirectory)) {
				$skeletonDirectory = str_replace('{lang}', 'default', $plainSkeletonDirectory);
			}
			if (!file_exists($skeletonDirectory)) {
				$skeletonDirectory = '';
			}
		}

		$instanceId = $config->getSystemValueString('instanceid', '');

		if ($instanceId === null) {
			throw new \RuntimeException('no instance id!');
		}
		$appdata = 'appdata_' . $instanceId;
		if ($userId === $appdata) {
			throw new \RuntimeException('username is reserved name: ' . $appdata);
		}

		if (!empty($skeletonDirectory)) {
			$logger->debug('copying skeleton for '.$userId.' from '.$skeletonDirectory.' to '.$userDirectory->getFullPath('/'), ['app' => 'files_skeleton']);
			self::copyr($skeletonDirectory, $userDirectory);
			// update the file cache
			$userDirectory->getStorage()->getScanner()->scan('', \OC\Files\Cache\Scanner::SCAN_RECURSIVE);

			/** @var ITemplateManager $templateManager */
			$templateManager = Server::get(ITemplateManager::class);
			$templateManager->initializeTemplateDirectory(null, $userId);
		}
	}

	/**
	 * copies a directory recursively by using streams
	 *
	 */
	public static function copyr(string $source, \OCP\Files\Folder $target): void {
		/** @var LoggerInterface $logger */
		$logger = Server::get(LoggerInterface::class);

		// Verify if folder exists
		$dir = opendir($source);
		if ($dir === false) {
			$logger->error(sprintf('Could not opendir "%s"', $source), ['app' => 'core']);
			return;
		}

		// Copy the files
		while (false !== ($file = readdir($dir))) {
			if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
				if (is_dir($source . '/' . $file)) {
					$child = $target->newFolder($file);
					self::copyr($source . '/' . $file, $child);
				} else {
					$child = $target->newFile($file);
					$sourceStream = fopen($source . '/' . $file, 'r');
					if ($sourceStream === false) {
						$logger->error(sprintf('Could not fopen "%s"', $source . '/' . $file), ['app' => 'core']);
						closedir($dir);
						return;
					}
					$child->putContent($sourceStream);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * @suppress PhanUndeclaredMethod
	 */
	public static function tearDownFS(): void {
		/** @var SetupManager $setupManager */
		$setupManager = Server::get(SetupManager::class);
		$setupManager->tearDown();
	}

	/**
	 * get the current installed version of ownCloud
	 *
	 */
	public static function getVersion(): array {
		OC_Util::loadVersion();
		return self::$versionCache['OC_Version'];
	}

	/**
	 * get the current installed version string of ownCloud
	 *
	 */
	public static function getVersionString(): string {
		OC_Util::loadVersion();
		return self::$versionCache['OC_VersionString'];
	}

	/**
	 * @deprecated the value is of no use anymore
	 *
	 */
	public static function getEditionString(): string {
		return '';
	}

	/**
	 * @description get the update channel of the current installed of ownCloud.
	 *
	 */
	public static function getChannel(): string {
		OC_Util::loadVersion();
		$config = Server::get(IConfig::class);
		return $config->getSystemValueString('updater.release.channel', self::$versionCache['OC_Channel']);
	}

	/**
	 * @description get the build number of the current installed of ownCloud.
	 *
	 */
	public static function getBuild(): string {
		OC_Util::loadVersion();
		return self::$versionCache['OC_Build'];
	}

	/**
	 * @description load the version.php into the session as cache
	 * @suppress PhanUndeclaredVariable
	 */
	private static function loadVersion(): void {
		if (self::$versionCache !== null) {
			return;
		}

		$timestamp = filemtime(OC::$SERVERROOT . '/version.php');
		require OC::$SERVERROOT . '/version.php';
		/** @var int $timestamp */
		self::$versionCache['OC_Version_Timestamp'] = $timestamp;
		/** @var string $OC_Version */
		self::$versionCache['OC_Version'] = $OC_Version;
		/** @var string $OC_VersionString */
		self::$versionCache['OC_VersionString'] = $OC_VersionString;
		/** @var string $OC_Build */
		self::$versionCache['OC_Build'] = $OC_Build;

		/** @var string $OC_Channel */
		self::$versionCache['OC_Channel'] = $OC_Channel;
	}

	/**
	 * generates a path for JS/CSS files. If no application is provided it will create the path for core.
	 *
	 */
	private static function generatePath(string $application, string $directory, string $file): string {
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
	 * @deprecated 24.0.0 - Use \OCP\Util::addScript
	 *
	 */
	public static function addScript(string $application, ?string $file = null, bool $prepend = false): void {
		$path = OC_Util::generatePath($application, 'js', $file);

		// core js files need separate handling
		if ($application !== 'core' && $file !== null) {
			self::addTranslations($application);
		}
		self::addExternalResource($application, $prepend, $path, "script");
	}

	/**
	 * add a javascript file from the vendor sub folder
	 *
	 */
	public static function addVendorScript(string $application, ?string $file = null, bool $prepend = false): void {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		self::addExternalResource($application, $prepend, $path, "script");
	}

	/**
	 * add a translation JS file
	 *
	 * @deprecated 24.0.0
	 *
	 * @param string $application application id
	 * @param string|null $languageCode language code, defaults to the current language
	 * @param bool|null $prepend prepend the Script to the beginning of the list
	 */
	public static function addTranslations(string $application, ?string $languageCode = null, bool $prepend = false): void {
		if (is_null($languageCode)) {
			$languageCode = Server::get(IFactory::class)->findLanguage($application);
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
	 */
	public static function addStyle(string $application, ?string $file = null, bool $prepend = false): void {
		$path = OC_Util::generatePath($application, 'css', $file);
		self::addExternalResource($application, $prepend, $path, "style");
	}

	/**
	 * add a css file from the vendor sub folder
	 *
	 */
	public static function addVendorStyle(string $application, ?string $file = null, bool $prepend = false): void {
		$path = OC_Util::generatePath($application, 'vendor', $file);
		self::addExternalResource($application, $prepend, $path, "style");
	}

	/**
	 * add an external resource css/js file
	 *
	 */
	private static function addExternalResource(string $application, bool $prepend, string $path, string $type = "script"): void {
		if ($type === "style") {
			if (!in_array($path, self::$styles)) {
				if ($prepend === true) {
					array_unshift(self::$styles, $path);
				} else {
					self::$styles[] = $path;
				}
			}
		} elseif ($type === "script") {
			if (!in_array($path, self::$scripts)) {
				if ($prepend === true) {
					array_unshift(self::$scripts, $path);
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
	 *
	 */
	public static function addHeader(string $tag, array $attributes, ?string $text = null, bool $prepend = false): void {
		$header = [
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		];
		if ($prepend === true) {
			array_unshift(self::$headers, $header);
		} else {
			self::$headers[] = $header;
		}
	}

	/**
	 * check if the current server configuration is suitable for ownCloud
	 *
	 */
	public static function checkServer(IConfig $config): array {
		$l = \OC::$server->getL10N('lib');
		$errors = [];
		$CONFIG_DATADIRECTORY = $config->getSystemValueString('datadirectory', OC::$SERVERROOT . '/data');

		if (!self::needUpgrade($config) && $config->getSystemValueBool('installed', false)) {
			// this check needs to be done every time
			$errors = self::checkDataDirectoryValidity($CONFIG_DATADIRECTORY);
		}

		// Assume that if checkServer() succeeded before in this session, then all is fine.
		if (\OC::$server->getSession()->exists('checkServer_succeeded') && \OC::$server->getSession()->get('checkServer_succeeded')) {
			return $errors;
		}

		$webServerRestart = false;
		$setup = \OCP\Server::get(\OC\Setup::class);

		$urlGenerator = \OC::$server->getURLGenerator();

		$availableDatabases = $setup->getSupportedDatabases();
		if (empty($availableDatabases)) {
			$errors[] = [
				'error' => $l->t('No database drivers (sqlite, mysql, or postgresql) installed.'),
				'hint' => '' //TODO: sane hint
			];
			$webServerRestart = true;
		}

		// Check if config folder is writable.
		if (!OC_Helper::isReadOnlyConfigEnabled()) {
			if (!is_writable(OC::$configDir) || !is_readable(OC::$configDir)) {
				$errors[] = [
					'error' => $l->t('Cannot write into "config" directory.'),
					'hint' => $l->t('This can usually be fixed by giving the web server write access to the config directory. See %s',
						[ $urlGenerator->linkToDocs('admin-dir_permissions') ]) . '. '
						. $l->t('Or, if you prefer to keep config.php file read only, set the option "config_is_read_only" to true in it. See %s',
							[ $urlGenerator->linkToDocs('admin-config') ])
				];
			}
		}

		// Check if there is a writable install folder.
		if ($config->getValue('appstoreenabled', true)) {
			if (OC_App::getInstallPath() === null
				|| !is_writable(OC_App::getInstallPath())
				|| !is_readable(OC_App::getInstallPath())
			) {
				$errors[] = [
					'error' => $l->t('Cannot write into "apps" directory.'),
					'hint' => $l->t('This can usually be fixed by giving the web server write access to the apps directory'
						. ' or disabling the App Store in the config file.')
				];
			}
		}
		// Create root dir.
		if ($config->getValue('installed', false)) {
			if (!is_dir($CONFIG_DATADIRECTORY)) {
				$success = @mkdir($CONFIG_DATADIRECTORY);
				if ($success) {
					$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
				} else {
					$errors[] = [
						'error' => $l->t('Cannot create "data" directory.'),
						'hint' => $l->t('This can usually be fixed by giving the web server write access to the root directory. See %s',
							[$urlGenerator->linkToDocs('admin-dir_permissions')])
					];
				}
			} elseif (!is_writable($CONFIG_DATADIRECTORY) || !is_readable($CONFIG_DATADIRECTORY)) {
				// is_writable doesn't work for NFS mounts, so try to write a file and check if it exists.
				$testFile = sprintf('%s/%s.tmp', $CONFIG_DATADIRECTORY, uniqid('data_dir_writability_test_'));
				$handle = fopen($testFile, 'w');
				if (!$handle || fwrite($handle, 'Test write operation') === false) {
					$permissionsHint = $l->t('Permissions can usually be fixed by giving the web server write access to the root directory. See %s.',
						[$urlGenerator->linkToDocs('admin-dir_permissions')]);
					$errors[] = [
						'error' => $l->t('Your data directory is not writable.'),
						'hint' => $permissionsHint
					];
				} else {
					fclose($handle);
					unlink($testFile);
				}
			} else {
				$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
			}
		}

		if (!OC_Util::isSetLocaleWorking()) {
			$errors[] = [
				'error' => $l->t('Setting locale to %s failed.',
					['en_US.UTF-8/fr_FR.UTF-8/es_ES.UTF-8/de_DE.UTF-8/ru_RU.UTF-8/'
						. 'pt_BR.UTF-8/it_IT.UTF-8/ja_JP.UTF-8/zh_CN.UTF-8']),
				'hint' => $l->t('Please install one of these locales on your system and restart your web server.')
			];
		}

		// Contains the dependencies that should be checked against
		// classes = class_exists
		// functions = function_exists
		// defined = defined
		// ini = ini_get
		// If the dependency is not found the missing module name is shown to the EndUser
		// When adding new checks always verify that they pass on Travis as well
		// for ini settings, see https://github.com/owncloud/administration/blob/master/travis-ci/custom.ini
		$dependencies = [
			'classes' => [
				'ZipArchive' => 'zip',
				'DOMDocument' => 'dom',
				'XMLWriter' => 'XMLWriter',
				'XMLReader' => 'XMLReader',
			],
			'functions' => [
				'xml_parser_create' => 'libxml',
				'mb_strcut' => 'mbstring',
				'ctype_digit' => 'ctype',
				'json_encode' => 'JSON',
				'gd_info' => 'GD',
				'gzencode' => 'zlib',
				'simplexml_load_string' => 'SimpleXML',
				'hash' => 'HASH Message Digest Framework',
				'curl_init' => 'cURL',
				'openssl_verify' => 'OpenSSL',
			],
			'defined' => [
				'PDO::ATTR_DRIVER_NAME' => 'PDO'
			],
			'ini' => [
				'default_charset' => 'UTF-8',
			],
		];
		$missingDependencies = [];
		$invalidIniSettings = [];

		$iniWrapper = Server::get(IniGetWrapper::class);
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
			if (strtolower($iniWrapper->getString($setting)) !== strtolower($expected)) {
				$invalidIniSettings[] = [$setting, $expected];
			}
		}

		foreach ($missingDependencies as $missingDependency) {
			$errors[] = [
				'error' => $l->t('PHP module %s not installed.', [$missingDependency]),
				'hint' => $l->t('Please ask your server administrator to install the module.'),
			];
			$webServerRestart = true;
		}
		foreach ($invalidIniSettings as $setting) {
			$errors[] = [
				'error' => $l->t('PHP setting "%s" is not set to "%s".', [$setting[0], var_export($setting[1], true)]),
				'hint' => $l->t('Adjusting this setting in php.ini will make Nextcloud run again')
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
		if ($iniWrapper->getBool('mbstring.func_overload') !== null &&
			$iniWrapper->getBool('mbstring.func_overload') === true) {
			$errors[] = [
				'error' => $l->t('<code>mbstring.func_overload</code> is set to <code>%s</code> instead of the expected value <code>0</code>.', [$iniWrapper->getString('mbstring.func_overload')]),
				'hint' => $l->t('To fix this issue set <code>mbstring.func_overload</code> to <code>0</code> in your php.ini.')
			];
		}

		if (!self::isAnnotationsWorking()) {
			$errors[] = [
				'error' => $l->t('PHP is apparently set up to strip inline doc blocks. This will make several core apps inaccessible.'),
				'hint' => $l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')
			];
		}

		if (!\OC::$CLI && $webServerRestart) {
			$errors[] = [
				'error' => $l->t('PHP modules have been installed, but they are still listed as missing?'),
				'hint' => $l->t('Please ask your server administrator to restart the web server.')
			];
		}

		foreach (['secret', 'instanceid', 'passwordsalt'] as $requiredConfig) {
			if ($config->getValue($requiredConfig, '') === '' && !\OC::$CLI && $config->getValue('installed', false)) {
				$errors[] = [
					'error' => $l->t('The required %s config variable is not configured in the config.php file.', [$requiredConfig]),
					'hint' => $l->t('Please ask your server administrator to check the Nextcloud configuration.')
				];
			}
		}

		// Cache the result of this function
		\OC::$server->getSession()->set('checkServer_succeeded', count($errors) == 0);

		return $errors;
	}

	/**
	 * Check for correct file permissions of data directory
	 *
	 */
	public static function checkDataDirectoryPermissions(string $dataDirectory): array {
		$config = Server::get(IConfig::class);
		if (!$config->getSystemValueBool('check_data_directory_permissions', true)) {
			return  [];
		}

		$perms = substr(decoct(@fileperms($dataDirectory)), -3);
		if (substr($perms, -1) !== '0') {
			chmod($dataDirectory, 0770);
			clearstatcache();
			$perms = substr(decoct(@fileperms($dataDirectory)), -3);
			if ($perms[2] !== '0') {
				$l = \OC::$server->getL10N('lib');
				return [[
					'error' => $l->t('Your data directory is readable by other people.'),
					'hint' => $l->t('Please change the permissions to 0770 so that the directory cannot be listed by other people.'),
				]];
			}
		}
		return [];
	}

	/**
	 * Check that the data directory exists and is valid by
	 * checking the existence of the ".ocdata" file.
	 *
	 */
	public static function checkDataDirectoryValidity(string $dataDirectory): array {
		$l = \OC::$server->getL10N('lib');
		$errors = [];
		if ($dataDirectory[0] !== '/') {
			$errors[] = [
				'error' => $l->t('Your data directory must be an absolute path.'),
				'hint' => $l->t('Check the value of "datadirectory" in your configuration.')
			];
		}
		if (!file_exists($dataDirectory . '/.ocdata')) {
			$errors[] = [
				'error' => $l->t('Your data directory is invalid.'),
				'hint' => $l->t('Ensure there is a file called ".ocdata"' .
					' in the root of the data directory.')
			];
		}
		return $errors;
	}

	/**
	 * Check if the user is logged in, redirects to home if not. With
	 * redirect URL parameter to the request URI.
	 *
	 */
	public static function checkLoggedIn(): void {
		// Check if we are a user
		/** @var IUserSession $userSession */
		$userSession = Server::get(IUserSession::class);
		if (!$userSession->isLoggedIn()) {
			header('Location: ' . \OC::$server->getURLGenerator()->linkToRoute(
				'core.login.showLoginForm',
				[
					'redirect_url' => \OC::$server->getRequest()->getRequestUri(),
				]
			)
			);
			exit();
		}
		// Redirect to 2FA challenge selection if 2FA challenge was not solved yet
		if (Server::get(TwoFactorAuthManager::class)->needsSecondFactor(\OC::$server->getUserSession()->getUser())) {
			header('Location: ' . \OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
			exit();
		}
	}

	/**
	 * Check if the user is a admin, redirects to home if not
	 *
	 */
	public static function checkAdminUser(): void {
		OC_Util::checkLoggedIn();
		if (!OC_User::isAdminUser(OC_User::getUser())) {
			header('Location: ' . \OCP\Util::linkToAbsolute('', 'index.php'));
			exit();
		}
	}

	/**
	 * Returns the URL of the default page
	 * based on the system configuration and
	 * the apps visible for the current user
	 *
	 * @suppress PhanDeprecatedFunction
	 */
	public static function getDefaultPageUrl(): string {
		/** @var IURLGenerator $urlGenerator */
		$urlGenerator = Server::get(IURLGenerator::class);
		return $urlGenerator->linkToDefaultPageUrl();
	}

	/**
	 * Redirect to the user default page
	 *
	 */
	public static function redirectToDefaultPage(): void {
		$location = self::getDefaultPageUrl();
		header('Location: ' . $location);
		exit();
	}

	/**
	 * get an id unique for this instance
	 *
	 */
	public static function getInstanceId(): string {
		$config = Server::get(IConfig::class);
		$id = $config->getSystemValueString('instanceid', null);
		if (is_null($id)) {
			// We need to guarantee at least one letter in instanceid so it can be used as the session_name
			$id = 'oc' . Server::get(ISecureRandom::class)->generate(10, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);
			$config->setValueString('instanceid', $id);
		}
		return $id;
	}

	/**
	 * Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 */
	public static function sanitizeHTML(string|array $value): string|array {
		if (is_array($value)) {
			/** @var string[] $value */
			$value = array_map([self::class, 'sanitizeHTML'], $value);
		} else {
			// Specify encoding for PHP<5.4
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
	 */
	public static function encodePath(string $component): string {
		$encoded = rawurlencode($component);
		$encoded = str_replace('%2F', '/', $encoded);
		return $encoded;
	}

	/**
	 * Check if the .htaccess test file can be created
	 *
	 * @throws \OCP\HintException If the test file can't get written.
	 */
	public function createHtaccessTestFile(IConfig $config): string|false {
		// php dev server does not support htaccess
		if (php_sapi_name() === 'cli-server') {
			return false;
		}

		// testdata
		$fileName = '/htaccesstest.txt';
		$testContent = 'This is used for testing whether htaccess is properly enabled to disallow access from the outside. This file can be safely removed.';

		// creating a test file
		$testFile = $config->getSystemValueString('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

		if (file_exists($testFile)) {// already running this test, possible recursive call
			return false;
		}

		$fp = @fopen($testFile, 'w');
		if (!$fp) {
			throw new \OCP\HintException('Can\'t create test file to check for working .htaccess file.',
				'Make sure it is possible for the web server to write to ' . $testFile);
		}
		fwrite($fp, $testContent);
		fclose($fp);

		return $testContent;
	}

	/**
	 * Check if the .htaccess file is working
	 *
	 * @throws Exception
	 * @throws \OCP\HintException If the test file can't get written.
	 */
	public function isHtaccessWorking(IConfig $config): bool {
		if (\OC::$CLI || !$config->getSystemValueBool('check_for_working_htaccess', true)) {
			return true;
		}

		$testContent = $this->createHtaccessTestFile($config);
		if ($testContent === false) {
			return false;
		}

		$fileName = '/htaccesstest.txt';
		$testFile = $config->getSystemValueString('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

		// accessing the file via http
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL(OC::$WEBROOT . '/data' . $fileName);
		try {
			$content = Server::get(IClientService::class)->newClient()->get($url)->getBody();
		} catch (\Exception $e) {
			$content = false;
		}

		if (str_starts_with($url, 'https:')) {
			$url = 'http:' . substr($url, 6);
		} else {
			$url = 'https:' . substr($url, 5);
		}

		try {
			$fallbackContent = Server::get(IClientService::class)->newClient()->get($url)->getBody();
		} catch (\Exception $e) {
			$fallbackContent = false;
		}

		// cleanup
		@unlink($testFile);

		/*
		 * If the content is not equal to test content our .htaccess
		 * is working as required
		 */
		return $content !== $testContent && $fallbackContent !== $testContent;
	}

	/**
	 * Check if current locale is non-UTF8
	 *
	 */
	private static function isNonUTF8Locale(): bool {
		if (function_exists('escapeshellcmd')) {
			return escapeshellcmd('§') === '';
		} elseif (function_exists('escapeshellarg')) {
			return escapeshellarg('§') === '\'\'';
		} else {
			return preg_match('/utf-?8/i', setlocale(LC_CTYPE, 0)) === 0;
		}
	}

	/**
	 * Check if the setlocale call does not work. This can happen if the right
	 * local packages are not available on the server.
	 *
	 */
	public static function isSetLocaleWorking(): bool {
		if (self::isNonUTF8Locale()) {
			// Borrowed from \Patchwork\Utf8\Bootup::initLocale
			setlocale(LC_ALL, 'C.UTF-8', 'C');
			setlocale(LC_CTYPE, 'en_US.UTF-8', 'fr_FR.UTF-8', 'es_ES.UTF-8', 'de_DE.UTF-8', 'ru_RU.UTF-8', 'pt_BR.UTF-8', 'it_IT.UTF-8', 'ja_JP.UTF-8', 'zh_CN.UTF-8', '0');

			// Check again
			if (self::isNonUTF8Locale()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if it's possible to get the inline annotations
	 *
	 */
	public static function isAnnotationsWorking(): bool {
		$reflection = new \ReflectionMethod(__METHOD__);
		$docs = $reflection->getDocComment();

		return (is_string($docs) && strlen($docs) > 50);
	}

	/**
	 * Check if the PHP module fileinfo is loaded.
	 *
	 */
	public static function fileInfoLoaded(): bool {
		return function_exists('finfo_open');
	}

	/**
	 * clear all levels of output buffering
	 *
	 */
	public static function obEnd(): void {
		while (ob_get_level()) {
			ob_end_clean();
		}
	}

	/**
	 * Checks whether the server is running on Mac OS X
	 *
	 */
	public static function runningOnMac(): bool {
		return (strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN');
	}

	/**
	 * Handles the case that there may not be a theme, then check if a "default"
	 * theme exists and take that one
	 *
	 */
	public static function getTheme(): string {
		$config = Server::get(IConfig::class);
		$theme = $config->getSystemValueString('theme', '');

		if ($theme === '') {
			if (is_dir(OC::$SERVERROOT . '/themes/default')) {
				$theme = 'default';
			}
		}

		return $theme;
	}

	/**
	 * Normalize a unicode string
	 *
	 */
	public static function normalizeUnicode(string $value): bool|string {
		if (Normalizer::isNormalized($value)) {
			return $value;
		}

		$normalizedValue = Normalizer::normalize($value);
		if ($normalizedValue === null || $normalizedValue === false) {
			/** @var LoggerInterface $logger */
			$logger = Server::get(LoggerInterface::class);
			$logger->warning('normalizing failed for "' . $value . '"', ['app' => 'core']);
			return $value;
		}

		return $normalizedValue;
	}

	/**
	 * A human readable string is generated based on version and build number
	 *
	 */
	public static function getHumanVersion(): string {
		$version = OC_Util::getVersionString();
		$build = OC_Util::getBuild();
		if (!empty($build) && OC_Util::getChannel() === 'daily') {
			$version .= ' Build:' . $build;
		}
		return $version;
	}

	/**
	 * Returns whether the given file name is valid
	 *
	 * @deprecated use \OC\Files\View::verifyPath()
	 */
	public static function isValidFileName(string $file): bool {
		$trimmed = trim($file);
		if ($trimmed === '') {
			return false;
		}
		if (\OC\Files\Filesystem::isIgnoredDir($trimmed)) {
			return false;
		}

		// detect part files
		if (preg_match('/' . \OCP\Files\FileInfo::BLACKLIST_FILES_REGEX . '/', $trimmed) !== 0) {
			return false;
		}

		foreach (\OCP\Util::getForbiddenFileNameChars() as $char) {
			if (str_contains($trimmed, $char)) {
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
	 * @throws \OCP\HintException When the upgrade from the given version is not allowed
	 */
	public static function needUpgrade(IConfig $config): bool {
		if ($config->getSystemValueBool('installed', false)) {
			$installedVersion = $config->getSystemValueString('version', '0.0.0');
			$currentVersion = implode('.', \OCP\Util::getVersion());
			$versionDiff = version_compare($currentVersion, $installedVersion);
			if ($versionDiff > 0) {
				return true;
			} elseif ($config->getSystemValueBool('debug', false) && $versionDiff < 0) {
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
					throw new \OCP\HintException('Downgrading is not supported and is likely to cause unpredictable issues (from ' . $installedVersion . ' to ' . $currentVersion . ')');
				}
			} elseif ($versionDiff < 0) {
				// downgrade attempt, throw exception
				throw new \OCP\HintException('Downgrading is not supported and is likely to cause unpredictable issues (from ' . $installedVersion . ' to ' . $currentVersion . ')');
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
