<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorBackupCodes\Settings;

use OCA\TwoFactorBackupCodes\AppInfo\Application;
use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Server;
use OCP\Template\ITemplate;
use OCP\Template\ITemplateManager;
use OCP\Util;

class Personal implements IPersonalProviderSettings {
	public function getBody(): ITemplate {
		Util::addScript(Application::APP_ID, 'settings-personal');
		Util::addStyle(Application::APP_ID, 'settings-personal');
		return Server::get(ITemplateManager::class)
			->getTemplate('twofactor_backupcodes', 'personal');
	}
}
