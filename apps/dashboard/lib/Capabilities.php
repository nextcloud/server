<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard;

use OCP\Capabilities\ICapability;
use OCP\Capabilities\IFeature;

class Capabilities implements ICapability, IFeature {
	/**
	 * @return array{dashboard: array{enabled: bool}}
	 */
	public function getCapabilities(): array {
		return [
			'dashboard' => [
				'enabled' => true,
			],
		];
	}

	public function getFeatures(): array {
		return [
			'dashboard' => [
				'widgets-v1',
				'widget-items-v1',
				'widget-items-v2',
				'layout-v3',
				'statuses-v3',
			],
		];
	}
}
