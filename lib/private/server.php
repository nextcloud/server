<?php

namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Db\Db;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Cache\UserCache;
use OC\Diagnostics\NullQueryLogger;
use OC\Diagnostics\EventLogger;
use OC\Diagnostics\QueryLogger;
use OC\Files\Config\StorageManager;
use OC\Security\CertificateManager;
use OC\Files\Node\Root;
use OC\Files\View;
use OC\Security\Crypto;
use OC\Security\Hasher;
use OC\Security\SecureRandom;
use OC\Diagnostics\NullEventLogger;
use OCP\IServerContainer;
use OCP\ISession;
use OC\Tagging\TagMapper;

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
	function __construct($webRoot) {
		$this->webRoot = $webRoot;

		$this->registerService('ContactsManager', function ($c) {
			return new ContactsManager();
		});
		$this->registerService('Request', function (Server $c) {
			if (isset($c['urlParams'])) {
				$urlParams = $c['urlParams'];
			} else {
				$urlParams = array();
			}

			if ($c->getSession()->exists('requesttoken')) {
				$requestToken = $c->getSession()->get('requesttoken');
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
				array(
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
				), $stream
			);
		});
		$this->registerService('PreviewManager', function ($c) {
			return new PreviewManager();
		});
		$this->registerService('TagMapper', function(Server $c) {
			return new TagMapper($c->getDb());
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
			return new \OC\URLGenerator($config);
		});
		$this->registerService('AppHelper', function ($c) {
			return new \OC\AppHelper();
		});
		$this->registerService('UserCache', function ($c) {
			return new UserCache();
		});
		$this->registerService('MemCacheFactory', function ($c) {
			$instanceId = \OC_Util::getInstanceId();
			return new \OC\Memcache\Factory($instanceId);
		});
		$this->registerService('ActivityManager', function ($c) {
			return new ActivityManager();
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
			$user = $c->getUserSession()->getUser();
			$uid = $user ? $user->getUID() : null;
			return new HTTPHelper($config, new \OC\Security\CertificateManager($uid, new \OC\Files\View()));
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
			$userSession = $c->getUserSession();
			$appConfig = $c->getAppConfig();
			$groupManager = $c->getGroupManager();
			return new \OC\App\AppManager($userSession, $appConfig, $groupManager);
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
	}

	/**
	 * @return \OCP\Contacts\IManager
	 */
	function getContactsManager() {
		return $this->query('ContactsManager');
	}

	/**
	 * The current request object holding all information about the request
	 * currently being processed is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest|null
	 */
	function getRequest() {
		return $this->query('Request');
	}

	/**
	 * Returns the preview manager which can create preview images for a given file
	 *
	 * @return \OCP\IPreview
	 */
	function getPreviewManager() {
		return $this->query('PreviewManager');
	}

	/**
	 * Returns the tag manager which can get and set tags for different object types
	 *
	 * @see \OCP\ITagManager::load()
	 * @return \OCP\ITagManager
	 */
	function getTagManager() {
		return $this->query('TagManager');
	}

	/**
	 * Returns the avatar manager, used for avatar functionality
	 *
	 * @return \OCP\IAvatarManager
	 */
	function getAvatarManager() {
		return $this->query('AvatarManager');
	}

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return \OCP\Files\Folder
	 */
	function getRootFolder() {
		return $this->query('RootFolder');
	}

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 */
	function getUserFolder($userId = null) {
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

			if (\OCP\App::isEnabled('files_encryption')) {
				// disable encryption proxy to prevent recursive calls
				$proxyStatus = \OC_FileProxy::$enabled;
				\OC_FileProxy::$enabled = false;
			}

			\OC_Util::copySkeleton($user, $folder);

			if (\OCP\App::isEnabled('files_encryption')) {
				// re-enable proxy - our work is done
				\OC_FileProxy::$enabled = $proxyStatus;
			}
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
	function getAppFolder() {
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
	function getUserManager() {
		return $this->query('UserManager');
	}

	/**
	 * @return \OC\Group\Manager
	 */
	function getGroupManager() {
		return $this->query('GroupManager');
	}

	/**
	 * @return \OC\User\Session
	 */
	function getUserSession() {
		return $this->query('UserSession');
	}

	/**
	 * @return \OCP\ISession
	 */
	function getSession() {
		return $this->query('UserSession')->getSession();
	}

	/**
	 * @param \OCP\ISession $session
	 */
	function setSession(\OCP\ISession $session) {
		return $this->query('UserSession')->setSession($session);
	}

	/**
	 * @return \OC\NavigationManager
	 */
	function getNavigationManager() {
		return $this->query('NavigationManager');
	}

	/**
	 * @return \OCP\IConfig
	 */
	function getConfig() {
		return $this->query('AllConfig');
	}

	/**
	 * For internal use only
	 *
	 * @return \OC\SystemConfig
	 */
	function getSystemConfig() {
		return $this->query('SystemConfig');
	}

	/**
	 * Returns the app config manager
	 *
	 * @return \OCP\IAppConfig
	 */
	function getAppConfig() {
		return $this->query('AppConfig');
	}

	/**
	 * get an L10N instance
	 *
	 * @param string $app appid
	 * @param string $lang
	 * @return \OC_L10N
	 */
	function getL10N($app, $lang = null) {
		return $this->query('L10NFactory')->get($app, $lang);
	}

	/**
	 * @return \OCP\IURLGenerator
	 */
	function getURLGenerator() {
		return $this->query('URLGenerator');
	}

	/**
	 * @return \OCP\IHelper
	 */
	function getHelper() {
		return $this->query('AppHelper');
	}

	/**
	 * Returns an ICache instance
	 *
	 * @return \OCP\ICache
	 */
	function getCache() {
		return $this->query('UserCache');
	}

	/**
	 * Returns an \OCP\CacheFactory instance
	 *
	 * @return \OCP\ICacheFactory
	 */
	function getMemCacheFactory() {
		return $this->query('MemCacheFactory');
	}

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 */
	function getDatabaseConnection() {
		return $this->query('DatabaseConnection');
	}

	/**
	 * Returns the activity manager
	 *
	 * @return \OCP\Activity\IManager
	 */
	function getActivityManager() {
		return $this->query('ActivityManager');
	}

	/**
	 * Returns an job list for controlling background jobs
	 *
	 * @return \OCP\BackgroundJob\IJobList
	 */
	function getJobList() {
		return $this->query('JobList');
	}

	/**
	 * Returns a logger instance
	 *
	 * @return \OCP\ILogger
	 */
	function getLogger() {
		return $this->query('Logger');
	}

	/**
	 * Returns a router for generating and matching urls
	 *
	 * @return \OCP\Route\IRouter
	 */
	function getRouter() {
		return $this->query('Router');
	}

	/**
	 * Returns a search instance
	 *
	 * @return \OCP\ISearch
	 */
	function getSearch() {
		return $this->query('Search');
	}

	/**
	 * Returns a SecureRandom instance
	 *
	 * @return \OCP\Security\ISecureRandom
	 */
	function getSecureRandom() {
		return $this->query('SecureRandom');
	}

	/**
	 * Returns a Crypto instance
	 *
	 * @return \OCP\Security\ICrypto
	 */
	function getCrypto() {
		return $this->query('Crypto');
	}

	/**
	 * Returns a Hasher instance
	 *
	 * @return \OCP\Security\IHasher
	 */
	function getHasher() {
		return $this->query('Hasher');
	}

	/**
	 * Returns an instance of the db facade
	 *
	 * @return \OCP\IDb
	 */
	function getDb() {
		return $this->query('Db');
	}

	/**
	 * Returns an instance of the HTTP helper class
	 *
	 * @return \OC\HTTPHelper
	 */
	function getHTTPHelper() {
		return $this->query('HTTPHelper');
	}

	/**
	 * Get the certificate manager for the user
	 *
	 * @param string $uid (optional) if not specified the current loggedin user is used
	 * @return \OCP\ICertificateManager
	 */
	function getCertificateManager($uid = null) {
		if (is_null($uid)) {
			$userSession = $this->getUserSession();
			$user = $userSession->getUser();
			if (is_null($user)) {
				return null;
			}
			$uid = $user->getUID();
		}
		return new CertificateManager($uid, new \OC\Files\View());
	}

	/**
	 * Create a new event source
	 *
	 * @return \OCP\IEventSource
	 */
	function createEventSource() {
		return new \OC_EventSource();
	}

	/**
	 * Get the active event logger
	 *
	 * The returned logger only logs data when debug mode is enabled
	 *
	 * @return \OCP\Diagnostics\IEventLogger
	 */
	function getEventLogger() {
		return $this->query('EventLogger');
	}

	/**
	 * Get the active query logger
	 *
	 * The returned logger only logs data when debug mode is enabled
	 *
	 * @return \OCP\Diagnostics\IQueryLogger
	 */
	function getQueryLogger() {
		return $this->query('QueryLogger');
	}

	/**
	 * Get the manager for temporary files and folders
	 *
	 * @return \OCP\ITempManager
	 */
	function getTempManager() {
		return $this->query('TempManager');
	}

	/**
	 * Get the app manager
	 *
	 * @return \OCP\App\IAppManager
	 */
	function getAppManager() {
		return $this->query('AppManager');
	}

	/**
	 * Get the webroot
	 *
	 * @return string
	 */
	function getWebRoot() {
		return $this->webRoot;
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
	function getMountProviderCollection(){
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
}
