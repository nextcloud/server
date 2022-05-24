<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class AvailabilitySettings implements ISettings {
	protected IUserSession $userSession;
	protected IConfig $config;
	protected IInitialState $initialState;

	public function __construct(IUserSession $userSession,
								IConfig $config,
								IInitialState $initialState) {
		$this->userSession = $userSession;
		$this->config = $config;
		$this->initialState = $initialState;
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'user_status_automation',
			$this->config->getUserValue(
				$this->userSession->getUser()->getUID(),
				'dav',
				'user_status_automation',
				'no'
			)
		);

		return new TemplateResponse(Application::APP_ID, 'settings-personal-availability');
	}

	public function getSection(): string {
		return 'groupware';
	}

	public function getPriority(): int {
		return 10;
	}
}
