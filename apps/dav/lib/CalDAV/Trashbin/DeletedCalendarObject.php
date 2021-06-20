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
use OCA\DAV\CalDAV\IRestorable;
use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

class DeletedCalendarObject implements IACL, ICalendarObject, IRestorable {
	use ACLTrait;

	/** @var string */
	private $name;

	/** @var mixed[] */
	private $objectData;

	/** @var string */
	private $principalUri;

	/** @var CalDavBackend */
	private $calDavBackend;

	public function __construct(string $name,
								array $objectData,
								string $principalUri,
								CalDavBackend $calDavBackend) {
		$this->name = $name;
		$this->objectData = $objectData;
		$this->calDavBackend = $calDavBackend;
		$this->principalUri = $principalUri;
	}

	public function delete() {
		$this->calDavBackend->deleteCalendarObject(
			$this->objectData['calendarid'],
			$this->objectData['uri'],
			CalDavBackend::CALENDAR_TYPE_CALENDAR,
			true
		);
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified() {
		return 0;
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		return $this->objectData['calendardata'];
	}

	public function getContentType() {
		$mime = 'text/calendar; charset=utf-8';
		if (isset($this->objectData['component']) && $this->objectData['component']) {
			$mime .= '; component='.$this->objectData['component'];
		}

		return $mime;
	}

	public function getETag() {
		return $this->objectData['etag'];
	}

	public function getSize() {
		return (int) $this->objectData['size'];
	}

	public function restore(): void {
		$this->calDavBackend->restoreCalendarObject($this->objectData);
	}

	public function getDeletedAt(): ?int {
		return $this->objectData['deleted_at'] ? (int) $this->objectData['deleted_at'] : null;
	}

	public function getCalendarUri(): string {
		return $this->objectData['calendaruri'];
	}

	public function getACL(): array {
		return [
			[
				'privilege' => '{DAV:}read', // For queries
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}unbind', // For moving and deletion
				'principal' => '{DAV:}owner',
				'protected' => true,
			],
		];
	}

	public function getOwner() {
		return $this->principalUri;
	}
}
