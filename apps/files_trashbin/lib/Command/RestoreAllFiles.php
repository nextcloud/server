<?php
/**
 * @copyright Copyright (c) 2021, Caitlin Hogan (cahogan16@gmail.com)
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
namespace OCA\Files_Trashbin\Command;

use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserBackend;
use OC\Files\View;
use OC\Files\Filesystem;
use OC_User;
use OCA\Files_Trashbin\Trashbin;
use OCA\Files_Trashbin\Helper;
use OCA\Files\Exception\TransferOwnershipException;
use OCA\Files\Service\OwnershipTransferService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreAllFiles extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/**
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbConnection
	 */
	public function __construct(IRootFolder $rootFolder, IUserManager $userManager, IDBConnection $dbConnection) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->dbConnection = $dbConnection;
	}

	protected function configure() {
		$this
			->setName('trashbin:restore')
			->setDescription('Restore all deleted files')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'restore all deleted files of the given user(s)'
			)
			->addOption(
				'all-users',
				null,
				InputOption::VALUE_NONE,
				'run action on all users'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$users = $input->getArgument('user_id');
		if ((!empty($users)) and ($input->getOption('all-users'))) {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		} elseif (!empty($users)) {
			foreach ($users as $user) {
				if ($this->userManager->userExists($user)) {
					$output->writeln("Restoring deleted files for user <info>$user</info>");
					$this->restoreDeletedFiles($user, $output);
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
						$this->restoreDeletedFiles($user, $output);
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
	 * Restore deleted files for the given user
	 *
	 * @param string $uid
     * @param OutputInterface $output
	 */
	protected function restoreDeletedFiles($uid, $output) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
        $filesInTrash = Helper::getTrashFiles('/', $uid, 'mtime');
        $trashCount = count($filesInTrash);
        $output->writeln("Preparing to restore <info>$trashCount</info> files...");
        $count = 0;
        foreach ($filesInTrash as $trashFile) {
            $filename = $trashFile->getName();
            $timestamp = $trashFile->getMtime();
            $output->writeln("File <info>$filename</info> originally deleted at timestamp <info>$timestamp</info>.");
            $file = $filename . '.d' . $timestamp;
            $location = Trashbin::getLocation($uid, $filename, $timestamp);;
            OC_User::setUserId($uid);
            $user = OC_User::getUser();
            $output->writeln("location $location");
            $result = Trashbin::restore($file, $filename, $timestamp);
            $count = $count + $result;
            $output->writeln($result);
        }

        $output->writeln("Successfully restored <info>$count</info> out of <info>$trashCount</info> files.");
	}
}
?>