<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\App;

use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Command {

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
			->setName('app:disable')
			->setDescription('disable an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'disable the specified app'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getArgument('app-id');
		if ($this->manager->isInstalled($appId)) {
			try {
				$this->manager->disableApp($appId);
				$output->writeln($appId . ' disabled');
			} catch(\Exception $e) {
				$output->writeln($e->getMessage());
				return 2;
			}
		} else {
			$output->writeln('No such app enabled: ' . $appId);
		}
	}
}
