<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
