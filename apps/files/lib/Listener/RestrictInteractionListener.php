<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Listener;

use OCP\Constants;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Resources\NodeResource;
use OCP\Interaction\RestrictInteractionEvent;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final class RestrictInteractionListener implements IEventListener {
	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->resource instanceof NodeResource && ($event->resource->getNodePermissions() & Constants::PERMISSION_READ) !== Constants::PERMISSION_READ) {
			throw new InteractionRestrictedException('No read permission on the node.');
		}
	}
}
