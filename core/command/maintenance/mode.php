<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com> and
 * Copyright (c) 2014 Stephen Colebrook <scolebrook@mac.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Maintenance;

use OC\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Mode extends Command {

	protected $config;

	public function __construct(Config $config) {
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
			$this->config->setValue('maintenance', true);
			$output->writeln('Maintenance mode enabled');
		} elseif ($input->getOption('off')) {
			$this->config->setValue('maintenance', false);
			$output->writeln('Maintenance mode disabled');
		} else {
			if ($this->config->getValue('maintenance', false)) {
				$output->writeln('Maintenance mode is currently enabled');
			} else {
				$output->writeln('Maintenance mode is currently disabled');
			}
		}
	}
}
