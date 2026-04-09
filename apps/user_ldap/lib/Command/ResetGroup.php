<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\GroupPluginManager;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ResetGroup extends Command {
	public function __construct(
		private IGroupManager $groupManager,
		private GroupPluginManager $pluginManager,
		private Group_Proxy $backend,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:reset-group')
			->setDescription('deletes an LDAP group independent of the group state in the LDAP')
			->addArgument(
				'gid',
				InputArgument::REQUIRED,
				'the group name as used in Nextcloud'
			)
			->addOption(
				'yes',
				'y',
				InputOption::VALUE_NONE,
				'do not ask for confirmation'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$gid = $input->getArgument('gid');
			$group = $this->groupManager->get($gid);
			if (!$group instanceof IGroup) {
				throw new \Exception('Group not found');
			}
			$backends = $group->getBackendNames();
			if (!in_array('LDAP', $backends)) {
				throw new \Exception('The given group is not a recognized LDAP group.');
			}
			if ($input->getOption('yes') === false) {
				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');
				$q = new Question('Delete all local data of this group (y|N)? ');
				$input->setOption('yes', $helper->ask($input, $output, $q) === 'y');
			}
			if ($input->getOption('yes') !== true) {
				throw new \Exception('Reset cancelled by operator');
			}

			// Disable real deletion if a plugin supports it
			$pluginManagerSuppressed = $this->pluginManager->setSuppressDeletion(true);
			// Bypass groupExists test to force mapping deletion
			$this->backend->getLDAPAccess($gid)->connection->writeToCache('groupExists' . $gid, false);
			echo "calling delete $gid\n";
			if ($group->delete()) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
				return self::SUCCESS;
			}
		} catch (\Throwable $e) {
			if (isset($pluginManagerSuppressed)) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
			}
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
		$output->writeln('<error>Error while resetting group</error>');
		return self::INVALID;
	}
}
