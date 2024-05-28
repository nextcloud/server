<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\AppInfo;

use OCA\Webhooks\Db\WebhookListenerMapper;
use OCA\Webhooks\Listener\WebhooksEventListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'webhooks';

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

		/* Listen to all events with at least one webhook configured */
		$configuredEvents = $mapper->getAllConfiguredEvents();
		foreach ($configuredEvents as $eventName) {
			// $logger->error($eventName.' '.\OCP\Files\Events\Node\NodeWrittenEvent::class, ['exception' => new \Exception('coucou')]);
			$dispatcher->addServiceListener(
				$eventName,
				WebhooksEventListener::class,
				-1,
			);
		}
	}
}
