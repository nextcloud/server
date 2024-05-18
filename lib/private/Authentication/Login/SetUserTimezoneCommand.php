<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
