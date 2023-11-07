<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\Core\Command\FilesMetadata;

use OC\User\NoUserException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private IRootFolder $rootFolder,
		private IFilesMetadataManager $filesMetadataManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('metadata:get')
			 ->setDescription('get stored metadata about a file, by its id')
			 ->addArgument(
			 	'fileId',
			 	InputArgument::REQUIRED,
			 	'id of the file document'
			 )
			 ->addArgument(
			 	'userId',
			 	InputArgument::OPTIONAL,
			 	'file owner'
			 )
			 ->addOption(
			 	'as-array',
			 	'',
			 	InputOption::VALUE_NONE,
			 	'display metadata as a simple key=>value array'
			 )
			 ->addOption(
			 	'refresh',
			 	'',
			 	InputOption::VALUE_NONE,
			 	'refresh metadata'
			 )
			 ->addOption(
			 	'reset',
			 	'',
			 	InputOption::VALUE_NONE,
			 	'refresh metadata from scratch'
			 );
	}

	/**
	 * @throws NotPermittedException
	 * @throws FilesMetadataNotFoundException
	 * @throws NoUserException
	 * @throws NotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$fileId = (int)$input->getArgument('fileId');

		if ($input->getOption('reset')) {
			$this->filesMetadataManager->deleteMetadata($fileId);
			if (!$input->getOption('refresh')) {
				return self::SUCCESS;
			}
		}

		if ($input->getOption('refresh')) {
			$node = $this->rootFolder->getUserFolder($input->getArgument('userId'))->getById($fileId);
			if (count($node) === 0) {
				throw new NotFoundException();
			}
			$metadata = $this->filesMetadataManager->refreshMetadata(
				$node[0],
				IFilesMetadataManager::PROCESS_LIVE | IFilesMetadataManager::PROCESS_BACKGROUND
			);
		} else {
			$metadata = $this->filesMetadataManager->getMetadata($fileId);
		}

		if ($input->getOption('as-array')) {
			$output->writeln(json_encode($metadata->asArray(), JSON_PRETTY_PRINT));
		} else {
			$output->writeln(json_encode($metadata, JSON_PRETTY_PRINT));
		}

		return self::SUCCESS;
	}
}
