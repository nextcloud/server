<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Permission;

use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISharePermissionPreset {
	/**
	 * Returns a user friendly display name for this permission preset.
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Returns a user friendly hint for this permission preset.
	 *
	 * @return ?non-empty-string
	 * @experimental 35.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;
}
