<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Settings;

use OCA\SystemTags\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\Settings\ISettings;
use OCP\Util;
use Override;

class Admin implements ISettings {

	public function __construct(
		private IAppConfig $appConfig,
		private IInitialState $initialStateService,
	) {
	}

	#[Override]
	public function getForm(): TemplateResponse {
		$restrictSystemTagsCreationToAdmin = $this->appConfig->getValueBool(Application::APP_ID, 'restrict_creation_to_admin', false);
		$this->initialStateService->provideInitialState('restrictSystemTagsCreationToAdmin', $restrictSystemTagsCreationToAdmin);

		Util::addScript('systemtags', 'admin');
		return new TemplateResponse('systemtags', 'admin', [], '');
	}

	#[Override]
	public function getSection(): string {
		return 'server';
	}

	#[Override]
	public function getPriority(): int {
		return 70;
	}
}
