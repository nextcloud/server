<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Reference;

use OCP\Collaboration\Reference\IDiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IInitialStateService;
use OCP\ICacheFactory;
/** @template-implements IEventListener<Event|RenderReferenceEvent> */
class RenderReferenceEventListener implements IEventListener {
	public function __construct(
		private IReferenceManager $manager,
		private IInitialStateService $initialStateService,
		private ICacheFactory $cacheFactory,
	) {
	}

	public static function register(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(RenderReferenceEvent::class, RenderReferenceEventListener::class);
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof RenderReferenceEvent)) {
			return;
		}

		$cache = $this->cacheFactory->createLocal('reference-provider-list');

		$jsonProviders = $cache->get('providers');
		$timestamps = $cache->get('timestamps');

		if ($jsonProviders === null || $timestamps === null) {
			$providers = $this->manager->getDiscoverableProviders();
			$jsonProviders = array_map(static function (IDiscoverableReferenceProvider $provider) {
				return $provider->jsonSerialize();
			}, $providers);
			$cache->set('providers', $jsonProviders, 24 * 3600);

			$timestamps = $this->manager->getUserProviderTimestamps();
			$cache->set('timestamps', $timestamps, 24 * 3600);
		}

		$this->initialStateService->provideInitialState('core', 'reference-provider-list', $jsonProviders);
		$this->initialStateService->provideInitialState('core', 'reference-provider-timestamps', $timestamps);
	}
}
