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
namespace OC\Repair;

use OCP\IDBConnection;
use OCP\Share\IManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairShareOwnership implements IRepairStep {
	private IDBConnection $dbConnection;
	private IManager $shareManager;

	public function __construct(
		IDBConnection $dbConnection,
		IManager $shareManager
	) {
		$this->dbConnection = $dbConnection;
		$this->shareManager = $shareManager;
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'Repair shares ownership';
	}

	protected function repairWrongShareOwnership(IOutput $output, bool $dryRun = true) {
		$qb = $this->dbConnection->getQueryBuilder();
		$brokenShare = $qb
			->select('s.id', 'm.user_id', 's.uid_owner', 's.uid_initiator', 's.share_with')
			->from('share', 's')
			->join('s', 'filecache', 'f', $qb->expr()->eq('s.item_source', 'f.fileid'))
			->join('s', 'mounts', 'm', $qb->expr()->eq('f.storage', 'm.storage_id'))
			->where($qb->expr()->neq('m.user_id', 's.uid_owner'))
			->andWhere($qb->expr()->eq($qb->func()->concat($qb->expr()->literal('/'), 'm.user_id', $qb->expr()->literal('/')), 'm.mount_point'))
			->executeQuery()
			->fetchAll();

		foreach ($brokenShare as $queryResult) {
			$shareId = $queryResult['id'];
			$initiator = $queryResult['uid_initiator'];
			$receiver = $queryResult['share_with'];
			$owner = $queryResult['uid_owner'];
			$mountOwner = $queryResult['user_id'];

			$output->info("Found share from $initiator to $receiver, owned by $owner, that should be owned by $mountOwner");

			if ($dryRun) {
				continue;
			}

			$share = $this->shareManager->getShareById($shareId);

			if ($share->getShareOwner() === $share->getSharedBy()) {
				$share->setSharedBy($mountOwner);
			}
			$share->setShareOwner($mountOwner);

			$this->shareManager->updateShare($share);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function run(IOutput $output) {
		$this->repairWrongShareOwnership($output);
	}
}
