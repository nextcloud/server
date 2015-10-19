<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
			->setDescription('repair this installation')
			->addOption(
				'include-expensive',
				null,
				InputOption::VALUE_NONE,
				'Use this option when you want to include resource and load expensive tasks'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$includeExpensive = $input->getOption('include-expensive');
		if ($includeExpensive) {
			foreach ($this->repair->getExpensiveRepairSteps() as $step) {
				$this->repair->addStep($step);
			}
		}

		$maintenanceMode = $this->config->getSystemValue('maintenance', false);
		$this->config->setSystemValue('maintenance', true);

		$this->repair->listen('\OC\Repair', 'step', function ($description) use ($output) {
			$output->writeln(' - ' . $description);
		});
		$this->repair->listen('\OC\Repair', 'info', function ($description) use ($output) {
			$output->writeln('     - ' . $description);
		});
		$this->repair->listen('\OC\Repair', 'warning', function ($description) use ($output) {
			$output->writeln('     - WARNING: ' . $description);
		});
		$this->repair->listen('\OC\Repair', 'error', function ($description) use ($output) {
			$output->writeln('     - ERROR: ' . $description);
		});

		$this->repair->run();

		$this->config->setSystemValue('maintenance', $maintenanceMode);
	}
}
