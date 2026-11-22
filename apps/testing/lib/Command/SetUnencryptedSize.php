<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Command;

use OCP\Files\IRootFolder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Overwrite the unencrypted size stored in the file cache, e.g. to simulate
 * file cache entries written by older versions.
 */
class SetUnencryptedSize extends Command {
	public function __construct(
		private IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('testing:set-unencrypted-size')
			->setDescription('Overwrite the unencrypted size of a file in the file cache')
			->addArgument('user', InputArgument::REQUIRED, 'Owner of the file')
			->addArgument('path', InputArgument::REQUIRED, 'Path of the file relative to the home of the user')
			->addArgument('size', InputArgument::REQUIRED, 'Unencrypted size to store');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$node = $this->rootFolder
			->getUserFolder($input->getArgument('user'))
			->get($input->getArgument('path'));

		$node->getStorage()->getCache()->update($node->getId(), [
			'unencrypted_size' => (int)$input->getArgument('size'),
		]);

		return self::SUCCESS;
	}
}
