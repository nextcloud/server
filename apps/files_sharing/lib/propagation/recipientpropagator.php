<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OCA\Files_Sharing\Propagation;

use Doctrine\DBAL\Connection;
use OC\Files\Cache\ChangePropagator;
use OC\Files\View;
use OC\Share\Share;
use OCP\IGroupManager;
use OCP\IUser;

/**
 * Propagate etags for share recipients
 */
class RecipientPropagator {
	/**
	 * @var string
	 */
	protected $userId;

	/**
	 * @var \OC\Files\Cache\ChangePropagator
	 */
	protected $changePropagator;

	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var PropagationManager
	 */
	private $manager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IUser
	 */
	private $user;

	/**
	 * @param IUser $user current user, must match the propagator's
	 * user
	 * @param \OC\Files\Cache\ChangePropagator $changePropagator change propagator
	 * initialized with a view for $user
	 * @param \OCP\IConfig $config
	 * @param PropagationManager $manager
	 * @param IGroupManager $groupManager
	 */
	public function __construct(IUser $user, $changePropagator, $config, PropagationManager $manager, IGroupManager $groupManager) {
		$this->userId = $user->getUID();
		$this->user = $user;
		$this->changePropagator = $changePropagator;
		$this->config = $config;
		$this->manager = $manager;
		$this->groupManager = $groupManager;
	}

	/**
	 * Propagate the etag changes for all shares marked as dirty and mark the shares as clean
	 *
	 * @param array $shares the shares for the users
	 * @param int $time
	 */
	public function propagateDirtyMountPoints(array $shares, $time = null) {
		if ($time === null) {
			$time = microtime(true);
		}
		$dirtyShares = $this->getDirtyShares($shares);
		foreach ($dirtyShares as $share) {
			$this->changePropagator->addChange($share['file_target']);
		}
		if (count($dirtyShares)) {
			$this->config->setUserValue($this->userId, 'files_sharing', 'last_propagate', $time);
			$this->changePropagator->propagateChanges(floor($time));
		}
	}

	/**
	 * Get all shares we need to update the etag for
	 *
	 * @param array $shares the shares for the users
	 * @return string[]
	 */
	protected function getDirtyShares($shares) {
		$dirty = [];
		$userTime = $this->config->getUserValue($this->userId, 'files_sharing', 'last_propagate', 0);
		$sharePropagations = [];
		foreach ($shares as $share) {
			$sharePropagations[(int) $share['id']] = 0;
		}

		$sharePropagations = $this->getPropagationTimestampForShares($sharePropagations);

		foreach ($shares as $share) {
			if ($sharePropagations[$share['id']] >= $userTime) {
				$dirty[] = $share;
			}
		}
		return $dirty;
	}

	/**
	 * Load the last dirty timestamp from the appconfig table
	 *
	 * @param int[] $sharePropagations
	 * @return int[]
	 */
	protected function getPropagationTimestampForShares(array $sharePropagations) {
		$sql = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$allShareIds = array_keys($sharePropagations);

		$shareIdChunks = array_chunk($allShareIds, 50);

		$sql->select(['configkey', 'configvalue'])
			->from('appconfig')
			->where($sql->expr()->eq('appid', $sql->createParameter('appid')))
			->andWhere($sql->expr()->in('configkey', $sql->createParameter('shareids')))
			->setParameter('appid', 'files_sharing', \PDO::PARAM_STR);

		foreach ($shareIdChunks as $shareIds) {
			$sql->setParameter('shareids', $shareIds, Connection::PARAM_STR_ARRAY);
			$result = $sql->execute();

			while ($row = $result->fetch()) {
				$sharePropagations[(int) $row['configkey']] = $row['configvalue'];
			}
			$result->closeCursor();
		}

		return $sharePropagations;
	}

	/**
	 * @param array $share
	 * @param float $time
	 */
	public function markDirty($share, $time = null) {
		if ($time === null) {
			$time = microtime(true);
		}
		$this->config->setAppValue('files_sharing', $share['id'], $time);
	}

	/**
	 * Listen on the propagator for updates made to shares owned by a user
	 *
	 * @param \OC\Files\Cache\ChangePropagator $propagator
	 * @param string $owner
	 */
	public function attachToPropagator(ChangePropagator $propagator, $owner) {
		$propagator->listen('\OC\Files', 'propagate', function ($path, $entry) use ($owner) {
			$this->propagateById($entry['fileid']);
		});
	}

	protected $propagatingIds = [];

	public function propagateById($id) {
		if (isset($this->propagatingIds[$id])) {
			return;
		}
		$this->propagatingIds[$id] = true;
		$shares = Share::getAllSharesForFileId($id);
		foreach ($shares as $share) {
			// propagate down the share tree
			$this->markDirty($share, microtime(true));

			// propagate up the share tree
			if ($this->isRecipientOfShare($share)) {
				$user = $share['uid_owner'];
				$view = new View('/' . $user . '/files');
				$path = $view->getPath($share['file_source']);
				$watcher = new ChangeWatcher($view, $this->manager->getSharePropagator($user));
				$watcher->writeHook(['path' => $path]);
			}
		}

		unset($this->propagatingIds[$id]);
	}

	/**
	 * Check if the user for this recipient propagator is the recipient of a share
	 *
	 * @param array $share
	 * @return bool
	 */
	private function isRecipientOfShare($share) {
		if ($share['share_with'] === $this->userId && $share['share_type'] == \OCP\Share::SHARE_TYPE_USER) { // == since 'share_type' is a string
			return true;
		}
		if ($share['share_type'] == \OCP\Share::SHARE_TYPE_GROUP) {
			$groups = $this->groupManager->getUserGroupIds($this->user);
			return in_array($share['share_with'], $groups);
		}
		return false;
	}
}
