<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Murena SAS <akhil.potukuchi.ext@murena.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/** @since 33.0.0 */
#[Listenable(since: '33.0.0')]
class UserConfigChangedEvent extends Event {
	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private string $userId,
		private string $appId,
		private string $key,
		private mixed $value,
		private mixed $oldValue = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 33.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @since 33.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @since 33.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @since 33.0.0
	 */
	public function getValue(): mixed {
		return $this->value;
	}

	/**
	 * @since 33.0.0
	 */
	public function getOldValue(): mixed {
		return $this->oldValue;
	}
}
