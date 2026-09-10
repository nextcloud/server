<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OCP\Serializer\Attribute\SerializationGroups;
use OCP\Serializer\Attribute\SerializationIgnore;
use OCP\Serializer\Attribute\SerializedName;
use OCP\Serializer\Attribute\SerializedPath;

class SerializerTestDto {
	public function __construct(
		#[SerializationGroups('basic', 'detailed')]
		#[SerializedName('full_name')]
		public string $name,
		#[SerializationGroups('detailed')]
		public string $address,
		#[SerializationGroups('basic')]
		#[SerializedPath('[meta][city]')]
		public string $city,
		#[SerializationIgnore]
		public string $secret = '',
	) {
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[SerializationGroups('basic')]
	public function isActive(): bool {
		return true;
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[SerializedName('user_score')]
	#[SerializationGroups('detailed')]
	public function getScore(): int {
		return 42;
	}

	/**
	 * Virtual attribute, not backed by a property
	 */
	#[SerializationIgnore]
	public function getSecretCode(): string {
		return 'nope';
	}
}
