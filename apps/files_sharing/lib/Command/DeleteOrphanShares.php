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

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Base;
use OCA\Files_Sharing\OrphanHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteOrphanShares extends Base {
	private OrphanHelper $orphanHelper;

	public function __construct(OrphanHelper $orphanHelper) {
		parent::__construct();
		$this->orphanHelper = $orphanHelper;
	}

	protected function configure(): void {
		$this
			->setName('sharing:delete-orphan-shares')
			->setDescription('Delete shares where the owner no longer has access to the file')
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'delete the shares without asking'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$force = $input->getOption('force');
		$shares = $this->orphanHelper->getAllShares();

		$orphans = [];
		foreach ($shares as $share) {
			if (!$this->orphanHelper->isShareValid($share['owner'], $share['fileid'])) {
				$orphans[] = $share['id'];
				$exists = $this->orphanHelper->fileExists($share['fileid']);
				$output->writeln("<info>{$share['target']}</info> owned by <info>{$share['owner']}</info>");
				if ($exists) {
					$output->writeln("  file still exists but the share owner lost access to it, run <info>occ info:file {$share['fileid']}</info> for more information about the file");
				} else {
					$output->writeln("  file no longer exists");
				}
			}
		}

		$count = count($orphans);

		if ($count === 0) {
			$output->writeln("No orphan shares detected");
			return 0;
		}

		if ($force) {
			$doDelete = true;
		} else {
			$output->writeln("");
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion("Delete <info>$count</info> orphan shares? [y/N] ", false);
			$doDelete = $helper->ask($input, $output, $question);
		}

		if ($doDelete) {
			$this->orphanHelper->deleteShares($orphans);
		}

		return 0;
	}
}
