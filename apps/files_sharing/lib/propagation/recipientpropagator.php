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

use OC\Files\Cache\ChangePropagator;
use OC\Files\View;
use OC\Share\Share;

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
	 * @param string $userId current user, must match the propagator's
	 * user
	 * @param \OC\Files\Cache\ChangePropagator $changePropagator change propagator
	 * initialized with a view for $user
	 * @param \OCP\IConfig $config
	 * @param PropagationManager $manager
	 */
	public function __construct($userId, $changePropagator, $config, PropagationManager $manager) {
		$this->userId = $userId;
		$this->changePropagator = $changePropagator;
		$this->config = $config;
		$this->manager = $manager;
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
		foreach ($shares as $share) {
			$updateTime = $this->config->getAppValue('files_sharing', $share['id'], 0);
			if ($updateTime >= $userTime) {
				$dirty[] = $share;
			}
		}
		return $dirty;
	}

	/**
	 * @param array $share
	 * @param int $time
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

	public function propagateById($id) {
		$shares = Share::getAllSharesForFileId($id);
		foreach ($shares as $share) {
			// propagate down the share tree
			$this->markDirty($share, microtime(true));

			// propagate up the share tree
			$user = $share['uid_owner'];
			if($user !== $this->userId) {
				$view = new View('/' . $user . '/files');
				$path = $view->getPath($share['file_source']);
				$watcher = new ChangeWatcher($view, $this->manager->getSharePropagator($user));
				$watcher->writeHook(['path' => $path]);
			}
		}
	}
}
