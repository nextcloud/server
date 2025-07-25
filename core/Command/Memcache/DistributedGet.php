<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Memcache;

use OC\Core\Command\Base;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DistributedGet extends Base {
	public function __construct(
		protected ICacheFactory $cacheFactory,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memcache:distributed:get')
			->setDescription('Get a value from the distributed memcache')
			->addArgument('key', InputArgument::REQUIRED, 'The key to retrieve');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$cache = $this->cacheFactory->createDistributed();
		$key = $input->getArgument('key');

		$value = $cache->get($key);
		$this->writeMixedInOutputFormat($input, $output, $value);
		return 0;
	}
}
