<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\IdentityProof;

use OCP\Security\IdentityProof\Algorithm;
use OCP\Security\IdentityProof\IKey;

class Key implements IKey {
	public function __construct(
		#[\SensitiveParameter]
		private string $publicKey,
		#[\SensitiveParameter]
		private string $privateKey,
		private Algorithm $algorithm,
	) {
	}

	#[\Override]
	public function getAlgorithm(): Algorithm {
		return $this->algorithm;
	}

	#[\Override]
	public function getPrivate(): string {
		return $this->privateKey;
	}

	#[\Override]
	public function getPublic(): string {
		return $this->publicKey;
	}
}
