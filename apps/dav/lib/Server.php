<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2022 Informatyka Boguslawski sp. z o.o. sp.k., http://www.ib.pl/
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Brandon Kirsch <brandonkirsch@github.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\DAV;

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\BulkUpload\BulkUploadPlugin;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CardDAV\HasPhotoPlugin;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\MultiGetExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\Comments\CommentsPlugin;
use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\ChecksumUpdatePlugin;
use OCA\DAV\Connector\Sabre\CommentPropertiesPlugin;
use OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCA\DAV\Connector\Sabre\DummyGetResponsePlugin;
use OCA\DAV\Connector\Sabre\FakeLockerPlugin;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\FilesReportPlugin;
use OCA\DAV\Connector\Sabre\PropfindCompressionPlugin;
use OCA\DAV\Connector\Sabre\QuotaPlugin;
use OCA\DAV\Connector\Sabre\RequestIdHeaderPlugin;
use OCA\DAV\Connector\Sabre\SharesPlugin;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCA\DAV\DAV\PublicAuth;
use OCA\DAV\DAV\ViewOnlyPlugin;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use OCA\DAV\Files\LazySearchBackend;
use OCA\DAV\Profiler\ProfilerPlugin;
use OCA\DAV\Provisioning\Apple\AppleProvisioningPlugin;
use OCA\DAV\SystemTag\SystemTagPlugin;
use OCA\DAV\Upload\ChunkingPlugin;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\Profiler\IProfiler;
use OCP\SabrePluginEvent;
use Psr\Log\LoggerInterface;
use Sabre\CardDAV\VCFExportPlugin;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\UUIDUtil;
use SearchDAV\DAV\SearchPlugin;

class Server {
	private IRequest $request;
	private string $baseUri;
	public Connector\Sabre\Server $server;
	private IProfiler $profiler;

