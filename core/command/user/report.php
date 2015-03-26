<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Core\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Report extends Command {
	protected function configure() {
		$this
			->setName('user:report')
			->setDescription('shows how many users have access');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$table = $this->getHelperSet()->get('table');
		$table->setHeaders(array('User Report', ''));
		$userCountArray = $this->countUsers();
		if(!empty($userCountArray)) {
			$total = 0;
			$rows = array();
			foreach($userCountArray as $classname => $users) {
				$total += $users;
				$rows[] = array($classname, $users);
			}

			$rows[] = array(' ');
			$rows[] = array('total users', $total);
		} else {
			$rows[] = array('No backend enabled that supports user counting', '');
		}

		$userDirectoryCount = $this->countUserDirectories();
		$rows[] = array(' ');
		$rows[] = array('user directories', $userDirectoryCount);

		$table->setRows($rows);
		$table->render($output);
	}

	private function countUsers() {
		$userManager = \OC::$server->getUserManager();
		return $userManager->countUsers();
	}

	private function countUserDirectories() {
		$dataview = new \OC\Files\View('/');
		$userDirectories = $dataview->getDirectoryContent('/', 'httpd/unix-directory');
		return count($userDirectories);
	}
}
