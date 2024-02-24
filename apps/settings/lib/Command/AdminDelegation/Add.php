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
		if (!in_array(IDelegatedSettings::class, (array) class_implements($settingClass), true)) {
			$io->error('The specified class isn’t a valid delegated setting.');
			return 2;
		}

		$groupId = $input->getArgument('groupId');
		if (!$this->groupManager->groupExists($groupId)) {
			$io->error('The specified group didn’t exist.');
			return 3;
		}

		$this->authorizedGroupService->create($groupId, $settingClass);

		$io->success('Administration of '.$settingClass.' delegated to '.$groupId.'.');

		return 0;
	}
}
