<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\OCS;

use OCP\Capabilities\ICapability;
use OCP\IConfig;
use OCP\IURLGenerator;

/**
 * Class Capabilities
 *
 * @package OC\OCS
 */
class CoreCapabilities implements ICapability {
	/**
	 * @param IConfig $config
	 */
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{
	 *     core: array{
	 *         pollinterval: int,
	 *         webdav-root: string,
	 *         reference-api: boolean,
	 *         reference-regex: string,
	 *         mod-rewrite-working: boolean,
	 *     },
	 * }
	 */
	public function getCapabilities(): array {
		return [
			'core' => [
				'pollinterval' => $this->config->getSystemValueInt('pollinterval', 60),
				'webdav-root' => $this->config->getSystemValueString('webdav-root', 'remote.php/webdav'),
				'reference-api' => true,
				'reference-regex' => IURLGenerator::URL_REGEX_NO_MODIFIERS,
				'mod-rewrite-working' => $this->config->getSystemValueBool('htaccess.IgnoreFrontController') || getenv('front_controller_active') === 'true',
			],
		];
	}
}
