<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RepairShareOwnership extends Command {
	private IDBConnection $dbConnection;
	private IManager $shareManager;
	private IUserManager $userManager;

	public function __construct(
		IDBConnection $dbConnection,
		IManager $shareManager,
		IUserManager $userManager
	) {
		$this->dbConnection = $dbConnection;
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:repair-share-owner')
			->setDescription('repair invalid share-owner entries in the database')
			->addOption('dry-run', null, InputOption::VALUE_NONE, "List detected issues without fixing them")
			->addArgument('user', InputArgument::OPTIONAL, "User to fix incoming shares for, if omitted all users will be fixed");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');
		$userId = $input->getArgument('user');
		if ($userId) {
			$user = $this->userManager->get($userId);
			if (!$user) {
				$output->writeln("<error>user $userId not found</error>");
				return 1;
			}
			$found = $this->repairWrongShareOwnershipForUser($user, $dryRun);
		} else {
			$found = [];
			$userCount = $this->userManager->countSeenUsers();
			$progress = new ProgressBar($output, $userCount);
			$this->userManager->callForSeenUsers(function (IUser $user) use ($dryRun, &$found, $progress) {
				$progress->advance();
				$found = array_merge($found, $this->repairWrongShareOwnershipForUser($user, $dryRun));
			});
			$progress->finish();
			$output->writeln("");
		}
		foreach ($found as $item) {
			$output->writeln($item);
		}
		return 0;
	}

	protected function repairWrongShareOwnershipForUser(IUser $user, bool $dryRun = true): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$brokenShare = $qb
			->select('s.id', 'm.user_id', 's.uid_owner', 's.uid_initiator', 's.share_with', 's.share_type')
			->from('share', 's')
			->join('s', 'filecache', 'f', $qb->expr()->eq('s.item_source', $qb->expr()->castColumn('f.fileid', IQueryBuilder::PARAM_STR)))
			->join('s', 'mounts', 'm', $qb->expr()->eq('f.storage', 'm.storage_id'))
			->where($qb->expr()->neq('m.user_id', 's.uid_owner'))
			->andWhere($qb->expr()->eq($qb->func()->concat($qb->expr()->literal('/'), 'm.user_id', $qb->expr()->literal('/')), 'm.mount_point'))
			->andWhere($qb->expr()->eq('s.share_with', $qb->createNamedParameter($user->getUID())))
			->executeQuery()
			->fetchAll();

		$found = [];

		foreach ($brokenShare as $queryResult) {
			$shareId = (int) $queryResult['id'];
			$shareType = (int) $queryResult['share_type'];
			$initiator = $queryResult['uid_initiator'];
			$receiver = $queryResult['share_with'];
			$owner = $queryResult['uid_owner'];
			$mountOwner = $queryResult['user_id'];

			$found[] = "Found share from $initiator to $receiver, owned by $owner, that should be owned by $mountOwner";

			if ($dryRun) {
				continue;
			}

			$provider = $this->shareManager->getProviderForType($shareType);
			$share = $provider->getShareById($shareId);

			if ($share->getShareOwner() === $share->getSharedBy()) {
				$share->setSharedBy($mountOwner);
			}
			$share->setShareOwner($mountOwner);

			$this->shareManager->updateShare($share);
		}

		return $found;
	}
}
