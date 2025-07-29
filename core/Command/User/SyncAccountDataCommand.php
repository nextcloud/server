<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Backend\IGetDisplayNameBackend;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncAccountDataCommand extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IAccountManager $accountManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:sync-account-data')
			->setDescription('sync user backend data to accounts table for configured users')
			->addOption(
				'limit',
				'l',
				InputOption::VALUE_OPTIONAL,
				'Number of users to retrieve',
				'500'
			)->addOption(
				'offset',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Offset for retrieving users',
				'0'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$users = $this->userManager->searchDisplayName('', (int)$input->getOption('limit'), (int)$input->getOption('offset'));

		foreach ($users as $user) {
			$this->updateUserAccount($user, $output);
		}
		return 0;
	}

	private function updateUserAccount(IUser $user, OutputInterface $output): void {
		$changed = false;
		$account = $this->accountManager->getAccount($user);
		if ($user->getBackend() instanceof IGetDisplayNameBackend) {
			try {
				$displayNameProperty = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
			} catch (PropertyDoesNotExistException) {
				$displayNameProperty = null;
			}
			if (!$displayNameProperty || $displayNameProperty->getValue() !== $user->getDisplayName()) {
				$output->writeln($user->getUID() . ' - updating changed display name');
				$account->setProperty(
					IAccountManager::PROPERTY_DISPLAYNAME,
					$user->getDisplayName(),
					$displayNameProperty ? $displayNameProperty->getScope() : IAccountManager::SCOPE_PRIVATE,
					$displayNameProperty ? $displayNameProperty->getVerified() : IAccountManager::NOT_VERIFIED,
					$displayNameProperty ? $displayNameProperty->getVerificationData() : ''
				);
				$changed = true;
			}
		}

		if ($changed) {
			$this->accountManager->updateAccount($account);
			$output->writeln($user->getUID() . ' - account data updated');
		} else {
			$output->writeln($user->getUID() . ' - nothing to update');
		}
	}
}
