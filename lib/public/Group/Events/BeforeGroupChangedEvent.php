<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Events;

use OCP\AppFramework\Attribute\Implementable;
use OCP\EventDispatcher\Event;
use OCP\IGroup;

/**
 * @since 26.0.0
 */
#[Implementable(since: '26.0.0')]
class BeforeGroupChangedEvent extends Event {

	/**
	 * @since 26.0.0
	 */
	public function __construct(
		private readonly IGroup $group,
		private readonly string $feature,
		private readonly mixed $value,
		private readonly mixed $oldValue = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 26.0.0
	 */
	public function getGroup(): IGroup {
		return $this->group;
	}

	/**
	 * @since 26.0.0
	 */
	public function getFeature(): string {
		return $this->feature;
	}

	/**
	 * @since 26.0.0
	 */
	public function getValue(): mixed {
		return $this->value;
	}

	/**
	 * @since 26.0.0
	 */
	public function getOldValue(): mixed {
		return $this->oldValue;
	}
}
