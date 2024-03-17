<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\Files_External\Settings;

use OCA\Files_External\Service\BackendService;
use OCP\Util;

trait CommonSettingsTrait {
	protected BackendService $backendService;

	/**
	 * Load the frontend script including the custom backend dependencies
	 */
	protected function loadScriptsAndStyles() {
		Util::addScript('files_external', 'settings');
		Util::addStyle('files_external', 'settings');

		// load custom JS
		foreach ($this->backendService->getAvailableBackends() as $backend) {
			foreach ($backend->getCustomJs() as $script) {
				Util::addScript('files_external', $script);
			}
		}
	
		foreach ($this->backendService->getAuthMechanisms() as $authMechanism) {
			foreach ($authMechanism->getCustomJs() as $script) {
				Util::addScript('files_external', $script);
			}
		}
	}
}
