<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Interaction;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Server;
use RuntimeException;

/**
 * This event can be emitted to check if a user is allowed to perform an action, such that the receivers would become aware of the resources.
 * If the resources have a distinction between an owner and a initiator, the event must be emitted for each separately.
 * Emitters may omit one or multiple properties, if they are not known.
 * Emitters must call {@see isInteractionRestricted} instead of dispatching the event manually, to ensure exception handling and audit logging is done correctly.
 * Listeners may ignore any of the properties and only check the ones that are relevant to them.
 * Listeners must throw an {@see InteractionRestrictedException}, if they want to restrict the interaction.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class RestrictInteractionEvent extends Event {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $userId,
		private ?IUser $user,
		/** @var list<InteractionResource> $resources */
		public readonly array $resources,
		public readonly ?InteractionAction $action,
		/** @var list<InteractionReceiver> $receivers */
		public readonly array $receivers,
	) {
		parent::__construct();
	}

	/**
	 * @since 34.0.2
	 */
	public function getUser(): IUser {
		if ($this->user instanceof IUser) {
			return $this->user;
		}

		$user = Server::get(IUserManager::class)->get($this->userId);
		if ($user === null) {
			throw new RuntimeException('User does not exist: ' . $this->userId);
		}

		return $this->user = $user;
	}

	/**
	 * @return false|string Returns a user friendly translated message if the interaction is restricted.
	 * @since 34.0.2
	 */
	public function isInteractionRestricted(): false|string {
		$eventDispatcher = Server::get(IEventDispatcher::class);

		$params = [
			$this->action instanceof InteractionAction ? $this->action::class : '?',
			$this->userId,
			$this->resources === [] ? '?' : implode(', ', array_map(static fn (InteractionResource $resource): string => $resource->getID(), $this->resources)),
			$this->receivers === [] ? '?' : implode(', ', array_map(static fn (InteractionReceiver $receiver): string => $receiver->getID() ?? $receiver::class, $this->receivers)),
		];

		try {
			$eventDispatcher->dispatchTyped($this);

			$eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
				'Interaction %s from user %s on %s to %s is allowed.',
				$params,
			));

			return false;
		} catch (InteractionRestrictedException $interactionRestrictedException) {
			$eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
				'Interaction %s from user %s on %s to %s is restricted: ' . $interactionRestrictedException->getMessage(),
				$params,
			));

			return $interactionRestrictedException->getHint();
		}
	}
}
