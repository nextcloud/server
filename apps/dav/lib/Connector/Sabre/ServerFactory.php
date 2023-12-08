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

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\DAV\ViewOnlyPlugin;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\SabrePluginEvent;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Plugin;

class ServerFactory {
	private IConfig $config;
	private LoggerInterface $logger;
	private IDBConnection $databaseConnection;
	private IUserSession $userSession;
	private IMountManager $mountManager;
	private ITagManager $tagManager;
	private IRequest $request;
	private IPreview $previewManager;
	private IEventDispatcher $eventDispatcher;
	private IL10N $l10n;

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
	 * @param callable $viewCallBack callback that should return the view for the dav endpoint
	 */
	public function createServer(string $baseUri,
		string $requestUri,
		Plugin $authPlugin,
		callable $viewCallBack): Server {
		// Fire up server
		$objectTree = new \OCA\DAV\Connector\Sabre\ObjectTree();
		$server = new \OCA\DAV\Connector\Sabre\Server($objectTree);
		// Set URL explicitly due to reverse-proxy situations
		$server->httpRequest->setUrl($requestUri);
		$server->setBaseUri($baseUri);

		// Load plugins
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\MaintenancePlugin($this->config, $this->l10n));
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin($this->config));
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin());
		$server->addPlugin($authPlugin);
		// FIXME: The following line is a workaround for legacy components relying on being able to send a GET to /
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\DummyGetResponsePlugin());
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin('webdav', $this->logger));
		$server->addPlugin(new \OCA\DAV\Connector\Sabre\LockPlugin());

		$server->addPlugin(new RequestIdHeaderPlugin(\OC::$server->get(IRequest::class)));

		// Some WebDAV clients do require Class 2 WebDAV support (locking), since
		// we do not provide locking we emulate it using a fake locking plugin.
		if ($this->request->isUserAgent([
			'/WebDAVFS/',
			'/OneNote/',
			'/Microsoft-WebDAV-MiniRedir/',
		])) {
			$server->addPlugin(new \OCA\DAV\Connector\Sabre\FakeLockerPlugin());
		}

		if (BrowserErrorPagePlugin::isBrowserRequest($this->request)) {
			$server->addPlugin(new BrowserErrorPagePlugin());
		}

		// wait with registering these until auth is handled and the filesystem is setup
		$server->on('beforeMethod:*', function () use ($server, $objectTree, $viewCallBack) {
			// ensure the skeleton is copied
			$userFolder = \OC::$server->getUserFolder();

			/** @var \OC\Files\View $view */
			$view = $viewCallBack($server);
			if ($userFolder instanceof Folder && $userFolder->getPath() === $view->getRoot()) {
				$rootInfo = $userFolder;
			} else {
				$rootInfo = $view->getFileInfo('');
			}

			// Create Nextcloud Dir
			if ($rootInfo->getType() === 'dir') {
				$root = new \OCA\DAV\Connector\Sabre\Directory($view, $rootInfo, $objectTree);
			} else {
				$root = new \OCA\DAV\Connector\Sabre\File($view, $rootInfo);
			}
			$objectTree->init($root, $view, $this->mountManager);

			$server->addPlugin(
				new \OCA\DAV\Connector\Sabre\FilesPlugin(
					$objectTree,
					$this->config,
					$this->request,
					$this->previewManager,
					$this->userSession,
					false,
					!$this->config->getSystemValue('debug', false)
				)
			);
			$server->addPlugin(new \OCA\DAV\Connector\Sabre\QuotaPlugin($view, true));
			$server->addPlugin(new \OCA\DAV\Connector\Sabre\ChecksumUpdatePlugin());

			// Allow view-only plugin for webdav requests
			$server->addPlugin(new ViewOnlyPlugin(
				$this->logger
			));

			if ($this->userSession->isLoggedIn()) {
				$server->addPlugin(new \OCA\DAV\Connector\Sabre\TagsPlugin($objectTree, $this->tagManager));
				$server->addPlugin(new \OCA\DAV\Connector\Sabre\SharesPlugin(
					$objectTree,
					$this->userSession,
					$userFolder,
					\OC::$server->getShareManager()
				));
				$server->addPlugin(new \OCA\DAV\Connector\Sabre\CommentPropertiesPlugin(\OC::$server->getCommentsManager(), $this->userSession));
				$server->addPlugin(new \OCA\DAV\Connector\Sabre\FilesReportPlugin(
					$objectTree,
					$view,
					\OC::$server->getSystemTagManager(),
					\OC::$server->getSystemTagObjectMapper(),
					\OC::$server->getTagManager(),
					$this->userSession,
					\OC::$server->getGroupManager(),
					$userFolder,
					\OC::$server->getAppManager()
				));
				// custom properties plugin must be the last one
				$server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new \OCA\DAV\DAV\CustomPropertiesBackend(
							$objectTree,
							$this->databaseConnection,
							$this->userSession->getUser()
						)
					)
				);
			}
			$server->addPlugin(new \OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin());

			// Load dav plugins from apps
			$event = new SabrePluginEvent($server);
			$this->eventDispatcher->dispatchTyped($event);
			$pluginManager = new PluginManager(
				\OC::$server,
				\OC::$server->getAppManager()
			);
			foreach ($pluginManager->getAppPlugins() as $appPlugin) {
				$server->addPlugin($appPlugin);
			}
		}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request
		return $server;
	}
}
