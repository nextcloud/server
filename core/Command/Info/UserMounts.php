<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Info;

use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserMounts extends Command {
	public function __construct(
		private FileUtils $fileUtils,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('info:user:mounts')
			->setDescription('list mounted storages available for a user')
			->addArgument('user', InputArgument::REQUIRED, "User id to get mounted storages for");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user');
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>user $userId not found</error>");
			return 1;
		}

		$userFolder = $this->rootFolder->getUserFolder($userId);
		$mounts = $this->rootFolder->getMountsIn($userFolder->getPath());
		$mounts[] = $userFolder->getMountPoint();
		usort($mounts, fn (IMountPoint $a, IMountPoint $b) => $a->getMountPoint() <=> $b->getMountPoint());

		foreach ($mounts as $mount) {
			$mountInfo = $this->fileUtils->formatMountType($mount);
			$output->writeln("<info>{$mount->getMountPoint()}</info>: $mountInfo");
		}
		return 0;
	}
}
