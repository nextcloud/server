<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Settings;

use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Template;

class Personal implements IPersonalProviderSettings {
	public function getBody(): Template {
		return new Template('twofactor_backupcodes', 'personal');
	}
}
