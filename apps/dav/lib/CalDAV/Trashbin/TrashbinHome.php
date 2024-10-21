<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\IProperties;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;
use function in_array;
use function sprintf;

class TrashbinHome implements IACL, ICollection, IProperties {
	use ACLTrait;

	public const NAME = 'trashbin';

	public function __construct(
		private CalDavBackend $caldavBackend,
		private array $principalInfo,
	) {
	}

	public function getOwner(): string {
		return $this->principalInfo['uri'];
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create files in the trashbin');
	}

	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create a directory in the trashbin');
	}

	public function getChild($name): INode {
		switch ($name) {
			case RestoreTarget::NAME:
				return new RestoreTarget();
			case DeletedCalendarObjectsCollection::NAME:
				return new DeletedCalendarObjectsCollection(
					$this->caldavBackend,
					$this->principalInfo
				);
		}

		throw new NotFound();
	}

	public function getChildren(): array {
		return [
			new RestoreTarget(),
			new DeletedCalendarObjectsCollection(
				$this->caldavBackend,
				$this->principalInfo
			),
		];
	}

	public function childExists($name): bool {
		return in_array($name, [
			RestoreTarget::NAME,
			DeletedCalendarObjectsCollection::NAME,
		], true);
	}

	public function delete() {
		throw new Forbidden('Permission denied to delete the trashbin');
	}

	public function getName(): string {
		return self::NAME;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename the trashbin');
	}

	public function getLastModified(): int {
		return 0;
	}

	public function propPatch(PropPatch $propPatch): void {
		throw new Forbidden('not implemented');
	}

	public function getProperties($properties): array {
		return [
			'{DAV:}resourcetype' => new ResourceType([
				'{DAV:}collection',
				sprintf('{%s}trash-bin', \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD),
			]),
		];
	}
}
