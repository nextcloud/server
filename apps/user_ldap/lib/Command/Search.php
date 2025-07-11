<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User_Proxy;
use OCP\IConfig;
use OCP\Server;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Search extends Command {
	public function __construct(
		protected IConfig $ocConfig,
		private User_Proxy $userProxy,
		private Group_Proxy $groupProxy,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:search')
			->setDescription('executes a user or group search')
			->addArgument(
				'search',
				InputArgument::REQUIRED,
				'the search string (can be empty)'
			)
			->addOption(
				'group',
				null,
				InputOption::VALUE_NONE,
				'searches groups instead of users'
			)
			->addOption(
				'offset',
				null,
				InputOption::VALUE_REQUIRED,
				'The offset of the result set. Needs to be a multiple of limit. defaults to 0.',
				'0'
			)
			->addOption(
				'limit',
				null,
				InputOption::VALUE_REQUIRED,
				'limit the results. 0 means no limit, defaults to 15',
				'15'
			)
		;
	}

	/**
	 * Tests whether the offset and limit options are valid
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function validateOffsetAndLimit(int $offset, int $limit): void {
		if ($limit < 0) {
			throw new \InvalidArgumentException('limit must be  0 or greater');
		}
		if ($offset < 0) {
			throw new \InvalidArgumentException('offset must be 0 or greater');
		}
		if ($limit === 0 && $offset !== 0) {
			throw new \InvalidArgumentException('offset must be 0 if limit is also set to 0');
		}
		if ($offset > 0 && ($offset % $limit !== 0)) {
			throw new \InvalidArgumentException('offset must be a multiple of limit');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$helper = Server::get(Helper::class);
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();

		$offset = (int)$input->getOption('offset');
		$limit = (int)$input->getOption('limit');
		$this->validateOffsetAndLimit($offset, $limit);

		if ($input->getOption('group')) {
			$proxy = $this->groupProxy;
			$getMethod = 'getGroups';
			$printID = false;
			// convert the limit of groups to null. This will show all the groups available instead of
			// nothing, and will match the same behaviour the search for users has.
			if ($limit === 0) {
				$limit = null;
			}
		} else {
			$proxy = $this->userProxy;
			$getMethod = 'getDisplayNames';
			$printID = true;
		}

		$result = $proxy->$getMethod($input->getArgument('search'), $limit, $offset);
		foreach ($result as $id => $name) {
			$line = $name . ($printID ? ' (' . $id . ')' : '');
			$output->writeln($line);
		}
		return self::SUCCESS;
	}
}
