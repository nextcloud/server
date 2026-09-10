<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OCP\Serializer\Attribute\SerializedName;

/**
 * A property and an accessor method, both attributed, resolving to the same attribute name
 * ("name") - the accessor is expected to win
 */
class SerializerCollisionTestDto {
	public function __construct(
		#[SerializedName('property_name')]
		public string $name,
	) {
	}

	#[SerializedName('accessor_name')]
	public function getName(): string {
		return $this->name;
	}
}
