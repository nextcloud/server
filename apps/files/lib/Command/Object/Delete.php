<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\IQuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
	name: 'files:object:delete',
	description: 'Delete an object from the object store',
)]
class Delete {
	public function __construct(
		private readonly ObjectUtil $objectUtils,
	) {
	}

	public function __invoke(
		IOutput $output,
		IQuestionHelper $questionHelper,
		#[Argument(description: 'Object to delete')] string $object,
		#[Option(description: "Bucket to delete the object from, only required in cases where it can't be determined from the config", shortcut: 'b')] ?string $bucket = null,
	): ExitCode|int {
		$objectStore = $this->objectUtils->getObjectStore($bucket, $output);
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

		$question = new ConfirmationQuestion("Delete $object? [y/N] ", false);
		if ($questionHelper->ask($question)) {
			$objectStore->deleteObject($object);
		}
		return ExitCode::Success;
	}
}
