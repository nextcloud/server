<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Connector\Sabre;

use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUserSession;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServerFactory {
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var IDBConnection */
	private $databaseConnection;
	/** @var IUserSession */
	private $userSession;
	/** @var IMountManager */
	private $mountManager;
	/** @var ITagManager */
	private $tagManager;
	/** @var EventDispatcherInterface */
	private $dispatcher;
	/** @var IRequest */
	private $request;

	/**
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param IDBConnection $databaseConnection
	 * @param IUserSession $userSession
	 * @param IMountManager $mountManager
	 * @param ITagManager $tagManager
	 * @param EventDispatcherInterface $dispatcher
	 * @param IRequest $request
	 */
	public function __construct(
		IConfig $config,
		ILogger $logger,
		IDBConnection $databaseConnection,
		IUserSession $userSession,
		IMountManager $mountManager,
		ITagManager $tagManager,
		EventDispatcherInterface $dispatcher,
		IRequest $request
	) {
		$this->config = $config;
		$this->logger = $logger;
		$this->databaseConnection = $databaseConnection;
		$this->userSession = $userSession;
		$this->mountManager = $mountManager;
		$this->tagManager = $tagManager;
		$this->dispatcher = $dispatcher;
		$this->request = $request;
	}

	/**
	 * @param string $baseUri
	 * @param string $requestUri
	 * @param BackendInterface $authBackend
	 * @param callable $viewCallBack callback that should return the view for the dav endpoint
	 * @return Server
	 */
	public function createServer($baseUri, $requestUri, BackendInterface $authBackend, callable $viewCallBack) {
		// Fire up server
		$objectTree = new \OC\Connector\Sabre\ObjectTree();
		$server = new \OC\Connector\Sabre\Server($objectTree);
		// Set URL explicitly due to reverse-proxy situations
		$server->httpRequest->setUrl($requestUri);
		$server->setBaseUri($baseUri);

		// Load plugins
		$defaults = new \OC_Defaults();
		$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin($this->config));
		$server->addPlugin(new \OC\Connector\Sabre\BlockLegacyClientPlugin($this->config));
		$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, $defaults->getName()));
		// FIXME: The following line is a workaround for legacy components relying on being able to send a GET to /
		$server->addPlugin(new \OC\Connector\Sabre\DummyGetResponsePlugin());
		$server->addPlugin(new \OC\Connector\Sabre\ExceptionLoggerPlugin('webdav', $this->logger));
		$server->addPlugin(new \OC\Connector\Sabre\LockPlugin($objectTree));
		$server->addPlugin(new \OC\Connector\Sabre\ListenerPlugin($this->dispatcher));

		// Finder on OS X requires Class 2 WebDAV support (locking), since we do
		// not provide locking we emulate it using a fake locking plugin.
		if($this->request->isUserAgent([
			'/WebDAVFS/',
			'/Microsoft Office OneNote 2013/',
			'/Microsoft-WebDAV-MiniRedir/',
		])) {
			$server->addPlugin(new \OC\Connector\Sabre\FakeLockerPlugin());
		}

		// wait with registering these until auth is handled and the filesystem is setup
		$server->on('beforeMethod', function () use ($server, $objectTree, $viewCallBack) {
			/** @var \OC\Files\View $view */
			$view = $viewCallBack();
			$rootInfo = $view->getFileInfo('');

			// Create ownCloud Dir
			if ($rootInfo->getType() === 'dir') {
				$root = new \OC\Connector\Sabre\Directory($view, $rootInfo);
			} else {
				$root = new \OC\Connector\Sabre\File($view, $rootInfo);
			}
			$objectTree->init($root, $view, $this->mountManager);

			$server->addPlugin(new \OC\Connector\Sabre\FilesPlugin($objectTree, $view));
			$server->addPlugin(new \OC\Connector\Sabre\QuotaPlugin($view));

			if($this->userSession->isLoggedIn()) {
				$server->addPlugin(new \OC\Connector\Sabre\TagsPlugin($objectTree, $this->tagManager));
				// custom properties plugin must be the last one
				$server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new \OC\Connector\Sabre\CustomPropertiesBackend(
							$objectTree,
							$this->databaseConnection,
							$this->userSession->getUser()
						)
					)
				);
			}
			$server->addPlugin(new \OC\Connector\Sabre\CopyEtagHeaderPlugin());
		}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request
		return $server;
	}
}
