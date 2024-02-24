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

use OCP\Files\IMimeTypeDetector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Put extends Command {
	public function __construct(
		private ObjectUtil $objectUtils,
		private IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:object:put')
			->setDescription('Write a file to the object store')
			->addArgument('input', InputArgument::REQUIRED, "Source local path, use - to read from STDIN")
			->addArgument('object', InputArgument::REQUIRED, "Object to write")
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket where to store the object, only required in cases where it can't be determined from the config");
		;
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$inputName = (string)$input->getArgument('input');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption("bucket"), $output);
		if (!$objectStore) {
			return -1;
		}

		if ($fileId = $this->objectUtils->objectExistsInDb($object)) {
			$output->writeln("<error>Warning, object $object belongs to an existing file, overwriting the object contents can lead to unexpected behavior.</error>");
			$output->writeln("You can use <info>occ files:put $inputName $fileId</info> to write to the file safely.");
			$output->writeln("");

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion("Write to the object anyway? [y/N] ", false);
			if (!$helper->ask($input, $output, $question)) {
				return -1;
			}
		}

		$source = $inputName === '-' ? STDIN : fopen($inputName, 'r');
		if (!$source) {
			$output->writeln("<error>Failed to open $inputName</error>");
			return self::FAILURE;
		}
		$objectStore->writeObject($object, $source, $this->mimeTypeDetector->detectPath($inputName));
		return self::SUCCESS;
	}

}
