<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use NCU\Config\IUserConfig;
use NCU\Security\Signature\ISignatureManager;
use OC\Accounts\AccountManager;
use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RequestId;
use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\Events\LoginFailed;
use OC\Authentication\Listeners\LoginFailedListener;
use OC\Authentication\Listeners\UserLoggedInListener;
use OC\Authentication\LoginCredentials\Store;
use OC\Authentication\Token\IProvider;
use OC\Avatar\AvatarManager;
use OC\Blurhash\Listener\GenerateBlurhashMetadata;
use OC\Collaboration\Collaborators\GroupPlugin;
use OC\Collaboration\Collaborators\MailPlugin;
use OC\Collaboration\Collaborators\RemoteGroupPlugin;
use OC\Collaboration\Collaborators\RemotePlugin;
use OC\Collaboration\Collaborators\UserPlugin;
use OC\Collaboration\Reference\ReferenceManager;
use OC\Command\CronBus;
use OC\Comments\ManagerFactory as CommentsManagerFactory;
use OC\Contacts\ContactsMenu\ActionFactory;
use OC\Contacts\ContactsMenu\ContactsStore;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\Diagnostics\EventLogger;
use OC\Diagnostics\QueryLogger;
use OC\Federation\CloudFederationFactory;
use OC\Federation\CloudFederationProviderManager;
use OC\Federation\CloudIdManager;
use OC\Files\Cache\FileAccess;
use OC\Files\Config\MountProviderCollection;
use OC\Files\Config\UserMountCache;
use OC\Files\Config\UserMountCacheListener;
use OC\Files\Conversion\ConversionManager;
use OC\Files\Lock\LockManager;
use OC\Files\Mount\CacheMountProvider;
use OC\Files\Mount\LocalHomeMountProvider;
use OC\Files\Mount\ObjectHomeMountProvider;
use OC\Files\Mount\ObjectStorePreviewCacheMountProvider;
use OC\Files\Mount\RootMountProvider;
use OC\Files\Node\HookConnector;
use OC\Files\Node\LazyRoot;
use OC\Files\Node\Root;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\SetupManager;
use OC\Files\Storage\StorageFactory;
use OC\Files\Template\TemplateManager;
use OC\Files\Type\Loader;
use OC\Files\View;
use OC\FilesMetadata\FilesMetadataManager;
use OC\FullTextSearch\FullTextSearchManager;
use OC\Http\Client\ClientService;
use OC\Http\Client\NegativeDnsCache;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\AppLocator;
use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OC\KnownUser\KnownUserService;
use OC\LDAP\NullLDAPProviderFactory;
use OC\Lock\DBLockingProvider;
use OC\Lock\MemcacheLockingProvider;
use OC\Lock\NoopLockingProvider;
use OC\Lockdown\LockdownManager;
use OC\Log\LogFactory;
use OC\Log\PsrLoggerAdapter;
use OC\Mail\Mailer;
use OC\Memcache\ArrayCache;
use OC\Memcache\Factory;
use OC\Notification\Manager;
use OC\OCM\Model\OCMProvider;
use OC\OCM\OCMDiscoveryService;
use OC\OCS\DiscoveryService;
use OC\Preview\GeneratorHelper;
use OC\Preview\IMagickSupport;
use OC\Preview\MimeIconProvider;
use OC\Profile\ProfileManager;
use OC\Profiler\Profiler;
use OC\Remote\Api\ApiFactory;
use OC\Remote\InstanceFactory;
use OC\RichObjectStrings\Validator;
use OC\Route\CachingRouter;
use OC\Route\Router;
use OC\Security\Bruteforce\Throttler;
use OC\Security\CertificateManager;
use OC\Security\CredentialsManager;
use OC\Security\Crypto;
use OC\Security\CSP\ContentSecurityPolicyManager;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Security\CSRF\CsrfTokenManager;
use OC\Security\CSRF\TokenStorage\SessionStorage;
use OC\Security\Hasher;
use OC\Security\Ip\RemoteAddress;
use OC\Security\RateLimiting\Limiter;
use OC\Security\SecureRandom;
use OC\Security\Signature\SignatureManager;
use OC\Security\TrustedDomainHelper;
use OC\Security\VerificationToken\VerificationToken;
use OC\Session\CryptoWrapper;
use OC\Settings\DeclarativeManager;
use OC\SetupCheck\SetupCheckManager;
use OC\Share20\ProviderFactory;
use OC\Share20\ShareHelper;
use OC\SpeechToText\SpeechToTextManager;
use OC\SystemTag\ManagerFactory as SystemTagManagerFactory;
use OC\Talk\Broker;
use OC\Teams\TeamManager;
use OC\Template\JSCombiner;
use OC\Translation\TranslationManager;
use OC\User\AvailabilityCoordinator;
use OC\User\DisplayNameCache;
use OC\User\Listeners\BeforeUserDeletedListener;
use OC\User\Listeners\UserChangedListener;
use OC\User\Session;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Authentication\Token\IProvider as OCPIProvider;
use OCP\BackgroundJob\IJobList;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Command\IBus;
use OCP\Comments\ICommentsManager;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IContactsStore;
use OCP\Defaults;
use OCP\Diagnostics\IEventLogger;
use OCP\Diagnostics\IQueryLogger;
use OCP\Encryption\IFile;
use OCP\Encryption\Keys\IStorage;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Cache\IFileAccess;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IStorageFactory;
use OCP\Files\Template\ITemplateManager;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\GlobalScale\IConfig;
use OCP\Group\ISubAdmin;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IAvatarManager;
use OCP\IBinaryFinder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\ICertificateManager;
use OCP\IDateTimeFormatter;
use OCP\IDateTimeZone;
use OCP\IDBConnection;
use OCP\IEventSourceFactory;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IPhoneNumberUtil;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\IServerContainer;
use OCP\ISession;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\ILDAPProviderFactory;
use OCP\Lock\ILockingProvider;
use OCP\Lockdown\ILockdownManager;
use OCP\Log\ILogFactory;
use OCP\Mail\IMailer;
use OCP\OCM\ICapabilityAwareOCMProvider;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCM\IOCMProvider;
use OCP\Preview\IMimeIconProvider;
use OCP\Profile\IProfileManager;
use OCP\Profiler\IProfiler;
use OCP\Remote\Api\IApiFactory;
use OCP\Remote\IInstanceFactory;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use OCP\Route\IRouter;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ICredentialsManager;
use OCP\Security\ICrypto;
use OCP\Security\IHasher;
use OCP\Security\Ip\IRemoteAddress;
use OCP\Security\ISecureRandom;
use OCP\Security\ITrustedDomainHelper;
use OCP\Security\RateLimiting\ILimiter;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\ServerVersion;
use OCP\Settings\IDeclarativeManager;
use OCP\SetupCheck\ISetupCheckManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShareHelper;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Talk\IBroker;
use OCP\Teams\ITeamManager;
use OCP\Translation\ITranslationManager;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedInWithCookieEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use OCP\User\Events\UserLoggedOutEvent;
use OCP\User\IAvailabilityCoordinator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Server
 *
 * @package OC
 *
 * TODO: hookup all manager classes
 */
class Server extends ServerContainer implements IServerContainer {
	/** @var string */
	private $webRoot;

