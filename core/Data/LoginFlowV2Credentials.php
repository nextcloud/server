<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Data;

class LoginFlowV2Credentials implements \JsonSerializable {
	public function __construct(
		private string $server,
		private string $loginName,
		private string $appPassword,
	) {
	}

	/**
	 * @return string
	 */
	public function getServer(): string {
		return $this->server;
	}

	/**
	 * @return string
	 */
	public function getLoginName(): string {
		return $this->loginName;
	}

	/**
	 * @return string
	 */
	public function getAppPassword(): string {
		return $this->appPassword;
	}

	public function jsonSerialize(): array {
		return [
			'server' => $this->server,
			'loginName' => $this->loginName,
			'appPassword' => $this->appPassword,
		];
	}
}
