<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
