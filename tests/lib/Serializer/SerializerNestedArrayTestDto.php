<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

class SerializerNestedArrayTestDto {
	public function __construct(
		/** @var SerializerNestedItemTestDto[] */
		public array $items,
	) {
	}
}
