<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
				$io->success('Removed delegation of ' . $settingClass . ' to ' . $groupId . '.');
				return 0;
			}
		}

		$io->success('Group ' . $groupId . ' didn’t have delegation for ' . $settingClass . '.');

		return 0;
	}
}
