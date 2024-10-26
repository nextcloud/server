<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\IdentityProof;

class Key {
	public function __construct(
		private string $publicKey,
		private string $privateKey,
	) {
	}

	public function getPrivate(): string {
		return $this->privateKey;
	}

	public function getPublic(): string {
		return $this->publicKey;
	}
}
