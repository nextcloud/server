<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
