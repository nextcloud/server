<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Data;

class LoginFlowV2Tokens {
	public function __construct(
		private string $loginToken,
		private string $pollToken,
	) {
	}

	public function getPollToken(): string {
		return $this->pollToken;
	}

	public function getLoginToken(): string {
		return $this->loginToken;
	}
}
