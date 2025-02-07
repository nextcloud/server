<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class ExampleContentSettings implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialState,
		private IAppManager $appManager,
	) {
	}

	public function getForm(): TemplateResponse {
		$enableDefaultContact = $this->config->getAppValue(Application::APP_ID, 'enableDefaultContact', 'no');
		$this->initialState->provideInitialState('enableDefaultContact', $enableDefaultContact);
		return new TemplateResponse(Application::APP_ID, 'settings-example-content');
	}
	public function getSection(): ?string {
		if (!$this->appManager->isEnabledForUser('contacts')) {
			return null;
		}

		return 'groupware';
	}

	public function getPriority(): int {
		return 10;
	}

}
