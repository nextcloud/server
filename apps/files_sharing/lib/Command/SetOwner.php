<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Info\FileUtils;
use OCA\Files_Sharing\SharedMount;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Share\IManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SetOwner extends Command {
	private FileUtils $fileUtils;
	private IRootFolder $rootFolder;
	private IManager $shareManager;
	private IUserManager $userManager;

	public function __construct(
		FileUtils $fileUtils,
		IRootFolder $rootFolder,
		IManager $shareManager,
		IUserManager $userManager
	) {
		$this->fileUtils = $fileUtils;
		$this->rootFolder = $rootFolder;
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('sharing:set-owner')
			->setDescription('Change the owner of a share, note that the new owner must already have access to the file')
			->addArgument('share-id', InputArgument::REQUIRED, "Id of the share to set the owner of")
			->addArgument('new-owner', InputArgument::REQUIRED, "User id of to set the owner to");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$shareId = $input->getArgument('share-id');
		$targetId = $input->getArgument('new-owner');

		$target = $this->userManager->get($targetId);
		if (!$target) {
			$output->writeln("<error>User $targetId not found</error>");
			return 1;
		}

		$share = $this->shareManager->getShareById($shareId);
		if (!$share) {
			$output->writeln("<error>Share $shareId not found</error>");
			return 1;
		}

		$sourceFile = $share->getNode();
		$usersWithAccessToSource = $this->fileUtils->getFilesByUser($sourceFile);

		$targetHasNonSharedAccess = false;
		$targetHasSharedAccess = false;
		$fileOrFolder = ($sourceFile instanceof File) ? "file" : "folder";;
		$sourceName = $sourceFile->getName();
		$targetNode = null;

		if (isset($usersWithAccessToSource[$target->getUID()])) {
			$targetSourceNodes = $usersWithAccessToSource[$target->getUID()];
			foreach ($targetSourceNodes as $targetSourceNode) {
				$targetNode = $targetSourceNode;
				$mount = $targetSourceNode->getMountPoint();
				if ($mount instanceof SharedMount) {
					if ($mount->getShare()->getId() === $share->getId()) {
						$targetHasSharedAccess = true;
						continue;
					}
				}
				$targetHasNonSharedAccess = true;
			}
		}

		if (!$targetHasSharedAccess && !$targetHasNonSharedAccess) {
			$output->writeln("<error>$targetId has no access to the $fileOrFolder $sourceName shared by $shareId</error>");
			return 1;
		}

		if (!$targetHasNonSharedAccess && $targetHasSharedAccess) {
			$output->writeln("<error>Target user $targetId only has access to the shared $fileOrFolder $sourceName through the share $shareId.</error>");
			return 1;
		}

		$share->setNode($targetNode);
		$share->setShareOwner($target->getUID());
		$share->setSharedBy($target->getUID());

		$this->shareManager->updateShare($share);

		return 0;
	}

}
