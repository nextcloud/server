<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Settings\AccountWarnings;

use OCP\Settings\IAccountWarningsProvider;
use OC\AppFramework\Bootstrap\Coordinator;

class AccountWarningsManager {
	public function __construct(
		private Coordinator $coordinator,
	) {
	}

	/**
	 * @return array<class-string, array{name:string,warnings:array<string,string>}>
	 */
	public function getAll(): array {
		$results = [];
		$providerRegistrations = $this->coordinator->getRegistrationContext()->getAccountWarningsProviders();
		foreach ($providerRegistrations as $providerRegistration) {
			/** @var IAccountWarningsProvider $provider */
			$provider = \OCP\Server::get($providerRegistration->getService());
			$warnings = $provider->getAccountWarnings();
			$results[$provider::class] = ['name' => $provider->getName(),'warnings' => []];
			foreach ($warnings as $warning) {
				$category = $warning->getSeverity();
				if (!isset($results[$provider::class]['warnings'][$category])) {
					$results[$provider::class]['warnings'][$category] = [];
				}
				$results[$provider::class]['warnings'][$category][] = $warning->getText();
			}
		}
		return $results;
	}
}
