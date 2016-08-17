<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OC\Share\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IGroupManager;
use OC\Share20\DefaultShareProvider;

/**
 * Repairs shares for which the received folder was not properly deduplicated.
 *
 * An unmerged share can for example happen when sharing a folder with the same
 * user through multiple ways, like several groups and also directly, additionally
 * to group shares. Since 9.0.0 these would create duplicate entries "folder (2)",
 * one for every share. This repair step rearranges them so they only appear as a single
 * folder.
 */
class RepairUnmergedShares implements IRepairStep {

	/** @var \OCP\IConfig */
	protected $config;

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IQueryBuilder */
	private $queryGetSharesWithUsers;

	/** @var IQueryBuilder */
	private $queryUpdateSharePermissionsAndTarget;

	/** @var IQueryBuilder */
	private $queryUpdateShareInBatch;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OCP\IDBConnection $connection
	 */
	public function __construct(
		IConfig $config,
		IDBConnection $connection,
		IUserManager $userManager,
		IGroupManager $groupManager
	) {
		$this->connection = $connection;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	public function getName() {
		return 'Repair unmerged shares';
	}

	/**
	 * Builds prepared queries for reuse
	 */
	private function buildPreparedQueries() {
		/**
		 * Retrieve shares for a given user/group and share type
		 */
		$query = $this->connection->getQueryBuilder();
		$query
			->select('item_source', 'id', 'file_target', 'permissions', 'parent', 'share_type', 'stime')
			->from('share')
			->where($query->expr()->eq('share_type', $query->createParameter('shareType')))
			->andWhere($query->expr()->in('share_with', $query->createParameter('shareWiths')))
			->andWhere($query->expr()->in('item_type', $query->createParameter('itemTypes')))
			->orderBy('item_source', 'ASC')
			->addOrderBy('stime', 'ASC');

		$this->queryGetSharesWithUsers = $query;

		/**
		 * Updates the file_target to the given value for all given share ids.
		 *
		 * This updates several shares in bulk which is faster than individually.
		 */
		$query = $this->connection->getQueryBuilder();
		$query->update('share')
			->set('file_target', $query->createParameter('file_target'))
			->where($query->expr()->in('id', $query->createParameter('ids')));

		$this->queryUpdateShareInBatch = $query;

		/**
		 * Updates the share permissions and target path of a single share.
		 */
		$query = $this->connection->getQueryBuilder();
		$query->update('share')
			->set('permissions', $query->createParameter('permissions'))
			->set('file_target', $query->createParameter('file_target'))
			->where($query->expr()->eq('id', $query->createParameter('shareid')));

		$this->queryUpdateSharePermissionsAndTarget = $query;

	}

	private function getSharesWithUser($shareType, $shareWiths) {
		$groupedShares = [];

		$query = $this->queryGetSharesWithUsers;
		$query->setParameter('shareWiths', $shareWiths, IQueryBuilder::PARAM_STR_ARRAY);
		$query->setParameter('shareType', $shareType);
		$query->setParameter('itemTypes', ['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY);

		$shares = $query->execute()->fetchAll();

		// group by item_source
		foreach ($shares as $share) {
			if (!isset($groupedShares[$share['item_source']])) {
				$groupedShares[$share['item_source']] = [];
			}
			$groupedShares[$share['item_source']][] = $share;
		}
		return $groupedShares;
	}

	private function isPotentialDuplicateName($name) {
		return (preg_match('/\(\d+\)(\.[^\.]+)?$/', $name) === 1);
	}

	/**
	 * Decide on the best target name based on all group shares and subshares,
	 * goal is to increase the likeliness that the chosen name matches what
	 * the user is expecting.
	 *
	 * For this, we discard the entries with parenthesis "(2)".
	 * In case the user also renamed the duplicates to a legitimate name, this logic
	 * will still pick the most recent one as it's the one the user is most likely to
	 * remember renaming.
	 *
	 * If no suitable subshare is found, use the least recent group share instead.
	 *
	 * @param array $groupShares group share entries
	 * @param array $subShares sub share entries
	 *
	 * @return string chosen target name
	 */
	private function findBestTargetName($groupShares, $subShares) {
		$pickedShare = null;
		// sort by stime, this also properly sorts the direct user share if any
		@usort($subShares, function($a, $b) {
			return ((int)$a['stime'] - (int)$b['stime']);
		});

		foreach ($subShares as $subShare) {
			// skip entries that have parenthesis with numbers
			if ($this->isPotentialDuplicateName($subShare['file_target'])) {
				continue;
			}
			// pick any share found that would match, the last being the most recent
			$pickedShare = $subShare;
		}

		// no suitable subshare found
		if ($pickedShare === null) {
			// use least recent group share target instead
			$pickedShare = $groupShares[0];
		}

		return $pickedShare['file_target'];
	}

	/**
	 * Fix the given received share represented by the set of group shares
	 * and matching sub shares
	 *
	 * @param array $groupShares group share entries
	 * @param array $subShares sub share entries
	 *
	 * @return boolean false if the share was not repaired, true if it was
	 */
	private function fixThisShare($groupShares, $subShares) {
		if (empty($subShares)) {
			return false;
		}

		$groupSharesById = [];
		foreach ($groupShares as $groupShare) {
			$groupSharesById[$groupShare['id']] = $groupShare;
		}

		if ($this->isThisShareValid($groupSharesById, $subShares)) {
			return false;
		}

		$targetPath = $this->findBestTargetName($groupShares, $subShares);

		// check whether the user opted out completely of all subshares
		$optedOut = true;
		foreach ($subShares as $subShare) {
			if ((int)$subShare['permissions'] !== 0) {
				$optedOut = false;
				break;
			}
		}

		$shareIds = [];
		foreach ($subShares as $subShare) {
			// only if the user deleted some subshares but not all, adjust the permissions of that subshare
			if (!$optedOut && (int)$subShare['permissions'] === 0 && (int)$subShare['share_type'] === DefaultShareProvider::SHARE_TYPE_USERGROUP) {
				// set permissions from parent group share
				$permissions = $groupSharesById[$subShare['parent']]['permissions'];

				// fix permissions and target directly
				$query = $this->queryUpdateSharePermissionsAndTarget;
				$query->setParameter('shareid', $subShare['id']);
				$query->setParameter('file_target', $targetPath);
				$query->setParameter('permissions', $permissions);
				$query->execute();
			} else {
				// gather share ids for bulk target update
				if ($subShare['file_target'] !== $targetPath) {
					$shareIds[] = (int)$subShare['id'];
				}
			}
		}

		if (!empty($shareIds)) {
			$query = $this->queryUpdateShareInBatch;
			$query->setParameter('ids', $shareIds, IQueryBuilder::PARAM_INT_ARRAY);
			$query->setParameter('file_target', $targetPath);
			$query->execute();
		}

		return true;
	}

	/**
	 * Checks whether the number of group shares is balanced with the child subshares.
	 * If all group shares have exactly one subshare, and the target of every subshare
	 * is the same, then the share is valid.
	 * If however there is a group share entry that has no matching subshare, it means
	 * we're in the bogus situation and the whole share must be repaired
	 *
	 * @param array $groupSharesById
	 * @param array $subShares
	 *
	 * @return true if the share is valid, false if it needs repair
	 */
	private function isThisShareValid($groupSharesById, $subShares) {
		$foundTargets = [];

		// every group share needs to have exactly one matching subshare
		foreach ($subShares as $subShare) {
			$foundTargets[$subShare['file_target']] = true;
			if (count($foundTargets) > 1) {
				// not all the same target path value => invalid
				return false;
			}
			if (isset($groupSharesById[$subShare['parent']])) {
				// remove it from the list as we found it
				unset($groupSharesById[$subShare['parent']]);
			}
		}

		// if we found one subshare per group entry, the set will be empty.
		// If not empty, it means that one of the group shares did not have
		// a matching subshare entry.
		return empty($groupSharesById);
	}

	/**
	 * Detect unmerged received shares and merge them properly
	 */
	private function fixUnmergedShares(IOutput $out, IUser $user) {
		$groups = $this->groupManager->getUserGroupIds($user);
		if (empty($groups)) {
			// user is in no groups, so can't have received group shares
			return;
		}

		// get all subshares grouped by item source
		$subSharesByItemSource = $this->getSharesWithUser(DefaultShareProvider::SHARE_TYPE_USERGROUP, [$user->getUID()]);

		// because sometimes one wants to give the user more permissions than the group share
		$userSharesByItemSource = $this->getSharesWithUser(Constants::SHARE_TYPE_USER, [$user->getUID()]);

		if (empty($subSharesByItemSource) && empty($userSharesByItemSource)) {
			// nothing to repair for this user, no need to do extra queries
			return;
		}

		$groupSharesByItemSource = $this->getSharesWithUser(Constants::SHARE_TYPE_GROUP, $groups);
		if (empty($groupSharesByItemSource) && empty($userSharesByItemSource)) {
			// nothing to repair for this user
			return;
		}

		foreach ($groupSharesByItemSource as $itemSource => $groupShares) {
			$subShares = [];
			if (isset($subSharesByItemSource[$itemSource])) {
				$subShares = $subSharesByItemSource[$itemSource];
			}

			if (isset($userSharesByItemSource[$itemSource])) {
				// add it to the subshares to get a similar treatment
				$subShares = array_merge($subShares, $userSharesByItemSource[$itemSource]);
			}

			$this->fixThisShare($groupShares, $subShares);
		}
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

	public function run(IOutput $output) {
		$ocVersionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');
		if (version_compare($ocVersionFromBeforeUpdate, '9.1.0.16', '<')) {
			// this situation was only possible between 9.0.0 and 9.0.3 included

			$function = function(IUser $user) use ($output) {
				$this->fixUnmergedShares($output, $user);
				$output->advance();
			};

			$this->buildPreparedQueries();

			$userCount = $this->countUsers();
			$output->startProgress($userCount);

			$this->userManager->callForAllUsers($function);

			$output->finishProgress();
		}
	}
}
