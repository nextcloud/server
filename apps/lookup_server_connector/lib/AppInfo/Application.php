<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\LookupServerConnector\AppInfo;

use Closure;
use OCA\LookupServerConnector\UpdateLookupServer;
use OCP\Accounts\UserUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'lookup_server_connector';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerEventListeners']));
	}

	/**
	 * @todo move the OCP events and then move the registration to `register`
	 */
	private function registerEventListeners(IEventDispatcher $dispatcher,
		ContainerInterface $appContainer): void {
		$dispatcher->addListener(UserUpdatedEvent::class, function (UserUpdatedEvent $event) use ($appContainer): void {
			/** @var UpdateLookupServer $updateLookupServer */
			$updateLookupServer = $appContainer->get(UpdateLookupServer::class);
			$updateLookupServer->userUpdated($event->getUser());
		});
	}
}
