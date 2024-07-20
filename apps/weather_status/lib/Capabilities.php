<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
