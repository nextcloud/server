<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Info;

use OC\Core\Command\Base;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Storage extends Base {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('info:storage')
			->setDescription('Get information a single storage')
			->addArgument('storage', InputArgument::REQUIRED, 'Storage to get information for');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$storage = $input->getArgument('storage');
		$storageId = $this->fileUtils->getNumericStorageId($storage);
		if (!$storageId) {
			$output->writeln('<error>No storage with id ' . $storage . ' found</error>');
			return 1;
		}

		$info = $this->fileUtils->getStorage($storageId);
		if (!$info) {
			$output->writeln('<error>No storage with id ' . $storage . ' found</error>');
			return 1;
		}
		$this->writeArrayInOutputFormat($input, $output, $this->fileUtils->formatStorage($info));
		return 0;
	}
}
