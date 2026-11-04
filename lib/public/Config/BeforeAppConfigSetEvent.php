<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * Emitted before an admin sets an app config value via occ or the provisioning API.
 * Listeners can refuse the change with setValid(false).
 *
 * @since 36.0.0
 */
#[Listenable(since: '36.0.0')]
class BeforeAppConfigSetEvent extends Event {
	protected bool $valid = true;

	/**
	 * @since 36.0.0
	 */
	public function __construct(
		protected string $appId,
		protected string $configKey,
		protected string $configValue,
	) {
		parent::__construct();
	}

	/**
	 * @since 36.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @since 36.0.0
	 */
	public function getConfigKey(): string {
		return $this->configKey;
	}

	/**
	 * @since 36.0.0
	 */
	public function getConfigValue(): string {
		return $this->configValue;
	}

	/**
	 * @since 36.0.0
	 */
	public function isRejected(): bool {
		return !$this->valid;
	}

	/**
	 * @since 36.0.0
	 */
	public function setValid(bool $valid): void {
		$this->valid = $valid;
	}
}
