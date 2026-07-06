<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\Service\ExampleContactService;
use OCA\DAV\Service\ExampleEventService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;
use OCP\Util;

class ExampleContentSettings implements ISettings {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IInitialState $initialState,
		private readonly ExampleEventService $exampleEventService,
		private readonly ExampleContactService $exampleContactService,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('create_example_event', $this->exampleEventService->shouldCreateExampleEvent());
		$this->initialState->provideInitialState('has_custom_example_event', $this->exampleEventService->hasCustomExampleEvent());
		$this->initialState->provideInitialState('enableDefaultContact', $this->exampleContactService->isDefaultContactEnabled());
		$this->initialState->provideInitialState('hasCustomDefaultContact', $this->appConfig->getAppValueBool('hasCustomDefaultContact'));

		Util::addStyle(Application::APP_ID, 'settings-admin-example-content');
		Util::addScript(Application::APP_ID, 'settings-admin-example-content');
		return new TemplateResponse(Application::APP_ID, 'settings-admin-example-content');
	}

	#[\Override]
	public function getSection(): ?string {
		return 'groupware';
	}

	#[\Override]
	public function getPriority(): int {
		return 10;
	}
}
