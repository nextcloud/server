<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use NCU\Sharing\Share;

final class TestSharePropertyTypeRequired extends TestSharePropertyType1 {
	#[\Override]
	public function getDefaultValue(Share $share): ?string {
		return $this->getValidValues()[0];
	}

	#[\Override]
	public function isRequired(Share $share): bool {
		return true;
	}
}
