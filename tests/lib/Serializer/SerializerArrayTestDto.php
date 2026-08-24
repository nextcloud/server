<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OCP\Serializer\Attribute\Groups;

class SerializerArrayTestDto {
	public function __construct(
		/** @var string[] */
		#[Groups(['basic'])]
		public array $tags,
	) {
	}
}
