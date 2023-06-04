<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Push;

use OCP\Push\IPushNotification;

/**
 * @since 28.0.0
 */
class PushNotification implements IPushNotification {

	private string $endpoint;
	private string $publicKey;
	private string $authToken;
	private string $payload;

	public function getEndpoint(): string {
		return $this->endpoint;
	}

	public function setEndpoint(string $endpoint): PushNotification {
		$this->endpoint = $endpoint;
		return $this;
	}

	public function getPublicKey(): string {
		return $this->publicKey;
	}

	public function setPublicKey(string $publicKey): PushNotification {
		$this->publicKey = $publicKey;
		return $this;
	}

	public function getAuthToken(): string {
		return $this->authToken;
	}

	public function setAuthToken(string $authToken): PushNotification {
		$this->authToken = $authToken;
		return $this;
	}

	public function getPayload(): string {
		return $this->payload;
	}

	public function setPayload(string $payload): PushNotification {
		$this->payload = $payload;
		return $this;
	}
}
