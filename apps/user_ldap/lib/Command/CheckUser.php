<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Service\CheckUserService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUser extends Command {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly CheckUserService $checkUserService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('ldap:check-user')
			->setDescription('checks whether a user exists on LDAP.')
			->addArgument(
				'ocName',
				InputArgument::OPTIONAL,
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
			->addOption(
				'all-seen-users',
				null,
				InputOption::VALUE_NONE,
				'sync all seen users instead of only one'
			)
			->addOption(
				'limit',
				null,
				InputOption::VALUE_REQUIRED,
				'limit the number of user to process for --all-seen-users'
			)
			->addOption(
				'offset',
				null,
				InputOption::VALUE_REQUIRED,
				'offset to apply for --all-seen-users',
				0
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			if (!$this->checkUserService->assertAllowed($input->getOption('force'))) {
				throw new \Exception('Cannot check user existence, because '
					. 'disabled LDAP configurations are present.');
			}

			$uid = $input->getArgument('ocName');

			if ($uid !== null) {
				return $this->checkUser($input, $output, $uid);
			} elseif ($input->getOption('all-seen-users')) {
				$offset = (int)$input->getOption('offset');
				$limit = $input->getOption('limit');
				if ($limit !== null) {
					$limit = (int)$limit;
				}
				$userIterator = $this->userManager->getSeenUsers($offset, $limit);
				foreach ($userIterator as $user) {
					try {
						$output->writeln('<info>Checking ' . $user->getUID() . '…</info>', OutputInterface::VERBOSITY_VERBOSE);
						$this->checkUser($input, $output, $user->getUID());
					} catch (\Exception $e) {
						$output->writeln('<error> ' . $user->getUID() . ': ' . $e->getMessage() . '</error>');
					}
				}
				$output->writeln('<info>Finished checking all seen users.</info>', OutputInterface::VERBOSITY_VERBOSE);
				return self::SUCCESS;
			} else {
				throw new \InvalidArgumentException('Either a user name or --all-seen-users is required');
			}
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
	}

	private function checkUser(InputInterface $input, OutputInterface $output, string $uid): int {
		$result = $this->checkUserService->checkUser($uid, $input->getOption('update'));
		if ($result['exists']) {
			$output->writeln('The user is still available on LDAP.');
		} elseif ($result['wasMapped']) {
			$output->writeln('The user does not exists on LDAP anymore.');
			$output->writeln('Clean up the user\'s remnants by: ./occ user:delete "' . $uid . '"');
		}
		if (isset($result['attributes'])) {
			foreach ($result['attributes'] as $attribute => $value) {
				$output->writeln('  ' . $attribute . ':     ' . $value);
			}
		}
		return self::SUCCESS;
	}
}
