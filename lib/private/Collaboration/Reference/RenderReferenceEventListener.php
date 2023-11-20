<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
