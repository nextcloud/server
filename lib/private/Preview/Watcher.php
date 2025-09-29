<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\Node;
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

	protected function deleteNode(FileInfo $node) {
		// We only handle files
		if ($node instanceof Folder) {
			return;
		}

		try {
			if (is_null($node->getId())) {
				return;
			}
			$folder = $this->appData->getFolder((string)$node->getId());
			$folder->delete();
		} catch (NotFoundException $e) {
			//Nothing to do
		}
	}

	public function versionRollback(array $data) {
		if (isset($data['node'])) {
			$this->deleteNode($data['node']);
		}
	}
}
