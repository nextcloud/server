<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class RestoreFolder implements ICollection, IMoveTarget {
	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		return null;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName() {
		return 'restore';
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return 0;
	}

	public function getChildren(): array {
		return [];
	}

	public function childExists($name): bool {
		return false;
	}

	public function moveInto($targetName, $sourcePath, INode $sourceNode): bool {
		if (!($sourceNode instanceof ITrash)) {
			return false;
		}

		return $sourceNode->restore();
	}
}
