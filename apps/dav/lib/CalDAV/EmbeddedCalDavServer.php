<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\CalDAV\Auth\PublicPrincipalPlugin;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\LockPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\PropFindPreloadNotifyPlugin;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\RootCollection;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class EmbeddedCalDavServer {
	private readonly \OCA\DAV\Connector\Sabre\Server $server;

	public function __construct(bool $public = true) {
		$baseUri = \OC::$WEBROOT . '/remote.php/dav/';
		$logger = Server::get(LoggerInterface::class);
		$dispatcher = Server::get(IEventDispatcher::class);
		$appConfig = Server::get(IAppConfig::class);
		$l10nFactory = Server::get(IL10NFactory::class);
		$l10n = $l10nFactory->get('dav');

		$root = new RootCollection();
		$this->server = new \OCA\DAV\Connector\Sabre\Server(new CachingTree($root));

		// Add maintenance plugin
		$this->server->addPlugin(new MaintenancePlugin(Server::get(IConfig::class), $l10n));

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
		if ($appConfig->getValueString('dav', 'sendInvitations', 'yes') === 'yes') {
			$this->server->addPlugin(Server::get(IMipPlugin::class));
		}

		// collection preload plugin
		$this->server->addPlugin(new PropFindPreloadNotifyPlugin());

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

	public function getServer(): \OCA\DAV\Connector\Sabre\Server {
		return $this->server;
	}
}
