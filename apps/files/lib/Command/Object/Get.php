<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private ObjectUtil $objectUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:object:get')
			->setDescription('Get the contents of an object')
			->addArgument('object', InputArgument::REQUIRED, 'Object to get')
			->addArgument('output', InputArgument::REQUIRED, 'Target local file to output to, use - for STDOUT')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to get the object from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$outputName = $input->getArgument('output');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return self::FAILURE;
		}

		if (!$objectStore->objectExists($object)) {
			$output->writeln("<error>Object $object does not exist</error>");
			return self::FAILURE;
		}

		try {
			$source = $objectStore->readObject($object);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
			$output->writeln("<error>Failed to read $object from object store: $msg</error>");
			return self::FAILURE;
		}
		$target = $outputName === '-' ? STDOUT : fopen($outputName, 'w');
		if (!$target) {
			$output->writeln("<error>Failed to open $outputName for writing</error>");
			return self::FAILURE;
		}

		stream_copy_to_stream($source, $target);
		return self::SUCCESS;
	}

}
