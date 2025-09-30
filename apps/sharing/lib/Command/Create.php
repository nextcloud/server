<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCA\Sharing\Exception\ShareException;
use OCA\Sharing\Manager;
use OCA\Sharing\Model\Share;
use OCA\Sharing\ResponseDefinitions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
class Create extends Command {
	public function __construct(
		private readonly Manager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('share:create')
			->setDescription('create a new share')
			->addArgument('data', InputArgument::REQUIRED, 'Share data');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var SharingShare $data */
		$data = json_decode((string)$input->getArgument('data'), true, 512, JSON_THROW_ON_ERROR);
		$share = Share::fromArray($data);

		try {
			$this->manager->insert(null, $share);
		} catch (ShareException $shareException) {
			$output->writeln($shareException->getMessage());
			return 1;
		}

		return 0;
	}
}
