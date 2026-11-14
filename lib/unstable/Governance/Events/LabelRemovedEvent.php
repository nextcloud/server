<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Events;

use NCU\Governance\LabelType;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\AppFramework\Attribute\ExceptionalImplementable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * Dispatched by the governance app after a label was removed from a resource, also when another label replaced it.
 * It is not dispatched when the label or the resource is deleted.
 *
 * @experimental 36.0.0
 */
#[Listenable(since: '36.0.0')]
#[ExceptionalImplementable(app: 'governance')]
final class LabelRemovedEvent extends Event {
	/**
	 * @param non-empty-string $labelId
	 * @experimental 36.0.0
	 */
	public function __construct(
		public readonly Actor $actor,
		public readonly ResourceReference $resource,
		public readonly string $labelId,
		public readonly LabelType $labelType,
	) {
		parent::__construct();
	}
}
