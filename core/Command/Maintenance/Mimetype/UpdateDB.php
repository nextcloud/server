<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance\Mimetype;

use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDB extends Command {
	public const DEFAULT_MIMETYPE = 'application/octet-stream';

	public function __construct(
		protected IMimeTypeDetector $mimetypeDetector,
		protected IMimeTypeLoader $mimetypeLoader,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:mimetype:update-db')
			->setDescription('Update database mimetypes and update filecache')
			->addOption(
				'repair-filecache',
				null,
				InputOption::VALUE_NONE,
				'Repair filecache for all mimetypes, not just new ones'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mappings = $this->mimetypeDetector->getAllMappings();

		$totalFilecacheUpdates = 0;
		$totalNewMimetypes = 0;

		foreach ($mappings as $ext => $mimetypes) {
			if ($ext[0] === '_') {
				// comment
				continue;
			}
			$mimetype = $mimetypes[0];
			$existing = $this->mimetypeLoader->exists($mimetype);
			// this will add the mimetype if it didn't exist
			$mimetypeId = $this->mimetypeLoader->getId($mimetype);

			if (!$existing) {
				$output->writeln('Added mimetype "' . $mimetype . '" to database');
				$totalNewMimetypes++;
			}

			if (!$existing || $input->getOption('repair-filecache')) {
				$touchedFilecacheRows = $this->mimetypeLoader->updateFilecache($ext, $mimetypeId);
				if ($touchedFilecacheRows > 0) {
					$output->writeln('Updated ' . $touchedFilecacheRows . ' filecache rows for mimetype "' . $mimetype . '"');
				}
				$totalFilecacheUpdates += $touchedFilecacheRows;
			}
		}

		$output->writeln('Added ' . $totalNewMimetypes . ' new mimetypes');
		$output->writeln('Updated ' . $totalFilecacheUpdates . ' filecache rows');
		return 0;
	}
}
