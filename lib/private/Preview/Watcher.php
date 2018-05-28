<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

/**
 * Class Watcher
 *
 * @package OC\Preview
 *
 * Class that will watch filesystem activity and remove previews as needed.
 */
class Watcher {
	/** @var IAppData */
	private $appData;

	/** @var int[] */
	private $toDelete = [];

	/**
	 * Watcher constructor.
	 *
	 * @param IAppData $appData
	 */
	public function __construct(IAppData $appData) {
		$this->appData = $appData;
	}

	public function postWrite(Node $node) {
		$this->deleteNode($node);
	}

	protected function deleteNode(Node $node) {
		// We only handle files
		if ($node instanceof Folder) {
			return;
		}

		try {
			$folder = $this->appData->getFolder($node->getId());
			$folder->delete();
		} catch (NotFoundException $e) {
			//Nothing to do
		}
	}

	public function preDelete(Node $node) {
		// To avoid cycles
		if ($this->toDelete !== []) {
			return;
		}

		if ($node instanceof File) {
			$this->toDelete[] = $node->getId();
			return;
		}

		/** @var Folder $node */
		$this->deleteFolder($node);
	}

	private function deleteFolder(Folder $folder) {
		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			if ($node instanceof File) {
				$this->toDelete[] = $node->getId();
			} else if ($node instanceof Folder) {
				$this->deleteFolder($node);
			}
		}
	}

	public function postDelete(Node $node) {
		foreach ($this->toDelete as $fid) {
			try {
				$folder = $this->appData->getFolder($fid);
				$folder->delete();
			} catch (NotFoundException $e) {
				// continue
			}
		}
	}

	public function versionRollback(array $data) {
		if (isset($data['node'])) {
			$this->deleteNode($data['node']);
		}
	}
}
