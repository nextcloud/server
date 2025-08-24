<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\AppInfo;

use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCA\WebhookListeners\Listener\WebhooksEventListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'webhook_listeners';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$context->injectFn($this->registerRuleListeners(...));
	}

	private function registerRuleListeners(
		IEventDispatcher $dispatcher,
		ContainerInterface $container,
		LoggerInterface $logger,
	): void {
		/** @var WebhookListenerMapper */
		$mapper = $container->get(WebhookListenerMapper::class);
		$userSession = $container->get(IUserSession::class);

		/* Listen to all events with at least one webhook configured */
		$configuredEvents = $mapper->getAllConfiguredEvents($userSession->getUser()?->getUID());
		foreach ($configuredEvents as $eventName) {
			$logger->debug("Listening to {$eventName}");
			$dispatcher->addServiceListener(
				$eventName,
				WebhooksEventListener::class,
				-1,
			);
		}
	}
}
