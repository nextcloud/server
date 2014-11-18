<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com> and
 * Copyright (c) 2014 Stephen Colebrook <scolebrook@mac.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Maintenance;

use \OCP\IConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Mode extends Command {

	/** @var IConfig */
	protected $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:mode')
			->setDescription('set maintenance mode')
			->addOption(
				'on',
				null,
				InputOption::VALUE_NONE,
				'enable maintenance mode'
			)
			->addOption(
				'off',
				null,
				InputOption::VALUE_NONE,
				'disable maintenance mode'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('on')) {
			$this->config->setSystemValue('maintenance', true);
			$output->writeln('Maintenance mode enabled');
		} elseif ($input->getOption('off')) {
			$this->config->setSystemValue('maintenance', false);
			$output->writeln('Maintenance mode disabled');
		} else {
			if ($this->config->getSystemValue('maintenance', false)) {
				$output->writeln('Maintenance mode is currently enabled');
			} else {
				$output->writeln('Maintenance mode is currently disabled');
			}
		}
	}
}
