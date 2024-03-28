<?php
/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 *
 */

namespace OCA\ContactsInteraction\Settings;

use OCA\ContactsInteraction\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {
	public function __construct(private IInitialState $initialState, private IConfig $config, private ?string $userId) {	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('generateContactsInteraction', $this->config->getUserValue($this->userId, Application::APP_ID, 'generateContactsInteraction', 'yes') === 'yes');
		Util::addScript(Application::APP_ID, 'settings-personal');
		return new TemplateResponse(Application::APP_ID, 'personal');
	}

	public function getSection(): string {
		return 'contacts';
	}

	/**
	 * @psalm-return 40
	 */
	public function getPriority(): int {
		return 40;
	}
}
