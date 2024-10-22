<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUser extends Command {
	public function __construct(
		protected User_Proxy $backend,
		protected Helper $helper,
		protected DeletedUsersIndex $dui,
		protected UserMapping $mapping,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:check-user')
			->setDescription('checks whether a user exists on LDAP.')
			->addArgument(
				'ocName',
				InputArgument::REQUIRED,
				'the user name as used in Nextcloud, or the LDAP DN'
			)
			->addOption(
				'force',
				null,
				InputOption::VALUE_NONE,
				'ignores disabled LDAP configuration'
			)
			->addOption(
				'update',
				null,
				InputOption::VALUE_NONE,
				'syncs values from LDAP'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->assertAllowed($input->getOption('force'));
			$uid = $input->getArgument('ocName');
			if ($this->backend->getLDAPAccess($uid)->stringResemblesDN($uid)) {
				$username = $this->backend->dn2UserName($uid);
				if ($username !== false) {
					$uid = $username;
				}
			}
			$wasMapped = $this->userWasMapped($uid);
			$exists = $this->backend->userExistsOnLDAP($uid, true);
			if ($exists === true) {
				$output->writeln('The user is still available on LDAP.');
				if ($input->getOption('update')) {
					$this->updateUser($uid, $output);
				}
				return self::SUCCESS;
			}

			if ($wasMapped) {
				$this->dui->markUser($uid);
				$output->writeln('The user does not exists on LDAP anymore.');
				$output->writeln('Clean up the user\'s remnants by: ./occ user:delete "'
					. $uid . '"');
				return self::SUCCESS;
			}

			throw new \Exception('The given user is not a recognized LDAP user.');
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
	}

	/**
	 * checks whether a user is actually mapped
	 * @param string $ocName the username as used in Nextcloud
	 */
	protected function userWasMapped(string $ocName): bool {
		$dn = $this->mapping->getDNByName($ocName);
		return $dn !== false;
	}

	/**
	 * checks whether the setup allows reliable checking of LDAP user existence
	 * @throws \Exception
	 */
	protected function assertAllowed(bool $force): void {
		if ($this->helper->haveDisabledConfigurations() && !$force) {
			throw new \Exception('Cannot check user existence, because '
				. 'disabled LDAP configurations are present.');
		}

		// we don't check ldapUserCleanupInterval from config.php because this
		// action is triggered manually, while the setting only controls the
		// background job.
	}

	private function updateUser(string $uid, OutputInterface $output): void {
		try {
			$access = $this->backend->getLDAPAccess($uid);
			$attrs = $access->userManager->getAttributes();
			$user = $access->userManager->get($uid);
			$avatarAttributes = $access->getConnection()->resolveRule('avatar');
			$baseDn = $this->helper->DNasBaseParameter($user->getDN());
			$result = $access->search('objectclass=*', $baseDn, $attrs, 1, 0);
			foreach ($result[0] as $attribute => $valueSet) {
				$output->writeln('  ' . $attribute . ': ');
				foreach ($valueSet as $value) {
					if (in_array($attribute, $avatarAttributes)) {
						$value = '{ImageData}';
					}
					$output->writeln('    ' . $value);
				}
			}
			$access->batchApplyUserAttributes($result);
		} catch (\Exception $e) {
			$output->writeln('<error>Error while trying to lookup and update attributes from LDAP</error>');
		}
	}
}
