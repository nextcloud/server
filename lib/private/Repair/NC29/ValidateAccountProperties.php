<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC29;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class ValidateAccountProperties implements IRepairStep {

	public function __construct(
		private IUserManager $userManager,
		private IAccountManager $accountManager,
		private LoggerInterface $logger,
	) {
	}

	public function getName(): string {
		return 'Validate account properties and store phone numbers in a known format for search';
	}

	public function run(IOutput $output): void {
		$numRemoved = 0;

		$this->userManager->callForSeenUsers(function (IUser $user) use (&$numRemoved) {
			$account = $this->accountManager->getAccount($user);
			while (true) {
				try {
					$this->accountManager->updateAccount($account);
					break;
				} catch (InvalidArgumentException $e) {
					if (in_array($e->getMessage(), IAccountManager::ALLOWED_PROPERTIES)) {
						$numRemoved++;
						$property = $account->getProperty($e->getMessage());
						$account->setProperty($property->getName(), '', $property->getScope(), IAccountManager::NOT_VERIFIED);
					} else {
						$this->logger->error('Error while sanitizing account property', ['exception' => $e, 'user' => $user->getUID()]);
						break;
					}
				}
			}
		});

		if ($numRemoved > 0) {
			$output->info('Cleaned ' . $numRemoved . ' invalid account property entries');
		}
	}
}
