<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Core\Command\Profiler;

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Enable extends Command {
	private IConfig $config;

	public function __construct(IConfig $config) {
		parent::__construct();
		$this->config = $config;
	}

	protected function configure(): void {
		$this
			->setName('profiler:enable')
			->setDescription('Enable profiling');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->config->setSystemValue('profiler', true);
		return 0;
	}
}
