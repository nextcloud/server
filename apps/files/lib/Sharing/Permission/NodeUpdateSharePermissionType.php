<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Permission;

use OCA\Files\AppInfo\Application;
use OCP\Constants;
use OCP\IAppConfig;
use OCP\L10N\IFactory;
use OCP\Sharing\Permission\ISharePermissionType;

final readonly class NodeUpdateSharePermissionType implements ISharePermissionType {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Edit files');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 70;
	}

	#[\Override]
	public function isEnabledByDefault(): bool {
		return ($this->appConfig->getValueInt(\OC\Core\AppInfo\Application::APP_ID, 'shareapi_default_permissions') & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE;
	}
}
