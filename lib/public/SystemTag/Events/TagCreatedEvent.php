<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemTag\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\SystemTag\ISystemTag;

/**
 * Event triggered when creating a new tag.
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
class TagCreatedEvent extends AbstractTagEvent {
	public function __construct(ISystemTag $tag) {
		parent::__construct($tag);
	}
}
