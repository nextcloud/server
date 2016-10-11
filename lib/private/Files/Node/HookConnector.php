<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OC\Files\Node;

use OCP\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Util;

class HookConnector {
	/**
	 * @var Root
	 */
	private $root;

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var FileInfo[]
	 */
	private $deleteMetaCache = [];

	/**
	 * HookConnector constructor.
	 *
	 * @param Root $root
	 * @param View $view
	 */
	public function __construct(Root $root, View $view) {
		$this->root = $root;
		$this->view = $view;
	}

	public function viewToNode() {
		Util::connectHook('OC_Filesystem', 'write', $this, 'write');
		Util::connectHook('OC_Filesystem', 'post_write', $this, 'postWrite');

		Util::connectHook('OC_Filesystem', 'create', $this, 'create');
		Util::connectHook('OC_Filesystem', 'post_create', $this, 'postCreate');

		Util::connectHook('OC_Filesystem', 'delete', $this, 'delete');
		Util::connectHook('OC_Filesystem', 'post_delete', $this, 'postDelete');

		Util::connectHook('OC_Filesystem', 'rename', $this, 'rename');
		Util::connectHook('OC_Filesystem', 'post_rename', $this, 'postRename');

		Util::connectHook('OC_Filesystem', 'copy', $this, 'copy');
		Util::connectHook('OC_Filesystem', 'post_copy', $this, 'postCopy');

		Util::connectHook('OC_Filesystem', 'touch', $this, 'touch');
		Util::connectHook('OC_Filesystem', 'post_touch', $this, 'postTouch');
	}

	public function write($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preWrite', [$node]);
	}

	public function postWrite($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postWrite', [$node]);
	}

	public function create($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preCreate', [$node]);
	}

	public function postCreate($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postCreate', [$node]);
	}

	public function delete($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->deleteMetaCache[$node->getPath()] = $node->getFileInfo();
		$this->root->emit('\OC\Files', 'preDelete', [$node]);
	}

	public function postDelete($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		unset($this->deleteMetaCache[$node->getPath()]);
		$this->root->emit('\OC\Files', 'postDelete', [$node]);
	}

	public function touch($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preTouch', [$node]);
	}

	public function postTouch($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postTouch', [$node]);
	}

	public function rename($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'preRename', [$source, $target]);
	}

	public function postRename($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'postRename', [$source, $target]);
	}

	public function copy($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'preCopy', [$source, $target]);
	}

	public function postCopy($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'postCopy', [$source, $target]);
	}

	private function getNodeForPath($path) {
		$info = Filesystem::getView()->getFileInfo($path);
		if (!$info) {

			$fullPath = Filesystem::getView()->getAbsolutePath($path);
			if (isset($this->deleteMetaCache[$fullPath])) {
				$info = $this->deleteMetaCache[$fullPath];
			} else {
				$info = null;
			}
			if (Filesystem::is_dir($path)) {
				return new NonExistingFolder($this->root, $this->view, $fullPath, $info);
			} else {
				return new NonExistingFile($this->root, $this->view, $fullPath, $info);
			}
		}
		if ($info->getType() === FileInfo::TYPE_FILE) {
			return new File($this->root, $this->view, $info->getPath(), $info);
		} else {
			return new Folder($this->root, $this->view, $info->getPath(), $info);
		}
	}
}
