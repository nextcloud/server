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

/** @template-implements IEventListener<Event|RenderReferenceEvent> */
class RenderReferenceEventListener implements IEventListener {
	public function __construct(
		private IReferenceManager $manager,
		private IInitialStateService $initialStateService,
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

		$providers = $this->manager->getDiscoverableProviders();
		$jsonProviders = array_map(static function (IDiscoverableReferenceProvider $provider) {
			return $provider->jsonSerialize();
		}, $providers);
		$this->initialStateService->provideInitialState('core', 'reference-provider-list', $jsonProviders);

		$timestamps = $this->manager->getUserProviderTimestamps();
		$this->initialStateService->provideInitialState('core', 'reference-provider-timestamps', $timestamps);
	}
}
