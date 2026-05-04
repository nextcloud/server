<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Permission;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermissionPreset;

final class NodeReadSharePermissionType implements ISharePermissionType {
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('View files');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
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
		return true;
	}
}
