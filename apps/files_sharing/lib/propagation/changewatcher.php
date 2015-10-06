<?php
/**
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
use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Files_Sharing\SharedMount;

/**
 * Watch for changes made in a shared mount and propagate the changes to the share owner
 */
class ChangeWatcher {
	/**
	 * The user view for the logged in user
	 *
	 * @var \OC\Files\View
	 */
	private $baseView;

	/**
	 * @var RecipientPropagator
	 */
	private $recipientPropagator;

	/**
	 * @param \OC\Files\View $baseView the view for the logged in user
	 * @param RecipientPropagator $recipientPropagator
	 */
	public function __construct(View $baseView, RecipientPropagator $recipientPropagator) {
		$this->baseView = $baseView;
		$this->recipientPropagator = $recipientPropagator;
	}


	public function writeHook($params) {
		$path = $params['path'];
		$fullPath = $this->baseView->getAbsolutePath($path);
		$mount = $this->baseView->getMount($path);
		if ($mount instanceof SharedMount) {
			$this->propagateForOwner($mount->getShare(), $mount->getInternalPath($fullPath), $mount->getOwnerPropagator());
		}
		$info = $this->baseView->getFileInfo($path);
		if ($info) {
			// trigger propagation if the subject of the write hook is shared.
			// if a parent folder of $path is shared the propagation will be triggered from the change propagator hooks
			$this->recipientPropagator->propagateById($info->getId());
		}
	}

	public function renameHook($params) {
		$path1 = $params['oldpath'];
		$path2 = $params['newpath'];
		$fullPath1 = $this->baseView->getAbsolutePath($path1);
		$fullPath2 = $this->baseView->getAbsolutePath($path2);
		$mount1 = $this->baseView->getMount($path1);
		$mount2 = $this->baseView->getMount($path2);
		if ($mount1 instanceof SharedMount and $mount1->getInternalPath($fullPath1) !== '') {
			$this->propagateForOwner($mount1->getShare(), $mount1->getInternalPath($fullPath1), $mount1->getOwnerPropagator());
		}
		if ($mount1 !== $mount2 and $mount2 instanceof SharedMount and $mount2->getInternalPath($fullPath2) !== '') {
			$this->propagateForOwner($mount2->getShare(), $mount2->getInternalPath($fullPath2), $mount2->getOwnerPropagator());
		}
	}

	/**
	 * @param array $share
	 * @param string $internalPath
	 * @param \OC\Files\Cache\ChangePropagator $propagator
	 */
	private function propagateForOwner($share, $internalPath, ChangePropagator $propagator) {
		// note that we have already set up the filesystem for the owner when mounting the share
		$view = new View('/' . $share['uid_owner'] . '/files');

		$shareRootPath = $view->getPath($share['item_source']);
		if (!is_null($shareRootPath)) {
			$path = $shareRootPath . '/' . $internalPath;
			$path = Filesystem::normalizePath($path);
			$propagator->addChange($path);
			$propagator->propagateChanges();
		}
	}

	public function permissionsHook($params) {
		$share = $params['share'];

		if ($share['item_type'] === 'file' || $share['item_type'] === 'folder') {
			$this->recipientPropagator->markDirty($share, microtime(true));
		}
	}
}
