<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Martin Konrad <konrad@frib.msu.edu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateEmptyConfig extends Command {
	public function __construct(
		protected Helper $helper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:create-empty-config')
			->setDescription('creates an empty LDAP configuration')
			->addOption(
				'only-print-prefix',
				'p',
				InputOption::VALUE_NONE,
				'outputs only the prefix'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configPrefix = $this->helper->getNextServerConfigurationPrefix();
		$configHolder = new Configuration($configPrefix);
		$configHolder->ldapConfigurationActive = false;
		$configHolder->saveConfiguration();

		$prose = '';
		if (!$input->getOption('only-print-prefix')) {
			$prose = 'Created new configuration with configID ';
		}
		$output->writeln($prose . "{$configPrefix}");
		return self::SUCCESS;
	}
}
