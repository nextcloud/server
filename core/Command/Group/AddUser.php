<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Group;

use OC\Core\Command\Base;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddUser extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('group:adduser')
			->setDescription('add a user to a group')
			->addArgument(
				'group',
				InputArgument::REQUIRED,
				'group to add the user to'
			)->addArgument(
				'user',
				InputArgument::REQUIRED + InputArgument::IS_ARRAY,
				'users to add to the group',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$group = $this->groupManager->get($input->getArgument('group'));
		if (is_null($group)) {
			$output->writeln('<error>group not found</error>');
			return Base::FAILURE;
		}

		$allUsersFound = true;
		$noUserFound = true;
		$users = (array)$input->getArgument('user');
		foreach ($users as $userId) {
			$user = $this->userManager->get($userId);
			if (is_null($user)) {
				$output->writeln('<error>user ' . $userId . ' not found</error>');
				$allUsersFound = false;
				continue;
			}
			$noUserFound = false;
			$group->addUser($user);
			unset($user);
			$output->writeln('<info>user ' . $userId . ' added</info>');
		}

		if (!$allUsersFound && !$noUserFound) {
			$output->writeln('<error>Some users were not found, all others where added to the group.</error>');
			return Base::FAILURE;
		}

		if ($noUserFound) {
			return Base::FAILURE;
		}

		return Base::SUCCESS;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'group') {
			return array_map(static fn (IGroup $group) => $group->getGID(), $this->groupManager->search($context->getCurrentWord()));
		}
		if ($argumentName === 'user') {
			$groupId = $context->getWordAtIndex($context->getWordIndex() - 1);
			$group = $this->groupManager->get($groupId);
			if ($group === null) {
				return [];
			}

			$members = array_map(static fn (IUser $user) => $user->getUID(), $group->searchUsers($context->getCurrentWord()));
			$users = array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->searchDisplayName($context->getCurrentWord()));
			return array_diff($users, $members);
		}
		return [];
	}
}
