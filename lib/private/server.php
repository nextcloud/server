<?php

namespace OC;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Cache\UserCache;
use OC\DB\ConnectionWrapper;
use OC\Files\Node\Root;
use OC\Files\View;
use OCP\IServerContainer;

/**
 * Class Server
 * @package OC
 *
 * TODO: hookup all manager classes
 */
class Server extends SimpleContainer implements IServerContainer {

	function __construct() {
		$this->registerService('ContactsManager', function($c) {
			return new ContactsManager();
		});
		$this->registerService('Request', function($c) {
			if (isset($c['urlParams'])) {
				$urlParams = $c['urlParams'];
			} else {
				$urlParams = array();
			}

			if (\OC::$session->exists('requesttoken')) {
				$requesttoken = \OC::$session->get('requesttoken');
			} else {
				$requesttoken = false;
			}

			if (defined('PHPUNIT_RUN') && PHPUNIT_RUN
			&& in_array('fakeinput', stream_get_wrappers())) {
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
					'requesttoken' => $requesttoken,
				), $stream
			);
		});
		$this->registerService('PreviewManager', function($c) {
			return new PreviewManager();
		});
		$this->registerService('TagManager', function($c) {
			$user = \OC_User::getUser();
			return new TagManager($user);
		});
		$this->registerService('RootFolder', function($c) {
			// TODO: get user and user manager from container as well
			$user = \OC_User::getUser();
			/** @var $c SimpleContainer */
			$userManager = $c->query('UserManager');
			$user = $userManager->get($user);
			$manager = \OC\Files\Filesystem::getMountManager();
			$view = new View();
			return new Root($manager, $view, $user);
		});
		$this->registerService('UserManager', function($c) {
			/**
			 * @var SimpleContainer $c
			 * @var \OC\AllConfig $config
			 */
			$config = $c->query('AllConfig');
			return new \OC\User\Manager($config);
		});
		$this->registerService('UserSession', function($c) {
			/**
			 * @var SimpleContainer $c
			 * @var \OC\User\Manager $manager
			 */
			$manager = $c->query('UserManager');
			$userSession = new \OC\User\Session($manager, \OC::$session);
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
		$this->registerService('NavigationManager', function($c) {
			return new \OC\NavigationManager();
		});
		$this->registerService('AllConfig', function($c) {
			return new \OC\AllConfig();
		});
		$this->registerService('AppConfig', function ($c) {
			return new \OC\AppConfig(\OC_DB::getConnection());
		});
		$this->registerService('L10NFactory', function($c) {
			return new \OC\L10N\Factory();
		});
		$this->registerService('URLGenerator', function($c) {
			/** @var $c SimpleContainer */
			$config = $c->query('AllConfig');
			return new \OC\URLGenerator($config);
		});
		$this->registerService('AppHelper', function($c) {
			return new \OC\AppHelper();
		});
		$this->registerService('UserCache', function($c) {
			return new UserCache();
		});
		$this->registerService('MemCacheFactory', function ($c) {
			$instanceId = \OC_Util::getInstanceId();
			return new \OC\Memcache\Factory($instanceId);
		});
		$this->registerService('ActivityManager', function($c) {
			return new ActivityManager();
		});
		$this->registerService('AvatarManager', function($c) {
			return new AvatarManager();
		});
		$this->registerService('JobList', function ($c) {
			/**
			 * @var Server $c
			 */
			$config = $c->getConfig();
			return new \OC\BackgroundJob\JobList($c->getDatabaseConnection(), $config);
		});
		$this->registerService('Router', function ($c){
			/**
			 * @var Server $c
			 */
			$cacheFactory = $c->getMemCacheFactory();
			if ($cacheFactory->isAvailable()) {
				$router = new \OC\Route\CachingRouter($cacheFactory->create('route'));
			} else {
				$router = new \OC\Route\Router();
			}
			return $router;
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
	 * @return \OCP\Files\Folder
	 */
	function getUserFolder() {

		$dir = '/files';
		$root = $this->getRootFolder();
		$folder = null;
		if(!$root->nodeExists($dir)) {
			$folder = $root->newFolder($dir);
		} else {
			$folder = $root->get($dir);
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
		if(!$root->nodeExists($dir)) {
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
	 * @return \OC\User\Session
	 */
	function getUserSession() {
		return $this->query('UserSession');
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
	 * Returns the app config manager
	 *
	 * @return \OCP\IAppConfig
	 */
	function getAppConfig(){
		return $this->query('AppConfig');
	}

	/**
	 * get an L10N instance
	 * @param string $app appid
	 * @return \OC_L10N
	 */
	function getL10N($app) {
		return $this->query('L10NFactory')->get($app);
	}

	/**
	 * @return \OC\URLGenerator
	 */
	function getURLGenerator() {
		return $this->query('URLGenerator');
	}

	/**
	 * @return \OC\Helper
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
	 * @return \OCP\ISession
	 */
	function getSession() {
		return \OC::$session;
	}

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 */
	function getDatabaseConnection() {
		return new ConnectionWrapper(\OC_DB::getConnection());
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
	function getJobList(){
		return $this->query('JobList');
	}

	/**
	 * Returns a router for generating and matching urls
	 *
	 * @return \OCP\Route\IRouter
	 */
	function getRouter(){
		return $this->query('Router');
	}
}
