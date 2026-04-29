<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\CalDAV\ICalendarObjectContainer;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;
use function array_map;
use function implode;
use function preg_match;

class DeletedCalendarObjectsCollection implements ICalendarObjectContainer, IACL {
	use ACLTrait;

	public const NAME = 'objects';

	public function __construct(
		protected CalDavBackend $caldavBackend,
		/** @var mixed[] */
		private array $principalInfo,
	) {
	}

	/**
	 * @see \OCA\DAV\CalDAV\Trashbin\DeletedCalendarObjectsCollection::calendarQuery
	 */
	#[\Override]
	public function getChildren() {
		throw new NotImplemented();
	}

	#[\Override]
	public function getChild($name) {
		if (!preg_match("/(\d+)\\.ics/", $name, $matches)) {
			throw new NotFound();
		}

		$data = $this->caldavBackend->getCalendarObjectById(
			$this->principalInfo['uri'],
			(int)$matches[1],
		);

		// If the object hasn't been deleted yet then we don't want to find it here
		if ($data === null) {
			throw new NotFound();
		}
		if (!isset($data['deleted_at'])) {
			throw new BadRequest('The calendar object you\'re trying to restore is not marked as deleted');
		}

		return new DeletedCalendarObject(
			$this->getRelativeObjectPath($data),
			$data,
			$this->principalInfo['uri'],
			$this->caldavBackend
		);
	}

	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	#[\Override]
	public function createDirectory($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function childExists($name) {
		try {
			$this->getChild($name);
		} catch (NotFound $e) {
			return false;
		}

		return true;
	}

	#[\Override]
	public function delete() {
		throw new Forbidden();
	}

	#[\Override]
	public function getName(): string {
		return self::NAME;
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
	public function calendarQuery(array $filters) {
		return array_map(function (array $calendarObjectInfo) {
			return $this->getRelativeObjectPath($calendarObjectInfo);
		}, $this->caldavBackend->getDeletedCalendarObjectsByPrincipal($this->principalInfo['uri']));
	}

	private function getRelativeObjectPath(array $calendarInfo): string {
		return implode(
			'.',
			[$calendarInfo['id'], 'ics'],
		);
	}

	#[\Override]
	public function getOwner() {
		return $this->principalInfo['uri'];
	}

	#[\Override]
	public function getACL(): array {
		return [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}unbind',
				'principal' => '{DAV:}owner',
				'protected' => true,
			]
		];
	}
}
