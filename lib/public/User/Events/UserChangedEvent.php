<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 18.0.0
 */
#[Listenable(since: '18.0.0')]
#[Dispatchable(since: '18.0.0')]
class UserChangedEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly IUser $user,
		private readonly string $feature,
		private readonly mixed $value,
		private readonly mixed $oldValue = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 18.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 18.0.0
	 */
	public function getFeature(): string {
		return $this->feature;
	}

	/**
	 * @since 18.0.0
	 */
	public function getValue(): mixed {
		return $this->value;
	}

	/**
	 * @since 18.0.0
	 */
	public function getOldValue(): mixed {
		return $this->oldValue;
	}
}
