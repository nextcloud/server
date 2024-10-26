<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config;

use OCP\EventDispatcher\Event;

/**
 * @since 25.0.0
 */
class BeforePreferenceSetEvent extends Event {
	protected string $userId;
	protected string $appId;
	protected string $configKey;
	protected string $configValue;
	protected bool $valid = false;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $userId, string $appId, string $configKey, string $configValue) {
		parent::__construct();
		$this->userId = $userId;
		$this->appId = $appId;
		$this->configKey = $configKey;
		$this->configValue = $configValue;
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
	public function getConfigValue(): string {
		return $this->configValue;
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
