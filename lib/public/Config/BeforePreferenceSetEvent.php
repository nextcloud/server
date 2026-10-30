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
class BeforePreferenceSetEvent extends Event {
	protected string $userId;
	protected string $appId;
	protected string $configKey;
	protected string $configValue;
	protected ?bool $valid = null;

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
		return $this->valid === true;
	}

	/**
	 * Whether a listener explicitly refused the change via setValid(false)
	 *
	 * Admin contexts (e.g. occ) only block the change when it was rejected,
	 * while user contexts require it to be valid.
	 *
	 * @since 36.0.0
	 */
	public function isRejected(): bool {
		return $this->valid === false;
	}

	/**
	 * @since 25.0.0
	 */
	public function setValid(bool $valid): void {
		$this->valid = $valid;
	}
}
