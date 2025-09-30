<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCA\Sharing\Exception\ShareException;
use OCA\Sharing\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Command {
	public function __construct(
		private readonly Manager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('share:delete')
			->setDescription('delete an existing share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$id = (string)$input->getArgument('id');

		try {
			$this->manager->delete(null, $id, false, false);
		} catch (ShareException $shareException) {
			$output->writeln($shareException->getMessage());
			return 1;
		}

		return 0;
	}
}
