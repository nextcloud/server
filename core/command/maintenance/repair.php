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

	/**
	 * @param \OC\Repair $repair
	 */
	public function __construct($repair) {
		$this->repair = $repair;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:repair')
			->setDescription('repair this installation');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		\OC_DB::enableCaching(false);
		$maintenanceMode = \OC_Config::getValue('maintenance', false);
		\OC_Config::setValue('maintenance', true);

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

		\OC_Config::setValue('maintenance', $maintenanceMode);
	}
}
