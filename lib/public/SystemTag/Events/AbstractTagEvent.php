<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\SystemTag\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;
use OCP\SystemTag\ISystemTag;

/**
 * Abstract event related to the lifecycle of a tag.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
abstract class AbstractTagEvent extends Event {
	/**
	 * AbstractTagEvent constructor
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly ISystemTag $tag,
	) {
	}

	/**
	 * @since 35.0.0
	 */
	public function getTag(): ISystemTag {
		return $this->tag;
	}
}
