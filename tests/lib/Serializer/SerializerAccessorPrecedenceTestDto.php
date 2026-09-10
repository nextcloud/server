<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OCP\Serializer\Attribute\SerializationGroups;

class SerializerAccessorPrecedenceTestDto {
	public function __construct(
		// The property carries the attribute, the accessor sharing its derived name carries
		// none: the property's own attribute must still apply.
		#[SerializationGroups('basic')]
		public bool $active,
		// The property carries no attribute, the accessor sharing its derived name does: the
		// accessor must win, rather than the bare property silently blocking it.
		public bool $verified = false,
	) {
	}

	public function isActive(): bool {
		return $this->active;
	}

	#[SerializationGroups('basic')]
	public function isVerified(): bool {
		return $this->verified;
	}
}
