<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class Applicable extends Base {
	public function __construct(
		protected GlobalStoragesService $globalService,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:applicable')
			->setDescription('Manage applicable users and groups for a mount')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'The id of the mount to edit'
			)->addOption(
				'add-user',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'user to add as applicable'
			)->addOption(
				'remove-user',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'user to remove as applicable'
			)->addOption(
				'add-group',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'group to add as applicable'
			)->addOption(
				'remove-group',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'group to remove as applicable'
			)->addOption(
				'remove-all',
				'',
				InputOption::VALUE_NONE,
				'Set the mount to be globally applicable'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mountId = $input->getArgument('mount_id');
		try {
			$mount = $this->globalService->getStorage($mountId);
		} catch (NotFoundException $e) {
			$output->writeln('<error>Mount with id "' . $mountId . ' not found, check "occ files_external:list" to get available mounts</error>');
			return Response::HTTP_NOT_FOUND;
		}

		if ($mount->getType() === StorageConfig::MOUNT_TYPE_PERSONAl) {
			$output->writeln('<error>Can\'t change applicables on personal mounts</error>');
			return self::FAILURE;
		}

		$addUsers = $input->getOption('add-user');
		$removeUsers = $input->getOption('remove-user');
		$addGroups = $input->getOption('add-group');
		$removeGroups = $input->getOption('remove-group');

		$applicableUsers = $mount->getApplicableUsers();
		$applicableGroups = $mount->getApplicableGroups();

		if ((count($addUsers) + count($removeUsers) + count($addGroups) + count($removeGroups) > 0) || $input->getOption('remove-all')) {
			foreach ($addUsers as $addUser) {
				if (!$this->userManager->userExists($addUser)) {
					$output->writeln('<error>User "' . $addUser . '" not found</error>');
					return Response::HTTP_NOT_FOUND;
				}
			}
			foreach ($addGroups as $addGroup) {
				if (!$this->groupManager->groupExists($addGroup)) {
					$output->writeln('<error>Group "' . $addGroup . '" not found</error>');
					return Response::HTTP_NOT_FOUND;
				}
			}

			if ($input->getOption('remove-all')) {
				$applicableUsers = [];
				$applicableGroups = [];
			} else {
				$applicableUsers = array_unique(array_merge($applicableUsers, $addUsers));
				$applicableUsers = array_values(array_diff($applicableUsers, $removeUsers));
				$applicableGroups = array_unique(array_merge($applicableGroups, $addGroups));
				$applicableGroups = array_values(array_diff($applicableGroups, $removeGroups));
			}
			$mount->setApplicableUsers($applicableUsers);
			$mount->setApplicableGroups($applicableGroups);
			$this->globalService->updateStorage($mount);
		}

		$this->writeArrayInOutputFormat($input, $output, [
			'users' => $applicableUsers,
			'groups' => $applicableGroups
		]);
		return self::SUCCESS;
	}
}
