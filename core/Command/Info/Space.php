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

namespace OC\Core\Command\Info;

use OCP\Files\Folder;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Space extends Command {
	public function __construct(
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('info:file:space')
			->setDescription('Summarize space usage of specified folder')
			->addArgument('file', InputArgument::REQUIRED, "File id or path")
			->addOption('count', 'c', InputOption::VALUE_REQUIRED, "Number of items to display", 25)
			->addOption('all', 'a', InputOption::VALUE_NONE, "Display all items");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$count = (int)$input->getOption('count');
		$all = $input->getOption('all');
		$node = $this->fileUtils->getNode($fileInput);
		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return 1;
		}
		$output->writeln($node->getName() . ": <info>" . Util::humanFileSize($node->getSize()) . "</info>");
		if ($node instanceof Folder) {
			$limits = $all ? [] : array_fill(0, $count - 1, 0);
			$this->fileUtils->outputLargeFilesTree($output, $node, '', $limits, $all);
		}
		return 0;
	}
}
