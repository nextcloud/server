<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Constants;
use Sabre\CalDAV\ICalendar;
use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IMultiGet;
use Sabre\DAV\INode;
use Sabre\DAV\IProperties;
use Sabre\DAV\PropPatch;

class FederatedCalendar implements ICalendar, IProperties, IMultiGet {

	private const CALENDAR_TYPE = CalDavBackend::CALENDAR_TYPE_FEDERATED;
	private const DAV_PROPERTY_CALENDAR_LABEL = '{DAV:}displayname';
	private const DAV_PROPERTY_CALENDAR_COLOR = '{http://apple.com/ns/ical/}calendar-color';

	private string $principalUri;
	private string $calendarUri;
	private ?array $calendarACL = null;
	private FederatedCalendarEntity $federationInfo;

	public function __construct(
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly FederatedCalendarSyncService $federatedCalendarService,
		private readonly CalDavBackend $caldavBackend,
		$calendarInfo,
	) {
		$this->principalUri = $calendarInfo['principaluri'];
		$this->calendarUri = $calendarInfo['uri'];
		$this->federationInfo = $federatedCalendarMapper->findByUri($this->principalUri, $this->calendarUri);
	}

	public function getResourceId(): int {
		return $this->federationInfo->getId();
	}

	public function getName(): string {
		return $this->federationInfo->getUri();
	}

	/**
	 * @param string $name Name of the file
	 */
	public function setName($name): void {
		throw new MethodNotAllowed('Renaming federated calendars is not allowed');
	}

	protected function getCalendarType(): int {
		return self::CALENDAR_TYPE;
	}

	public function getPrincipalURI(): string {
		return $this->federationInfo->getPrincipaluri();
	}

	public function getOwner(): ?string {
		return $this->federationInfo->getSharedByPrincipal();
	}

	public function getGroup(): ?string {
		return null;
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public function getACL(): array {
		if ($this->calendarACL !== null) {
			return $this->calendarACL;
		}

		$permissions = $this->federationInfo->getPermissions();
		// default permission
		$acl = [
			// read object permission
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->principalUri,
				'protected' => true,
			],
			// read acl permission
			[
				'privilege' => '{DAV:}read-acl',
				'principal' => $this->principalUri,
				'protected' => true,
			],
			// write properties permission (calendar name, color)
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->principalUri,
				'protected' => true,
			],
		];
		// create permission
		if ($permissions & Constants::PERMISSION_CREATE) {
			$acl[] = [
				'privilege' => '{DAV:}bind',
				'principal' => $this->principalUri,
				'protected' => true,
			];
		}
		// update permission
		if ($permissions & Constants::PERMISSION_UPDATE) {
			$acl[] = [
				'privilege' => '{DAV:}write-content',
				'principal' => $this->principalUri,
				'protected' => true,
			];
		}
		// delete permission
		if ($permissions & Constants::PERMISSION_DELETE) {
			$acl[] = [
				'privilege' => '{DAV:}unbind',
				'principal' => $this->principalUri,
				'protected' => true,
			];
		}

		// cache the calculated ACL for later use
		$this->calendarACL = $acl;

