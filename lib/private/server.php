<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Bernhard Reiter <ockham@raz.or.at>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Sander <brantje@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Db\Db;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Command\AsyncBus;
use OC\Diagnostics\EventLogger;
use OC\Diagnostics\NullEventLogger;
use OC\Diagnostics\NullQueryLogger;
use OC\Diagnostics\QueryLogger;
use OC\Files\Node\Root;
use OC\Files\View;
use OC\Http\Client\ClientService;
use OC\Lock\MemcacheLockingProvider;
use OC\Lock\NoopLockingProvider;
use OC\Mail\Mailer;
use OC\Memcache\ArrayCache;
use OC\Memcache\NullCache;
use OC\Security\CertificateManager;
use OC\Security\Crypto;
use OC\Security\Hasher;
use OC\Security\SecureRandom;
use OC\Security\TrustedDomainHelper;
use OC\Tagging\TagMapper;
use OCP\IServerContainer;

/**
 * Class Server
 *
 * @package OC
 *
 * TODO: hookup all manager classes
 */
class Server extends SimpleContainer implements IServerContainer {
	/** @var string */
	private $webRoot;

	/**
	 * @param string $webRoot
	 */
	public function __construct($webRoot) {
		$this->webRoot = $webRoot;

		$this->registerService('ContactsManager', function ($c) {
			return new ContactsManager();
		});

		$this->registerService('PreviewManager', function (Server $c) {
			return new PreviewManager($c->getConfig());
		});

		$this->registerService('EncryptionManager', function (Server $c) {
			return new Encryption\Manager($c->getConfig(), $c->getLogger(), $c->getL10N('core'));
		});

		$this->registerService('EncryptionFileHelper', function (Server $c) {
			$util = new \OC\Encryption\Util(
				new \OC\Files\View(),
				$c->getUserManager(),
				$c->getGroupManager(),
				$c->getConfig()
			);
			return new Encryption\File($util);
		});

		$this->registerService('EncryptionKeyStorage', function (Server $c) {
			$view = new \OC\Files\View();
			$util = new \OC\Encryption\Util(
				$view,
				$c->getUserManager(),
				$c->getGroupManager(),
				$c->getConfig()
			);

			return new Encryption\Keys\Storage($view, $util);
		});
		$this->registerService('TagMapper', function(Server $c) {
			return new TagMapper($c->getDatabaseConnection());
		});
		$this->registerService('TagManager', function (Server $c) {
			$tagMapper = $c->query('TagMapper');
			return new TagManager($tagMapper, $c->getUserSession());
		});
		$this->registerService('RootFolder', function (Server $c) {
			// TODO: get user and user manager from container as well
			$user = \OC_User::getUser();
			/** @var $c SimpleContainer */
			$userManager = $c->query('UserManager');
			$user = $userManager->get($user);
			$manager = \OC\Files\Filesystem::getMountManager();
			$view = new View();
			return new Root($manager, $view, $user);
		});
		$this->registerService('UserManager', function (Server $c) {
			$config = $c->getConfig();
			return new \OC\User\Manager($config);
		});
		$this->registerService('GroupManager', function (Server $c) {
			$groupManager = new \OC\Group\Manager($this->getUserManager());
			$groupManager->listen('\OC\Group', 'preCreate', function ($gid) {
				\OC_Hook::emit('OC_Group', 'pre_createGroup', array('run' => true, 'gid' => $gid));
			});
			$groupManager->listen('\OC\Group', 'postCreate', function (\OC\Group\Group $gid) {
				\OC_Hook::emit('OC_User', 'post_createGroup', array('gid' => $gid->getGID()));
			});
			$groupManager->listen('\OC\Group', 'preDelete', function (\OC\Group\Group $group) {
				\OC_Hook::emit('OC_Group', 'pre_deleteGroup', array('run' => true, 'gid' => $group->getGID()));
			});
			$groupManager->listen('\OC\Group', 'postDelete', function (\OC\Group\Group $group) {
				\OC_Hook::emit('OC_User', 'post_deleteGroup', array('gid' => $group->getGID()));
			});
			$groupManager->listen('\OC\Group', 'preAddUser', function (\OC\Group\Group $group, \OC\User\User $user) {
				\OC_Hook::emit('OC_Group', 'pre_addToGroup', array('run' => true, 'uid' => $user->getUID(), 'gid' => $group->getGID()));
			});
			$groupManager->listen('\OC\Group', 'postAddUser', function (\OC\Group\Group $group, \OC\User\User $user) {
				\OC_Hook::emit('OC_Group', 'post_addToGroup', array('uid' => $user->getUID(), 'gid' => $group->getGID()));
			});
			return $groupManager;
		});
		$this->registerService('UserSession', function (Server $c) {
			$manager = $c->getUserManager();
			$userSession = new \OC\User\Session($manager, new \OC\Session\Memory(''));
			$userSession->listen('\OC\User', 'preCreateUser', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_createUser', array('run' => true, 'uid' => $uid, 'password' => $password));
			});
			$userSession->listen('\OC\User', 'postCreateUser', function ($user, $password) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_createUser', array('uid' => $user->getUID(), 'password' => $password));
			});
			$userSession->listen('\OC\User', 'preDelete', function ($user) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'pre_deleteUser', array('run' => true, 'uid' => $user->getUID()));
			});
			$userSession->listen('\OC\User', 'postDelete', function ($user) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_deleteUser', array('uid' => $user->getUID()));
			});
			$userSession->listen('\OC\User', 'preSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'pre_setPassword', array('run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword));
			});
			$userSession->listen('\OC\User', 'postSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_setPassword', array('run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword));
			});
			$userSession->listen('\OC\User', 'preLogin', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_login', array('run' => true, 'uid' => $uid, 'password' => $password));
			});
			$userSession->listen('\OC\User', 'postLogin', function ($user, $password) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_login', array('run' => true, 'uid' => $user->getUID(), 'password' => $password));
			});
			$userSession->listen('\OC\User', 'logout', function () {
				\OC_Hook::emit('OC_User', 'logout', array());
			});
			return $userSession;
		});
		$this->registerService('NavigationManager', function ($c) {
			return new \OC\NavigationManager();
		});
		$this->registerService('AllConfig', function (Server $c) {
			return new \OC\AllConfig(
				$c->getSystemConfig()
			);
		});
		$this->registerService('SystemConfig', function ($c) {
			return new \OC\SystemConfig();
		});
		$this->registerService('AppConfig', function ($c) {
			return new \OC\AppConfig(\OC_DB::getConnection());
		});
		$this->registerService('L10NFactory', function ($c) {
			return new \OC\L10N\Factory();
		});
		$this->registerService('URLGenerator', function (Server $c) {
			$config = $c->getConfig();
			$cacheFactory = $c->getMemCacheFactory();
			return new \OC\URLGenerator(
				$config,
				$cacheFactory
			);
		});
		$this->registerService('AppHelper', function ($c) {
			return new \OC\AppHelper();
		});
		$this->registerService('UserCache', function ($c) {
			return new Cache\File();
		});
		$this->registerService('MemCacheFactory', function (Server $c) {
			$config = $c->getConfig();

			if($config->getSystemValue('installed', false) && !(defined('PHPUNIT_RUN') && PHPUNIT_RUN)) {
				$v = \OC_App::getAppVersions();
				$v['core'] = implode('.', \OC_Util::getVersion());
				$version = implode(',', $v);
				$instanceId = \OC_Util::getInstanceId();
				$path = \OC::$SERVERROOT;
				$prefix = md5($instanceId.'-'.$version.'-'.$path);
				return new \OC\Memcache\Factory($prefix,
					$config->getSystemValue('memcache.local', null),
					$config->getSystemValue('memcache.distributed', null),
					$config->getSystemValue('memcache.locking', null)
				);
			}

			return new \OC\Memcache\Factory('',
				new ArrayCache(),
				new ArrayCache(),
				new ArrayCache()
			);
		});
		$this->registerService('ActivityManager', function (Server $c) {
			return new ActivityManager(
				$c->getRequest(),
				$c->getUserSession(),
				$c->getConfig()
			);
		});
		$this->registerService('AvatarManager', function ($c) {
			return new AvatarManager();
		});
		$this->registerService('Logger', function (Server $c) {
			$logClass = $c->query('AllConfig')->getSystemValue('log_type', 'owncloud');
			$logger = 'OC_Log_' . ucfirst($logClass);
			call_user_func(array($logger, 'init'));

			return new Log($logger);
		});
		$this->registerService('JobList', function (Server $c) {
			$config = $c->getConfig();
			return new \OC\BackgroundJob\JobList($c->getDatabaseConnection(), $config);
		});
		$this->registerService('Router', function (Server $c) {
			$cacheFactory = $c->getMemCacheFactory();
			if ($cacheFactory->isAvailable()) {
				$router = new \OC\Route\CachingRouter($cacheFactory->create('route'));
			} else {
				$router = new \OC\Route\Router();
			}
			return $router;
		});
		$this->registerService('Search', function ($c) {
			return new Search();
		});
		$this->registerService('SecureRandom', function ($c) {
			return new SecureRandom();
		});
		$this->registerService('Crypto', function (Server $c) {
			return new Crypto($c->getConfig(), $c->getSecureRandom());
		});
		$this->registerService('Hasher', function (Server $c) {
			return new Hasher($c->getConfig());
		});
		$this->registerService('DatabaseConnection', function (Server $c) {
			$factory = new \OC\DB\ConnectionFactory();
			$systemConfig = $c->getSystemConfig();
			$type = $systemConfig->getValue('dbtype', 'sqlite');
			if (!$factory->isValidType($type)) {
				throw new \OC\DatabaseException('Invalid database type');
			}
			$connectionParams = $factory->createConnectionParams($systemConfig);
			$connection = $factory->getConnection($type, $connectionParams);
			$connection->getConfiguration()->setSQLLogger($c->getQueryLogger());
			return $connection;
		});
		$this->registerService('Db', function (Server $c) {
			return new Db($c->getDatabaseConnection());
		});
		$this->registerService('HTTPHelper', function (Server $c) {
			$config = $c->getConfig();
			return new HTTPHelper(
				$config,
				$c->getHTTPClientService()
			);
		});
		$this->registerService('HttpClientService', function (Server $c) {
			$user = \OC_User::getUser();
			$uid = $user ? $user : null;
			return new ClientService(
				$c->getConfig(),
				new \OC\Security\CertificateManager($uid, new \OC\Files\View())
			);
		});
		$this->registerService('EventLogger', function (Server $c) {
			if (defined('DEBUG') and DEBUG) {
				return new EventLogger();
			} else {
				return new NullEventLogger();
			}
		});
		$this->registerService('QueryLogger', function ($c) {
			if (defined('DEBUG') and DEBUG) {
				return new QueryLogger();
			} else {
				return new NullQueryLogger();
			}
		});
		$this->registerService('TempManager', function (Server $c) {
			return new TempManager(get_temp_dir(), $c->getLogger());
		});
		$this->registerService('AppManager', function(Server $c) {
			return new \OC\App\AppManager(
				$c->getUserSession(),
				$c->getAppConfig(),
				$c->getGroupManager(),
				$c->getMemCacheFactory()
			);
		});
		$this->registerService('DateTimeZone', function(Server $c) {
			return new DateTimeZone(
				$c->getConfig(),
				$c->getSession()
			);
		});
		$this->registerService('DateTimeFormatter', function(Server $c) {
			$language = $c->getConfig()->getUserValue($c->getSession()->get('user_id'), 'core', 'lang', null);

			return new DateTimeFormatter(
				$c->getDateTimeZone()->getTimeZone(),
				$c->getL10N('lib', $language)
			);
		});
		$this->registerService('MountConfigManager', function () {
			$loader = \OC\Files\Filesystem::getLoader();
			return new \OC\Files\Config\MountProviderCollection($loader);
		});
		$this->registerService('IniWrapper', function ($c) {
			return new IniGetWrapper();
		});
		$this->registerService('AsyncCommandBus', function (Server $c) {
			$jobList = $c->getJobList();
			return new AsyncBus($jobList);
		});
		$this->registerService('TrustedDomainHelper', function ($c) {
			return new TrustedDomainHelper($this->getConfig());
		});
		$this->registerService('Request', function ($c) {
			if (isset($this['urlParams'])) {
				$urlParams = $this['urlParams'];
			} else {
				$urlParams = [];
			}

			if ($this->getSession()->exists('requesttoken')) {
				$requestToken = $this->getSession()->get('requesttoken');
			} else {
				$requestToken = false;
			}

			if (defined('PHPUNIT_RUN') && PHPUNIT_RUN
				&& in_array('fakeinput', stream_get_wrappers())
			) {
				$stream = 'fakeinput://data';
			} else {
				$stream = 'php://input';
			}

			return new Request(
				[
					'get' => $_GET,
					'post' => $_POST,
					'files' => $_FILES,
					'server' => $_SERVER,
					'env' => $_ENV,
					'cookies' => $_COOKIE,
					'method' => (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']))
						? $_SERVER['REQUEST_METHOD']
						: null,
					'urlParams' => $urlParams,
					'requesttoken' => $requestToken,
				],
				$this->getSecureRandom(),
				$this->getConfig(),
				$stream
			);
		});
		$this->registerService('Mailer', function(Server $c) {
			return new Mailer(
				$c->getConfig(),
				$c->getLogger(),
				new \OC_Defaults()
			);
		});
		$this->registerService('OcsClient', function(Server $c) {
			return new OCSClient(
				$this->getHTTPClientService(),
				$this->getConfig(),
				$this->getLogger()
			);
		});
		$this->registerService('LockingProvider', function (Server $c) {
			if ($c->getConfig()->getSystemValue('filelocking.enabled', false) or (defined('PHPUNIT_RUN') && PHPUNIT_RUN)) {
				/** @var \OC\Memcache\Factory $memcacheFactory */
				$memcacheFactory = $c->getMemCacheFactory();
				$memcache = $memcacheFactory->createLocking('lock');
				if (!($memcache instanceof \OC\Memcache\NullCache)) {
					return new MemcacheLockingProvider($memcache);
				}
				throw new HintException(
					'File locking is enabled but the locking cache class was not found',
					'Please check the "memcache.locking" setting and make sure the matching PHP module is installed and enabled'
				);
			}
			return new NoopLockingProvider();
		});
	}

	/**
	 * @return \OCP\Contacts\IManager
	 */
	public function getContactsManager() {
		return $this->query('ContactsManager');
	}

	/**
	 * @return \OC\Encryption\Manager
	 */
	public function getEncryptionManager() {
		return $this->query('EncryptionManager');
	}

	/**
	 * @return \OC\Encryption\File
	 */
	public function getEncryptionFilesHelper() {
		return $this->query('EncryptionFileHelper');
	}

	/**
	 * @return \OCP\Encryption\Keys\IStorage
	 */
	public function getEncryptionKeyStorage() {
		return $this->query('EncryptionKeyStorage');
	}

	/**
	 * The current request object holding all information about the request
	 * currently being processed is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest
	 */
	public function getRequest() {
		return $this->query('Request');
	}

	/**
	 * Returns the preview manager which can create preview images for a given file
	 *
	 * @return \OCP\IPreview
	 */
	public function getPreviewManager() {
		return $this->query('PreviewManager');
	}

	/**
	 * Returns the tag manager which can get and set tags for different object types
	 *
	 * @see \OCP\ITagManager::load()
	 * @return \OCP\ITagManager
	 */
	public function getTagManager() {
		return $this->query('TagManager');
	}

	/**
	 * Returns the avatar manager, used for avatar functionality
	 *
	 * @return \OCP\IAvatarManager
	 */
	public function getAvatarManager() {
		return $this->query('AvatarManager');
	}

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return \OCP\Files\IRootFolder
	 */
	public function getRootFolder() {
		return $this->query('RootFolder');
	}

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 */
	public function getUserFolder($userId = null) {
		if ($userId === null) {
			$user = $this->getUserSession()->getUser();
			if (!$user) {
				return null;
			}
			$userId = $user->getUID();
		} else {
			$user = $this->getUserManager()->get($userId);
		}
		\OC\Files\Filesystem::initMountPoints($userId);
		$dir = '/' . $userId;
		$root = $this->getRootFolder();
		$folder = null;

		if (!$root->nodeExists($dir)) {
			$folder = $root->newFolder($dir);
		} else {
			$folder = $root->get($dir);
		}

		$dir = '/files';
		if (!$folder->nodeExists($dir)) {
			$folder = $folder->newFolder($dir);
			\OC_Util::copySkeleton($user, $folder);
		} else {
			$folder = $folder->get($dir);
		}

		return $folder;
	}

	/**
	 * Returns an app-specific view in ownClouds data directory
	 *
	 * @return \OCP\Files\Folder
	 */
	public function getAppFolder() {
		$dir = '/' . \OC_App::getCurrentApp();
		$root = $this->getRootFolder();
		$folder = null;
		if (!$root->nodeExists($dir)) {
			$folder = $root->newFolder($dir);
		} else {
			$folder = $root->get($dir);
		}
		return $folder;
	}

	/**
	 * @return \OC\User\Manager
	 */
	public function getUserManager() {
		return $this->query('UserManager');
	}

	/**
	 * @return \OC\Group\Manager
	 */
	public function getGroupManager() {
		return $this->query('GroupManager');
	}

	/**
	 * @return \OC\User\Session
	 */
	public function getUserSession() {
		return $this->query('UserSession');
	}

	/**
	 * @return \OCP\ISession
	 */
	public function getSession() {
		return $this->query('UserSession')->getSession();
	}

	/**
	 * @param \OCP\ISession $session
	 */
	public function setSession(\OCP\ISession $session) {
		return $this->query('UserSession')->setSession($session);
	}

	/**
	 * @return \OC\NavigationManager
	 */
	public function getNavigationManager() {
		return $this->query('NavigationManager');
	}

	/**
	 * @return \OCP\IConfig
	 */
	public function getConfig() {
		return $this->query('AllConfig');
	}

	/**
	 * For internal use only
	 *
	 * @return \OC\SystemConfig
	 */
	public function getSystemConfig() {
		return $this->query('SystemConfig');
	}

	/**
	 * Returns the app config manager
	 *
	 * @return \OCP\IAppConfig
	 */
	public function getAppConfig() {
		return $this->query('AppConfig');
	}

	/**
	 * get an L10N instance
	 *
	 * @param string $app appid
	 * @param string $lang
	 * @return \OC_L10N
	 */
	public function getL10N($app, $lang = null) {
		return $this->query('L10NFactory')->get($app, $lang);
	}

	/**
	 * @return \OCP\IURLGenerator
	 */
	public function getURLGenerator() {
		return $this->query('URLGenerator');
	}

	/**
	 * @return \OCP\IHelper
	 */
	public function getHelper() {
		return $this->query('AppHelper');
	}

	/**
	 * Returns an ICache instance. Since 8.1.0 it returns a fake cache. Use
	 * getMemCacheFactory() instead.
	 *
	 * @return \OCP\ICache
	 * @deprecated 8.1.0 use getMemCacheFactory to obtain a proper cache
	 */
	public function getCache() {
		return $this->query('UserCache');
	}

	/**
	 * Returns an \OCP\CacheFactory instance
	 *
	 * @return \OCP\ICacheFactory
	 */
	public function getMemCacheFactory() {
		return $this->query('MemCacheFactory');
	}

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 */
	public function getDatabaseConnection() {
		return $this->query('DatabaseConnection');
	}

	/**
	 * Returns the activity manager
	 *
	 * @return \OCP\Activity\IManager
	 */
	public function getActivityManager() {
		return $this->query('ActivityManager');
	}

	/**
	 * Returns an job list for controlling background jobs
	 *
	 * @return \OCP\BackgroundJob\IJobList
	 */
	public function getJobList() {
		return $this->query('JobList');
	}

	/**
	 * Returns a logger instance
	 *
	 * @return \OCP\ILogger
	 */
	public function getLogger() {
		return $this->query('Logger');
	}

	/**
	 * Returns a router for generating and matching urls
	 *
	 * @return \OCP\Route\IRouter
	 */
	public function getRouter() {
		return $this->query('Router');
	}

	/**
	 * Returns a search instance
	 *
	 * @return \OCP\ISearch
	 */
	public function getSearch() {
		return $this->query('Search');
	}

	/**
	 * Returns a SecureRandom instance
	 *
	 * @return \OCP\Security\ISecureRandom
	 */
	public function getSecureRandom() {
		return $this->query('SecureRandom');
	}

	/**
	 * Returns a Crypto instance
	 *
	 * @return \OCP\Security\ICrypto
	 */
	public function getCrypto() {
		return $this->query('Crypto');
	}

	/**
	 * Returns a Hasher instance
	 *
	 * @return \OCP\Security\IHasher
	 */
	public function getHasher() {
		return $this->query('Hasher');
	}

	/**
	 * Returns an instance of the db facade
	 * @deprecated use getDatabaseConnection, will be removed in ownCloud 10
	 * @return \OCP\IDb
	 */
	public function getDb() {
		return $this->query('Db');
	}

	/**
	 * Returns an instance of the HTTP helper class
	 * @deprecated Use getHTTPClientService()
	 * @return \OC\HTTPHelper
	 */
	public function getHTTPHelper() {
		return $this->query('HTTPHelper');
	}

	/**
	 * Get the certificate manager for the user
	 *
	 * @param string $userId (optional) if not specified the current loggedin user is used
	 * @return \OCP\ICertificateManager | null if $uid is null and no user is logged in
	 */
	public function getCertificateManager($userId = null) {
		if (is_null($userId)) {
			$userSession = $this->getUserSession();
			$user = $userSession->getUser();
			if (is_null($user)) {
				return null;
			}
			$userId = $user->getUID();
		}
		return new CertificateManager($userId, new \OC\Files\View());
	}

	/**
	 * Returns an instance of the HTTP client service
	 *
	 * @return \OCP\Http\Client\IClientService
	 */
	public function getHTTPClientService() {
		return $this->query('HttpClientService');
	}

	/**
	 * Create a new event source
	 *
	 * @return \OCP\IEventSource
	 */
	public function createEventSource() {
		return new \OC_EventSource();
	}

	/**
	 * Get the active event logger
	 *
	 * The returned logger only logs data when debug mode is enabled
	 *
	 * @return \OCP\Diagnostics\IEventLogger
	 */
	public function getEventLogger() {
		return $this->query('EventLogger');
	}

	/**
	 * Get the active query logger
	 *
	 * The returned logger only logs data when debug mode is enabled
	 *
	 * @return \OCP\Diagnostics\IQueryLogger
	 */
	public function getQueryLogger() {
		return $this->query('QueryLogger');
	}

	/**
	 * Get the manager for temporary files and folders
	 *
	 * @return \OCP\ITempManager
	 */
	public function getTempManager() {
		return $this->query('TempManager');
	}

	/**
	 * Get the app manager
	 *
	 * @return \OCP\App\IAppManager
	 */
	public function getAppManager() {
		return $this->query('AppManager');
	}

	/**
	 * Creates a new mailer
	 *
	 * @return \OCP\Mail\IMailer
	 */
	public function getMailer() {
		return $this->query('Mailer');
	}

	/**
	 * Get the webroot
	 *
	 * @return string
	 */
	public function getWebRoot() {
		return $this->webRoot;
	}

	/**
	 * @return \OC\OCSClient
	 */
	public function getOcsClient() {
		return $this->query('OcsClient');
	}

	/**
	 * @return \OCP\IDateTimeZone
	 */
	public function getDateTimeZone() {
		return $this->query('DateTimeZone');
	}

	/**
	 * @return \OCP\IDateTimeFormatter
	 */
	public function getDateTimeFormatter() {
		return $this->query('DateTimeFormatter');
	}

	/**
	 * @return \OCP\Files\Config\IMountProviderCollection
	 */
	public function getMountProviderCollection(){
		return $this->query('MountConfigManager');
	}

	/**
	 * Get the IniWrapper
	 *
	 * @return IniGetWrapper
	 */
	public function getIniWrapper() {
		return $this->query('IniWrapper');
	}

	/**
	 * @return \OCP\Command\IBus
	 */
	public function getCommandBus(){
		return $this->query('AsyncCommandBus');
	}

	/**
	 * Get the trusted domain helper
	 *
	 * @return TrustedDomainHelper
	 */
	public function getTrustedDomainHelper() {
		return $this->query('TrustedDomainHelper');
	}

	/**
	 * Get the locking provider
	 *
	 * @return \OCP\Lock\ILockingProvider
	 * @since 8.1.0
	 */
	public function getLockingProvider() {
		return $this->query('LockingProvider');
	}
}
