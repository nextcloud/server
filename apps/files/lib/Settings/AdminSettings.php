<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Settings;

use OCA\Files\AppInfo\Application;
use OCA\Files\Service\SettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {

	public function __construct(
		private IL10N $l,
		private SettingsService $service,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialState,
	) {
	}

	public function getSection(): string {
		return 'server';
	}

	public function getPriority(): int {
		return 10;
	}

	public function getForm(): TemplateResponse {
		$windowSupport = $this->service->hasFilesWindowsSupport();
		$this->initialState->provideInitialState('filesCompatibilitySettings', [
			'docUrl' => $this->urlGenerator->linkToDocs(''),
			'status' => $this->service->getSanitizationStatus(),
			'windowsSupport' => $windowSupport,
		]);

		Util::addScript(Application::APP_ID, 'settings-admin');
		return new TemplateResponse(Application::APP_ID, 'settings-admin', renderAs: TemplateResponse::RENDER_AS_BLANK);
	}
}
