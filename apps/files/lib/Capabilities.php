<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OCP\Capabilities\ICapability;
use OCP\IConfig;

class Capabilities implements ICapability {

	protected IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{bigfilechunking: bool, blacklisted_files: array<mixed>, forbidden_filename_characters: array<string>}}
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'bigfilechunking' => true,
				'blacklisted_files' => (array)$this->config->getSystemValue('blacklisted_files', ['.htaccess']),
				'forbidden_filename_characters' => \OCP\Util::getForbiddenFileNameChars(),
			],
		];
	}
}
