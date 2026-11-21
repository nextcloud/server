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
use OCP\IL10N;
use OCP\L10N\IFactory;

abstract class ACorePermission implements IPermission {
	public function __construct(
		private readonly IFactory $l10nFactory,
	) {
	}

	protected function getL10N(): IL10N {
		return $this->l10nFactory->get(Application::APP_ID);
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getKindOf(): ?CorePermission {
		return null;
	}
}
