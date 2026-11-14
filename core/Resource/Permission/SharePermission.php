<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Resource\Permission;

use NCU\Resource\Permission\CorePermission;

final class SharePermission extends ACorePermission {
	#[\Override]
	public function getId(): string {
		return CorePermission::Share->value;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->getL10N()->t('Share');
	}
}
