<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IConfig;

class Capabilities implements ICapability {
	public function __construct(
		private IConfig $config,
		private IAppManager $appManager,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{versioning: bool, version_labeling: bool, version_deletion: bool}}
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'versioning' => true,
				'version_labeling' => $this->config->getSystemValueBool('enable_version_labeling', true),
				'version_deletion' => $this->config->getSystemValueBool('enable_version_deletion', true),
			]
		];
	}
}
