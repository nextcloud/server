<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Repair;

use Doctrine\DBAL\Driver\Statement;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Notification\IManager;

class RemoveLinkShares implements IRepairStep {
	/** @var IDBConnection */
	private $connection;
	/** @var IConfig */
	private $config;
	/** @var string[] */
	private $userToNotify = [];
	/** @var IGroupManager */
	private $groupManager;
	/** @var IManager */
	private $notificationManager;
	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(IDBConnection $connection,
								IConfig $config,
								IGroupManager $groupManager,
								IManager $notificationManager,
								ITimeFactory $timeFactory) {
		$this->connection = $connection;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->notificationManager = $notificationManager;
		$this->timeFactory = $timeFactory;
	}


	public function getName(): string {
		return 'Remove potentially over exposing share links';
	}

	private function shouldRun(): bool {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');

		if (version_compare($versionFromBeforeUpdate, '14.0.11', '<')) {
			return true;
		}
		if (version_compare($versionFromBeforeUpdate, '15.0.8', '<')) {
			return true;
		}
		if (version_compare($versionFromBeforeUpdate, '16.0.0', '<=')) {
			return true;
		}

		return false;
	}

	/**
	 * Delete the share
	 *
	 * @param int $id
	 */
	private function deleteShare(int $id) {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
		$qb->execute();
	}

	/**
	 * Get the total of affected shares
	 *
	 * @return int
	 */
	private function getTotal(): int {
		$sql = 'SELECT COUNT(*) AS `total`
 		FROM `*PREFIX*share`
		WHERE `id` IN (
			SELECT `s1`.`id`
			FROM (
				SELECT *
				FROM `*PREFIX*share`
				WHERE `parent` IS NOT NULL
				AND `share_type` = 3
			) AS s1
			JOIN `*PREFIX*share` AS s2
			ON `s1`.`parent` = `s2`.`id`
			WHERE (`s2`.`share_type` = 1 OR `s2`.`share_type` = 2)
			AND `s1`.`item_source` = `s2`.`item_source`
		)';
		$cursor = $this->connection->executeQuery($sql);
		$data = $cursor->fetchAll();
		$total = (int)$data[0]['total'];
		$cursor->closeCursor();

		return $total;
	}

	/**
	 * Get the cursor to fetch all the shares
	 *
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	private function getShares(): Statement {
		$sql = 'SELECT `s1`.`id`, `s1`.`uid_owner`, `s1`.`uid_initiator`
			FROM (
				SELECT *
				FROM `*PREFIX*share`
				WHERE `parent` IS NOT NULL
				AND `share_type` = 3
			) AS s1
			JOIN `*PREFIX*share` AS s2
			ON `s1`.`parent` = `s2`.`id`
			WHERE (`s2`.`share_type` = 1 OR `s2`.`share_type` = 2)
			AND `s1`.`item_source` = `s2`.`item_source`';
		$cursor = $this->connection->executeQuery($sql);
		return $cursor;
	}

	/**
	 * Process a single share
	 *
	 * @param array $data
	 */
	private function processShare(array $data) {
		$id = $data['id'];

		$this->addToNotify($data['uid_owner']);
		$this->addToNotify($data['uid_initiator']);

		$this->deleteShare((int)$id);
	}

	/**
	 * Update list of users to notify
	 *
	 * @param string $uid
	 */
	private function addToNotify(string $uid) {
		if (!isset($this->userToNotify[$uid])) {
			$this->userToNotify[$uid] = true;
		}
	}

	/**
	 * Send all notifications
	 */
	private function sendNotification() {
		$time = $this->timeFactory->getDateTime();

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('core')
			->setDateTime($time)
			->setObject('repair', 'exposing_links')
			->setSubject('repair_exposing_links', []);

		$users = array_keys($this->userToNotify);
		foreach ($users as $user) {
			$notification->setUser($user);
			$this->notificationManager->notify($notification);
		}
	}

	private function repair(IOutput $output) {
		$total = $this->getTotal();
		$output->startProgress($total);

		$shareCursor = $this->getShares();
		while($data = $shareCursor->fetch()) {
			$this->processShare($data);
			$output->advance();
		}
		$output->finishProgress();
		$shareCursor->closeCursor();

		// Notifiy all admins
		$adminGroup = $this->groupManager->get('admin');
		$adminUsers = $adminGroup->getUsers();
		foreach ($adminUsers as $user) {
			$this->addToNotify($user->getUID());
		}

		$output->info('Sending notifications to admins and affected users');
		$this->sendNotification();
	}

	public function run(IOutput $output) {
		if ($this->shouldRun()) {
			$output->info('Removing potentially over exposing link shares');
			$this->repair($output);
			$output->info('Removed potentially over exposing link shares');
		} else {
			$output->info('No need to remove link shares.');
		}
	}
}
