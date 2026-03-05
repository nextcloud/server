<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\InvitationResponse;

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\CalDAV\Auth\PublicPrincipalPlugin;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\LockPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\RootCollection;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Sabre\VObject\ITip\Message;

class InvitationResponseServer {
	/** @var \OCA\DAV\Connector\Sabre\Server */
	public $server;

	/**
	 * InvitationResponseServer constructor.
	 */
	public function __construct(bool $public = true) {
		$baseUri = \OC::$WEBROOT . '/remote.php/dav/';
		$logger = Server::get(LoggerInterface::class);
		$dispatcher = Server::get(IEventDispatcher::class);

		$root = new RootCollection();
		$this->server = new \OCA\DAV\Connector\Sabre\Server(new CachingTree($root));

		// Add maintenance plugin
		$this->server->addPlugin(new MaintenancePlugin(Server::get(IConfig::class), \OC::$server->getL10N('dav')));

		// Set URL explicitly due to reverse-proxy situations
		$this->server->httpRequest->setUrl($baseUri);
		$this->server->setBaseUri($baseUri);

		$this->server->addPlugin(new BlockLegacyClientPlugin(
			Server::get(IConfig::class),
			Server::get(ThemingDefaults::class),
		));
		$this->server->addPlugin(new AnonymousOptionsPlugin());

		// allow custom principal uri option
		if ($public) {
			$this->server->addPlugin(new PublicPrincipalPlugin());
		} else {
			$this->server->addPlugin(new CustomPrincipalPlugin());
		}

		// allow setup of additional auth backends
		$event = new SabrePluginAuthInitEvent($this->server);
		$dispatcher->dispatchTyped($event);

		$this->server->addPlugin(new ExceptionLoggerPlugin('webdav', $logger));
		$this->server->addPlugin(new LockPlugin());
		$this->server->addPlugin(new \Sabre\DAV\Sync\Plugin());

		// acl
		$acl = new DavAclPlugin();
		$acl->principalCollectionSet = [
			'principals/users', 'principals/groups'
		];
		$this->server->addPlugin($acl);

		// calendar plugins
		$this->server->addPlugin(new \OCA\DAV\CalDAV\Plugin());
		$this->server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
		$this->server->addPlugin(new \OCA\DAV\CalDAV\Schedule\Plugin(Server::get(IConfig::class), Server::get(LoggerInterface::class), Server::get(DefaultCalendarValidator::class)));
		$this->server->addPlugin(new \Sabre\CalDAV\Subscriptions\Plugin());
		$this->server->addPlugin(new \Sabre\CalDAV\Notifications\Plugin());
		//$this->server->addPlugin(new \OCA\DAV\DAV\Sharing\Plugin($authBackend, \OC::$server->getRequest()));
		$this->server->addPlugin(new PublishPlugin(
			Server::get(IConfig::class),
			Server::get(IURLGenerator::class)
		));

		// wait with registering these until auth is handled and the filesystem is setup
		$this->server->on('beforeMethod:*', function () use ($root): void {
			// register plugins from apps
			$pluginManager = new PluginManager(
				\OC::$server,
				Server::get(IAppManager::class)
			);
			foreach ($pluginManager->getAppPlugins() as $appPlugin) {
				$this->server->addPlugin($appPlugin);
			}
			foreach ($pluginManager->getAppCollections() as $appCollection) {
				$root->addChild($appCollection);
			}
		});
	}

	/**
	 * @param Message $iTipMessage
	 * @return void
	 */
	public function handleITipMessage(Message $iTipMessage) {
		/** @var \OCA\DAV\CalDAV\Schedule\Plugin $schedulingPlugin */
		$schedulingPlugin = $this->server->getPlugin('caldav-schedule');
		$schedulingPlugin->scheduleLocalDelivery($iTipMessage);
	}

	public function isExternalAttendee(string $principalUri): bool {
		/** @var \Sabre\DAVACL\Plugin $aclPlugin */
		$aclPlugin = $this->getServer()->getPlugin('acl');
		return $aclPlugin->getPrincipalByUri($principalUri) === null;
	}

	public function getServer(): \OCA\DAV\Connector\Sabre\Server {
		return $this->server;
	}
}
