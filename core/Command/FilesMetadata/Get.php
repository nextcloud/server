<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$node = $this->rootFolder->getUserFolder($input->getArgument('userId'))->getFirstNodeById($fileId);
			if (!$node) {
				throw new NotFoundException();
			}
			$metadata = $this->filesMetadataManager->refreshMetadata(
				$node,
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
