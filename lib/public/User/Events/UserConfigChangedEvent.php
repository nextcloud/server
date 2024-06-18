<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Murena SAS <akhil.potukuchi.ext@murena.com>
 *
 * @author Murena SAS <akhil.potukuchi.ext@murena.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

class UserConfigChangedEvent extends Event {
	private string $userId;
	private string $appId;
	private string $key;
	private mixed $value;
	private mixed $oldValue;

	public function __construct(string $userId,
		string $appId,
		string $key,
		mixed $value,
		mixed $oldValue = null) {
		parent::__construct();
		$this->userId = $userId;
		$this->appId = $appId;
		$this->key = $key;
		$this->value = $value;
		$this->oldValue = $oldValue;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getAppId(): string {
		return $this->appId;
	}
	public function getKey(): string {
		return $this->key;
	}

	public function getValue() {
		return $this->value;
	}

	public function getOldValue() {
		return $this->oldValue;
	}
}
