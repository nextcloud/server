<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Validator;

use OCP\Validator\Constraints\NotNull;

class ValidatorNotNullTestDto {
	public function __construct(
		#[NotNull]
		public ?string $value,
	) {
	}
}
