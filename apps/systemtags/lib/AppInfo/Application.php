<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\SystemTags\Activity\Listener;
use OCA\SystemTags\Capabilities;
use OCA\SystemTags\Search\TagSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\MapperEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'systemtags';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(TagSearchProvider::class);
		$context->registerCapability(Capabilities::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IEventDispatcher $dispatcher) use ($context) {
			/*
			 * @todo move the OCP events and then move the registration to `register`
			 */
			$dispatcher->addListener(
				LoadAdditionalScriptsEvent::class,
				function () {
					\OCP\Util::addScript('core', 'systemtags');
					\OCP\Util::addInitScript(self::APP_ID, 'init');
				}
			);

			$managerListener = function (ManagerEvent $event) use ($context) {
				/** @var \OCA\SystemTags\Activity\Listener $listener */
				$listener = $context->getServerContainer()->query(Listener::class);
				$listener->event($event);
			};
			$dispatcher->addListener(ManagerEvent::EVENT_CREATE, $managerListener);
			$dispatcher->addListener(ManagerEvent::EVENT_DELETE, $managerListener);
			$dispatcher->addListener(ManagerEvent::EVENT_UPDATE, $managerListener);

			$mapperListener = function (MapperEvent $event) use ($context) {
				/** @var \OCA\SystemTags\Activity\Listener $listener */
				$listener = $context->getServerContainer()->query(Listener::class);
				$listener->mapperEvent($event);
			};
			$dispatcher->addListener(MapperEvent::EVENT_ASSIGN, $mapperListener);
			$dispatcher->addListener(MapperEvent::EVENT_UNASSIGN, $mapperListener);
		});
	}
}