	public function __construct(IRequest $request, string $baseUri) {
		$this->profiler = \OC::$server->get(IProfiler::class);
		if ($this->profiler->isEnabled()) {
			/** @var IEventLogger $eventLogger */
			$eventLogger = \OC::$server->get(IEventLogger::class);
			$eventLogger->start('runtime', 'DAV Runtime');
		}

		$this->request = $request;
		$this->baseUri = $baseUri;
		$logger = \OC::$server->get(LoggerInterface::class);
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = \OC::$server->get(IEventDispatcher::class);

		$root = new RootCollection();
		$this->server = new \OCA\DAV\Connector\Sabre\Server(new CachingTree($root));

		// Add maintenance plugin
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\MaintenancePlugin(\OC::$server->getConfig(), \OC::$server->getL10N('dav')));

		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\AppleQuirksPlugin());

		// Backends
		$authBackend = new Auth(
			\OC::$server->getSession(),
			\OC::$server->getUserSession(),
			\OC::$server->getRequest(),
			\OC::$server->getTwoFactorAuthManager(),
			\OC::$server->getBruteForceThrottler()
		);

		// Set URL explicitly due to reverse-proxy situations
		$this->server->httpRequest->setUrl($this->request->getRequestUri());
		$this->server->setBaseUri($this->baseUri);

		$this->server->addPlugin(new ProfilerPlugin($this->request));
		$this->server->addPlugin(new BlockLegacyClientPlugin(\OC::$server->getConfig()));
		$this->server->addPlugin(new AnonymousOptionsPlugin());
		$authPlugin = new Plugin();
		$authPlugin->addBackend(new PublicAuth());
		$this->server->addPlugin($authPlugin);

		// allow setup of additional auth backends
		$event = new SabrePluginEvent($this->server);
		$dispatcher->dispatch('OCA\DAV\Connector\Sabre::authInit', $event);

		$newAuthEvent = new SabrePluginAuthInitEvent($this->server);
		$dispatcher->dispatchTyped($newAuthEvent);

		$bearerAuthBackend = new BearerAuth(
			\OC::$server->getUserSession(),
			\OC::$server->getSession(),
			\OC::$server->getRequest()
		);
		$authPlugin->addBackend($bearerAuthBackend);
		// because we are throwing exceptions this plugin has to be the last one
		$authPlugin->addBackend($authBackend);

		// debugging
		if (\OC::$server->getConfig()->getSystemValue('debug', false)) {
			$this->server->addPlugin(new \Sabre\DAV\Browser\Plugin());
		} else {
			$this->server->addPlugin(new DummyGetResponsePlugin());
		}

		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin('webdav', $logger));
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\LockPlugin());
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
			$this->server->addPlugin(new \OCA\DAV\CalDAV\Plugin());
			$this->server->addPlugin(new \OCA\DAV\CalDAV\ICSExportPlugin\ICSExportPlugin(\OC::$server->getConfig(), $logger));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\Schedule\Plugin(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class)));
			if (\OC::$server->getConfig()->getAppValue('dav', 'sendInvitations', 'yes') === 'yes') {
				$this->server->addPlugin(\OC::$server->query(\OCA\DAV\CalDAV\Schedule\IMipPlugin::class));
			}

			$this->server->addPlugin(\OC::$server->get(\OCA\DAV\CalDAV\Trashbin\Plugin::class));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\WebcalCaching\Plugin($request));
			if (\OC::$server->getConfig()->getAppValue('dav', 'allow_calendar_link_subscriptions', 'yes') === 'yes') {
				$this->server->addPlugin(new \Sabre\CalDAV\Subscriptions\Plugin());
			}

			$this->server->addPlugin(new \Sabre\CalDAV\Notifications\Plugin());
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OC::$server->getRequest(), \OC::$server->getConfig()));
			$this->server->addPlugin(new \OCA\DAV\CalDAV\Publishing\PublishPlugin(
				\OC::$server->getConfig(),
				\OC::$server->getURLGenerator()
			));
		}

		// addressbook plugins
		if ($this->requestIsForSubtree(['addressbooks', 'principals'])) {
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OC::$server->getRequest(), \OC::$server->getConfig()));
			$this->server->addPlugin(new \OCA\DAV\CardDAV\Plugin());
			$this->server->addPlugin(new VCFExportPlugin());
			$this->server->addPlugin(new MultiGetExportPlugin());
			$this->server->addPlugin(new HasPhotoPlugin());
			$this->server->addPlugin(new ImageExportPlugin(new PhotoCache(
				\OC::$server->getAppDataDir('dav-photocache'),
				$logger)
			));
		}

		// system tags plugins
		$this->server->addPlugin(\OC::$server->get(SystemTagPlugin::class));

		// comments plugin
		$this->server->addPlugin(new CommentsPlugin(
			\OC::$server->getCommentsManager(),
			\OC::$server->getUserSession()
		));

		$this->server->addPlugin(new CopyEtagHeaderPlugin());
		$this->server->addPlugin(new RequestIdHeaderPlugin(\OC::$server->get(IRequest::class)));
		$this->server->addPlugin(new ChunkingV2Plugin(\OCP\Server::get(ICacheFactory::class)));
		$this->server->addPlugin(new ChunkingPlugin());

		// allow setup of additional plugins
		$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);
		$typedEvent = new SabrePluginAddEvent($this->server);
		$dispatcher->dispatchTyped($typedEvent);

		// Some WebDAV clients do require Class 2 WebDAV support (locking), since
		// we do not provide locking we emulate it using a fake locking plugin.
		if ($request->isUserAgent([
			'/WebDAVFS/',
			'/OneNote/',
			'/^Microsoft-WebDAV/',// Microsoft-WebDAV-MiniRedir/6.1.7601
		])) {
			$this->server->addPlugin(new FakeLockerPlugin());
		}

		// Allow view-only plugin for webdav requests
		$this->server->addPlugin(new ViewOnlyPlugin(
			$logger
		));

		if (BrowserErrorPagePlugin::isBrowserRequest($request)) {
			$this->server->addPlugin(new BrowserErrorPagePlugin());
		}

		$lazySearchBackend = new LazySearchBackend();
		$this->server->addPlugin(new SearchPlugin($lazySearchBackend));

		// wait with registering these until auth is handled and the filesystem is setup
		$this->server->on('beforeMethod:*', function () use ($root, $lazySearchBackend, $logger) {
			// custom properties plugin must be the last one
			$userSession = \OC::$server->getUserSession();
			$user = $userSession->getUser();
			if ($user !== null) {
				$view = \OC\Files\Filesystem::getView();
				$this->server->addPlugin(
					new FilesPlugin(
						$this->server->tree,
						\OC::$server->getConfig(),
						$this->request,
						\OC::$server->getPreviewManager(),
						\OC::$server->getUserSession(),
						false,
						!\OC::$server->getConfig()->getSystemValue('debug', false)
					)
				);
				$this->server->addPlugin(new ChecksumUpdatePlugin());

				$this->server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new CustomPropertiesBackend(
							$this->server->tree,
							\OC::$server->getDatabaseConnection(),
							\OC::$server->getUserSession()->getUser()
						)
					)
				);
				if ($view !== null) {
					$this->server->addPlugin(
						new QuotaPlugin($view));
				}
				$this->server->addPlugin(
					new TagsPlugin(
						$this->server->tree, \OC::$server->getTagManager()
					)
				);
				// TODO: switch to LazyUserFolder
				$userFolder = \OC::$server->getUserFolder();
				$this->server->addPlugin(new SharesPlugin(
					$this->server->tree,
					$userSession,
					$userFolder,
					\OC::$server->getShareManager()
				));
				$this->server->addPlugin(new CommentPropertiesPlugin(
					\OC::$server->getCommentsManager(),
					$userSession
				));
				$this->server->addPlugin(new \OCA\DAV\CalDAV\Search\SearchPlugin());
				if ($view !== null) {
					$this->server->addPlugin(new FilesReportPlugin(
						$this->server->tree,
						$view,
						\OC::$server->getSystemTagManager(),
						\OC::$server->getSystemTagObjectMapper(),
						\OC::$server->getTagManager(),
						$userSession,
						\OC::$server->getGroupManager(),
						$userFolder,
						\OC::$server->getAppManager()
					));
					$lazySearchBackend->setBackend(new \OCA\DAV\Files\FileSearchBackend(
						$this->server->tree,
						$user,
						\OC::$server->getRootFolder(),
						\OC::$server->getShareManager(),
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
				$this->server->addPlugin(new \OCA\DAV\CalDAV\BirthdayCalendar\EnablePlugin(
					\OC::$server->getConfig(),
					\OC::$server->query(BirthdayService::class),
					$user
				));
				$this->server->addPlugin(new AppleProvisioningPlugin(
					\OC::$server->getUserSession(),
					\OC::$server->getURLGenerator(),
					\OC::$server->getThemingDefaults(),
					\OC::$server->getRequest(),
					\OC::$server->getL10N('dav'),
					function () {
						return UUIDUtil::getUUID();
					}
				));
			}

			// register plugins from apps
			$pluginManager = new PluginManager(
				\OC::$server,
				\OC::$server->getAppManager()
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
		$eventLogger = \OC::$server->get(IEventLogger::class);
		$eventLogger->start('dav_server_exec', '');
		$this->server->exec();
		$eventLogger->end('dav_server_exec');
		if ($this->profiler->isEnabled()) {
			$eventLogger->end('runtime');
			$profile = $this->profiler->collect(\OC::$server->get(IRequest::class), new Response());
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

	public function getSabreServer(): Connector\Sabre\Server {
		return $this->server;
	}
}
