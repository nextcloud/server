<?php

declare(strict_types = 1);
/**
 * @copyright Copyright (c) 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
namespace OCA\Settings\Command\AdminDelegation;

use OC\Core\Command\Base;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\IGroupManager;
use OCP\Settings\IManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Remove extends Base {
	public function __construct(
		private IManager $settingManager,
		private AuthorizedGroupService $authorizedGroupService,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('admin-delegation:remove')
			->setDescription('remove settings delegation from a group')
			->addArgument('settingClass', InputArgument::REQUIRED, 'Admin setting class')
			->addArgument('groupId', InputArgument::REQUIRED, 'Group ID to remove')
			->addUsage('\'OCA\Settings\Settings\Admin\Server\' mygroup')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);
		$settingClass = $input->getArgument('settingClass');
		$groups = $this->authorizedGroupService->findExistingGroupsForClass($settingClass);
		$groupId = $input->getArgument('groupId');
		foreach ($groups as $group) {
			if ($group->getGroupId() === $groupId) {
				$this->authorizedGroupService->delete($group->getId());
				$io->success('Removed delegation of '.$settingClass.' to '.$groupId.'.');
				return 0;
			}
		}

		$io->success('Group '.$groupId.' didn’t have delegation for '.$settingClass.'.');

		return 0;
	}
}
