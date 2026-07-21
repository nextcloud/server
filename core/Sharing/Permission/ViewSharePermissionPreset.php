<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Permission;

use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Sharing\Permission\ISharePermissionPreset;

// TODO: Add integration test to check that all default values result in the view preset being selected for new shares
final class ViewSharePermissionPreset implements ISharePermissionPreset {
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Can view');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}
}
