<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\OCS;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

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
		private IAppManager $appManager,
		private IUserSession $userSession,
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
	 *         enabled-apps: list<string>,
	 *     },
	 * }
	 */
	public function getCapabilities(): array {
		$capabilities = [
			'core' => [
				'pollinterval' => $this->config->getSystemValueInt('pollinterval', 60),
				'webdav-root' => $this->config->getSystemValueString('webdav-root', 'remote.php/webdav'),
				'reference-api' => true,
				'reference-regex' => IURLGenerator::URL_REGEX_NO_MODIFIERS,
				'mod-rewrite-working' => $this->config->getSystemValueBool('htaccess.IgnoreFrontController') || getenv('front_controller_active') === 'true',
			],
		];

		$user = $this->userSession->getUser();
		if ($user === null) {
			$capabilities['core']['enabled-apps'] = $this->appManager->getEnabledApps();
		} else {
			$capabilities['core']['enabled-apps'] = $this->appManager->getEnabledAppsForUser($user);
		}

		return $capabilities;
	}
}
