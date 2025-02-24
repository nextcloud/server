<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC29;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class SanitizeAccountPropertiesJob extends QueuedJob {

	private const PROPERTIES_TO_CHECK = [
		IAccountManager::PROPERTY_PHONE,
		IAccountManager::PROPERTY_WEBSITE,
		IAccountManager::PROPERTY_TWITTER,
		IAccountManager::PROPERTY_FEDIVERSE,
	];

	public function __construct(
		ITimeFactory $timeFactory,
		private IUserManager $userManager,
		private IAccountManager $accountManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setAllowParallelRuns(false);
	}

	protected function run(mixed $argument): void {
		$numRemoved = 0;

		$this->userManager->callForSeenUsers(function (IUser $user) use (&$numRemoved) {
			$account = $this->accountManager->getAccount($user);
			$properties = array_keys($account->jsonSerialize());

			// Check if there are some properties we can sanitize - reduces number of db queries
			if (empty(array_intersect($properties, self::PROPERTIES_TO_CHECK))) {
				return;
			}

			// Limit the loop to the properties we check to ensure there are no infinite loops
			// we add one additional loop (+ 1) as we need 1 loop for checking + 1 for update.
			$iteration = count(self::PROPERTIES_TO_CHECK) + 1;
			while ($iteration-- > 0) {
				try {
					$this->accountManager->updateAccount($account);
					return;
				} catch (InvalidArgumentException $e) {
					if (in_array($e->getMessage(), IAccountManager::ALLOWED_PROPERTIES)) {
						$numRemoved++;
						$property = $account->getProperty($e->getMessage());
						$account->setProperty($property->getName(), '', $property->getScope(), IAccountManager::NOT_VERIFIED);
					} else {
						$this->logger->error('Error while sanitizing account property', ['exception' => $e, 'user' => $user->getUID()]);
						return;
					}
				}
			}
			$this->logger->error('Iteration limit exceeded while cleaning account properties', ['user' => $user->getUID()]);
		});

		if ($numRemoved > 0) {
			$this->logger->info('Cleaned ' . $numRemoved . ' invalid account property entries');
		}
	}
}
