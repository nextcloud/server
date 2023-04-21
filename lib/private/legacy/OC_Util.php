<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Birk Borkason <daniel.niccoli@gmail.com>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author cmeh <cmeh@users.noreply.github.com>
 * @author Eric Masseran <rico.masseran@gmail.com>
 * @author Felix Epp <work@felixepp.de>
 * @author Florin Peter <github@florin-peter.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author helix84 <helix84@centrum.sk>
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Individual IT Services <info@individual-it.net>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kawohl <john@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Markus Goetz <markus@woboq.com>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Robert Dailey <rcdailey@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sebastian Wessalowski <sebastian@wessalowski.org>
 * @author Stefan Rado <owncloud@sradonia.net>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Files\SetupManager;
use OCP\Files\Template\ITemplateManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
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
	 * @param string|null $user
	 * @return boolean
	 * @description configure the initial filesystem based on the configuration
	 * @suppress PhanDeprecatedFunction
	 * @suppress PhanAccessMethodInternal
	 */
	public static function setupFS(?string $user = '') {
		// If we are not forced to load a specific user we load the one that is logged in
		if ($user === '') {
			$userObject = \OC::$server->get(\OCP\IUserSession::class)->getUser();
		} else {
			$userObject = \OC::$server->get(\OCP\IUserManager::class)->get($user);
		}

		/** @var SetupManager $setupManager */
		$setupManager = \OC::$server->get(SetupManager::class);

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
	 * @param bool $checkGroupMembership Check group membership exclusion
	 * @return boolean
	 * @suppress PhanDeprecatedFunction
	 */
	public static function isPublicLinkPasswordRequired(bool $checkGroupMembership = true) {
		/** @var IManager $shareManager */
		$shareManager = \OC::$server->get(IManager::class);
		return $shareManager->shareApiLinkEnforcePassword($checkGroupMembership);
	}

	/**
	 * check if sharing is disabled for the current user
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param IUser|null $user
	 * @return bool
	 */
	public static function isSharingDisabledForUser(IConfig $config, IGroupManager $groupManager, $user) {
		/** @var IManager $shareManager */
		$shareManager = \OC::$server->get(IManager::class);
		$userId = $user ? $user->getUID() : null;
		return $shareManager->sharingDisabledForUser($userId);
	}

	/**
	 * check if share API enforces a default expire date
	 *
	 * @return bool
	 * @suppress PhanDeprecatedFunction
	 */
	public static function isDefaultExpireDateEnforced() {
		/** @var IManager $shareManager */
		$shareManager = \OC::$server->get(IManager::class);
		return $shareManager->shareApiLinkDefaultExpireDateEnforced();
	}

	/**
	 * Get the quota of a user
	 *
	 * @param IUser|null $user
	 * @return int|\OCP\Files\FileInfo::SPACE_UNLIMITED|false|float Quota bytes
	 */
	public static function getUserQuota(?IUser $user) {
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
	 * @param string $userId
	 * @param \OCP\Files\Folder $userDirectory
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @suppress PhanDeprecatedFunction
	 */
	public static function copySkeleton($userId, \OCP\Files\Folder $userDirectory) {
		/** @var LoggerInterface $logger */
		$logger = \OC::$server->get(LoggerInterface::class);

		$plainSkeletonDirectory = \OC::$server->getConfig()->getSystemValue('skeletondirectory', \OC::$SERVERROOT . '/core/skeleton');
		$userLang = \OC::$server->getL10NFactory()->findLanguage();
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

		$instanceId = \OC::$server->getConfig()->getSystemValue('instanceid', '');

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
			$templateManager = \OC::$server->get(ITemplateManager::class);
			$templateManager->initializeTemplateDirectory(null, $userId);
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
		$logger = \OC::$server->getLogger();

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
	 * @return void
	 * @suppress PhanUndeclaredMethod
	 */
	public static function tearDownFS() {
		/** @var SetupManager $setupManager */
		$setupManager = \OC::$server->get(SetupManager::class);
		$setupManager->tearDown();
	}

	/**
	 * get the current installed version of ownCloud
	 *
	 * @return array
	 */
	public static function getVersion() {
		OC_Util::loadVersion();
		return self::$versionCache['OC_Version'];
	}

	/**
	 * get the current installed version string of ownCloud
	 *
	 * @return string
	 */
	public static function getVersionString() {
		OC_Util::loadVersion();
		return self::$versionCache['OC_VersionString'];
	}

	/**
	 * @deprecated the value is of no use anymore
	 * @return string
	 */
	public static function getEditionString() {
		return '';
	}

	/**
	 * @description get the update channel of the current installed of ownCloud.
	 * @return string
	 */
	public static function getChannel() {
		OC_Util::loadVersion();
		return \OC::$server->getConfig()->getSystemValue('updater.release.channel', self::$versionCache['OC_Channel']);
	}

	/**
	 * @description get the build number of the current installed of ownCloud.
	 * @return string
	 */
	public static function getBuild() {
		OC_Util::loadVersion();
		return self::$versionCache['OC_Build'];
	}

	/**
	 * @description load the version.php into the session as cache
	 * @suppress PhanUndeclaredVariable
	 */
	private static function loadVersion() {
		if (self::$versionCache !== null) {
			return;
		}

		require OC::$SERVERROOT . '/version.php';
		/** @var int $timestamp */
		self::$versionCache['OC_Version_Timestamp'] = \OC::$VERSION_MTIME;
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
	 * @deprecated 24.0.0 - Use \OCP\Util::addScript
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
			self::addTranslations($application);
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
	 * @deprecated 24.0.0
	 *
	 * @param string $application application id
	 * @param string|null $languageCode language code, defaults to the current language
	 * @param bool|null $prepend prepend the Script to the beginning of the list
	 */
	public static function addTranslations($application, $languageCode = null, $prepend = false) {
		if (is_null($languageCode)) {
			$languageCode = \OC::$server->getL10NFactory()->findLanguage($application);
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
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 * @param bool $prepend prepend the header to the beginning of the list
	 */
	public static function addHeader($tag, $attributes, $text = null, $prepend = false) {
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
	 * @param \OC\SystemConfig $config
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer(\OC\SystemConfig $config) {
		$l = \OC::$server->getL10N('lib');
		$errors = [];
		$CONFIG_DATADIRECTORY = $config->getValue('datadirectory', OC::$SERVERROOT . '/data');

		if (!self::needUpgrade($config) && $config->getValue('installed', false)) {
			// this check needs to be done every time
			$errors = self::checkDataDirectoryValidity($CONFIG_DATADIRECTORY);
		}

		// Assume that if checkServer() succeeded before in this session, then all is fine.
		if (\OC::$server->getSession()->exists('checkServer_succeeded') && \OC::$server->getSession()->get('checkServer_succeeded')) {
			return $errors;
		}

		$webServerRestart = false;
		$setup = new \OC\Setup(
			$config,
			\OC::$server->get(IniGetWrapper::class),
			\OC::$server->getL10N('lib'),
			\OC::$server->get(\OCP\Defaults::class),
			\OC::$server->get(LoggerInterface::class),
			\OC::$server->getSecureRandom(),
			\OC::$server->get(\OC\Installer::class)
		);

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
			if (!is_writable(OC::$configDir) or !is_readable(OC::$configDir)) {
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
			} elseif (!is_writable($CONFIG_DATADIRECTORY) or !is_readable($CONFIG_DATADIRECTORY)) {
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

		$iniWrapper = \OC::$server->get(IniGetWrapper::class);
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

		if (function_exists('xml_parser_create') &&
			LIBXML_LOADED_VERSION < 20700) {
			$version = LIBXML_LOADED_VERSION;
			$major = floor($version / 10000);
			$version -= ($major * 10000);
			$minor = floor($version / 100);
			$version -= ($minor * 100);
			$patch = $version;
			$errors[] = [
				'error' => $l->t('libxml2 2.7.0 is at least required. Currently %s is installed.', [$major . '.' . $minor . '.' . $patch]),
				'hint' => $l->t('To fix this issue update your libxml2 version and restart your web server.')
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
		$errors = [];
		$dbType = \OC::$server->getSystemConfig()->getValue('dbtype', 'sqlite');
		if ($dbType === 'pgsql') {
			// check PostgreSQL version
			// TODO latest postgresql 8 released was 8 years ago, maybe remove the
			// check completely?
			try {
				/** @var IDBConnection $connection */
				$connection = \OC::$server->get(IDBConnection::class);
				$result = $connection->executeQuery('SHOW SERVER_VERSION');
				$data = $result->fetch();
				$result->closeCursor();
				if (isset($data['server_version'])) {
					$version = $data['server_version'];
					if (version_compare($version, '9.0.0', '<')) {
						$errors[] = [
							'error' => $l->t('PostgreSQL >= 9 required.'),
							'hint' => $l->t('Please upgrade your database version.')
						];
					}
				}
			} catch (\Doctrine\DBAL\Exception $e) {
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
		if (\OC::$server->getConfig()->getSystemValue('check_data_directory_permissions', true) === false) {
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
					'error' => $l->t('Your data directory is readable by other users.'),
					'hint' => $l->t('Please change the permissions to 0770 so that the directory cannot be listed by other users.'),
				]];
			}
		}
		return [];
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
	 * @return void
	 */
	public static function checkLoggedIn() {
		// Check if we are a user
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
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
		if (\OC::$server->getTwoFactorAuthManager()->needsSecondFactor(\OC::$server->getUserSession()->getUser())) {
			header('Location: ' . \OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
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
	 * Returns the URL of the default page
	 * based on the system configuration and
	 * the apps visible for the current user
	 *
	 * @return string URL
	 * @suppress PhanDeprecatedFunction
	 */
	public static function getDefaultPageUrl() {
		/** @var IURLGenerator $urlGenerator */
		$urlGenerator = \OC::$server->get(IURLGenerator::class);
		return $urlGenerator->linkToDefaultPageUrl();
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
	 * @param string|string[] $value
	 * @return string|string[] an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML($value) {
		if (is_array($value)) {
			/** @var string[] $value */
			$value = array_map(function ($value) {
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
		$testContent = 'This is used for testing whether htaccess is properly enabled to disallow access from the outside. This file can be safely removed.';

		// creating a test file
		$testFile = $config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $fileName;

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
	 * @param \OCP\IConfig $config
	 * @return bool
	 * @throws Exception
	 * @throws \OCP\HintException If the test file can't get written.
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

		if (strpos($url, 'https:') === 0) {
			$url = 'http:' . substr($url, 6);
		} else {
			$url = 'https:' . substr($url, 5);
		}

		try {
			$fallbackContent = \OC::$server->getHTTPClientService()->newClient()->get($url)->getBody();
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
	 * @return bool
	 */
	private static function isNonUTF8Locale() {
		if (function_exists('escapeshellcmd')) {
			return '' === escapeshellcmd('§');
		} elseif (function_exists('escapeshellarg')) {
			return '\'\'' === escapeshellarg('§');
		} else {
			return 0 === preg_match('/utf-?8/i', setlocale(LC_CTYPE, 0));
		}
	}

	/**
	 * Check if the setlocale call does not work. This can happen if the right
	 * local packages are not available on the server.
	 *
	 * @return bool
	 */
	public static function isSetLocaleWorking() {
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
	 * Checks whether the server is running on Mac OS X
	 *
	 * @return bool true if running on Mac OS X, false otherwise
	 */
	public static function runningOnMac() {
		return (strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN');
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
	 * Normalize a unicode string
	 *
	 * @param string $value a not normalized string
	 * @return bool|string
	 */
	public static function normalizeUnicode($value) {
		if (Normalizer::isNormalized($value)) {
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
	 * A human readable string is generated based on version and build number
	 *
	 * @return string
	 */
	public static function getHumanVersion() {
		$version = OC_Util::getVersionString();
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

		// detect part files
		if (preg_match('/' . \OCP\Files\FileInfo::BLACKLIST_FILES_REGEX . '/', $trimmed) !== 0) {
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
	 * @param \OC\SystemConfig $config
	 * @return bool whether the core or any app needs an upgrade
	 * @throws \OCP\HintException When the upgrade from the given version is not allowed
	 */
	public static function needUpgrade(\OC\SystemConfig $config) {
		if ($config->getValue('installed', false)) {
			$installedVersion = $config->getValue('version', '0.0.0');
			$currentVersion = implode('.', \OCP\Util::getVersion());
			$versionDiff = version_compare($currentVersion, $installedVersion);
			if ($versionDiff > 0) {
				return true;
			} elseif ($config->getValue('debug', false) && $versionDiff < 0) {
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
