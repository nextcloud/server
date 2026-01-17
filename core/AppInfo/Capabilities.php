<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\AppInfo;

use OCP\Capabilities\ICapability;
use OCP\Config\IUserConfig;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IUserSession;
use OCP\Server;

class Capabilities implements ICapability {

	public function __construct(
		private IUserSession $userSession,
		private IUserConfig $userConfig,
		private IConfig $serverConfig,
	) {
	}

	/**
	 * Return the core capabilities
	 *
	 * @return array{core: array{'user'?: array{language: string, locale: string, timezone: string}, 'can-create-app-token'?: bool } }
	 */
	public function getCapabilities(): array {
		$capabilities = [];

		$user = $this->userSession->getUser();
		if ($user !== null) {
			$timezone = Server::get(IDateTimeZone::class)->getTimeZone();

			$capabilities['user'] = [
				'language' => $this->userConfig->getValueString($user->getUID(), Application::APP_ID, ConfigLexicon::USER_LANGUAGE),
				'locale' => $this->userConfig->getValueString($user->getUID(), Application::APP_ID, ConfigLexicon::USER_LOCALE),
				'timezone' => $timezone->getName(),
			];

			$capabilities['can-create-app-token'] = $this->userSession->getImpersonatingUserID() === null
				&& $this->serverConfig->getSystemValueBool('auth_can_create_app_token', true);
		}

		return [
			'core' => $capabilities,
		];
	}
}