		return $acl;
	}

	public function setACL(array $acl): void {
		throw new MethodNotAllowed('Changing ACLs on federated calendars is not allowed');
	}

	public function getSupportedPrivilegeSet(): ?array {
		return null;
	}

	/**
	 * @return array<string, mixed> properties array, with property name as key
	 */
	public function getProperties($properties): array {
		return [
			self::DAV_PROPERTY_CALENDAR_LABEL => $this->federationInfo->getDisplayName(),
			self::DAV_PROPERTY_CALENDAR_COLOR => $this->federationInfo->getColor(),
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(explode(',', $this->federationInfo->getComponents())),
		];
	}

	public function propPatch(PropPatch $propPatch): void {
		$mutations = $propPatch->getMutations();
		if (count($mutations) > 0) {
			// evaluate if name was changed
			if (isset($mutations[self::DAV_PROPERTY_CALENDAR_LABEL])) {
				$this->federationInfo->setDisplayName($mutations[self::DAV_PROPERTY_CALENDAR_LABEL]);
				$propPatch->setResultCode(self::DAV_PROPERTY_CALENDAR_LABEL, 200);
			}
			// evaluate if color was changed
			if (isset($mutations[self::DAV_PROPERTY_CALENDAR_COLOR])) {
				$this->federationInfo->setColor($mutations[self::DAV_PROPERTY_CALENDAR_COLOR]);
				$propPatch->setResultCode(self::DAV_PROPERTY_CALENDAR_COLOR, 200);
			}
			$this->federatedCalendarMapper->update($this->federationInfo);
		}
	}


	public function getChildACL(): array {
		return $this->getACL();
	}

	public function getLastModified(): ?int {
		return $this->federationInfo->getLastSync();
	}

	public function delete(): void {
		$this->federatedCalendarMapper->deleteById($this->getResourceId());
	}

	/**
	 * @param string $name Name of the file
	 */
	public function createDirectory($name): void {
		throw new MethodNotAllowed('Creating nested collection is not allowed');
	}

	public function calendarQuery(array $filters): array {
		$uris = $this->caldavBackend->calendarQuery($this->federationInfo->getId(), $filters, $this->getCalendarType());
		return $uris;
	}

	/**
	 * @param string $name Name of the file
	 */
	public function getChild($name): INode {
		$obj = $this->caldavBackend->getCalendarObject($this->federationInfo->getId(), $name, $this->getCalendarType());

		if ($obj === null) {
			throw new NotFound('Calendar object not found');
		}

		return new FederatedCalendarObject($this, $obj);
	}

	/**
	 * @return array<INode>
	 */
	public function getChildren(): array {
		$objs = $this->caldavBackend->getCalendarObjects($this->federationInfo->getId(), $this->getCalendarType());

		$children = [];
		foreach ($objs as $obj) {
			$children[] = new FederatedCalendarObject($this, $obj);
		}

		return $children;
	}

	/**
	 * @param array<string> $paths Names of the files
	 *
	 * @return array<INode>
	 */
	public function getMultipleChildren(array $paths): array {
		$objs = $this->caldavBackend->getMultipleCalendarObjects($this->federationInfo->getId(), $paths, $this->getCalendarType());

		$children = [];
		foreach ($objs as $obj) {
			$children[] = new FederatedCalendarObject($this, $obj);
		}

		return $children;
	}

	/**
	 * @param string $name Name of the file
	 */
	public function childExists($name): bool {
		$obj = $this->caldavBackend->getCalendarObject($this->federationInfo->getId(), $name, $this->getCalendarType());
		return $obj !== null;
	}

	/**
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 */
	public function createFile($name, $data = null): string {
		if (is_resource($data)) {
			$data = stream_get_contents($data);
		}

		// Create on remote server first
		$etag = $this->federatedCalendarService->createCalendarObject($this->federationInfo, $name, $data);

		if (empty($etag)) {
			throw new \Exception('Failed to create calendar object on remote server');
		}

		// Then store locally
		return $this->caldavBackend->createCalendarObject($this->federationInfo->getId(), $name, $data, $this->getCalendarType());
	}

	/**
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 */
	public function updateFile($name, $data): string {
		if (is_resource($data)) {
			$data = stream_get_contents($data);
		}

		// Update remote calendar first
		$etag = $this->federatedCalendarService->updateCalendarObject($this->federationInfo, $name, $data);

		if (empty($etag)) {
			throw new \Exception('Failed to update calendar object on remote server');
		}

		// Then update locally
		return $this->caldavBackend->updateCalendarObject($this->federationInfo->getId(), $name, $data, $this->getCalendarType());
	}

	public function deleteFile(string $name): void {
		// Delete from remote server first
		$this->federatedCalendarService->deleteCalendarObject($this->federationInfo, $name);

		// Then delete locally
		$this->caldavBackend->deleteCalendarObject($this->federationInfo->getId(), $name, $this->getCalendarType());
	}

}
