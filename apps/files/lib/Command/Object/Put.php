<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			->addArgument('input', InputArgument::REQUIRED, 'Source local path, use - to read from STDIN')
			->addArgument('object', InputArgument::REQUIRED, 'Object to write')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket where to store the object, only required in cases where it can't be determined from the config");
		;
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$inputName = (string)$input->getArgument('input');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return -1;
		}

		if ($fileId = $this->objectUtils->objectExistsInDb($object)) {
			$output->writeln("<error>Warning, object $object belongs to an existing file, overwriting the object contents can lead to unexpected behavior.</error>");
			$output->writeln("You can use <info>occ files:put $inputName $fileId</info> to write to the file safely.");
			$output->writeln('');

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Write to the object anyway? [y/N] ', false);
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
