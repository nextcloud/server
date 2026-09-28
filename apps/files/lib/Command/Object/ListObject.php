<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OC\Core\Command\Base;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListObject extends Base {
	private const CHUNK_SIZE = 100;

	public function __construct(
		private readonly ObjectUtil $objectUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:list')
			->setDescription('List all objects in the object store')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to list the objects from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return self::FAILURE;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support listing objects</error>');
			return self::FAILURE;
		}
		$objects = $objectStore->listObjects();
		$objects = $this->objectUtils->formatObjects($objects, $input->getOption('output') === self::OUTPUT_FORMAT_PLAIN);
		$this->writeStreamingTableInOutputFormat($input, $output, $objects, self::CHUNK_SIZE);

		return self::SUCCESS;
	}
}
