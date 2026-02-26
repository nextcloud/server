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
 * Event triggered when updated a tag.
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
class TagUpdatedEvent extends AbstractTagEvent {
	public function __construct(
		ISystemTag $tag,
		private readonly ISystemTag $beforeTag,
	) {
		parent::__construct($tag);
	}

	/**
	 * Return the tag state before it was updated.
	 *
	 * @since 34.0.0
	 */
	public function getTagBefore(): ISystemTag {
		return $this->beforeTag;
	}
}
