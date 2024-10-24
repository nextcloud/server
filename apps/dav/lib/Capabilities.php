<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV;

use OCP\Capabilities\ICapability;
use OCP\IConfig;

class Capabilities implements ICapability {
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @return array{dav: array{chunking: string, bulkupload?: string}}
	 */
	public function getCapabilities() {
		$capabilities = [
			'dav' => [
				'chunking' => '1.0',
			]
		];
		if ($this->config->getSystemValueBool('bulkupload.enabled', true)) {
			$capabilities['dav']['bulkupload'] = '1.0';
		}
		return $capabilities;
	}
}
