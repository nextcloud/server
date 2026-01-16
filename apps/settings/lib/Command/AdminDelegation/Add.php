<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Command\AdminDelegation;

use OC\Core\Command\Base;
use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\ConflictException;
use OCP\IGroupManager;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Add extends Base {
	public function __construct(
		private IManager $settingManager,
		private AuthorizedGroupService $authorizedGroupService,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('admin-delegation:add')
			->setDescription('add setting delegation to a group')
			->addArgument('settingClass', InputArgument::REQUIRED, 'Admin setting class')
			->addArgument('groupId', InputArgument::REQUIRED, 'Delegate to group ID')
			->addUsage('\'OCA\Settings\Settings\Admin\Server\' mygroup')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);
		$settingClass = $input->getArgument('settingClass');
		if (!in_array(IDelegatedSettings::class, (array)class_implements($settingClass), true)) {
			$io->error('The specified class isn’t a valid delegated setting.');
			return 2;
		}

		$groupId = $input->getArgument('groupId');
		if (!$this->groupManager->groupExists($groupId)) {
			$io->error('The specified group didn’t exist.');
			return 3;
		}

		try {
			$this->authorizedGroupService->create($groupId, $settingClass);
		} catch (ConflictException) {
			$io->warning('Administration of ' . $settingClass . ' is already delegated to ' . $groupId . '.');
			return 4;
		}

		$io->success('Administration of ' . $settingClass . ' delegated to ' . $groupId . '.');

		return 0;
	}
}
