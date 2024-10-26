<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\IRestorable;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class RestoreTarget implements ICollection, IMoveTarget {
	public const NAME = 'restore';

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		throw new NotFound();
	}

	public function getChildren(): array {
		return [];
	}

	public function childExists($name): bool {
		return false;
	}

	public function moveInto($targetName, $sourcePath, INode $sourceNode): bool {
		if ($sourceNode instanceof IRestorable) {
			$sourceNode->restore();
			return true;
		}

		return false;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return 'restore';
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified() {
		return 0;
	}
}
