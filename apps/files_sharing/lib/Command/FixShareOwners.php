<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Base;
use OCA\Files_Sharing\OrphanHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixShareOwners extends Base {
	public function __construct(
		private readonly OrphanHelper $orphanHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('sharing:fix-share-owners')
			->setDescription('Fix owner of broken shares after transfer ownership on old versions')
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'only show which shares would be updated'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$shares = $this->orphanHelper->getAllShares();
		$dryRun = $input->getOption('dry-run');
		$count = 0;

		foreach ($shares as $share) {
			if ($this->orphanHelper->isShareValid($share['owner'], $share['fileid']) || !$this->orphanHelper->fileExists($share['fileid'])) {
				continue;
			}

			$owner = $this->orphanHelper->findOwner($share['fileid']);

			if ($owner !== null) {
				if ($dryRun) {
					$output->writeln("Share with id <info>{$share['id']}</info> (target: <info>{$share['target']}</info>) can be updated to owner <info>$owner</info>");
				} else {
					$this->orphanHelper->updateShareOwner($share['id'], $owner);
					$output->writeln("Share with id <info>{$share['id']}</info> (target: <info>{$share['target']}</info>) updated to owner <info>$owner</info>");
				}
				$count++;
			}
		}

		if ($count === 0) {
			$output->writeln('No broken shares detected');
		}

		return static::SUCCESS;
	}
}
