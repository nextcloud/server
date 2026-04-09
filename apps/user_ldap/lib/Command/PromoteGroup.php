<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Group_Proxy;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PromoteGroup extends Command {

	public function __construct(
		private IGroupManager $groupManager,
		private Group_Proxy $backend,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:promote-group')
			->setDescription('declares the specified group as admin group (only one is possible per LDAP configuration)')
			->addArgument(
				'group',
				InputArgument::REQUIRED,
				'the group ID in Nextcloud or a group name'
			)
			->addOption(
				'yes',
				'y',
				InputOption::VALUE_NONE,
				'do not ask for confirmation'
			);
	}

	protected function formatGroupName(IGroup $group): string {
		$idLabel = '';
		if ($group->getGID() !== $group->getDisplayName()) {
			$idLabel = sprintf(' (Group ID: %s)', $group->getGID());
		}
		return sprintf('%s%s', $group->getDisplayName(), $idLabel);
	}

	protected function promoteGroup(IGroup $group, InputInterface $input, OutputInterface $output): void {
		$access = $this->backend->getLDAPAccess($group->getGID());
		$currentlyPromotedGroupId = $access->connection->ldapAdminGroup;
		if ($currentlyPromotedGroupId === $group->getGID()) {
			$output->writeln('<info>The specified group is already promoted</info>');
			return;
		}

		if ($input->getOption('yes') === false) {
			$currentlyPromotedGroup = $this->groupManager->get($currentlyPromotedGroupId);
			$demoteLabel = '';
			if ($currentlyPromotedGroup instanceof IGroup && $this->backend->groupExists($currentlyPromotedGroup->getGID())) {
				$groupNameLabel = $this->formatGroupName($currentlyPromotedGroup);
				$demoteLabel = sprintf('and demote %s ', $groupNameLabel);
			}

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$q = new Question(sprintf('Promote %s to the admin group %s(y|N)? ', $this->formatGroupName($group), $demoteLabel));
			$input->setOption('yes', $helper->ask($input, $output, $q) === 'y');
		}
		if ($input->getOption('yes') === true) {
			$access->connection->setConfiguration(['ldapAdminGroup' => $group->getGID()]);
			$access->connection->saveConfiguration();
			$output->writeln(sprintf('<info>Group %s was promoted</info>', $group->getDisplayName()));
		} else {
			$output->writeln('<comment>Group promotion cancelled</comment>');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$groupInput = (string)$input->getArgument('group');
		$group = $this->groupManager->get($groupInput);

		if ($group instanceof IGroup && $this->backend->groupExists($group->getGID())) {
			$this->promoteGroup($group, $input, $output);
			return 0;
		}

		$groupCandidates = $this->backend->getGroups($groupInput, 20);
		foreach ($groupCandidates as $gidCandidate) {
			$group = $this->groupManager->get($gidCandidate);
			if ($group !== null
				&& $this->backend->groupExists($group->getGID()) // ensure it is an LDAP group
				&& ($group->getGID() === $groupInput
					|| $group->getDisplayName() === $groupInput)
			) {
				$this->promoteGroup($group, $input, $output);
				return 0;
			}
		}

		$output->writeln('<error>No matching group found</error>');
		return 1;
	}

}
