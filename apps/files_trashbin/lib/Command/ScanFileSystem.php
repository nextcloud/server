<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Nextcloud, GmbH.
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\Files_Trashbin\Command;

use OC\Core\Command\Base;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserBackend;
use OCA\Files_Trashbin\Trashbin;
use OCA\Files_Trashbin\Helper;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScanFileSystem extends Base {
	protected IUserManager $userManager;
	protected IRootFolder $rootFolder;
	protected IDBConnection $dbConnection;
	protected IL10N $l10n;

	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IDBConnection $dbConnection,
		IFactory $l10nFactory,
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->dbConnection = $dbConnection;
		$this->l10n = $l10nFactory->get('files_trashbin');
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('trashbin:scan')
			->setDescription('Rescan trashbin for a user, and fix inconsistencies if needed')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'scan all deleted files of the given user(s)'
			)
			->addOption(
				'all-users',
				null,
				InputOption::VALUE_NONE,
				'run action on all users'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string[] $users */
		$users = $input->getArgument('user_id');
		if ((!empty($users)) and ($input->getOption('all-users'))) {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		} elseif (!empty($users)) {
			foreach ($users as $user) {
				if ($this->userManager->userExists($user)) {
					$output->writeln("Restoring deleted files for user <info>$user</info>");
					$this->scanDeletedFiles($user, $output);
				} else {
					$output->writeln("<error>Unknown user $user</error>");
					return 1;
				}
			}
		} elseif ($input->getOption('all-users')) {
			$output->writeln('Restoring deleted files for all users');
			foreach ($this->userManager->getBackends() as $backend) {
				$name = get_class($backend);
				if ($backend instanceof IUserBackend) {
					$name = $backend->getBackendName();
				}
				$output->writeln("Restoring deleted files for users on backend <info>$name</info>");
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						$output->writeln("<info>$user</info>");
						$this->scanDeletedFiles($user, $output);
					}
					$offset += $limit;
				} while (count($users) >= $limit);
			}
		} else {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		}
		return 0;
	}

	/**
	 * Scan deleted files for the given user
	 */
	protected function scanDeletedFiles(string $uid, OutputInterface $output): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
		\OC_User::setUserId($uid);

		$filesInTrash = Helper::getTrashFiles('/', $uid);
		$filesInTrashDatabase = Trashbin::getLocations($uid);

		$trashCount = count($filesInTrash);
		$trashCountDatabase = count($filesInTrashDatabase);
		if ($trashCount === 0 && $trashCountDatabase === 0) {
			$output->writeln("User has no deleted files in the trashbin");
			return;
		}
		$output->writeln("Preparing to scan <info>$trashCount</info> files...");
		$count = 0;
		foreach ($filesInTrash as $trashFile) {
			$filename = $trashFile->getName();
			$timestamp = $trashFile->getMtime();
			$humanTime = $this->l10n->l('datetime', $timestamp);
			if (isset($filesInTrashDatabase[$filename][$timestamp])) {
				$count++;
				$output->writeln("File <info>$filename</info> originally deleted at <info>$humanTime</info> is clean");
				unset($filesInTrashDatabase[$filename][$timestamp]);
			} else {
				$output->writeln("<error>File <info>$filename</info> originally deleted at <info>$humanTime</info> is missing from database</error>");
			}
		}

		$output->writeln("Found <info>$count</info> clean files out of <info>$trashCount</info> files.");

		$filesInTrashDatabase = array_filter($filesInTrashDatabase);

		$trashCountDatabase = count($filesInTrashDatabase);

		$output->writeln("Found <info>$trashCountDatabase</info> files in database missing from storage.");
	}
}
