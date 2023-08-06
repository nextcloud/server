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

use OC\Core\Command\Base;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreAllFiles extends Base {

	private const SCOPE_ALL = 0;
	private const SCOPE_USER = 1;
	private const SCOPE_GROUPFOLDERS = 2;

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var ITrashManager */
	protected $trashManager;

	/** @var IL10N */
	protected $l10n;

	/**
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbConnection
	 * @param ITrashManager $trashManager
	 * @param IFactory $l10nFactory
	 */
	public function __construct(IRootFolder $rootFolder, IUserManager $userManager, IDBConnection $dbConnection, ITrashManager $trashManager, IFactory $l10nFactory) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->dbConnection = $dbConnection;
		$this->trashManager = $trashManager;
		$this->l10n = $l10nFactory->get('files_trashbin');
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('trashbin:restore')
			->setDescription('Restore all deleted files according to the given filters')
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
			)
			->addOption(
				'scope',
				's',
				InputOption::VALUE_OPTIONAL,
				'Restore files from the given scope. Possible values are "user", "groupfolders" or "all"',
				'user'
			)
			->addOption(
				'restore-from',
				'f',
				InputOption::VALUE_OPTIONAL,
				'Only restore files deleted after the given timestamp'
			)
			->addOption(
				'restore-to',
				't',
				InputOption::VALUE_OPTIONAL,
				'Only restore files deleted before the given timestamp'
			)
			->addOption(
				'dry-run',
				'd',
				InputOption::VALUE_NONE,
				'Only show which files would be restored but do not perform any action'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string[] $users */
		$users = $input->getArgument('user_id');
		if ((!empty($users)) and ($input->getOption('all-users'))) {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		}

		[$scope, $restoreFrom, $restoreTo, $dryRun] = $this->parseArgs($input);

		if (!empty($users)) {
			foreach ($users as $user) {
				if ($this->userManager->userExists($user)) {
					$output->writeln("Restoring deleted files for user <info>$user</info>");
					$this->restoreDeletedFiles($user, $scope, $restoreFrom, $restoreTo, $dryRun, $output);
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
						$this->restoreDeletedFiles($user, $scope, $restoreFrom, $restoreTo, $dryRun, $output);
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
	 * @param int $scope
	 * @param int|null $restoreFrom
	 * @param int|null $restoreTo
	 * @param bool $dryRun
	 * @param OutputInterface $output
	 */
	protected function restoreDeletedFiles(string $uid, int $scope, ?int $restoreFrom, ?int $restoreTo, bool $dryRun, OutputInterface $output): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
		\OC_User::setUserId($uid);

		$user = $this->userManager->get($uid);
		$userTrashItems = $this->filterTrashItems(
			$this->trashManager->listTrashRoot($user),
			$scope,
			$restoreFrom,
			$restoreTo,
			$output);

		$trashCount = count($userTrashItems);
		if ($trashCount == 0) {
			$output->writeln("User has no deleted files in the trashbin");
			return;
		}
		$prepMsg = $dryRun ? 'Would restore' : 'Preparing to restore';
		$output->writeln("$prepMsg <info>$trashCount</info> files...");
		$count = 0;
		foreach($userTrashItems as $trashItem) {
			$filename = $trashItem->getName();
			$humanTime = $this->l10n->l('datetime', $trashItem->getDeletedTime());
			// We use getTitle() here instead of getOriginalLocation() because
			// for groupfolders this contains the groupfolder name itself as prefix
			// which makes it more human readable
			$location = $trashItem->getTitle();

			if ($dryRun) {
				$output->writeln("Would restore <info>$filename</info> originally deleted at <info>$humanTime</info> to <info>/$location</info>");
				continue;
			}

			$output->write("File <info>$filename</info> originally deleted at <info>$humanTime</info> restoring to <info>/$location</info>:");

			try {
				$trashItem->getTrashBackend()->restoreItem($trashItem);
			} catch (\Throwable $e) {
				$output->writeln(" <error>failed</error>");
				$output->writeln("<error>" . $e->getMessage() . "</error>");
				$output->writeln("<error>" . $e->getTraceAsString() . "</error>", OutputInterface::VERBOSITY_VERY_VERBOSE);
				continue;
			}

			$count = $count + 1;
			$output->writeln(" <info>success</info>");
		}
		
		if (!$dryRun) {
			$output->writeln("Successfully restored <info>$count</info> out of <info>$trashCount</info> files.");
		}
	}

	protected function parseArgs(InputInterface $input): array {
		$restoreFrom = $this->parseTimestamp($input->getOption('restore-from'));
		$restoreTo = $this->parseTimestamp($input->getOption('restore-to'));

		if ($restoreFrom !== null and $restoreTo !== null and $restoreFrom > $restoreTo) {
			throw new InvalidOptionException('restore-from must be before restore-to');
		}

		return [
			$this->parseScope($input->getOption('scope')),
			$restoreFrom,
			$restoreTo,
			$input->getOption('dry-run')
		];
	}

	/**
	 * @param string $scope
	 * @return int
	 */
	protected function parseScope(string $scope): int {
		switch ($scope) {
			case 'user':
				return self::SCOPE_USER;
			case 'groupfolders':
				return self::SCOPE_GROUPFOLDERS;
			case 'all':
				return self::SCOPE_ALL;
			default:
				throw new InvalidOptionException("Invalid scope '$scope'");
		}
	}

	/**
	 * @param string|null $timestamp
	 * @return int|null
	 */
	protected function parseTimestamp(?string $timestamp): ?int {
		if ($timestamp === null) {
			return null;
		}
		$timestamp = strtotime($timestamp);
		if ($timestamp === false) {
			throw new InvalidOptionException("Invalid timestamp '$timestamp'");
		}
		return $timestamp;
	}

	/**
	 * @param ITrashItem[] $trashItem
	 * @param int $scope
	 * @param int|null $restoreFrom
	 * @param int|null $restoreTo
	 * @param OutputInterface $output
	 * @return ITrashItem[]
	 */
	protected function filterTrashItems(array $trashItems, int $scope, ?int $restoreFrom, ?int $restoreTo, OutputInterface $output): array {
		$filteredTrashItems = [];
		foreach ($trashItems as $trashItem) {
			// Check scope with exact class names
			if ($scope === self::SCOPE_USER && get_class($trashItem) !== \OCA\Files_Trashbin\Trash\TrashItem::class) {
				$output->writeln("Skipping <info>" . $trashItem->getName() . "</info> because it is not a user trash item", OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}
			if ($scope === self::SCOPE_GROUPFOLDERS && get_class($trashItem) !== \OCA\GroupFolders\Trash\GroupTrashItem::class) {
				$output->writeln("Skipping <info>" . $trashItem->getName() . "</info> because it is not a groupfolders trash item", OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}

			// Check left timestamp boundary
			if ($restoreFrom !== null && $trashItem->getDeletedTime() <= $restoreFrom) {
				$output->writeln("Skipping <info>" . $trashItem->getName() . "</info> because it was deleted before the restore-from timestamp", OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}

			// Check right timestamp boundary
			if ($restoreTo !== null && $trashItem->getDeletedTime() >= $restoreTo) {
				$output->writeln("Skipping <info>" . $trashItem->getName() . "</info> because it was deleted after the restore-to timestamp", OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}
			
			$filteredTrashItems[] = $trashItem;
		}
		return $filteredTrashItems;
	}
}
