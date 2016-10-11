<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Repair;

use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class RemoveRootShares
 *
 * @package OC\Repair
 */
class RemoveRootShares implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/**
	 * RemoveRootShares constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IDBConnection $connection,
								IUserManager $userManager,
								IRootFolder $rootFolder) {
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Remove shares of a users root folder';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if ($this->rootSharesExist()) {
			$this->removeRootShares($output);
		}
	}

	/**
	 * @param IOutput $output
	 */
	private function removeRootShares(IOutput $output) {
		$function = function(IUser $user) use ($output) {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$fileId = $userFolder->getId();

			$qb = $this->connection->getQueryBuilder();
			$qb->delete('share')
				->where($qb->expr()->eq('file_source', $qb->createNamedParameter($fileId)))
				->andWhere($qb->expr()->orX(
					$qb->expr()->eq('item_type', $qb->expr()->literal('file')),
					$qb->expr()->eq('item_type', $qb->expr()->literal('folder'))
				));

			$qb->execute();

			$output->advance();
		};

		$userCount = $this->countUsers();
		$output->startProgress($userCount);

		$this->userManager->callForAllUsers($function);

		$output->finishProgress();
	}

	/**
	 * Count all the users
	 *
	 * @return int
	 */
	private function countUsers() {
		$allCount = $this->userManager->countUsers();

		$totalCount = 0;
		foreach ($allCount as $backend => $count) {
			$totalCount += $count;
		}

		return $totalCount;
	}

	/**
	 * Verify if this repair steps is required
	 * It *should* not be necessary in most cases and it can be very
	 * costly.
	 *
	 * @return bool
	 */
	private function rootSharesExist() {
		$qb = $this->connection->getQueryBuilder();
		$qb2 = $this->connection->getQueryBuilder();

		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->eq('path', $qb->expr()->literal('files')));

		$qb2->select('id')
			->from('share')
			->where($qb2->expr()->in('file_source', $qb2->createFunction($qb->getSQL())))
			->andWhere($qb2->expr()->orX(
				$qb2->expr()->eq('item_type', $qb->expr()->literal('file')),
				$qb2->expr()->eq('item_type', $qb->expr()->literal('folder'))
			))
			->setMaxResults(1);

		$cursor = $qb2->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			return false;
		}

		return true;
	}
}

