<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Sharing\Permission\SharePermissionPreset;

final class TestSharePermissionType2 extends TestSharePermissionType1 {
	/**
	 * @return list<SharePermissionPreset>
	 */
	#[\Override]
	public function getPresets(): array {
		return [
			SharePermissionPreset::Edit,
		];
	}
}
