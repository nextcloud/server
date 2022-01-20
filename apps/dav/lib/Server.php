<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Brandon Kirsch <brandonkirsch@github.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

use OCP\Diagnostics\IEventLogger;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OC\EventDispatcher\SymfonyAdapter;
use OC\Security\Bruteforce\Throttler;
use OCA\DAV\CalDAV\BirthdayCalendar\EnablePlugin;
use OCA\DAV\CalDAV\ICSExportPlugin\ICSExportPlugin;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\LockPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Events\SabreAddPluginEvent;
use OCA\DAV\Files\FileSearchBackend;
use OCP\App\IAppManager;
use OCP\Comments\ICommentsManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IPreview;
use OCP\ITagManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\SabrePluginEvent;
use OCP\Share\IManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use OCA\DAV\AppInfo\PluginManager;
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
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use OCA\DAV\Files\LazySearchBackend;
use OCA\DAV\BulkUpload\BulkUploadPlugin;
use OCA\DAV\Provisioning\Apple\AppleProvisioningPlugin;
use OCA\DAV\SystemTag\SystemTagPlugin;
use OCA\DAV\Upload\ChunkingPlugin;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use Sabre\CardDAV\VCFExportPlugin;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\UUIDUtil;
use SearchDAV\DAV\SearchPlugin;

class Server {

	/** @var IRequest */
	private $request;

	/** @var  string */
	private $baseUri;

	/** @var Connector\Sabre\Server  */
	public $server;

