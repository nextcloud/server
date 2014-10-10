<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_External;

use OC\Files\Filesystem;

/**
 * Updates the etag of parent folders whenever a new external storage mount
 * point has been created or deleted. Updates need to be triggered using
 * the updateHook() method.
 *
 * There are two modes of operation:
 * - for personal mount points, the etag is propagated directly
 * - for system mount points, a dirty flag is saved in the configuration and
 * the etag will be updated the next time propagateDirtyMountPoints() is called
 */
class EtagPropagator {
	/**
	 * @var \OCP\IUser
	 */
	protected $user;

	/**
	 * @var \OC\Files\Cache\ChangePropagator
	 */
	protected $changePropagator;

	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @param \OCP\IUser $user current user, must match the propagator's
	 * user
	 * @param \OC\Files\Cache\ChangePropagator $changePropagator change propagator
	 * initialized with a view for $user
	 * @param \OCP\IConfig $config
	 */
	public function __construct($user, $changePropagator, $config) {
		$this->user = $user;
		$this->changePropagator = $changePropagator;
		$this->config = $config;
	}

	/**
	 * Propagate the etag changes for all mountpoints marked as dirty and mark the mountpoints as clean
	 *
	 * @param int $time
	 */
	public function propagateDirtyMountPoints($time = null) {
		if ($time === null) {
			$time = time();
		}
		$mountPoints = $this->getDirtyMountPoints();
		foreach ($mountPoints as $mountPoint) {
			$this->changePropagator->addChange($mountPoint);
			$this->config->setUserValue($this->user->getUID(), 'files_external', $mountPoint, $time);
		}
		if (count($mountPoints)) {
			$this->changePropagator->propagateChanges($time);
		}
	}

	/**
	 * Get all mountpoints we need to update the etag for
	 *
	 * @return string[]
	 */
	protected function getDirtyMountPoints() {
		$dirty = array();
		$mountPoints = $this->config->getAppKeys('files_external');
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, 1) === '/') {
				$updateTime = $this->config->getAppValue('files_external', $mountPoint);
				$userTime = $this->config->getUserValue($this->user->getUID(), 'files_external', $mountPoint);
				if ($updateTime > $userTime) {
					$dirty[] = $mountPoint;
				}
			}
		}
		return $dirty;
	}

	/**
	 * @param string $mountPoint
	 * @param int $time
	 */
	protected function markDirty($mountPoint, $time = null) {
		if ($time === null) {
			$time = time();
		}
		$this->config->setAppValue('files_external', $mountPoint, $time);
	}

	/**
	 * Update etags for mount points for known user
	 * For global or group mount points, updating the etag for every user is not feasible
	 * instead we mark the mount point as dirty and update the etag when the filesystem is loaded for the user
	 * For personal mount points, the change is propagated directly
	 *
	 * @param array $params hook parameters
	 * @param int $time update time to use when marking a mount point as dirty
	 */
	public function updateHook($params, $time = null) {
		if ($time === null) {
			$time = time();
		}
		$users = $params[Filesystem::signal_param_users];
		$type = $params[Filesystem::signal_param_mount_type];
		$mountPoint = $params[Filesystem::signal_param_path];
		$mountPoint = Filesystem::normalizePath($mountPoint);
		if ($type === \OC_Mount_Config::MOUNT_TYPE_GROUP or $users === 'all') {
			$this->markDirty($mountPoint, $time);
		} else {
			$this->changePropagator->addChange($mountPoint);
			$this->changePropagator->propagateChanges($time);
		}
	}
}
