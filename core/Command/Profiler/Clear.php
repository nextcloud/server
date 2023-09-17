<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Core\Command\Profiler;

use OC\Core\Command\Base;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clear extends Base {
	private IProfiler $profiler;

	public function __construct(IProfiler $profiler) {
		parent::__construct();
		$this->profiler = $profiler;
	}

	protected function configure(): void {
		$this
			->setName('profiler:clear')
			->setDescription('Remove all saved profiles');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->profiler->clear();

		return 0;
	}
}
