<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\IConfig;
use OCP\ISession;

class SetUserTimezoneCommand extends ALoginCommand {
	/** @var IConfig */
	private $config;

	/** @var ISession */
	private $session;

	public function __construct(IConfig $config,
		ISession $session) {
		$this->config = $config;
		$this->session = $session;
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->getTimeZoneOffset() !== '' && $this->isValidTimezone($loginData->getTimeZone())) {
			$this->config->setUserValue(
				$loginData->getUser()->getUID(),
				'core',
				'timezone',
				$loginData->getTimeZone()
			);
			$this->session->set(
				'timezone',
				$loginData->getTimeZoneOffset()
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}

	private function isValidTimezone(?string $value): bool {
		return $value && in_array($value, \DateTimeZone::listIdentifiers());
	}
}
