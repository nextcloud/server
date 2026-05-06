<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class RestoreFolder implements ICollection, IMoveTarget {
	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	#[\Override]
	public function createDirectory($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getChild($name) {
		return null;
	}

	#[\Override]
	public function delete() {
		throw new Forbidden();
	}

	#[\Override]
	public function getName() {
		return 'restore';
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getLastModified(): int {
		return 0;
	}

	#[\Override]
	public function getChildren(): array {
		return [];
	}

	#[\Override]
	public function childExists($name): bool {
		return false;
	}

	#[\Override]
	public function moveInto($targetName, $sourcePath, INode $sourceNode): bool {
		if (!($sourceNode instanceof VersionFile)) {
			return false;
		}

		$sourceNode->rollBack();
		return true;
	}
}
