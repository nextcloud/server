<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Core\Command\App;

use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Enable extends Command {

	/** @var IAppManager */
	protected $manager;

	/**
	 * @param IAppManager $manager
	 */
	public function __construct(IAppManager $manager) {
		parent::__construct();
		$this->manager = $manager;
	}

	protected function configure() {
		$this
			->setName('app:enable')
			->setDescription('enable an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'enable the specified app'
			)
			->addOption(
				'groups',
				'g',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'enable the app only for a list of groups'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getArgument('app-id');

		if (!\OC_App::getAppPath($appId)) {
			$output->writeln($appId . ' not found');
			return 1;
		}

		$groups = $input->getOption('groups');
		if (empty($groups)) {
			\OC_App::enable($appId);
			$output->writeln($appId . ' enabled');
		} else {
			\OC_App::enable($appId, $groups);
			$output->writeln($appId . ' enabled for groups: ' . implode(', ', $groups));
		}
		return 0;
	}
}
