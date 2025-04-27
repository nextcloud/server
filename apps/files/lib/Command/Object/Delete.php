<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Delete extends Command {
	public function __construct(
		private ObjectUtil $objectUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:object:delete')
			->setDescription('Delete an object from the object store')
			->addArgument('object', InputArgument::REQUIRED, 'Object to delete')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to delete the object from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return -1;
		}

		if ($fileId = $this->objectUtils->objectExistsInDb($object)) {
			$output->writeln("<error>Warning, object $object belongs to an existing file, deleting the object will lead to unexpected behavior if not replaced</error>");
			$output->writeln("  Note: use <info>occ files:delete $fileId</info> to delete the file cleanly or <info>occ info:file $fileId</info> for more information about the file");
			$output->writeln('');
		}

		if (!$objectStore->objectExists($object)) {
			$output->writeln("<error>Object $object does not exist</error>");
			return -1;
		}

		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion("Delete $object? [y/N] ", false);
		if ($helper->ask($input, $output, $question)) {
			$objectStore->deleteObject($object);
		}
		return self::SUCCESS;
	}
}
