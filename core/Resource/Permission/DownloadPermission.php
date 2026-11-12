<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Resource\Permission;

use NCU\Resource\Permission\CorePermission;
use NCU\Resource\Permission\IPermission;
use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;

final class DownloadPermission implements IPermission {
	public function __construct(
		private readonly IFactory $l10nFactory,
	) {
	}

	#[\Override]
	public function getId(): string {
		return CorePermission::DOWNLOAD;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Download');
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getKindOf(): ?CorePermission {
		return CorePermission::Read;
	}
}
