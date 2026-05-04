<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing\Permission;

use OC\Core\AppInfo\Application;
use OCP\Constants;
use OCP\IAppConfig;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermissionPreset;

final class ReshareSharePermissionType implements ISharePermissionType {
	private ?IAppConfig $appConfig = null;

	private function getAppConfig(): IAppConfig {
		return $this->appConfig ??= Server::get(IAppConfig::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Share with others');
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
			SharePermissionPreset::Edit,
		];
	}

	#[\Override]
	public function getDefault(): bool {
		return ($this->getAppConfig()->getValueInt(Application::APP_ID, 'shareapi_default_permissions') & Constants::PERMISSION_SHARE) === Constants::PERMISSION_SHARE;
	}
}
