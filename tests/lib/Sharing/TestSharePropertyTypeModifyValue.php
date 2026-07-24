<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Sharing\Property\ISharePropertyTypeModifyValue;

final class TestSharePropertyTypeModifyValue extends TestSharePropertyType1 implements ISharePropertyTypeModifyValue {

	#[\Override]
	public function getDefaultValue(): string {
		return 'modify-on-save';
	}

	#[\Override]
	public function modifyValueOnSave(?string $oldValue, ?string $newValue): ?string {
		if ($newValue === 'modify-on-save') {
			return 'modified-on-save';
		}

		if ($newValue === 'modify-on-save-old-value') {
			return $oldValue;
		}

		return $newValue;
	}

	#[\Override]
	public function modifyValueOnLoad(?string $value): ?string {
		if ($value === 'modify-on-load') {
			return 'modified-on-load';
		}

		return $value;
	}
}
