<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2015 Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace OC\Core\Command\Background;

use OCP\IAppConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mode extends Command {
	public function __construct(
		private IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('background:cron')
			->setAliases(['background:ajax', 'background:webcron'])
			->setDescription('Use cron, ajax or webcron to run background jobs');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var 'background:cron'|'background:ajax'|'background:webcron' $command */
		$command = $input->getArgument('command');

		$mode = match ($command) {
			'background:cron' => 'cron',
			'background:ajax' => 'ajax',
			'background:webcron' => 'webcron',
		};

		$this->appConfig->setValueString('core', 'backgroundjobs_mode', $mode);
		$output->writeln("Set mode for background jobs to '" . $mode . "'");

		return 0;
	}
}
