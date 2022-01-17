<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Hinrich Mahler <nextcloud@mahlerhome.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Sharing\Settings;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	/** @var IConfig */
	private $config;
	/** @var IInitialState */
	private $initialState;
	/** @var string */
	private $userId;

	public function __construct(IConfig $config, IInitialState $initialState, string $userId) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->userId = $userId;
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
