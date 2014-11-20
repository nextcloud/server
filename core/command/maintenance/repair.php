<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Repair extends Command {
	/**
	 * @var \OC\Repair $repair
	 */
	protected $repair;
	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param \OC\Repair $repair
	 * @param \OCP\IConfig $config
	 */
	public function __construct(\OC\Repair $repair, \OCP\IConfig $config) {
		$this->repair = $repair;
		$this->config = $config;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:repair')
			->setDescription('repair this installation');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$maintenanceMode = $this->config->getSystemValue('maintenance', false);
		$this->config->setSystemValue('maintenance', true);

		$this->repair->listen('\OC\Repair', 'step', function ($description) use ($output) {
			$output->writeln(' - ' . $description);
		});
		$this->repair->listen('\OC\Repair', 'info', function ($description) use ($output) {
			$output->writeln('     - ' . $description);
		});
		$this->repair->listen('\OC\Repair', 'error', function ($description) use ($output) {
			$output->writeln('     - ERROR: ' . $description);
		});

		$this->repair->run();

		$this->config->setSystemValue('maintenance', $maintenanceMode);
	}
}
