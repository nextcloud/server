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

class FixBrokenShares extends Base {
	public function __construct(
		private OrphanHelper $orphanHelper
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('sharing:fix-broken-shares')
			->setDescription('Fix broken shares after transfer ownership')
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

		foreach ($shares as $share) {
			if ($this->orphanHelper->isShareValid($share['owner'], $share['fileid']) || !$this->orphanHelper->fileExists($share['fileid'])) {
				continue;
			}

			$owner = $this->orphanHelper->findOwner($share['fileid']);

			if ($owner !== null) {
				if ($dryRun) {
					$output->writeln("Share {$share['id']} can be updated to owner $owner");
				} else {
					$this->orphanHelper->updateShareOwner($share['id'], $owner);
					$output->writeln("Share {$share['id']} updated to owner $owner");
				}
			}
		}

		return static::SUCCESS;
	}
}
