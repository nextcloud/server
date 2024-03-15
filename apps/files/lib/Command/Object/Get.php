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
			->addArgument('object', InputArgument::REQUIRED, "Object to get")
			->addArgument('output', InputArgument::REQUIRED, "Target local file to output to, use - for STDOUT")
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to get the object from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$outputName = $input->getArgument('output');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption("bucket"), $output);
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
