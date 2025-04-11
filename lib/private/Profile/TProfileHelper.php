<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profile;

use OCP\IConfig;

trait TProfileHelper {
	protected function isProfileEnabledByDefault(IConfig $config): ?bool {
		return filter_var(
			$config->getAppValue('settings', 'profile_enabled_by_default', '1'),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);
	}
}
