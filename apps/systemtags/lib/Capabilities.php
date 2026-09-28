<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\SystemTags;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	/**
	 * @return array{systemtags: array{enabled: true}}
	 */
	public function getCapabilities() {
		$capabilities = [
			'systemtags' => [
				'enabled' => true,
			]
		];
		return $capabilities;
	}
}
