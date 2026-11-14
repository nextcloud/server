<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Events;

use NCU\Resource\ResourceReference;
use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * Dispatched by the app owning a resource after the resource was deleted for good,
 * so that other apps can remove what they stored for it, e.g. restrictions or labels.
 *
 * @experimental 36.0.0
 */
#[Dispatchable(since: '36.0.0')]
#[Listenable(since: '36.0.0')]
final class ResourceDeletedEvent extends Event {
	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		public readonly ResourceReference $resource,
	) {
		parent::__construct();
	}
}