	public function __construct(IRequest $request, $baseUri) {
		$this->request = $request;
		$this->baseUri = $baseUri;
		$logger = \OC::$server->get(LoggerInterface::class);
		$dispatcher = \OC::$server->get(SymfonyAdapter::class);
		/** @var IEventDispatcher $newDispatcher */
		$newDispatcher = \OC::$server->get(IEventDispatcher::class);

		$root = new RootCollection();
		$this->server = new Connector\Sabre\Server(new CachingTree($root));

		// Add maintenance plugin
		$this->server->addPlugin(new MaintenancePlugin(\OC::$server->get(IConfig::class), \OC::$server->get(IFactory::class)->get('dav')));

		// Backends
		$authBackend = new Auth(
			\OC::$server->get(IUserSession::class)->getSession(),
			\OC::$server->get(IUserSession::class),
			\OC::$server->get(IRequest::class),
			\OC::$server->get(TwoFactorAuthManager::class),
			\OC::$server->get(Throttler::class)
		);

		// Set URL explicitly due to reverse-proxy situations
		$this->server->httpRequest->setUrl($this->request->getRequestUri());
		$this->server->setBaseUri($this->baseUri);

		$this->server->addPlugin(new BlockLegacyClientPlugin(\OC::$server->get(IConfig::class)));
		$this->server->addPlugin(new AnonymousOptionsPlugin());
		$authPlugin = new Plugin();
		$authPlugin->addBackend(new PublicAuth());
		$this->server->addPlugin($authPlugin);

		// allow setup of additional auth backends

		$newAuthEvent = new SabrePluginAuthInitEvent($this->server);
		$newDispatcher->dispatchTyped($newAuthEvent);

		$bearerAuthBackend = new BearerAuth(
			\OC::$server->get(IUserSession::class),
			\OC::$server->get(IUserSession::class)->getSession(),
			\OC::$server->get(IRequest::class)
		);
		$authPlugin->addBackend($bearerAuthBackend);
		// because we are throwing exceptions this plugin has to be the last one
		$authPlugin->addBackend($authBackend);

		// debugging
		if (\OC::$server->get(IConfig::class)->getSystemValue('debug', false)) {
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
			$this->server->addPlugin(new CalDAV\Plugin());
			$this->server->addPlugin(new ICSExportPlugin(\OC::$server->get(IConfig::class), \OC::$server->get(LoggerInterface::class)));
			$this->server->addPlugin(new CalDAV\Schedule\Plugin(\OC::$server->get(IConfig::class)));
			if (\OC::$server->get(IConfig::class)->getAppValue('dav', 'sendInvitations', 'yes') === 'yes') {
				$this->server->addPlugin(\OC::$server->get(IMipPlugin::class));
			}

			$this->server->addPlugin(\OC::$server->get(CalDAV\Trashbin\Plugin::class));
			$this->server->addPlugin(new CalDAV\WebcalCaching\Plugin($request));
			$this->server->addPlugin(new \Sabre\CalDAV\Subscriptions\Plugin());

			$this->server->addPlugin(new \Sabre\CalDAV\Notifications\Plugin());
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OC::$server->get(IRequest::class), \OC::$server->get(IConfig::class)));
			$this->server->addPlugin(new PublishPlugin(
				\OC::$server->get(IConfig::class),
				\OC::$server->get(IURLGenerator::class)
			));
		}

		// addressbook plugins
		if ($this->requestIsForSubtree(['addressbooks', 'principals'])) {
			$this->server->addPlugin(new DAV\Sharing\Plugin($authBackend, \OC::$server->get(IRequest::class), \OC::$server->get(IConfig::class)));
			$this->server->addPlugin(new CardDAV\Plugin());
			$this->server->addPlugin(new VCFExportPlugin());
			$this->server->addPlugin(new MultiGetExportPlugin());
			$this->server->addPlugin(new HasPhotoPlugin());
			$this->server->addPlugin(new ImageExportPlugin(new PhotoCache(
				\OC::$server->getAppDataDir('dav-photocache'),
				\OC::$server->get(LoggerInterface::class))
			));
		}

		// system tags plugins
		$this->server->addPlugin(new SystemTagPlugin(
			\OC::$server->get(ISystemTagManager::class),
			\OC::$server->get(IGroupManager::class),
			\OC::$server->get(IUserSession::class)
		));

		// comments plugin
		$this->server->addPlugin(new CommentsPlugin(
			\OC::$server->get(ICommentsManager::class),
			\OC::$server->get(IUserSession::class)
		));

		$this->server->addPlugin(new CopyEtagHeaderPlugin());
		$this->server->addPlugin(new RequestIdHeaderPlugin(\OC::$server->get(IRequest::class)));
		$this->server->addPlugin(new ChunkingPlugin());

		// allow setup of additional plugins
		$newDispatcher->dispatchTyped(new SabreAddPluginEvent($this->server));
		$event = new SabrePluginEvent($this->server);
		$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);

		// Some WebDAV clients do require Class 2 WebDAV support (locking), since
		// we do not provide locking we emulate it using a fake locking plugin.
		if ($request->isUserAgent([
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
		$this->server->on('beforeMethod:*', function () use ($root, $lazySearchBackend) {
			// custom properties plugin must be the last one
			$userSession = \OC::$server->get(IUserSession::class);
			$user = $userSession->getUser();
			if ($user !== null) {
				$view = \OC\Files\Filesystem::getView();
				$this->server->addPlugin(
					new FilesPlugin(
						$this->server->tree,
						\OC::$server->get(IConfig::class),
						$this->request,
						\OC::$server->get(IPreview::class),
						\OC::$server->get(IUserSession::class),
						false,
						!\OC::$server->get(IConfig::class)->getSystemValue('debug', false)
					)
				);

				$this->server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new CustomPropertiesBackend(
							$this->server->tree,
							\OC::$server->get(IDBConnection::class),
							\OC::$server->get(IUserSession::class)->getUser()
						)
					)
				);
				if ($view !== null) {
					$this->server->addPlugin(
						new QuotaPlugin($view));
				}
				$this->server->addPlugin(
					new TagsPlugin(
						$this->server->tree, \OC::$server->get(ITagManager::class)
					)
				);
				// TODO: switch to LazyUserFolder
				$userFolder = \OC::$server->getUserFolder();
				$this->server->addPlugin(new SharesPlugin(
					$this->server->tree,
					$userSession,
					$userFolder,
					\OC::$server->get(IManager::class)
				));
				$this->server->addPlugin(new CommentPropertiesPlugin(
					\OC::$server->get(ICommentsManager::class),
					$userSession
				));
				$this->server->addPlugin(new CalDAV\Search\SearchPlugin());
				if ($view !== null) {
					$this->server->addPlugin(new FilesReportPlugin(
						$this->server->tree,
						$view,
						\OC::$server->get(ISystemTagManager::class),
						\OC::$server->get(ISystemTagObjectMapper::class),
						\OC::$server->get(ITagManager::class),
						$userSession,
						\OC::$server->get(IGroupManager::class),
						$userFolder,
						\OC::$server->get(IAppManager::class)
					));
					$lazySearchBackend->setBackend(new FileSearchBackend(
						$this->server->tree,
						$user,
						\OC::$server->get(IRootFolder::class),
						\OC::$server->get(IManager::class),
						$view
					));
					$logger = \OC::$server->get(LoggerInterface::class);
					$this->server->addPlugin(
						new BulkUploadPlugin($userFolder, $logger)
					);
				}
				$this->server->addPlugin(new EnablePlugin(
					\OC::$server->get(IConfig::class),
					\OC::$server->get(BirthdayService::class)
				));
				$this->server->addPlugin(new AppleProvisioningPlugin(
					\OC::$server->get(IUserSession::class),
					\OC::$server->get(IURLGenerator::class),
					\OC::$server->get('ThemingDefaults'),
					\OC::$server->get(IRequest::class),
					\OC::$server->get(IFactory::class)->get('dav'),
					function () {
						return UUIDUtil::getUUID();
					}
				));
			}

			// register plugins from apps
			$pluginManager = new PluginManager(
				\OC::$server,
				\OC::$server->get(IAppManager::class)
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

	/**
	 * @deprecated
	 */
	public function exec() {
		$this->start();
	}

	public function start() {
		/** @var IEventLogger $eventLogger */
		$eventLogger = \OC::$server->get(IEventLogger::class);
		$eventLogger->start('dav_server_start', '');
		$this->server->start();
		$eventLogger->end('dav_server_start');
	}

	private function requestIsForSubtree(array $subTrees): bool {
		foreach ($subTrees as $subTree) {
			$subTree = trim($subTree, ' /');
			if (strpos($this->server->getRequestUri(), $subTree.'/') === 0) {
				return true;
			}
		}
		return false;
	}
}
