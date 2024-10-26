<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\SystemTags\Activity\Listener;
use OCA\SystemTags\Capabilities;
use OCA\SystemTags\Listeners\BeforeSabrePubliclyLoadedListener;
use OCA\SystemTags\Listeners\BeforeTemplateRenderedListener;
use OCA\SystemTags\Listeners\LoadAdditionalScriptsListener;
use OCA\SystemTags\Search\TagSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\MapperEvent;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'systemtags';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(TagSearchProvider::class);
		$context->registerCapability(Capabilities::class);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeSabrePubliclyLoadedEvent::class, BeforeSabrePubliclyLoadedListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IEventDispatcher $dispatcher) use ($context): void {
			/*
			 * @todo move the OCP events and then move the registration to `register`
			 */
			$dispatcher->addListener(
				LoadAdditionalScriptsEvent::class,
				function (): void {
					Util::addScript('core', 'systemtags');
					Util::addInitScript(self::APP_ID, 'init');
				}
			);

			$managerListener = function (ManagerEvent $event) use ($context): void {
				/** @var Listener $listener */
				$listener = $context->getServerContainer()->query(Listener::class);
				$listener->event($event);
			};
			$dispatcher->addListener(ManagerEvent::EVENT_CREATE, $managerListener);
			$dispatcher->addListener(ManagerEvent::EVENT_DELETE, $managerListener);
			$dispatcher->addListener(ManagerEvent::EVENT_UPDATE, $managerListener);

			$mapperListener = function (MapperEvent $event) use ($context): void {
				/** @var Listener $listener */
				$listener = $context->getServerContainer()->query(Listener::class);
				$listener->mapperEvent($event);
			};
			$dispatcher->addListener(MapperEvent::EVENT_ASSIGN, $mapperListener);
			$dispatcher->addListener(MapperEvent::EVENT_UNASSIGN, $mapperListener);
		});
	}
}
