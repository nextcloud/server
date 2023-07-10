<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OC\Repair\NC21;

use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ValidatePhoneNumber implements IRepairStep {
	/** @var IConfig */
	protected $config;
	/** @var IUserManager */
	protected $userManager;
	/** @var IAccountManager */
	private $accountManager;

	public function __construct(IUserManager $userManager,
								IAccountManager $accountManager,
								IConfig $config) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
	}

	public function getName(): string {
		return 'Validate the phone number and store it in a known format for search';
	}

	public function run(IOutput $output): void {
		if ($this->config->getSystemValueString('default_phone_region', '') === '') {
			$output->warning('Can not validate phone numbers without `default_phone_region` being set in the config file');
			return;
		}

		$numUpdated = 0;
		$numRemoved = 0;

		$this->userManager->callForSeenUsers(function (IUser $user) use (&$numUpdated, &$numRemoved) {
			$account = $this->accountManager->getAccount($user);
			$property = $account->getProperty(IAccountManager::PROPERTY_PHONE);

			if ($property->getValue() !== '') {
				$this->accountManager->updateAccount($account);
				$updatedAccount = $this->accountManager->getAccount($user);
				$updatedProperty = $updatedAccount->getProperty(IAccountManager::PROPERTY_PHONE);

				if ($property->getValue() !== $updatedProperty->getValue()) {
					if ($updatedProperty->getValue() === '') {
						$numRemoved++;
					} else {
						$numUpdated++;
					}
				}
			}
		});

		if ($numRemoved > 0 || $numUpdated > 0) {
			$output->info('Updated ' . $numUpdated . ' entries and cleaned ' . $numRemoved . ' invalid phone numbers');
		}
	}
}
