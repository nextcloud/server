<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Propagation;

use OC\Files\Cache\ChangePropagator;
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

	function __construct(View $baseView) {
		$this->baseView = $baseView;
	}


	public function writeHook($params) {
		$path = $params['path'];
		$fullPath = $this->baseView->getAbsolutePath($path);
		$mount = $this->baseView->getMount($path);
		if ($mount instanceof SharedMount) {
			$this->propagateForOwner($mount->getShare(), $mount->getInternalPath($fullPath), $mount->getOwnerPropagator());
		}
	}

	public function renameHook($params) {
		$path1 = $params['oldpath'];
		$path2 = $params['newpath'];
		$fullPath1 = $this->baseView->getAbsolutePath($path1);
		$fullPath2 = $this->baseView->getAbsolutePath($path2);
		$mount1 = $this->baseView->getMount($path1);
		$mount2 = $this->baseView->getMount($path2);
		if ($mount1 instanceof SharedMount) {
			$this->propagateForOwner($mount1->getShare(), $mount1->getInternalPath($fullPath1), $mount1->getOwnerPropagator());
		}
		if ($mount1 !== $mount2 and $mount2 instanceof SharedMount) {
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
		if ($shareRootPath) {
			$path = $shareRootPath . '/' . $internalPath;
			$propagator->addChange($path);
			$propagator->propagateChanges();
		}
	}
}
