<?php

declare(strict_types=1);

/**
 * @copyright 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\LDAPProviderFactory;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class UnsetDefaultProvider implements IRepairStep {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName(): string {
		return 'Unset default LDAP provider';
	}

	public function run(IOutput $output): void {
		$current = $this->config->getSystemValue('ldapProviderFactory', null);
		if ($current === LDAPProviderFactory::class) {
			$this->config->deleteSystemValue('ldapProviderFactory');
		}
	}
}
