<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\CalDAV\ICalendarObjectContainer;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use function array_map;
use function implode;
use function preg_match;

class DeletedCalendarObjectsCollection implements ICalendarObjectContainer {
	public const NAME = 'objects';

	/** @var CalDavBackend */
	protected $caldavBackend;

	/** @var mixed[] */
	private $principalInfo;

	public function __construct(CalDavBackend $caldavBackend,
								array $principalInfo) {
		$this->caldavBackend = $caldavBackend;
		$this->principalInfo = $principalInfo;
	}

	/**
	 * @see \OCA\DAV\CalDAV\Trashbin\DeletedCalendarObjectsCollection::calendarQuery
	 */
	public function getChildren() {
		throw new NotImplemented();
	}

	public function getChild($name) {
		if (!preg_match("/(\d+)\\.ics/", $name, $matches)) {
			throw new NotFound();
		}

		$data = $this->caldavBackend->getCalendarObjectById(
			$this->principalInfo['uri'],
			(int) $matches[1],
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

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function childExists($name) {
		try {
			$this->getChild($name);
		} catch (NotFound $e) {
			return false;
		}

		return true;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return self::NAME;
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return 0;
	}

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
}
