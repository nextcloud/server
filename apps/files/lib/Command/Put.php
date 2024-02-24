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

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Put extends Command {
	public function __construct(
		private FileUtils $fileUtils,
		private IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:put')
			->setDescription('Write contents of a file')
			->addArgument('input', InputArgument::REQUIRED, "Source local path, use - to read from STDIN")
			->addArgument('file', InputArgument::REQUIRED, "Target Nextcloud file path to write to or fileid of existing file");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileOutput = $input->getArgument('file');
		$inputName = $input->getArgument('input');
		$node = $this->fileUtils->getNode($fileOutput);

		if ($node instanceof Folder) {
			$output->writeln("<error>$fileOutput is a folder</error>");
			return self::FAILURE;
		}
		if (!$node and is_numeric($fileOutput)) {
			$output->writeln("<error>$fileOutput not found</error>");
			return self::FAILURE;
		}

		$source = ($inputName === null || $inputName === '-') ? STDIN : fopen($inputName, 'r');
		if (!$source) {
			$output->writeln("<error>Failed to open $inputName</error>");
			return self::FAILURE;
		}
		if ($node instanceof File) {
			$target = $node->fopen('w');
			if (!$target) {
				$output->writeln("<error>Failed to open $fileOutput</error>");
				return self::FAILURE;
			}
			stream_copy_to_stream($source, $target);
		} else {
			$this->rootFolder->newFile($fileOutput, $source);
		}
		return self::SUCCESS;
	}
}
