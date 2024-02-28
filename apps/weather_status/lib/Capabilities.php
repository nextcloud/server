<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
namespace OCA\WeatherStatus;

use OCA\WeatherStatus\AppInfo\Application;

use OCP\Capabilities\ICapability;

/**
 * Class Capabilities
 *
 * @package OCA\UserStatus
 */
class Capabilities implements ICapability {

	/**
	 * Capabilities constructor.
	 *
	 */
	public function __construct() {
	}

	/**
	 * @return array{weather_status: array{enabled: bool}}
	 */
	public function getCapabilities() {
		return [
			Application::APP_ID => [
				'enabled' => true,
			],
		];
	}
}
