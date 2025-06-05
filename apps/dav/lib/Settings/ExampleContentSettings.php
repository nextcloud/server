<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\Service\ExampleEventService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Settings\ISettings;

class ExampleContentSettings implements ISettings {
	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IInitialState $initialState,
		private readonly IAppManager $appManager,
		private readonly ExampleEventService $exampleEventService,
	) {
	}

	public function getForm(): TemplateResponse {
		$calendarEnabled = $this->appManager->isEnabledForUser('calendar');
		$contactsEnabled = $this->appManager->isEnabledForUser('contacts');
		$this->initialState->provideInitialState('calendarEnabled', $calendarEnabled);
		$this->initialState->provideInitialState('contactsEnabled', $contactsEnabled);

		if ($calendarEnabled) {
			$enableDefaultEvent = $this->exampleEventService->shouldCreateExampleEvent();
			$this->initialState->provideInitialState('create_example_event', $enableDefaultEvent);
			$this->initialState->provideInitialState(
				'has_custom_example_event',
				$this->exampleEventService->hasCustomExampleEvent(),
			);
		}

		if ($contactsEnabled) {
			$enableDefaultContact = $this->config->getAppValue(Application::APP_ID, 'enableDefaultContact', 'no');
			$this->initialState->provideInitialState('enableDefaultContact', $enableDefaultContact);
			$this->initialState->provideInitialState(
				'hasCustomDefaultContact',
				$this->appConfig->getValueBool(Application::APP_ID, 'hasCustomDefaultContact'),
			);
		}

		return new TemplateResponse(Application::APP_ID, 'settings-example-content');
	}

	public function getSection(): ?string {
		if (!$this->appManager->isEnabledForUser('contacts')
				&& !$this->appManager->isEnabledForUser('calendar')) {
			return null;
		}

		return 'groupware';
	}

	public function getPriority(): int {
		return 10;
	}
}
