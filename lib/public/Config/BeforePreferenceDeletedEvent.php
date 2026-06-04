<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

#[Listenable(since: '25.0.0')]
class BeforePreferenceDeletedEvent extends Event {
	protected string $userId;
	protected string $appId;
	protected string $configKey;
	protected bool $valid = false;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $userId, string $appId, string $configKey) {
		parent::__construct();
		$this->userId = $userId;
		$this->appId = $appId;
		$this->configKey = $configKey;
	}

	/**
	 * @since 25.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @since 25.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @since 25.0.0
	 */
	public function getConfigKey(): string {
		return $this->configKey;
	}

	/**
	 * @since 25.0.0
	 */
	public function isValid(): bool {
		return $this->valid;
	}

	/**
	 * @since 25.0.0
	 */
	public function setValid(bool $valid): void {
		$this->valid = $valid;
	}
}
