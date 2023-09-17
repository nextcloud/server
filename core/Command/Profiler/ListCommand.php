<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Core\Command\Profiler;

use OC\Core\Command\Base;
use OC\Profiler\DataCollector\DbDataCollector;
use OC\Profiler\DataCollector\MemoryDataCollector;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	private IProfiler $profiler;

	public function __construct(IProfiler $profiler) {
		parent::__construct();
		$this->profiler = $profiler;
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('profiler:list')
			->setDescription('List captured profiles')
			->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of profiles to return')
			->addOption('url', null, InputOption::VALUE_REQUIRED, 'Url to list profiles for')
			->addOption('since', null, InputOption::VALUE_REQUIRED, 'Minimum date for listed profiles, as unix timestamp')
			->addOption('before', null, InputOption::VALUE_REQUIRED, 'Maximum date for listed profiles, as unix timestamp');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$since = $input->getOption('since') ? (int)$input->getOption('since') : null;
		$before = $input->getOption('before') ? (int)$input->getOption('before') : null;
		$limit = $input->getOption('limit') ? (int)$input->getOption('limit') : 1000;
		$url = $input->getOption('url');

		$profiles = $this->profiler->find($url, $limit, null, $since, $before);
		$profiles = array_reverse($profiles);
		foreach ($profiles as &$profile) {
			$info = $this->profiler->loadProfile($profile['token']);

			/** @var DbDataCollector $dbCollector */
			$dbCollector = $info->getCollector('db');
			/** @var MemoryDataCollector $memoryCollector */
			$memoryCollector = $info->getCollector('memory');

			if ($dbCollector) {
				$profile['queries'] = count($dbCollector->getQueries());
			} else {
				$profile['queries'] = '--';
			}
			if ($memoryCollector) {
				$profile['memory'] = $memoryCollector->getMemory();
			} else {
				$profile['memory'] = '--';
			}
		}

		$outputType = $input->getOption('output');

		if ($profiles) {
			if ($outputType === self::OUTPUT_FORMAT_JSON || $outputType === self::OUTPUT_FORMAT_JSON_PRETTY) {
				$this->writeArrayInOutputFormat($input, $output, $profiles);
			} else {
				$table = new Table($output);
				$table->setHeaders(array_keys($profiles[0]));
				$table->setRows($profiles);
				$table->render();
			}
		}

		return 0;
	}
}
