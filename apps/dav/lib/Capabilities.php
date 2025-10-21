<?php

/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV;

use OCP\Capabilities\ICapability;
use OCP\IConfig;
use OCP\User\IAvailabilityCoordinator;

class Capabilities implements ICapability {
	public function __construct(
		private IConfig $config,
		private IAvailabilityCoordinator $coordinator,
	) {
	}

	/**
	 * @return array{dav: array{chunking: string, public_shares_chunking: bool, bulkupload?: string, absence-supported?: bool, absence-replacement?: bool}}
	 */
	public function getCapabilities() {
		$capabilities = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => false,
			]
		];
		if ($this->config->getSystemValueBool('bulkupload.enabled', true)) {
			$capabilities['dav']['bulkupload'] = '1.0';
		}
		if ($this->coordinator->isEnabled()) {
			$capabilities['dav']['absence-supported'] = true;
			$capabilities['dav']['absence-replacement'] = true;
		}
		return $capabilities;
	}
}
