<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Admin;

use OCA\Settings\AppInfo\Application;
use OCA\Settings\ResponseDefinitions;
use OCA\Settings\Service\PreviewSettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

/**
 * @psalm-import-type SettingsPreviewSettings from ResponseDefinitions
 */
class Previews implements IDelegatedSettings {
	public function __construct(
		private readonly PreviewSettingsService $service,
		private readonly IInitialState $initialState,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('previewSettings', $this->service->getSettings());
		Util::addStyle(Application::APP_ID, 'admin-settings-previews');
		Util::addScript(Application::APP_ID, 'admin-settings-previews');
		return new TemplateResponse(Application::APP_ID, 'settings/admin/previews');
	}

	#[\Override]
	public function getSection(): string {
		return 'previews';
	}

	#[\Override]
	public function getPriority(): int {
		return 10;
	}

	#[\Override]
	public function getName(): ?string {
		return null;
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
