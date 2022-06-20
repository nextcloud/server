<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Core\Command\User;

use OC\Files\View;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Report extends Command {
	public const DEFAULT_COUNT_DIRS_MAX_USERS = 500;

	protected IUserManager $userManager;
	private IConfig $config;

	public function __construct(IUserManager $userManager,
								IConfig $config) {
		$this->userManager = $userManager;
		$this->config = $config;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:report')
			->setDescription('shows how many users have access')
			->addOption(
				'count-dirs',
				null,
				InputOption::VALUE_NONE,
				'Also count the number of user directories in the database (could time out on huge installations, therefore defaults to no with ' . self::DEFAULT_COUNT_DIRS_MAX_USERS . '+ users)'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$table = new Table($output);
		$table->setHeaders(['User Report', '']);
		$userCountArray = $this->countUsers();
		$total = 0;
		if (!empty($userCountArray)) {
			$rows = [];
			foreach ($userCountArray as $classname => $users) {
				$total += $users;
				$rows[] = [$classname, $users];
			}

			$rows[] = [' '];
			$rows[] = ['total users', $total];
		} else {
			$rows[] = ['No backend enabled that supports user counting', ''];
		}
		$rows[] = [' '];

		if ($total <= self::DEFAULT_COUNT_DIRS_MAX_USERS || $input->getOption('count-dirs')) {
			$userDirectoryCount = $this->countUserDirectories();
			$rows[] = ['user directories', $userDirectoryCount];
		}

		$activeUsers = $this->userManager->countSeenUsers();
		$rows[] = ['active users', $activeUsers];

		$disabledUsers = $this->config->getUsersForUserValue('core', 'enabled', 'false');
		$disabledUsersCount = count($disabledUsers);
		$rows[] = ['disabled users', $disabledUsersCount];

		$table->setRows($rows);
		$table->render();
		return 0;
	}

	private function countUsers(): array {
		return $this->userManager->countUsers();
	}

	private function countUserDirectories(): int {
		$dataview = new View('/');
		$userDirectories = $dataview->getDirectoryContent('/', 'httpd/unix-directory');
		return count($userDirectories);
	}
}
