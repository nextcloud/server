<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Core\Command\Profiler;

use _HumbugBox1cb33d1f20f1\LanguageServerProtocol\PackageDescriptor;
use OC\Core\Command\Base;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Base {
	private IProfiler $profiler;

	public function __construct(IProfiler $profiler) {
		parent::__construct();
		$this->profiler = $profiler;
	}

	protected function configure() {
		$this
			->setName('profiler:export')
			->setDescription('Export captured profiles as json')
			->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of profiles to export')
			->addOption('url', null, InputOption::VALUE_REQUIRED, 'Url to export profiles for')
			->addOption('since', null, InputOption::VALUE_REQUIRED, 'Minimum date for exported profiles, as unix timestamp')
			->addOption('before', null, InputOption::VALUE_REQUIRED, 'Maximum date for exported profiles, as unix timestamp')
			->addOption('token', null, InputOption::VALUE_REQUIRED, 'Export only profile for a single request token');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$since = $input->getOption('since') ? (int)$input->getOption('since') : null;
		$before = $input->getOption('before') ? (int)$input->getOption('before') : null;
		$limit = $input->getOption('limit') ? (int)$input->getOption('limit') : 1000;
		$token = $input->getOption('token') ? $input->getOption('token') : null;
		$url = $input->getOption('url');

		if ($token) {
			$profiles = [$this->profiler->loadProfile($token)];
			$profiles = array_filter($profiles);
		} else {
			$profiles = $this->profiler->find($url, $limit, null, $since, $before);
			$profiles = array_reverse($profiles);
			$profiles = array_map(function (array $profile) {
				return $this->profiler->loadProfile($profile['token']);
			}, $profiles);
		}

		$output->writeln(json_encode($profiles));

		return 0;
	}
}
