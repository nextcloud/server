<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

final class TestSharePropertyTypeRequired extends TestSharePropertyType1 {
	#[\Override]
	public function getDefaultValue(): ?string {
		return $this->getValidValues()[0];
	}

	#[\Override]
	public function getRequired(): bool {
		return true;
	}
}
