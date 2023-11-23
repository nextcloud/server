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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:get')
			->setDescription('Get the contents of a file')
			->addArgument('file', InputArgument::REQUIRED, "Source file id or Nextcloud path")
			->addArgument('output', InputArgument::OPTIONAL, "Target local file to output to, defaults to STDOUT");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$outputName = $input->getArgument('output');
		$node = $this->fileUtils->getNode($fileInput);

		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return self::FAILURE;
		}

		if (!($node instanceof File)) {
			$output->writeln("<error>$fileInput is a directory</error>");
			return self::FAILURE;
		}

		$isTTY = stream_isatty(STDOUT);
		if ($outputName === null && $isTTY && $node->getMimePart() !== 'text') {
			$output->writeln([
				"<error>Warning: Binary output can mess up your terminal</error>",
				"         Use <info>occ files:get $fileInput -</info> to output it to the terminal anyway",
				"         Or <info>occ files:get $fileInput <FILE></info> to save to a file instead"
			]);
			return self::FAILURE;
		}
		$source = $node->fopen('r');
		if (!$source) {
			$output->writeln("<error>Failed to open $fileInput for reading</error>");
			return self::FAILURE;
		}
		$target = ($outputName === null || $outputName === '-') ? STDOUT : fopen($outputName, 'w');
		if (!$target) {
			$output->writeln("<error>Failed to open $outputName for reading</error>");
			return self::FAILURE;
		}

		stream_copy_to_stream($source, $target);
		return self::SUCCESS;
	}
}
