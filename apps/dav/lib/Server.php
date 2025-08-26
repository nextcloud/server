<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV;

use OC\Files\Filesystem;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\BulkUpload\BulkUploadPlugin;
use OCA\DAV\CalDAV\BirthdayCalendar\EnablePlugin;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\CalDAV\EventComparisonService;
use OCA\DAV\CalDAV\ICSExportPlugin\ICSExportPlugin;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCA\DAV\CalDAV\Security\RateLimitingPlugin;
use OCA\DAV\CalDAV\Validation\CalDavValidatePlugin;
use OCA\DAV\CardDAV\HasPhotoPlugin;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\MultiGetExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\CardDAV\Security\CardDavRateLimitingPlugin;
use OCA\DAV\CardDAV\Validation\CardDavValidatePlugin;
use OCA\DAV\Comments\CommentsPlugin;
use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use OCA\DAV\Connector\Sabre\AppleQuirksPlugin;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\ChecksumUpdatePlugin;
use OCA\DAV\Connector\Sabre\CommentPropertiesPlugin;
use OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCA\DAV\Connector\Sabre\DummyGetResponsePlugin;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\FakeLockerPlugin;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\FilesReportPlugin;
use OCA\DAV\Connector\Sabre\LockPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\PropfindCompressionPlugin;
use OCA\DAV\Connector\Sabre\PropFindMonitorPlugin;
use OCA\DAV\Connector\Sabre\PropFindPreloadNotifyPlugin;
use OCA\DAV\Connector\Sabre\QuotaPlugin;
use OCA\DAV\Connector\Sabre\RequestIdHeaderPlugin;
use OCA\DAV\Connector\Sabre\SharesPlugin;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCA\DAV\Connector\Sabre\ZipFolderPlugin;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCA\DAV\DAV\PublicAuth;
use OCA\DAV\DAV\ViewOnlyPlugin;
use OCA\DAV\Db\PropertyMapper;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use OCA\DAV\Files\FileSearchBackend;
use OCA\DAV\Files\LazySearchBackend;
use OCA\DAV\Paginate\PaginatePlugin;
use OCA\DAV\Profiler\ProfilerPlugin;
use OCA\DAV\Provisioning\Apple\AppleProvisioningPlugin;
use OCA\DAV\SystemTag\SystemTagPlugin;
use OCA\DAV\Upload\ChunkingPlugin;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCA\DAV\Upload\UploadAutoMkcolPlugin;
use OCA\Theming\ThemingDefaults;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Comments\ICommentsManager;
use OCP\Defaults;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IFilenameValidator;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\ITagManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Profiler\IProfiler;
use OCP\SabrePluginEvent;
use OCP\Security\Bruteforce\IThrottler;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use Sabre\CardDAV\VCFExportPlugin;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\UUIDUtil;
use SearchDAV\DAV\SearchPlugin;

class Server {
	public Connector\Sabre\Server $server;
	private IProfiler $profiler;

