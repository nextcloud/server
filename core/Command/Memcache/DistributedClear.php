<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Memcache;

use OC\Core\Command\Base;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DistributedClear extends Base {
	public function __construct(
		protected ICacheFactory $cacheFactory,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memcache:distributed:clear')
			->setDescription('Clear values from the distributed memcache')
			->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Only remove keys matching the prefix');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$cache = $this->cacheFactory->createDistributed();
		$prefix = $input->getOption('prefix');
		if ($cache->clear($prefix)) {
			if ($prefix) {
				$output->writeln('<info>Distributed cache matching prefix ' . $prefix . ' cleared</info>');
			} else {
				$output->writeln('<info>Distributed cache cleared</info>');
			}
			return 0;
		} else {
			$output->writeln('<error>Failed to clear cache</error>');
			return 1;
		}
	}
}
