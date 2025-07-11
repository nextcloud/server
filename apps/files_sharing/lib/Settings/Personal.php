<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Settings;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialState,
		private string $userId,
	) {
	}

	public function getForm(): TemplateResponse {
		$defaultAcceptSystemConfig = $this->config->getSystemValueBool('sharing.enable_share_accept', false) ? 'no' : 'yes';
		$shareFolderSystemConfig = $this->config->getSystemValue('share_folder', '/');
		$acceptDefault = $this->config->getUserValue($this->userId, Application::APP_ID, 'default_accept', $defaultAcceptSystemConfig) === 'yes';
		$enforceAccept = $this->config->getSystemValueBool('sharing.force_share_accept', false);
		$allowCustomDirectory = $this->config->getSystemValueBool('sharing.allow_custom_share_folder', true);
		$shareFolderDefault = $this->config->getUserValue($this->userId, Application::APP_ID, 'share_folder', $shareFolderSystemConfig);
		$this->initialState->provideInitialState('accept_default', $acceptDefault);
		$this->initialState->provideInitialState('enforce_accept', $enforceAccept);
		$this->initialState->provideInitialState('allow_custom_share_folder', $allowCustomDirectory);
		$this->initialState->provideInitialState('share_folder', $shareFolderDefault);
		$this->initialState->provideInitialState('default_share_folder', $shareFolderSystemConfig);
		return new TemplateResponse('files_sharing', 'Settings/personal');
	}

	public function getSection(): string {
		return 'sharing';
	}

	public function getPriority(): int {
		return 90;
	}
}
