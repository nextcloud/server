<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * Propagates changes in etag and mtime up the filesystem tree
 *
 * @package OC\Files\Cache
 */
class ChangePropagator {
	/**
	 * @var string[]
	 */
	protected $changedFiles = array();

	/**
	 * @var \OC\Files\View
	 */
	protected $view;

	/**
	 * @param \OC\Files\View $view
	 */
	public function __construct(\OC\Files\View $view) {
		$this->view = $view;
	}

	public function addChange($path) {
		$this->changedFiles[] = $path;
	}

	public function getChanges() {
		return $this->changedFiles;
	}

	/**
	 * propagate the registered changes to their parent folders
	 *
	 * @param int $time (optional) the mtime to set for the folders, if not set the current time is used
	 */
	public function propagateChanges($time = null) {
		$parents = $this->getAllParents();
		$this->changedFiles = array();
		if (!$time) {
			$time = time();
		}
		foreach ($parents as $parent) {
			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath
			 */

			list($storage, $internalPath) = $this->view->resolvePath($parent);
			if ($storage) {
				$cache = $storage->getCache();
				$id = $cache->getId($internalPath);
				$cache->update($id, array('mtime' => $time, 'etag' => $storage->getETag($internalPath)));
			}
		}
	}

	/**
	 * @return string[]
	 */
	public function getAllParents() {
		$parents = array();
		foreach ($this->getChanges() as $path) {
			$parents = array_values(array_unique(array_merge($parents, $this->getParents($path))));
		}
		return $parents;
	}

	/**
	 * get all parent folders of $path
	 *
	 * @param string $path
	 * @return string[]
	 */
	protected function getParents($path) {
		$parts = explode('/', $path);

		// remove the singe file
		array_pop($parts);
		$result = array('/');
		$resultPath = '';
		foreach ($parts as $part) {
			if ($part) {
				$resultPath .= '/' . $part;
				$result[] = $resultPath;
			}
		}
		return $result;
	}
}
