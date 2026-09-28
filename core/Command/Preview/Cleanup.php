<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Preview;

use OC\Core\Command\Base;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Base {

	public function __construct(
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('preview:cleanup')
			->setDescription('Removes existing preview files');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$appDataFolder = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName());

			if (!$appDataFolder instanceof Folder) {
				$this->logger->error("Previews can't be removed: appdata is not a folder");
				$output->writeln("Previews can't be removed: appdata is not a folder");
				return 1;
			}

			/** @var Folder $previewFolder */
			$previewFolder = $appDataFolder->get('preview');

		} catch (NotFoundException $e) {
			$this->logger->error("Previews can't be removed: appdata folder can't be found", ['exception' => $e]);
			$output->writeln("Previews can't be removed: preview folder isn't deletable");
			return 1;
		}

		if (!$previewFolder->isDeletable()) {
			$this->logger->error("Previews can't be removed: preview folder isn't deletable");
			$output->writeln("Previews can't be removed: preview folder isn't deletable");
			return 1;
		}

		try {
			$previewFolder->delete();
			$this->logger->debug('Preview folder deleted');
			$output->writeln('Preview folder deleted', OutputInterface::VERBOSITY_VERBOSE);
		} catch (NotFoundException $e) {
			$output->writeln("Previews weren't deleted: preview folder was not found while deleting it");
			$this->logger->error("Previews weren't deleted: preview folder was not found while deleting it", ['exception' => $e]);
			return 1;
		} catch (NotPermittedException $e) {
			$output->writeln("Previews weren't deleted: you don't have the permission to delete preview folder");
			$this->logger->error("Previews weren't deleted: you don't have the permission to delete preview folder", ['exception' => $e]);
			return 1;
		}

		try {
			$appDataFolder->newFolder('preview');
			$this->logger->debug('Preview folder recreated');
			$output->writeln('Preview folder recreated', OutputInterface::VERBOSITY_VERBOSE);
		} catch (NotPermittedException $e) {
			$output->writeln("Preview folder was deleted, but you don't have the permission to create preview folder");
			$this->logger->error("Preview folder was deleted, but you don't have the permission to create preview folder", ['exception' => $e]);
			return 1;
		}

		$output->writeln('Previews removed');
		return 0;
	}
}
