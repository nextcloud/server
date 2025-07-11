<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Info;

use OC\Core\Command\Base;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Storages extends Base {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('info:storages')
			->setDescription('List storages ordered by the number of files')
			->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of storages to display', 25)
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Display all storages');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$count = (int)$input->getOption('count');
		$all = $input->getOption('all');

		$limit = $all ? null : $count;
		$storages = $this->fileUtils->listStorages($limit);
		$this->writeStreamingTableInOutputFormat($input, $output, $this->fileUtils->formatStorages($storages), 100);
		return 0;
	}
}
