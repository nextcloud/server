<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OCP\Serializer\Attribute\Groups;
use OCP\Serializer\Attribute\Ignore;
use OCP\Serializer\Attribute\SerializedName;
use OCP\Serializer\Attribute\SerializedPath;

class SerializerTestDto {
	public function __construct(
		#[Groups(['basic', 'detailed'])]
		#[SerializedName('full_name')]
		public string $name,
		#[Groups(['detailed'])]
		public string $address,
		#[Groups(['basic'])]
		#[SerializedPath('[meta][city]')]
		public string $city,
		#[Ignore]
		public string $secret = '',
	) {
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[Groups(['basic'])]
	public function isActive(): bool {
		return true;
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[SerializedName('user_score')]
	#[Groups(['detailed'])]
	public function getScore(): int {
		return 42;
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[Ignore]
	public function getSecretCode(): string {
		return 'nope';
	}
}
