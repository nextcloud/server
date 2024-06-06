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
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'webhooks';

	private ?ICache $cache = null;

	private const CACHE_KEY = 'eventsUsedInWebhooks';

	public function __construct(
		ICacheFactory $cacheFactory,
	) {
		parent::__construct(self::APP_ID);
		if ($cacheFactory->isAvailable()) {
			$this->cache = $cacheFactory->createDistributed();
		}
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
		/* Listen to all events with at least one webhook configured */
		$configuredEvents = $this->getAllConfiguredEvents($container);
		foreach ($configuredEvents as $eventName) {
			$logger->debug("Listening to {$eventName}");
			$dispatcher->addServiceListener(
				$eventName,
				WebhooksEventListener::class,
				-1,
			);
		}
	}

	/**
	 * List all events with at least one webhook configured, with cache
	 */
	private function getAllConfiguredEvents(ContainerInterface $container) {
		$events = $this->cache?->get(self::CACHE_KEY);
		if ($events !== null) {
			return json_decode($events);
		}
		/** @var WebhookListenerMapper */
		$mapper = $container->get(WebhookListenerMapper::class);
		$events = $mapper->getAllConfiguredEvents();
		// cache for 5 minutes
		$this->cache?->set(self::CACHE_KEY, json_encode($events), 300);
	}
}
