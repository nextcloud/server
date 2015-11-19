<?php

namespace OCA\DAV;

use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\DAV\Files\CustomPropertiesBackend;
use OCP\IRequest;
use Sabre\DAV\Auth\Plugin;
use Sabre\HTTP\Util;

class Server {

	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request, $baseUri) {
		$this->request = $request;
		$this->baseUri = $baseUri;
		$logger = \OC::$server->getLogger();
		$dispatcher = \OC::$server->getEventDispatcher();

		$root = new RootCollection();
		$this->server = new \OCA\DAV\Connector\Sabre\Server($root);

		// Backends
		$authBackend = new Auth(
			\OC::$server->getSession(),
			\OC::$server->getUserSession()
		);

		// Set URL explicitly due to reverse-proxy situations
		$this->server->httpRequest->setUrl($this->request->getRequestUri());
		$this->server->setBaseUri($this->baseUri);

		$this->server->addPlugin(new BlockLegacyClientPlugin(\OC::$server->getConfig()));
		$this->server->addPlugin(new Plugin($authBackend, 'ownCloud'));
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\DummyGetResponsePlugin());
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin('webdav', $logger));
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\LockPlugin());
		$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\ListenerPlugin($dispatcher));

		// calendar plugins
		$this->server->addPlugin(new \Sabre\CalDAV\Plugin());
		$this->server->addPlugin(new \Sabre\DAVACL\Plugin());
		$this->server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
		$senderEmail = \OCP\Util::getDefaultEmailAddress('no-reply');
		$this->server->addPlugin(new \Sabre\CalDAV\Schedule\Plugin());
		$this->server->addPlugin(new \Sabre\CalDAV\Schedule\IMipPlugin($senderEmail));
		$this->server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
		$this->server->addPlugin(new \Sabre\CalDAV\Subscriptions\Plugin());
		$this->server->addPlugin(new \Sabre\CalDAV\Notifications\Plugin());

		// addressbook plugins
		$this->server->addPlugin(new \Sabre\CardDAV\Plugin());

		// Finder on OS X requires Class 2 WebDAV support (locking), since we do
		// not provide locking we emulate it using a fake locking plugin.
		if($request->isUserAgent(['/WebDAVFS/'])) {
			$this->server->addPlugin(new \OCA\DAV\Connector\Sabre\FakeLockerPlugin());
		}

		// wait with registering these until auth is handled and the filesystem is setup
		$this->server->on('beforeMethod', function () {
			// custom properties plugin must be the last one
			$user = \OC::$server->getUserSession()->getUser();
			if (!is_null($user)) {
				$this->server->addPlugin(
					new \Sabre\DAV\PropertyStorage\Plugin(
						new CustomPropertiesBackend(
							$this->server->tree,
							\OC::$server->getDatabaseConnection(),
							\OC::$server->getUserSession()->getUser()
						)
					)
				);
			}
		});
	}

	public function exec() {
		$this->server->exec();
	}
}
