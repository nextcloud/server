<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Settings;

use OCA\Files\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

class PersonalSettings implements ISettings {
	#[\Override]
	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, 'settings-personal');
		return new TemplateResponse(Application::APP_ID, 'settings-personal');
	}

	#[\Override]
	public function getSection(): string {
		return 'sharing';
	}

	#[\Override]
	public function getPriority(): int {
		return 90;
	}
}
