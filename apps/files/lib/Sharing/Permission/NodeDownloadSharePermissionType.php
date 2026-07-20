<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Permission;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\Permission\ISharePermissionType;

final class NodeDownloadSharePermissionType implements ISharePermissionType {
	private ?IManager $legacyManager = null;

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Download files');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		// If previews are still allowed, the download option is only hidden, because on a technical level it is still possible to download.
		if ($this->getLegacyManager()->allowViewWithoutDownload()) {
			return $l10nFactory->get(Application::APP_ID)->t('When disabled, the option to download will be hidden');
		}

		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 40;
	}

	#[\Override]
	public function isEnabledByDefault(): bool {
		return false;
	}
}