	/**
	 * @param string $webRoot
	 * @param \OC\Config $config
	 */
	public function __construct($webRoot, \OC\Config $config) {
		parent::__construct();
		$this->webRoot = $webRoot;

		// To find out if we are running from CLI or not
		$this->registerParameter('isCLI', \OC::$CLI);
		$this->registerParameter('serverRoot', \OC::$SERVERROOT);

		$this->registerService(ContainerInterface::class, function (ContainerInterface $c) {
			return $c;
		});
		$this->registerService(\OCP\IServerContainer::class, function (ContainerInterface $c) {
			return $c;
		});

		$this->registerAlias(\OCP\Calendar\IManager::class, \OC\Calendar\Manager::class);

		$this->registerAlias(\OCP\Calendar\Resource\IManager::class, \OC\Calendar\Resource\Manager::class);

		$this->registerAlias(\OCP\Calendar\Room\IManager::class, \OC\Calendar\Room\Manager::class);

		$this->registerAlias(\OCP\Contacts\IManager::class, \OC\ContactsManager::class);

		$this->registerAlias(\OCP\DirectEditing\IManager::class, \OC\DirectEditing\Manager::class);
		$this->registerAlias(ITemplateManager::class, TemplateManager::class);
		$this->registerAlias(\OCP\Template\ITemplateManager::class, \OC\Template\TemplateManager::class);

		$this->registerAlias(IActionFactory::class, ActionFactory::class);

		$this->registerService(View::class, function (Server $c) {
			return new View();
		}, false);

		$this->registerService(IPreview::class, function (ContainerInterface $c) {
			return new PreviewManager(
				$c->get(\OCP\IConfig::class),
				$c->get(IRootFolder::class),
				new \OC\Preview\Storage\Root(
					$c->get(IRootFolder::class),
					$c->get(SystemConfig::class)
				),
				$c->get(IEventDispatcher::class),
				$c->get(GeneratorHelper::class),
				$c->get(ISession::class)->get('user_id'),
				$c->get(Coordinator::class),
				$c->get(IServerContainer::class),
				$c->get(IBinaryFinder::class),
				$c->get(IMagickSupport::class)
			);
		});
		$this->registerAlias(IMimeIconProvider::class, MimeIconProvider::class);

		$this->registerService(\OC\Preview\Watcher::class, function (ContainerInterface $c) {
			return new \OC\Preview\Watcher(
				new \OC\Preview\Storage\Root(
					$c->get(IRootFolder::class),
					$c->get(SystemConfig::class)
				)
			);
		});

		$this->registerService(IProfiler::class, function (Server $c) {
			return new Profiler($c->get(SystemConfig::class));
		});

		$this->registerService(Encryption\Manager::class, function (Server $c): Encryption\Manager {
			$view = new View();
			$util = new Encryption\Util(
				$view,
				$c->get(IUserManager::class),
				$c->get(IGroupManager::class),
				$c->get(\OCP\IConfig::class)
			);
			return new Encryption\Manager(
				$c->get(\OCP\IConfig::class),
				$c->get(LoggerInterface::class),
				$c->getL10N('core'),
				new View(),
				$util,
				new ArrayCache()
			);
		});
		$this->registerAlias(\OCP\Encryption\IManager::class, Encryption\Manager::class);

		$this->registerService(IFile::class, function (ContainerInterface $c) {
			$util = new Encryption\Util(
				new View(),
				$c->get(IUserManager::class),
				$c->get(IGroupManager::class),
				$c->get(\OCP\IConfig::class)
			);
			return new Encryption\File(
				$util,
				$c->get(IRootFolder::class),
				$c->get(\OCP\Share\IManager::class)
			);
		});

		$this->registerService(IStorage::class, function (ContainerInterface $c) {
			$view = new View();
			$util = new Encryption\Util(
				$view,
				$c->get(IUserManager::class),
				$c->get(IGroupManager::class),
				$c->get(\OCP\IConfig::class)
			);

			return new Encryption\Keys\Storage(
				$view,
				$util,
				$c->get(ICrypto::class),
				$c->get(\OCP\IConfig::class)
			);
		});

		$this->registerAlias(\OCP\ITagManager::class, TagManager::class);

		$this->registerService('SystemTagManagerFactory', function (ContainerInterface $c) {
			/** @var \OCP\IConfig $config */
			$config = $c->get(\OCP\IConfig::class);
			$factoryClass = $config->getSystemValue('systemtags.managerFactory', SystemTagManagerFactory::class);
			return new $factoryClass($this);
		});
		$this->registerService(ISystemTagManager::class, function (ContainerInterface $c) {
			return $c->get('SystemTagManagerFactory')->getManager();
		});
		/** @deprecated 19.0.0 */
		$this->registerDeprecatedAlias('SystemTagManager', ISystemTagManager::class);

		$this->registerService(ISystemTagObjectMapper::class, function (ContainerInterface $c) {
			return $c->get('SystemTagManagerFactory')->getObjectMapper();
		});
		$this->registerAlias(IFileAccess::class, FileAccess::class);
		$this->registerService('RootFolder', function (ContainerInterface $c) {
			$manager = \OC\Files\Filesystem::getMountManager();
			$view = new View();
			/** @var IUserSession $userSession */
			$userSession = $c->get(IUserSession::class);
			$root = new Root(
				$manager,
				$view,
				$userSession->getUser(),
				$c->get(IUserMountCache::class),
				$this->get(LoggerInterface::class),
				$this->get(IUserManager::class),
				$this->get(IEventDispatcher::class),
				$this->get(ICacheFactory::class),
			);

			$previewConnector = new \OC\Preview\WatcherConnector(
				$root,
				$c->get(SystemConfig::class),
				$this->get(IEventDispatcher::class)
			);
			$previewConnector->connectWatcher();

			return $root;
		});
		$this->registerService(HookConnector::class, function (ContainerInterface $c) {
			return new HookConnector(
				$c->get(IRootFolder::class),
				new View(),
				$c->get(IEventDispatcher::class),
				$c->get(LoggerInterface::class)
			);
		});

		$this->registerService(IRootFolder::class, function (ContainerInterface $c) {
			return new LazyRoot(function () use ($c) {
				return $c->get('RootFolder');
			});
		});

		$this->registerAlias(\OCP\IUserManager::class, \OC\User\Manager::class);

		$this->registerService(DisplayNameCache::class, function (ContainerInterface $c) {
			return $c->get(\OC\User\Manager::class)->getDisplayNameCache();
		});

		$this->registerService(\OCP\IGroupManager::class, function (ContainerInterface $c) {
			$groupManager = new \OC\Group\Manager(
				$this->get(IUserManager::class),
				$this->get(IEventDispatcher::class),
				$this->get(LoggerInterface::class),
				$this->get(ICacheFactory::class),
				$this->get(IRemoteAddress::class),
			);
			return $groupManager;
		});

		$this->registerService(Store::class, function (ContainerInterface $c) {
			$session = $c->get(ISession::class);
			if (\OC::$server->get(SystemConfig::class)->getValue('installed', false)) {
				$tokenProvider = $c->get(IProvider::class);
			} else {
				$tokenProvider = null;
			}
			$logger = $c->get(LoggerInterface::class);
			$crypto = $c->get(ICrypto::class);
			return new Store($session, $logger, $crypto, $tokenProvider);
		});
		$this->registerAlias(IStore::class, Store::class);
		$this->registerAlias(IProvider::class, Authentication\Token\Manager::class);
		$this->registerAlias(OCPIProvider::class, Authentication\Token\Manager::class);

		$this->registerService(\OC\User\Session::class, function (Server $c) {
			$manager = $c->get(IUserManager::class);
			$session = new \OC\Session\Memory();
			$timeFactory = new TimeFactory();
			// Token providers might require a working database. This code
			// might however be called when Nextcloud is not yet setup.
			if (\OC::$server->get(SystemConfig::class)->getValue('installed', false)) {
				$provider = $c->get(IProvider::class);
			} else {
				$provider = null;
			}

			$userSession = new \OC\User\Session(
				$manager,
				$session,
				$timeFactory,
				$provider,
				$c->get(\OCP\IConfig::class),
				$c->get(ISecureRandom::class),
				$c->get('LockdownManager'),
				$c->get(LoggerInterface::class),
				$c->get(IEventDispatcher::class),
			);
			/** @deprecated 21.0.0 use BeforeUserCreatedEvent event with the IEventDispatcher instead */
			$userSession->listen('\OC\User', 'preCreateUser', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_createUser', ['run' => true, 'uid' => $uid, 'password' => $password]);
			});
			/** @deprecated 21.0.0 use UserCreatedEvent event with the IEventDispatcher instead */
			$userSession->listen('\OC\User', 'postCreateUser', function ($user, $password) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'post_createUser', ['uid' => $user->getUID(), 'password' => $password]);
			});
			/** @deprecated 21.0.0 use BeforeUserDeletedEvent event with the IEventDispatcher instead */
			$userSession->listen('\OC\User', 'preDelete', function ($user) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'pre_deleteUser', ['run' => true, 'uid' => $user->getUID()]);
			});
			/** @deprecated 21.0.0 use UserDeletedEvent event with the IEventDispatcher instead */
			$userSession->listen('\OC\User', 'postDelete', function ($user) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'post_deleteUser', ['uid' => $user->getUID()]);
			});
			$userSession->listen('\OC\User', 'preSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'pre_setPassword', ['run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword]);
			});
			$userSession->listen('\OC\User', 'postSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'post_setPassword', ['run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword]);
			});
			$userSession->listen('\OC\User', 'preLogin', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_login', ['run' => true, 'uid' => $uid, 'password' => $password]);

				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new BeforeUserLoggedInEvent($uid, $password));
			});
			$userSession->listen('\OC\User', 'postLogin', function ($user, $loginName, $password, $isTokenLogin) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'post_login', ['run' => true, 'uid' => $user->getUID(), 'loginName' => $loginName, 'password' => $password, 'isTokenLogin' => $isTokenLogin]);

				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new UserLoggedInEvent($user, $loginName, $password, $isTokenLogin));
			});
			$userSession->listen('\OC\User', 'preRememberedLogin', function ($uid) {
				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new BeforeUserLoggedInWithCookieEvent($uid));
			});
			$userSession->listen('\OC\User', 'postRememberedLogin', function ($user, $password) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'post_login', ['run' => true, 'uid' => $user->getUID(), 'password' => $password]);

				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new UserLoggedInWithCookieEvent($user, $password));
			});
			$userSession->listen('\OC\User', 'logout', function ($user) {
				\OC_Hook::emit('OC_User', 'logout', []);

				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new BeforeUserLoggedOutEvent($user));
			});
			$userSession->listen('\OC\User', 'postLogout', function ($user) {
				/** @var IEventDispatcher $dispatcher */
				$dispatcher = $this->get(IEventDispatcher::class);
				$dispatcher->dispatchTyped(new UserLoggedOutEvent($user));
			});
			$userSession->listen('\OC\User', 'changeUser', function ($user, $feature, $value, $oldValue) {
				/** @var \OC\User\User $user */
				\OC_Hook::emit('OC_User', 'changeUser', ['run' => true, 'user' => $user, 'feature' => $feature, 'value' => $value, 'old_value' => $oldValue]);
			});
			return $userSession;
		});
		$this->registerAlias(\OCP\IUserSession::class, \OC\User\Session::class);

		$this->registerAlias(\OCP\Authentication\TwoFactorAuth\IRegistry::class, \OC\Authentication\TwoFactorAuth\Registry::class);

		$this->registerAlias(INavigationManager::class, \OC\NavigationManager::class);

		$this->registerAlias(\OCP\IConfig::class, \OC\AllConfig::class);

		$this->registerService(\OC\SystemConfig::class, function ($c) use ($config) {
			return new \OC\SystemConfig($config);
		});

		$this->registerAlias(IAppConfig::class, \OC\AppConfig::class);
		$this->registerAlias(IUserConfig::class, \OC\Config\UserConfig::class);

		$this->registerService(IFactory::class, function (Server $c) {
			return new \OC\L10N\Factory(
				$c->get(\OCP\IConfig::class),
				$c->getRequest(),
				$c->get(IUserSession::class),
				$c->get(ICacheFactory::class),
				\OC::$SERVERROOT,
				$c->get(IAppManager::class),
			);
		});

		$this->registerAlias(IURLGenerator::class, URLGenerator::class);

		$this->registerService(ICache::class, function ($c) {
			return new Cache\File();
		});

		$this->registerService(Factory::class, function (Server $c) {
			$profiler = $c->get(IProfiler::class);
			$arrayCacheFactory = new \OC\Memcache\Factory(fn () => '', $c->get(LoggerInterface::class),
				$profiler,
				ArrayCache::class,
				ArrayCache::class,
				ArrayCache::class
			);
			/** @var SystemConfig $config */
			$config = $c->get(SystemConfig::class);
			/** @var ServerVersion $serverVersion */
			$serverVersion = $c->get(ServerVersion::class);

			if ($config->getValue('installed', false) && !(defined('PHPUNIT_RUN') && PHPUNIT_RUN)) {
				$logQuery = $config->getValue('log_query');
				$prefixClosure = function () use ($logQuery, $serverVersion): ?string {
					if (!$logQuery) {
						try {
							$v = \OCP\Server::get(IAppConfig::class)->getAppInstalledVersions(true);
						} catch (\Doctrine\DBAL\Exception $e) {
							// Database service probably unavailable
							// Probably related to https://github.com/nextcloud/server/issues/37424
							return null;
						}
					} else {
						// If the log_query is enabled, we can not get the app versions
						// as that does a query, which will be logged and the logging
						// depends on redis and here we are back again in the same function.
						$v = [
							'log_query' => 'enabled',
						];
					}
					$v['core'] = implode(',', $serverVersion->getVersion());
					$version = implode(',', array_keys($v)) . implode(',', $v);
					$instanceId = \OC_Util::getInstanceId();
					$path = \OC::$SERVERROOT;
					return md5($instanceId . '-' . $version . '-' . $path);
				};
				return new \OC\Memcache\Factory($prefixClosure,
					$c->get(LoggerInterface::class),
					$profiler,
					/** @psalm-taint-escape callable */
					$config->getValue('memcache.local', null),
					/** @psalm-taint-escape callable */
					$config->getValue('memcache.distributed', null),
					/** @psalm-taint-escape callable */
					$config->getValue('memcache.locking', null),
					/** @psalm-taint-escape callable */
					$config->getValue('redis_log_file')
				);
			}
			return $arrayCacheFactory;
		});
		$this->registerAlias(ICacheFactory::class, Factory::class);

		$this->registerService('RedisFactory', function (Server $c) {
			$systemConfig = $c->get(SystemConfig::class);
			return new RedisFactory($systemConfig, $c->get(IEventLogger::class));
		});

		$this->registerService(\OCP\Activity\IManager::class, function (Server $c) {
			$l10n = $this->get(IFactory::class)->get('lib');
			return new \OC\Activity\Manager(
				$c->getRequest(),
				$c->get(IUserSession::class),
				$c->get(\OCP\IConfig::class),
				$c->get(IValidator::class),
				$c->get(IRichTextFormatter::class),
				$l10n
			);
		});

		$this->registerService(\OCP\Activity\IEventMerger::class, function (Server $c) {
			return new \OC\Activity\EventMerger(
				$c->getL10N('lib')
			);
		});
		$this->registerAlias(IValidator::class, Validator::class);

		$this->registerService(AvatarManager::class, function (Server $c) {
			return new AvatarManager(
				$c->get(IUserSession::class),
				$c->get(\OC\User\Manager::class),
				$c->getAppDataDir('avatar'),
				$c->getL10N('lib'),
				$c->get(LoggerInterface::class),
				$c->get(\OCP\IConfig::class),
				$c->get(IAccountManager::class),
				$c->get(KnownUserService::class)
			);
		});

		$this->registerAlias(IAvatarManager::class, AvatarManager::class);

		$this->registerAlias(\OCP\Support\CrashReport\IRegistry::class, \OC\Support\CrashReport\Registry::class);
		$this->registerAlias(\OCP\Support\Subscription\IRegistry::class, \OC\Support\Subscription\Registry::class);
		$this->registerAlias(\OCP\Support\Subscription\IAssertion::class, \OC\Support\Subscription\Assertion::class);

		/** Only used by the PsrLoggerAdapter should not be used by apps */
		$this->registerService(\OC\Log::class, function (Server $c) {
			$logType = $c->get(AllConfig::class)->getSystemValue('log_type', 'file');
			$factory = new LogFactory($c, $this->get(SystemConfig::class));
			$logger = $factory->get($logType);
			$registry = $c->get(\OCP\Support\CrashReport\IRegistry::class);

			return new Log($logger, $this->get(SystemConfig::class), crashReporters: $registry);
		});
		// PSR-3 logger
		$this->registerAlias(LoggerInterface::class, PsrLoggerAdapter::class);

		$this->registerService(ILogFactory::class, function (Server $c) {
			return new LogFactory($c, $this->get(SystemConfig::class));
		});

		$this->registerAlias(IJobList::class, \OC\BackgroundJob\JobList::class);

		$this->registerService(Router::class, function (Server $c) {
			$cacheFactory = $c->get(ICacheFactory::class);
			if ($cacheFactory->isLocalCacheAvailable()) {
				$router = $c->resolve(CachingRouter::class);
			} else {
				$router = $c->resolve(Router::class);
			}
			return $router;
		});
		$this->registerAlias(IRouter::class, Router::class);

		$this->registerService(\OC\Security\RateLimiting\Backend\IBackend::class, function ($c) {
			$config = $c->get(\OCP\IConfig::class);
			if (ltrim($config->getSystemValueString('memcache.distributed', ''), '\\') === \OC\Memcache\Redis::class) {
				$backend = new \OC\Security\RateLimiting\Backend\MemoryCacheBackend(
					$c->get(AllConfig::class),
					$this->get(ICacheFactory::class),
					new \OC\AppFramework\Utility\TimeFactory()
				);
			} else {
				$backend = new \OC\Security\RateLimiting\Backend\DatabaseBackend(
					$c->get(AllConfig::class),
					$c->get(IDBConnection::class),
					new \OC\AppFramework\Utility\TimeFactory()
				);
			}

			return $backend;
		});

		$this->registerAlias(\OCP\Security\ISecureRandom::class, SecureRandom::class);
		$this->registerAlias(\OCP\Security\IRemoteHostValidator::class, \OC\Security\RemoteHostValidator::class);
		$this->registerAlias(IVerificationToken::class, VerificationToken::class);

		$this->registerAlias(ICrypto::class, Crypto::class);

		$this->registerAlias(IHasher::class, Hasher::class);

		$this->registerAlias(ICredentialsManager::class, CredentialsManager::class);

		$this->registerAlias(IDBConnection::class, ConnectionAdapter::class);
		$this->registerService(Connection::class, function (Server $c) {
			$systemConfig = $c->get(SystemConfig::class);
			$factory = new \OC\DB\ConnectionFactory($systemConfig, $c->get(ICacheFactory::class));
			$type = $systemConfig->getValue('dbtype', 'sqlite');
			if (!$factory->isValidType($type)) {
				throw new \OC\DatabaseException('Invalid database type');
			}
			$connection = $factory->getConnection($type, []);
			return $connection;
		});

		$this->registerAlias(ICertificateManager::class, CertificateManager::class);
		$this->registerAlias(IClientService::class, ClientService::class);
		$this->registerService(NegativeDnsCache::class, function (ContainerInterface $c) {
			return new NegativeDnsCache(
				$c->get(ICacheFactory::class),
			);
		});
		$this->registerDeprecatedAlias('HttpClientService', IClientService::class);
		$this->registerService(IEventLogger::class, function (ContainerInterface $c) {
			return new EventLogger($c->get(SystemConfig::class), $c->get(LoggerInterface::class), $c->get(Log::class));
		});

		$this->registerService(IQueryLogger::class, function (ContainerInterface $c) {
			$queryLogger = new QueryLogger();
			if ($c->get(SystemConfig::class)->getValue('debug', false)) {
				// In debug mode, module is being activated by default
				$queryLogger->activate();
			}
			return $queryLogger;
		});

		$this->registerAlias(ITempManager::class, TempManager::class);

		$this->registerService(AppManager::class, function (ContainerInterface $c) {
			// TODO: use auto-wiring
			return new \OC\App\AppManager(
				$c->get(IUserSession::class),
				$c->get(\OCP\IConfig::class),
				$c->get(IGroupManager::class),
				$c->get(ICacheFactory::class),
				$c->get(IEventDispatcher::class),
				$c->get(LoggerInterface::class),
				$c->get(ServerVersion::class),
			);
		});
		$this->registerAlias(IAppManager::class, AppManager::class);

		$this->registerAlias(IDateTimeZone::class, DateTimeZone::class);

		$this->registerService(IDateTimeFormatter::class, function (Server $c) {
			$language = $c->get(\OCP\IConfig::class)->getUserValue($c->get(ISession::class)->get('user_id'), 'core', 'lang', null);

			return new DateTimeFormatter(
				$c->get(IDateTimeZone::class)->getTimeZone(),
				$c->getL10N('lib', $language)
			);
		});

		$this->registerService(IUserMountCache::class, function (ContainerInterface $c) {
			$mountCache = $c->get(UserMountCache::class);
			$listener = new UserMountCacheListener($mountCache);
			$listener->listen($c->get(IUserManager::class));
			return $mountCache;
		});

		$this->registerService(IMountProviderCollection::class, function (ContainerInterface $c) {
			$loader = $c->get(IStorageFactory::class);
			$mountCache = $c->get(IUserMountCache::class);
			$eventLogger = $c->get(IEventLogger::class);
			$manager = new MountProviderCollection($loader, $mountCache, $eventLogger);

			// builtin providers

			$config = $c->get(\OCP\IConfig::class);
			$logger = $c->get(LoggerInterface::class);
			$objectStoreConfig = $c->get(PrimaryObjectStoreConfig::class);
			$manager->registerProvider(new CacheMountProvider($config));
			$manager->registerHomeProvider(new LocalHomeMountProvider());
			$manager->registerHomeProvider(new ObjectHomeMountProvider($objectStoreConfig));
			$manager->registerRootProvider(new RootMountProvider($objectStoreConfig, $config));
			$manager->registerRootProvider(new ObjectStorePreviewCacheMountProvider($logger, $config));

			return $manager;
		});

		$this->registerService(IBus::class, function (ContainerInterface $c) {
			$busClass = $c->get(\OCP\IConfig::class)->getSystemValueString('commandbus');
			if ($busClass) {
				[$app, $class] = explode('::', $busClass, 2);
				if ($c->get(IAppManager::class)->isEnabledForUser($app)) {
					$c->get(IAppManager::class)->loadApp($app);
					return $c->get($class);
				} else {
					throw new ServiceUnavailableException("The app providing the command bus ($app) is not enabled");
				}
			} else {
				$jobList = $c->get(IJobList::class);
				return new CronBus($jobList);
			}
		});
		$this->registerDeprecatedAlias('AsyncCommandBus', IBus::class);
		$this->registerAlias(ITrustedDomainHelper::class, TrustedDomainHelper::class);
		$this->registerAlias(IThrottler::class, Throttler::class);

		$this->registerService(\OC\Security\Bruteforce\Backend\IBackend::class, function ($c) {
			$config = $c->get(\OCP\IConfig::class);
			if (!$config->getSystemValueBool('auth.bruteforce.protection.force.database', false)
				&& ltrim($config->getSystemValueString('memcache.distributed', ''), '\\') === \OC\Memcache\Redis::class) {
				$backend = $c->get(\OC\Security\Bruteforce\Backend\MemoryCacheBackend::class);
			} else {
				$backend = $c->get(\OC\Security\Bruteforce\Backend\DatabaseBackend::class);
			}

			return $backend;
		});

		$this->registerDeprecatedAlias('IntegrityCodeChecker', Checker::class);
		$this->registerService(Checker::class, function (ContainerInterface $c) {
			// IConfig requires a working database. This code
			// might however be called when Nextcloud is not yet setup.
			if (\OC::$server->get(SystemConfig::class)->getValue('installed', false)) {
				$config = $c->get(\OCP\IConfig::class);
				$appConfig = $c->get(\OCP\IAppConfig::class);
			} else {
				$config = null;
				$appConfig = null;
			}

			return new Checker(
				$c->get(ServerVersion::class),
				$c->get(EnvironmentHelper::class),
				new FileAccessHelper(),
				new AppLocator(),
				$config,
				$appConfig,
				$c->get(ICacheFactory::class),
				$c->get(IAppManager::class),
				$c->get(IMimeTypeDetector::class)
			);
		});
		$this->registerService(\OCP\IRequest::class, function (ContainerInterface $c) {
			if (isset($this['urlParams'])) {
				$urlParams = $this['urlParams'];
			} else {
				$urlParams = [];
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
						: '',
					'urlParams' => $urlParams,
				],
				$this->get(IRequestId::class),
				$this->get(\OCP\IConfig::class),
				$this->get(CsrfTokenManager::class),
				$stream
			);
		});

		$this->registerService(IRequestId::class, function (ContainerInterface $c): IRequestId {
			return new RequestId(
				$_SERVER['UNIQUE_ID'] ?? '',
				$this->get(ISecureRandom::class)
			);
		});

		$this->registerService(IMailer::class, function (Server $c) {
			return new Mailer(
				$c->get(\OCP\IConfig::class),
				$c->get(LoggerInterface::class),
				$c->get(Defaults::class),
				$c->get(IURLGenerator::class),
				$c->getL10N('lib'),
				$c->get(IEventDispatcher::class),
				$c->get(IFactory::class)
			);
		});

		/** @since 30.0.0 */
		$this->registerAlias(\OCP\Mail\Provider\IManager::class, \OC\Mail\Provider\Manager::class);

		$this->registerService(ILDAPProviderFactory::class, function (ContainerInterface $c) {
			$config = $c->get(\OCP\IConfig::class);
			$factoryClass = $config->getSystemValue('ldapProviderFactory', null);
			if (is_null($factoryClass) || !class_exists($factoryClass)) {
				return new NullLDAPProviderFactory($this);
			}
			/** @var \OCP\LDAP\ILDAPProviderFactory $factory */
			return new $factoryClass($this);
		});
		$this->registerService(ILDAPProvider::class, function (ContainerInterface $c) {
			$factory = $c->get(ILDAPProviderFactory::class);
			return $factory->getLDAPProvider();
		});
		$this->registerService(ILockingProvider::class, function (ContainerInterface $c) {
			$ini = $c->get(IniGetWrapper::class);
			$config = $c->get(\OCP\IConfig::class);
			$ttl = $config->getSystemValueInt('filelocking.ttl', max(3600, $ini->getNumeric('max_execution_time')));
			if ($config->getSystemValueBool('filelocking.enabled', true) or (defined('PHPUNIT_RUN') && PHPUNIT_RUN)) {
				/** @var \OC\Memcache\Factory $memcacheFactory */
				$memcacheFactory = $c->get(ICacheFactory::class);
				$memcache = $memcacheFactory->createLocking('lock');
				if (!($memcache instanceof \OC\Memcache\NullCache)) {
					$timeFactory = $c->get(ITimeFactory::class);
					return new MemcacheLockingProvider($memcache, $timeFactory, $ttl);
				}
				return new DBLockingProvider(
					$c->get(IDBConnection::class),
					new TimeFactory(),
					$ttl,
					!\OC::$CLI
				);
			}
			return new NoopLockingProvider();
		});

		$this->registerService(ILockManager::class, function (Server $c): LockManager {
			return new LockManager();
		});

		$this->registerAlias(ILockdownManager::class, 'LockdownManager');
		$this->registerService(SetupManager::class, function ($c) {
			// create the setupmanager through the mount manager to resolve the cyclic dependency
			return $c->get(\OC\Files\Mount\Manager::class)->getSetupManager();
		});
		$this->registerAlias(IMountManager::class, \OC\Files\Mount\Manager::class);

		$this->registerService(IMimeTypeDetector::class, function (ContainerInterface $c) {
			return new \OC\Files\Type\Detection(
				$c->get(IURLGenerator::class),
				$c->get(LoggerInterface::class),
				\OC::$configDir,
				\OC::$SERVERROOT . '/resources/config/'
			);
		});

		$this->registerAlias(IMimeTypeLoader::class, Loader::class);
		$this->registerService(BundleFetcher::class, function () {
			return new BundleFetcher($this->getL10N('lib'));
		});
		$this->registerAlias(\OCP\Notification\IManager::class, Manager::class);

		$this->registerService(CapabilitiesManager::class, function (ContainerInterface $c) {
			$manager = new CapabilitiesManager($c->get(LoggerInterface::class));
			$manager->registerCapability(function () use ($c) {
				return new \OC\OCS\CoreCapabilities($c->get(\OCP\IConfig::class));
			});
			$manager->registerCapability(function () use ($c) {
				return $c->get(\OC\Security\Bruteforce\Capabilities::class);
			});
			return $manager;
		});

		$this->registerService(ICommentsManager::class, function (Server $c) {
			$config = $c->get(\OCP\IConfig::class);
			$factoryClass = $config->getSystemValue('comments.managerFactory', CommentsManagerFactory::class);
			/** @var \OCP\Comments\ICommentsManagerFactory $factory */
			$factory = new $factoryClass($this);
			$manager = $factory->getManager();

			$manager->registerDisplayNameResolver('user', function ($id) use ($c) {
				$manager = $c->get(IUserManager::class);
				$userDisplayName = $manager->getDisplayName($id);
				if ($userDisplayName === null) {
					$l = $c->get(IFactory::class)->get('core');
					return $l->t('Unknown account');
				}
				return $userDisplayName;
			});

			return $manager;
		});

		$this->registerAlias(\OC_Defaults::class, 'ThemingDefaults');
		$this->registerService('ThemingDefaults', function (Server $c) {
			try {
				$classExists = class_exists('OCA\Theming\ThemingDefaults');
			} catch (\OCP\AutoloadNotAllowedException $e) {
				// App disabled or in maintenance mode
				$classExists = false;
			}

			if ($classExists && $c->get(\OCP\IConfig::class)->getSystemValueBool('installed', false) && $c->get(IAppManager::class)->isEnabledForAnyone('theming') && $c->get(TrustedDomainHelper::class)->isTrustedDomain($c->getRequest()->getInsecureServerHost())) {
				$backgroundService = new BackgroundService(
					$c->get(IRootFolder::class),
					$c->getAppDataDir('theming'),
					$c->get(IAppConfig::class),
					$c->get(\OCP\IConfig::class),
					$c->get(ISession::class)->get('user_id'),
				);
				$imageManager = new ImageManager(
					$c->get(\OCP\IConfig::class),
					$c->getAppDataDir('theming'),
					$c->get(IURLGenerator::class),
					$c->get(ICacheFactory::class),
					$c->get(LoggerInterface::class),
					$c->get(ITempManager::class),
					$backgroundService,
				);
				return new ThemingDefaults(
					$c->get(\OCP\IConfig::class),
					$c->get(\OCP\IAppConfig::class),
					$c->getL10N('theming'),
					$c->get(IUserSession::class),
					$c->get(IURLGenerator::class),
					$c->get(ICacheFactory::class),
					new Util($c->get(ServerVersion::class), $c->get(\OCP\IConfig::class), $this->get(IAppManager::class), $c->getAppDataDir('theming'), $imageManager),
					$imageManager,
					$c->get(IAppManager::class),
					$c->get(INavigationManager::class),
					$backgroundService,
				);
			}
			return new \OC_Defaults();
		});
		$this->registerService(JSCombiner::class, function (Server $c) {
			return new JSCombiner(
				$c->getAppDataDir('js'),
				$c->get(IURLGenerator::class),
				$this->get(ICacheFactory::class),
				$c->get(SystemConfig::class),
				$c->get(LoggerInterface::class)
			);
		});
		$this->registerAlias(\OCP\EventDispatcher\IEventDispatcher::class, \OC\EventDispatcher\EventDispatcher::class);

		$this->registerService('CryptoWrapper', function (ContainerInterface $c) {
			// FIXME: Instantiated here due to cyclic dependency
			$request = new Request(
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
				],
				$c->get(IRequestId::class),
				$c->get(\OCP\IConfig::class)
			);

			return new CryptoWrapper(
				$c->get(ICrypto::class),
				$c->get(ISecureRandom::class),
				$request
			);
		});
		$this->registerService(SessionStorage::class, function (ContainerInterface $c) {
			return new SessionStorage($c->get(ISession::class));
		});
		$this->registerAlias(\OCP\Security\IContentSecurityPolicyManager::class, ContentSecurityPolicyManager::class);

		$this->registerService(IProviderFactory::class, function (ContainerInterface $c) {
			$config = $c->get(\OCP\IConfig::class);
			$factoryClass = $config->getSystemValue('sharing.managerFactory', ProviderFactory::class);
			/** @var \OCP\Share\IProviderFactory $factory */
			return $c->get($factoryClass);
		});

		$this->registerAlias(\OCP\Share\IManager::class, \OC\Share20\Manager::class);

		$this->registerService(\OCP\Collaboration\Collaborators\ISearch::class, function (Server $c) {
			$instance = new Collaboration\Collaborators\Search($c);

			// register default plugins
			$instance->registerPlugin(['shareType' => 'SHARE_TYPE_USER', 'class' => UserPlugin::class]);
			$instance->registerPlugin(['shareType' => 'SHARE_TYPE_GROUP', 'class' => GroupPlugin::class]);
			$instance->registerPlugin(['shareType' => 'SHARE_TYPE_EMAIL', 'class' => MailPlugin::class]);
			$instance->registerPlugin(['shareType' => 'SHARE_TYPE_REMOTE', 'class' => RemotePlugin::class]);
			$instance->registerPlugin(['shareType' => 'SHARE_TYPE_REMOTE_GROUP', 'class' => RemoteGroupPlugin::class]);

			return $instance;
		});
		$this->registerAlias(\OCP\Collaboration\Collaborators\ISearchResult::class, \OC\Collaboration\Collaborators\SearchResult::class);

		$this->registerAlias(\OCP\Collaboration\AutoComplete\IManager::class, \OC\Collaboration\AutoComplete\Manager::class);

		$this->registerAlias(\OCP\Collaboration\Resources\IProviderManager::class, \OC\Collaboration\Resources\ProviderManager::class);
		$this->registerAlias(\OCP\Collaboration\Resources\IManager::class, \OC\Collaboration\Resources\Manager::class);

		$this->registerAlias(IReferenceManager::class, ReferenceManager::class);
		$this->registerAlias(ITeamManager::class, TeamManager::class);

		$this->registerDeprecatedAlias('SettingsManager', \OC\Settings\Manager::class);
		$this->registerAlias(\OCP\Settings\IManager::class, \OC\Settings\Manager::class);
		$this->registerService(\OC\Files\AppData\Factory::class, function (ContainerInterface $c) {
			return new \OC\Files\AppData\Factory(
				$c->get(IRootFolder::class),
				$c->get(SystemConfig::class)
			);
		});

		$this->registerService('LockdownManager', function (ContainerInterface $c) {
			return new LockdownManager(function () use ($c) {
				return $c->get(ISession::class);
			});
		});

		$this->registerService(\OCP\OCS\IDiscoveryService::class, function (ContainerInterface $c) {
			return new DiscoveryService(
				$c->get(ICacheFactory::class),
				$c->get(IClientService::class)
			);
		});
		$this->registerAlias(IOCMDiscoveryService::class, OCMDiscoveryService::class);

		$this->registerService(ICloudIdManager::class, function (ContainerInterface $c) {
			return new CloudIdManager(
				$c->get(\OCP\Contacts\IManager::class),
				$c->get(IURLGenerator::class),
				$c->get(IUserManager::class),
				$c->get(ICacheFactory::class),
				$c->get(IEventDispatcher::class),
			);
		});

		$this->registerAlias(\OCP\GlobalScale\IConfig::class, \OC\GlobalScale\Config::class);
		$this->registerAlias(ICloudFederationProviderManager::class, CloudFederationProviderManager::class);
		$this->registerService(ICloudFederationFactory::class, function (Server $c) {
			return new CloudFederationFactory();
		});

		$this->registerAlias(\OCP\AppFramework\Utility\IControllerMethodReflector::class, \OC\AppFramework\Utility\ControllerMethodReflector::class);

		$this->registerAlias(\OCP\AppFramework\Utility\ITimeFactory::class, \OC\AppFramework\Utility\TimeFactory::class);
		$this->registerAlias(\Psr\Clock\ClockInterface::class, \OCP\AppFramework\Utility\ITimeFactory::class);

		$this->registerService(Defaults::class, function (Server $c) {
			return new Defaults(
				$c->get('ThemingDefaults')
			);
		});

		$this->registerService(\OCP\ISession::class, function (ContainerInterface $c) {
			return $c->get(\OCP\IUserSession::class)->getSession();
		}, false);

		$this->registerService(IShareHelper::class, function (ContainerInterface $c) {
			return new ShareHelper(
				$c->get(\OCP\Share\IManager::class)
			);
		});

		$this->registerService(Installer::class, function (ContainerInterface $c) {
			return new Installer(
				$c->get(AppFetcher::class),
				$c->get(IClientService::class),
				$c->get(ITempManager::class),
				$c->get(LoggerInterface::class),
				$c->get(\OCP\IConfig::class),
				\OC::$CLI
			);
		});

		$this->registerService(IApiFactory::class, function (ContainerInterface $c) {
			return new ApiFactory($c->get(IClientService::class));
		});

		$this->registerService(IInstanceFactory::class, function (ContainerInterface $c) {
			$memcacheFactory = $c->get(ICacheFactory::class);
			return new InstanceFactory($memcacheFactory->createLocal('remoteinstance.'), $c->get(IClientService::class));
		});

		$this->registerAlias(IContactsStore::class, ContactsStore::class);
		$this->registerAlias(IAccountManager::class, AccountManager::class);

		$this->registerAlias(IStorageFactory::class, StorageFactory::class);

		$this->registerAlias(\OCP\Dashboard\IManager::class, \OC\Dashboard\Manager::class);

		$this->registerAlias(IFullTextSearchManager::class, FullTextSearchManager::class);
		$this->registerAlias(IFilesMetadataManager::class, FilesMetadataManager::class);

		$this->registerAlias(ISubAdmin::class, SubAdmin::class);

		$this->registerAlias(IInitialStateService::class, InitialStateService::class);

		$this->registerAlias(\OCP\IEmojiHelper::class, \OC\EmojiHelper::class);

		$this->registerAlias(\OCP\UserStatus\IManager::class, \OC\UserStatus\Manager::class);

		$this->registerAlias(IBroker::class, Broker::class);

		$this->registerAlias(\OCP\Files\AppData\IAppDataFactory::class, \OC\Files\AppData\Factory::class);

		$this->registerAlias(\OCP\Files\IFilenameValidator::class, \OC\Files\FilenameValidator::class);

		$this->registerAlias(IBinaryFinder::class, BinaryFinder::class);

		$this->registerAlias(\OCP\Share\IPublicShareTemplateFactory::class, \OC\Share20\PublicShareTemplateFactory::class);

		$this->registerAlias(ITranslationManager::class, TranslationManager::class);

		$this->registerAlias(IConversionManager::class, ConversionManager::class);

		$this->registerAlias(ISpeechToTextManager::class, SpeechToTextManager::class);

		$this->registerAlias(IEventSourceFactory::class, EventSourceFactory::class);

		$this->registerAlias(\OCP\TextProcessing\IManager::class, \OC\TextProcessing\Manager::class);

		$this->registerAlias(\OCP\TextToImage\IManager::class, \OC\TextToImage\Manager::class);

		$this->registerAlias(ILimiter::class, Limiter::class);

		$this->registerAlias(IPhoneNumberUtil::class, PhoneNumberUtil::class);

		$this->registerAlias(ICapabilityAwareOCMProvider::class, OCMProvider::class);
		$this->registerDeprecatedAlias(IOCMProvider::class, OCMProvider::class);

		$this->registerAlias(ISetupCheckManager::class, SetupCheckManager::class);

		$this->registerAlias(IProfileManager::class, ProfileManager::class);

		$this->registerAlias(IAvailabilityCoordinator::class, AvailabilityCoordinator::class);

		$this->registerAlias(IDeclarativeManager::class, DeclarativeManager::class);

		$this->registerAlias(\OCP\TaskProcessing\IManager::class, \OC\TaskProcessing\Manager::class);

		$this->registerAlias(IRemoteAddress::class, RemoteAddress::class);

		$this->registerAlias(\OCP\Security\Ip\IFactory::class, \OC\Security\Ip\Factory::class);

		$this->registerAlias(IRichTextFormatter::class, \OC\RichObjectStrings\RichTextFormatter::class);

		$this->registerAlias(ISignatureManager::class, SignatureManager::class);

		$this->connectDispatcher();
	}

	public function boot() {
		/** @var HookConnector $hookConnector */
		$hookConnector = $this->get(HookConnector::class);
		$hookConnector->viewToNode();
	}

	private function connectDispatcher(): void {
		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $this->get(IEventDispatcher::class);
		$eventDispatcher->addServiceListener(LoginFailed::class, LoginFailedListener::class);
		$eventDispatcher->addServiceListener(PostLoginEvent::class, UserLoggedInListener::class);
		$eventDispatcher->addServiceListener(UserChangedEvent::class, UserChangedListener::class);
		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, BeforeUserDeletedListener::class);

		FilesMetadataManager::loadListeners($eventDispatcher);
		GenerateBlurhashMetadata::loadListeners($eventDispatcher);
	}

	/**
	 * @return \OCP\Contacts\IManager
	 * @deprecated 20.0.0
	 */
	public function getContactsManager() {
		return $this->get(\OCP\Contacts\IManager::class);
	}

	/**
	 * @return \OC\Encryption\Manager
	 * @deprecated 20.0.0
	 */
	public function getEncryptionManager() {
		return $this->get(\OCP\Encryption\IManager::class);
	}

	/**
	 * @return \OC\Encryption\File
	 * @deprecated 20.0.0
	 */
	public function getEncryptionFilesHelper() {
		return $this->get(IFile::class);
	}

	/**
	 * The current request object holding all information about the request
	 * currently being processed is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest
	 * @deprecated 20.0.0
	 */
	public function getRequest() {
		return $this->get(IRequest::class);
	}

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return IRootFolder
	 * @deprecated 20.0.0
	 */
	public function getRootFolder() {
		return $this->get(IRootFolder::class);
	}

	/**
	 * Returns the root folder of ownCloud's data directory
	 * This is the lazy variant so this gets only initialized once it
	 * is actually used.
	 *
	 * @return IRootFolder
	 * @deprecated 20.0.0
	 */
	public function getLazyRootFolder() {
		return $this->get(IRootFolder::class);
	}

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder|null
	 * @deprecated 20.0.0
	 */
	public function getUserFolder($userId = null) {
		if ($userId === null) {
			$user = $this->get(IUserSession::class)->getUser();
			if (!$user) {
				return null;
			}
			$userId = $user->getUID();
		}
		$root = $this->get(IRootFolder::class);
		return $root->getUserFolder($userId);
	}

	/**
	 * @return \OC\User\Manager
	 * @deprecated 20.0.0
	 */
	public function getUserManager() {
		return $this->get(IUserManager::class);
	}

	/**
	 * @return \OC\Group\Manager
	 * @deprecated 20.0.0
	 */
	public function getGroupManager() {
		return $this->get(IGroupManager::class);
	}

	/**
	 * @return \OC\User\Session
	 * @deprecated 20.0.0
	 */
	public function getUserSession() {
		return $this->get(IUserSession::class);
	}

	/**
	 * @return \OCP\ISession
	 * @deprecated 20.0.0
	 */
	public function getSession() {
		return $this->get(Session::class)->getSession();
	}

	/**
	 * @param \OCP\ISession $session
	 * @return void
	 */
	public function setSession(\OCP\ISession $session) {
		$this->get(SessionStorage::class)->setSession($session);
		$this->get(Session::class)->setSession($session);
		$this->get(Store::class)->setSession($session);
	}

	/**
	 * @return \OCP\IConfig
	 * @deprecated 20.0.0
	 */
	public function getConfig() {
		return $this->get(AllConfig::class);
	}

	/**
	 * @return \OC\SystemConfig
	 * @deprecated 20.0.0
	 */
	public function getSystemConfig() {
		return $this->get(SystemConfig::class);
	}

	/**
	 * @return IFactory
	 * @deprecated 20.0.0
	 */
	public function getL10NFactory() {
		return $this->get(IFactory::class);
	}

	/**
	 * get an L10N instance
	 *
	 * @param string $app appid
	 * @param string $lang
	 * @return IL10N
	 * @deprecated 20.0.0 use DI of {@see IL10N} or {@see IFactory} instead, or {@see \OCP\Util::getL10N()} as a last resort
	 */
	public function getL10N($app, $lang = null) {
		return $this->get(IFactory::class)->get($app, $lang);
	}

	/**
	 * @return IURLGenerator
	 * @deprecated 20.0.0
	 */
	public function getURLGenerator() {
		return $this->get(IURLGenerator::class);
	}

	/**
	 * Returns an ICache instance. Since 8.1.0 it returns a fake cache. Use
	 * getMemCacheFactory() instead.
	 *
	 * @return ICache
	 * @deprecated 8.1.0 use getMemCacheFactory to obtain a proper cache
	 */
	public function getCache() {
		return $this->get(ICache::class);
	}

	/**
	 * Returns an \OCP\CacheFactory instance
	 *
	 * @return \OCP\ICacheFactory
	 * @deprecated 20.0.0
	 */
	public function getMemCacheFactory() {
		return $this->get(ICacheFactory::class);
	}

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 * @deprecated 20.0.0
	 */
	public function getDatabaseConnection() {
		return $this->get(IDBConnection::class);
	}

	/**
	 * Returns the activity manager
	 *
	 * @return \OCP\Activity\IManager
	 * @deprecated 20.0.0
	 */
	public function getActivityManager() {
		return $this->get(\OCP\Activity\IManager::class);
	}

	/**
	 * Returns an job list for controlling background jobs
	 *
	 * @return IJobList
	 * @deprecated 20.0.0
	 */
	public function getJobList() {
		return $this->get(IJobList::class);
	}

	/**
	 * Returns a SecureRandom instance
	 *
	 * @return \OCP\Security\ISecureRandom
	 * @deprecated 20.0.0
	 */
	public function getSecureRandom() {
		return $this->get(ISecureRandom::class);
	}

	/**
	 * Returns a Crypto instance
	 *
	 * @return ICrypto
	 * @deprecated 20.0.0
	 */
	public function getCrypto() {
		return $this->get(ICrypto::class);
	}

	/**
	 * Returns a Hasher instance
	 *
	 * @return IHasher
	 * @deprecated 20.0.0
	 */
	public function getHasher() {
		return $this->get(IHasher::class);
	}

	/**
	 * Get the certificate manager
	 *
	 * @return \OCP\ICertificateManager
	 */
	public function getCertificateManager() {
		return $this->get(ICertificateManager::class);
	}

	/**
	 * Get the manager for temporary files and folders
	 *
	 * @return \OCP\ITempManager
	 * @deprecated 20.0.0
	 */
	public function getTempManager() {
		return $this->get(ITempManager::class);
	}

	/**
	 * Get the app manager
	 *
	 * @return \OCP\App\IAppManager
	 * @deprecated 20.0.0
	 */
	public function getAppManager() {
		return $this->get(IAppManager::class);
	}

	/**
	 * Creates a new mailer
	 *
	 * @return IMailer
	 * @deprecated 20.0.0
	 */
	public function getMailer() {
		return $this->get(IMailer::class);
	}

	/**
	 * Get the webroot
	 *
	 * @return string
	 * @deprecated 20.0.0
	 */
	public function getWebRoot() {
		return $this->webRoot;
	}

	/**
	 * Get the locking provider
	 *
	 * @return ILockingProvider
	 * @since 8.1.0
	 * @deprecated 20.0.0
	 */
	public function getLockingProvider() {
		return $this->get(ILockingProvider::class);
	}

	/**
	 * Get the MimeTypeDetector
	 *
	 * @return IMimeTypeDetector
	 * @deprecated 20.0.0
	 */
	public function getMimeTypeDetector() {
		return $this->get(IMimeTypeDetector::class);
	}

	/**
	 * Get the MimeTypeLoader
	 *
	 * @return IMimeTypeLoader
	 * @deprecated 20.0.0
	 */
	public function getMimeTypeLoader() {
		return $this->get(IMimeTypeLoader::class);
	}

	/**
	 * Get the Notification Manager
	 *
	 * @return \OCP\Notification\IManager
	 * @since 8.2.0
	 * @deprecated 20.0.0
	 */
	public function getNotificationManager() {
		return $this->get(\OCP\Notification\IManager::class);
	}

	/**
	 * @return \OCA\Theming\ThemingDefaults
	 * @deprecated 20.0.0
	 */
	public function getThemingDefaults() {
		return $this->get('ThemingDefaults');
	}

	/**
	 * @return \OC\IntegrityCheck\Checker
	 * @deprecated 20.0.0
	 */
	public function getIntegrityCodeChecker() {
		return $this->get('IntegrityCodeChecker');
	}

	/**
	 * @return CsrfTokenManager
	 * @deprecated 20.0.0
	 */
	public function getCsrfTokenManager() {
		return $this->get(CsrfTokenManager::class);
	}

	/**
	 * @return ContentSecurityPolicyNonceManager
	 * @deprecated 20.0.0
	 */
	public function getContentSecurityPolicyNonceManager() {
		return $this->get(ContentSecurityPolicyNonceManager::class);
	}

	/**
	 * @return \OCP\Settings\IManager
	 * @deprecated 20.0.0
	 */
	public function getSettingsManager() {
		return $this->get(\OC\Settings\Manager::class);
	}

	/**
	 * @return \OCP\Files\IAppData
	 * @deprecated 20.0.0 Use get(\OCP\Files\AppData\IAppDataFactory::class)->get($app) instead
	 */
	public function getAppDataDir($app) {
		/** @var \OC\Files\AppData\Factory $factory */
		$factory = $this->get(\OC\Files\AppData\Factory::class);
		return $factory->get($app);
	}

	/**
	 * @return \OCP\Federation\ICloudIdManager
	 * @deprecated 20.0.0
	 */
	public function getCloudIdManager() {
		return $this->get(ICloudIdManager::class);
	}

	private function registerDeprecatedAlias(string $alias, string $target) {
		$this->registerService($alias, function (ContainerInterface $container) use ($target, $alias) {
			try {
				/** @var LoggerInterface $logger */
				$logger = $container->get(LoggerInterface::class);
				$logger->debug('The requested alias "' . $alias . '" is deprecated. Please request "' . $target . '" directly. This alias will be removed in a future Nextcloud version.', ['app' => 'serverDI']);
			} catch (ContainerExceptionInterface $e) {
				// Could not get logger. Continue
			}

			return $container->get($target);
		}, false);
	}
}
