<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\AppInfo;

use OCP\Capabilities\ICapability;
use OCP\Config\IUserConfig;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Server;

class Capabilities implements ICapability {

	public function __construct(
		private IUserSession $session,
		private IUserConfig $userConfig,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * Return the core capabilities
	 *
	 * @return array{core: array{'user'?: array{language: string, locale: string, timezone: string} } }
	 */
	public function getCapabilities(): array {
		$capabilities = [];

		$user = $this->session->getUser();
		if ($user !== null) {
			$timezone = Server::get(IDateTimeZone::class)->getTimeZone();

			$capabilities['user'] = [
				'language' => $this->userConfig->getValueString($user->getUID(), Application::APP_ID, ConfigLexicon::USER_LANGUAGE),
				'locale' => $this->userConfig->getValueString($user->getUID(), Application::APP_ID, ConfigLexicon::USER_LOCALE),
				'timezone' => $timezone->getName(),
			];
		}

		return [
			'core' => $capabilities,
		];
	}
}
