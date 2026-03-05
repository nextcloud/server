<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Memcache;

use OC\Core\Command\Base;
use OC\Core\Command\Config\System\CastHelper;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DistributedSet extends Base {
	public function __construct(
		protected ICacheFactory $cacheFactory,
		private CastHelper $castHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memcache:distributed:set')
			->setDescription('Set a value in the distributed memcache')
			->addArgument('key', InputArgument::REQUIRED, 'The key to set')
			->addArgument('value', InputArgument::REQUIRED, 'The value to set')
			->addOption(
				'type',
				null,
				InputOption::VALUE_REQUIRED,
				'Value type [string, integer, float, boolean, json, null]',
				'string'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$cache = $this->cacheFactory->createDistributed();
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');
		$type = $input->getOption('type');
		['value' => $value, 'readable-value' => $readable] = $this->castHelper->castValue($value, $type);
		if ($cache->set($key, $value)) {
			$output->writeln('Distributed cache key <info>' . $key . '</info> set to <info>' . $readable . '</info>');
			return 0;
		} else {
			$output->writeln('<error>Failed to set cache key ' . $key . '</error>');
			return 1;
		}
	}
}
