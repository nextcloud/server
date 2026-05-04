<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\L10N\IFactory;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermissionPreset;

class TestSharePermissionType1 implements ISharePermissionType {
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): string {
		return 'hint ' . $this->getDisplayName($l10nFactory);
	}

	/**
	 * @return list<SharePermissionPreset>
	 */
	#[\Override]
	public function getPresets(): array {
		return [
			SharePermissionPreset::View,
			SharePermissionPreset::Edit,
		];
	}

	#[\Override]
	public function getDefault(): bool {
		return false;
	}
}
