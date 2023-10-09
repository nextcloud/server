<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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

namespace OC\SetupCheck;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Server;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\ISetupCheckManager;

class SetupCheckManager implements ISetupCheckManager {
	private Coordinator $coordinator;

	public function __construct(Coordinator $coordinator) {
		$this->coordinator = $coordinator;
	}

	public function runAll(): array {
		$results = [];
		$setupChecks = $this->coordinator->getRegistrationContext()->getSetupChecks();
		foreach ($setupChecks as $setupCheck) {
			/** @var ISetupCheck $setupCheckObject */
			$setupCheckObject = Server::get($setupCheck->getService());
			$setupResult = $setupCheckObject->run();
			$category = $setupCheckObject->getCategory();
			if (!isset($results[$category])) {
				$results[$category] = [];
			}
			$results[$category][$setupCheckObject->getName()] = $setupResult;
		}
		return $results;
	}
}