	public function __construct(
		private IRequest $request,
		private string $baseUri,
	) {
		$debugEnabled = \OCP\Server::get(IConfig::class)->getSystemValue('debug', false);
		$this->profiler = \OCP\Server::get(IProfiler::class);
		if ($this->profiler->isEnabled()) {
			/** @var IEventLogger $eventLogger */
			$eventLogger = \OCP\Server::get(IEventLogger::class);
			$eventLogger->start('runtime', 'DAV Runtime');
		}

		$logger = \OCP\Server::get(LoggerInterface::class);
		$eventDispatcher = \OCP\Server::get(IEventDispatcher::class);

		$root = new RootCollection();
		$this->server = new \OCA\DAV\Connector\Sabre\Server(new CachingTree($root));
		$this->server->setLogger($logger);

		// Add maintenance plugin
		$this->server->addPlugin(new MaintenancePlugin(\OCP\Server::get(IConfig::class), \OC::$server->getL10N('dav')));

		$this->server->addPlugin(new AppleQuirksPlugin());

		// Backends
		$authBackend = new Auth(
			\OCP\Server::get(ISession::class),
			\OCP\Server::get(IUserSession::class),
			\OCP\Server::get(IRequest::class),
			\OCP\Server::get(\OC\Authentication\TwoFactorAuth\Manager::class),
			\OCP\Server::get(IThrottler::class)
		);

		// Set URL explicitly due to reverse-proxy situations
		$this->server->httpRequest->setUrl($this->request->getRequestUri());
		$this->server->setBaseUri($this->baseUri);

		$this->server->addPlugin(new ProfilerPlugin($this->request));
		$this->server->addPlugin(new BlockLegacyClientPlugin(
			\OCP\Server::get(IConfig::class),
			\OCP\Server::get(ThemingDefaults::class),
		));
		$this->server->addPlugin(new AnonymousOptionsPlugin());
		$authPlugin = new Plugin();
		$authPlugin->addBackend(new PublicAuth());
		$this->server->addPlugin($authPlugin);

		// allow setup of additional auth backends
		$event = new SabrePluginEvent($this->server);
		$eventDispatcher->dispatch('OCA\DAV\Connector\Sabre::authInit', $event);

		$newAuthEvent = new SabrePluginAuthInitEvent($this->server);
		$eventDispatcher->dispatchTyped($newAuthEvent);

		$bearerAuthBackend = new BearerAuth(
			\OCP\Server::get(IUserSession::class),
			\OCP\Server::get(ISession::class),
			\OCP\Server::get(IRequest::class),
			\OCP\Server::get(IConfig::class),
		);
		$authPlugin->addBackend($bearerAuthBackend);
		// because we are throwing exceptions this plugin has to be the last one
		$authPlugin->addBackend($authBackend);

		// debugging
		if ($debugEnabled) {
			$this->server->debugEnabled = true;
			$this->server->addPlugin(new PropFindMonitorPlugin());
			$this->server->addPlugin(new \Sabre\DAV\Browser\Plugin());
		} else {
			$this->server->addPlugin(new DummyGetResponsePlugin());
		}

		$this->server->addPlugin(new ExceptionLoggerPlugin('webdav', $logger));
		$this->server->addPlugin(new LockPlugin());
		$this->server->addPlugin(new \Sabre\DAV\Sync\Plugin());

		// acl
		$acl = new DavAclPlugin();
		$acl->principalCollectionSet = [
			'principals/users',
			'principals/groups',
			'principals/calendar-resources',
			'principals/calendar-rooms',
		];
		$this->server->addPlugin($acl);

		// calendar plugins
		if ($this->requestIsForSubtree(['calendars', 'public-calendars', 'system-calendars', 'principals'])) {
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OCP\Server::get(IRequest::class), \OCP\Server::get(IConfig::class)));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\Plugin());
			$this->server->addPlugin(new ICSExportPlugin(\OCP\Server::get(IConfig::class), $logger));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\Schedule\Plugin(\OCP\Server::get(IConfig::class), \OCP\Server::get(LoggerInterface::class), \OCP\Server::get(DefaultCalendarValidator::class)));

			$this->server->addPlugin(\OCP\Server::get(\OCA\DAV\CalDAV\Trashbin\Plugin::class));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\WebcalCaching\Plugin($this->request));
			if (\OCP\Server::get(IConfig::class)->getAppValue('dav', 'allow_calendar_link_subscriptions', 'yes') === 'yes') {
				$this->server->addPlugin(new \Sabre\CalDAV\Subscriptions\Plugin());
			}

			$this->server->addPlugin(new \Sabre\CalDAV\Notifications\Plugin());
			$this->server->addPlugin(new PublishPlugin(
				\OCP\Server::get(IConfig::class),
				\OCP\Server::get(IURLGenerator::class)
			));

			$this->server->addPlugin(\OCP\Server::get(RateLimitingPlugin::class));
			$this->server->addPlugin(\OCP\Server::get(CalDavValidatePlugin::class));
		}

		// addressbook plugins
		if ($this->requestIsForSubtree(['addressbooks', 'principals'])) {
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OCP\Server::get(IRequest::class), \OCP\Server::get(IConfig::class)));
			$this->server->addPlugin(new \OCA\DAV\CardDAV\Plugin());
			$this->server->addPlugin(new VCFExportPlugin());
			$this->server->addPlugin(new MultiGetExportPlugin());
			$this->server->addPlugin(new HasPhotoPlugin());
			$this->server->addPlugin(new ImageExportPlugin(\OCP\Server::get(PhotoCache::class)));

			$this->server->addPlugin(\OCP\Server::get(CardDavRateLimitingPlugin::class));
			$this->server->addPlugin(\OCP\Server::get(CardDavValidatePlugin::class));
		}

		// system tags plugins
		$this->server->addPlugin(\OCP\Server::get(SystemTagPlugin::class));

		// comments plugin
		$this->server->addPlugin(new CommentsPlugin(
			\OCP\Server::get(ICommentsManager::class),
			\OCP\Server::get(IUserSession::class)
		));

		// performance improvement plugins
		$this->server->addPlugin(new CopyEtagHeaderPlugin());
		$this->server->addPlugin(new RequestIdHeaderPlugin(\OCP\Server::get(IRequest::class)));
		$this->server->addPlugin(new UploadAutoMkcolPlugin());
		$this->server->addPlugin(new ChunkingV2Plugin(\OCP\Server::get(ICacheFactory::class)));
		$this->server->addPlugin(new ChunkingPlugin());
		$this->server->addPlugin(new ZipFolderPlugin(
			$this->server->tree,
			$logger,
			$eventDispatcher,
			\OCP\Server::get(IDateTimeZone::class),
		));
		$this->server->addPlugin(\OCP\Server::get(PaginatePlugin::class));
		$this->server->addPlugin(new PropFindPreloadNotifyPlugin());

		// allow setup of additional plugins
		$eventDispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);
		$typedEvent = new SabrePluginAddEvent($this->server);
		$eventDispatcher->dispatchTyped($typedEvent);

		// Some WebDAV clients do require Class 2 WebDAV support (locking), since
		// we do not provide locking we emulate it using a fake locking plugin.
		if ($this->request->isUserAgent([
			'/WebDAVFS/',
			'/OneNote/',
			'/^Microsoft-WebDAV/',// Microsoft-WebDAV-MiniRedir/6.1.7601
		])) {
			$this->server->addPlugin(new FakeLockerPlugin());
		}

		if (BrowserErrorPagePlugin::isBrowserRequest($request)) {
			$this->server->addPlugin(new BrowserErrorPagePlugin());
		}

		$lazySearchBackend = new LazySearchBackend();
		$this->server->addPlugin(new SearchPlugin($lazySearchBackend));

		// wait with registering these until auth is handled and the filesystem is setup
		$this->server->on('beforeMethod:*', function () use ($root, $lazySearchBackend, $logger): void {
			// Allow view-only plugin for webdav requests
			$this->server->addPlugin(new ViewOnlyPlugin(
				\OC::$server->getUserFolder(),
			));

			// custom properties plugin must be the last one
			$userSession = \OCP\Server::get(IUserSession::class);
			$user = $userSession->getUser();
			if ($user !== null) {
				$view = Filesystem::getView();
				$config = \OCP\Server::get(IConfig::class);
				$this->server->addPlugin(
					new FilesPlugin(
						$this->server->tree,
						$config,
						$this->request,
						\OCP\Server::get(IPreview::class),
						\OCP\Server::get(IUserSession::class),
						\OCP\Server::get(IFilenameValidator::class),
						\OCP\Server::get(IAccountManager::class),
						false,
						$config->getSystemValueBool('debug', false) === false,
					)
				);
				$this->server->addPlugin(new ChecksumUpdatePlugin());

				$this->server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new CustomPropertiesBackend(
							$this->server,
							$this->server->tree,
							\OCP\Server::get(IDBConnection::class),
							\OCP\Server::get(IUserSession::class)->getUser(),
							\OCP\Server::get(PropertyMapper::class),
							\OCP\Server::get(DefaultCalendarValidator::class),
						)
					)
				);
				if ($view !== null) {
					$this->server->addPlugin(
						new QuotaPlugin($view));
				}
				$this->server->addPlugin(
					new TagsPlugin(
						$this->server->tree, \OCP\Server::get(ITagManager::class), \OCP\Server::get(IEventDispatcher::class), \OCP\Server::get(IUserSession::class)
					)
				);

				// TODO: switch to LazyUserFolder
				$userFolder = \OC::$server->getUserFolder();
				$shareManager = \OCP\Server::get(\OCP\Share\IManager::class);
				$this->server->addPlugin(new SharesPlugin(
					$this->server->tree,
					$userSession,
					$userFolder,
					$shareManager,
				));
				$this->server->addPlugin(new CommentPropertiesPlugin(
					\OCP\Server::get(ICommentsManager::class),
					$userSession
				));
				if (\OCP\Server::get(IConfig::class)->getAppValue('dav', 'sendInvitations', 'yes') === 'yes') {
					$this->server->addPlugin(new IMipPlugin(
						\OCP\Server::get(IAppConfig::class),
						\OCP\Server::get(IMailer::class),
						\OCP\Server::get(LoggerInterface::class),
						\OCP\Server::get(ITimeFactory::class),
						\OCP\Server::get(Defaults::class),
						$userSession,
						\OCP\Server::get(IMipService::class),
						\OCP\Server::get(EventComparisonService::class),
						\OCP\Server::get(\OCP\Mail\Provider\IManager::class)
					));
				}
				$this->server->addPlugin(new \OCA\DAV\CalDAV\Search\SearchPlugin());
				if ($view !== null) {
					$this->server->addPlugin(new FilesReportPlugin(
						$this->server->tree,
						$view,
						\OCP\Server::get(ISystemTagManager::class),
						\OCP\Server::get(ISystemTagObjectMapper::class),
						\OCP\Server::get(ITagManager::class),
						$userSession,
						\OCP\Server::get(IGroupManager::class),
						$userFolder,
						\OCP\Server::get(IAppManager::class)
					));
					$lazySearchBackend->setBackend(new FileSearchBackend(
						$this->server,
						$this->server->tree,
						$user,
						\OCP\Server::get(IRootFolder::class),
						$shareManager,
						$view,
						\OCP\Server::get(IFilesMetadataManager::class)
					));
					$this->server->addPlugin(
						new BulkUploadPlugin(
							$userFolder,
							$logger
						)
					);
				}
				$this->server->addPlugin(new EnablePlugin(
					\OCP\Server::get(IConfig::class),
					\OCP\Server::get(BirthdayService::class),
					$user
				));
				$this->server->addPlugin(new AppleProvisioningPlugin(
					\OCP\Server::get(IUserSession::class),
					\OCP\Server::get(IURLGenerator::class),
					\OCP\Server::get(ThemingDefaults::class),
					\OCP\Server::get(IRequest::class),
					\OC::$server->getL10N('dav'),
					function () {
						return UUIDUtil::getUUID();
					}
				));
			}

			// register plugins from apps
			$pluginManager = new PluginManager(
				\OC::$server,
				\OCP\Server::get(IAppManager::class)
			);
			foreach ($pluginManager->getAppPlugins() as $appPlugin) {
				$this->server->addPlugin($appPlugin);
			}
			foreach ($pluginManager->getAppCollections() as $appCollection) {
				$root->addChild($appCollection);
			}
		});

		$this->server->addPlugin(
			new PropfindCompressionPlugin()
		);
	}

	public function exec() {
		/** @var IEventLogger $eventLogger */
		$eventLogger = \OCP\Server::get(IEventLogger::class);
		$eventLogger->start('dav_server_exec', '');
		$this->server->start();
		$eventLogger->end('dav_server_exec');
		if ($this->profiler->isEnabled()) {
			$eventLogger->end('runtime');
			$profile = $this->profiler->collect(\OCP\Server::get(IRequest::class), new Response());
			$this->profiler->saveProfile($profile);
		}
	}

	private function requestIsForSubtree(array $subTrees): bool {
		foreach ($subTrees as $subTree) {
			$subTree = trim($subTree, ' /');
			if (str_starts_with($this->server->getRequestUri(), $subTree . '/')) {
				return true;
			}
		}
		return false;
	}

}
