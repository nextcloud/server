<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use OCP\App\IAppManager;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SabrePluginEvent;
use OCP\Share\IManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Exception;

class ServerFactory {
	/** @var IConfig */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var IDBConnection */
	private $databaseConnection;
	/** @var IUserSession */
	private $userSession;
	/** @var IMountManager */
	private $mountManager;
	/** @var ITagManager */
	private $tagManager;
	/** @var IRequest */
	private $request;
	/** @var IPreview  */
	private $previewManager;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var IL10N */
	private $l10n;

	/**
	 * @param IConfig $config
	 * @param LoggerInterface $logger
	 * @param IDBConnection $databaseConnection
	 * @param IUserSession $userSession
	 * @param IMountManager $mountManager
	 * @param ITagManager $tagManager
	 * @param IRequest $request
	 * @param IPreview $previewManager
	 * @param IEventDispatcher $eventDispatcher
	 * @param IL10N $l10n
	 */
	public function __construct(
		IConfig $config,
		LoggerInterface $logger,
		IDBConnection $databaseConnection,
		IUserSession $userSession,
		IMountManager $mountManager,
		ITagManager $tagManager,
		IRequest $request,
		IPreview $previewManager,
		IEventDispatcher $eventDispatcher,
		IL10N $l10n
	) {
		$this->config = $config;
		$this->logger = $logger;
		$this->databaseConnection = $databaseConnection;
		$this->userSession = $userSession;
		$this->mountManager = $mountManager;
		$this->tagManager = $tagManager;
		$this->request = $request;
		$this->previewManager = $previewManager;
		$this->eventDispatcher = $eventDispatcher;
		$this->l10n = $l10n;
	}

	/**
	 * @param string $baseUri
	 * @param string $requestUri
	 * @param Plugin $authPlugin
	 * @param callable $viewCallBack callback that should return the view for the dav endpoint
	 * @return Server
	 * @throws Exception
	 */
	public function createServer(string   $baseUri,
								 string   $requestUri,
								 Plugin   $authPlugin,
								 callable $viewCallBack): Server {
		// Fire up server
		$objectTree = new ObjectTree();
		$server = new Server($objectTree);
		// Set URL explicitly due to reverse-proxy situations
		$server->httpRequest->setUrl($requestUri);
		$server->setBaseUri($baseUri);

		// Load plugins
		$server->addPlugin(new MaintenancePlugin($this->config, $this->l10n));
		$server->addPlugin(new BlockLegacyClientPlugin($this->config));
		$server->addPlugin(new AnonymousOptionsPlugin());
		$server->addPlugin($authPlugin);
		// FIXME: The following line is a workaround for legacy components relying on being able to send a GET to /
		$server->addPlugin(new DummyGetResponsePlugin());
		$server->addPlugin(new ExceptionLoggerPlugin('webdav', $this->logger));
		$server->addPlugin(new LockPlugin());
		$server->addPlugin(new RequestIdHeaderPlugin($this->request));
		// Some WebDAV clients do require Class 2 WebDAV support (locking), since
		// we do not provide locking we emulate it using a fake locking plugin.
		if ($this->request->isUserAgent([
			'/WebDAVFS/',
			'/OneNote/',
			'/Microsoft-WebDAV-MiniRedir/',
		])) {
			$server->addPlugin(new FakeLockerPlugin());
		}

		if (BrowserErrorPagePlugin::isBrowserRequest($this->request)) {
			$server->addPlugin(new BrowserErrorPagePlugin());
		}

		// wait with registering these until auth is handled and the filesystem is setup
		$server->on('beforeMethod:*', function () use ($server, $objectTree, $viewCallBack) {
			// ensure the skeleton is copied
			$userFolder = null;
			$root = \OC::$server->get(IRootFolder::class);
			/** @var IUser $user */
			if ($user = \OC::$server->get(IUserSession::class)->getUser()) {
				/** @var IRootFolder $root */
				$rootInfo = $root->getUserFolder($user->getUID());
			} else {
				$rootInfo = $root;
			}

			/** @var View $view */
			$view = $viewCallBack($server);

			// Create Nextcloud Dir
			if ($rootInfo->getType() === 'dir') {
				$root = new Directory($view, $rootInfo, $objectTree);
			} else {
				$root = new File($view, $rootInfo);
			}
			$objectTree->init($root, $view, $this->mountManager);

			$server->addPlugin(
				new FilesPlugin(
					$objectTree,
					$this->config,
					$this->request,
					$this->previewManager,
					$this->userSession,
					false,
					!$this->config->getSystemValue('debug', false)
				)
			);
			$server->addPlugin(new QuotaPlugin($view, true));

			if ($this->userSession->isLoggedIn()) {
				$server->addPlugin(new TagsPlugin($objectTree, $this->tagManager));
				$server->addPlugin(new SharesPlugin(
					$objectTree,
					$this->userSession,
					$userFolder,
					\OC::$server->get(IManager::class)
				));
				$server->addPlugin(new CommentPropertiesPlugin(\OC::$server->get(ICommentsManager::class), $this->userSession));
				$server->addPlugin(new FilesReportPlugin(
					$objectTree,
					$view,
					\OC::$server->get(ISystemTagManager::class),
					\OC::$server->get(ISystemTagObjectMapper::class),
					\OC::$server->get(ITagManager::class),
					$this->userSession,
					\OC::$server->get(IGroupManager::class),
					$userFolder,
					\OC::$server->get(IAppManager::class)
				));
				// custom properties plugin must be the last one
				$server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new CustomPropertiesBackend(
							$objectTree,
							$this->databaseConnection,
							$this->userSession->getUser()
						)
					)
				);
			}
			$server->addPlugin(new CopyEtagHeaderPlugin());

			// Load dav plugins from apps
			$event = new SabrePluginEvent($server);
			$this->eventDispatcher->dispatchTyped($event);
			$pluginManager = new PluginManager(
				\OC::$server,
				\OC::$server->get(IAppManager::class)
			);
			foreach ($pluginManager->getAppPlugins() as $appPlugin) {
				$server->addPlugin($appPlugin);
			}
		}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request
		return $server;
	}
}
