<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
				InputArgument::REQUIRED,
				'user to add to the group'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$group = $this->groupManager->get($input->getArgument('group'));
		if (is_null($group)) {
			$output->writeln('<error>group not found</error>');
			return 1;
		}
		$user = $this->userManager->get($input->getArgument('user'));
		if (is_null($user)) {
			$output->writeln('<error>user not found</error>');
			return 1;
		}
		$group->addUser($user);
		return 0;
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
			$users = array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
			return array_diff($users, $members);
		}
		return [];
	}
}
