<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

/**
 * @method string getPrincipaluri()
 * @method void setPrincipaluri(string $principaluri)
 * @method string getUri()
 * @method void setUri(string $uri)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string|null getColor()
 * @method void setColor(string|null $color)
 * @method int getPermissions()
 * @method void setPermissions(int $permissions)
 * @method int getSyncToken()
 * @method void setSyncToken(int $syncToken)
 * @method string getRemoteUrl()
 * @method void setRemoteUrl(string $remoteUrl)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method int|null getLastSync()
 * @method void setLastSync(int|null $lastSync)
 * @method string getSharedBy()
 * @method void setSharedBy(string $sharedBy)
 * @method string getSharedByDisplayName()
 * @method void setSharedByDisplayName(string $sharedByDisplayName)
 * @method string getComponents()
 * @method void setComponents(string $components)
 */
class FederatedCalendarEntity extends Entity {
	protected string $principaluri = '';
	protected string $uri = '';
	protected string $displayName = '';
	protected ?string $color = null;
	protected int $permissions = 0;
	protected int $syncToken = 0;
	protected string $remoteUrl = '';
	protected string $token = '';
	protected ?int $lastSync = null;
	protected string $sharedBy = '';
	protected string $sharedByDisplayName = '';
	protected string $components = '';

	public function __construct() {
		$this->addType('principaluri', Types::STRING);
		$this->addType('uri', Types::STRING);
		$this->addType('color', Types::STRING);
		$this->addType('displayName', Types::STRING);
		$this->addType('permissions', Types::INTEGER);
		$this->addType('syncToken', Types::INTEGER);
		$this->addType('remoteUrl', Types::STRING);
		$this->addType('token', Types::STRING);
		$this->addType('lastSync', Types::INTEGER);
		$this->addType('sharedBy', Types::STRING);
		$this->addType('sharedByDisplayName', Types::STRING);
		$this->addType('components', Types::STRING);
	}

	public function getSyncTokenForSabre(): string {
		return 'http://sabre.io/ns/sync/' . $this->getSyncToken();
	}

	public function getSharedByPrincipal(): string {
		return RemoteUserPrincipalBackend::PRINCIPAL_PREFIX . '/' . base64_encode($this->getSharedBy());
	}

	public function getSupportedCalendarComponentSet(): SupportedCalendarComponentSet {
		$components = explode(',', $this->getComponents());
		return new SupportedCalendarComponentSet($components);
	}

	public function toCalendarInfo(): array {
		return [
			'id' => $this->getId(),
			'uri' => $this->getUri(),
			'principaluri' => $this->getPrincipaluri(),
			'federated' => 1,

			'{DAV:}displayname' => $this->getDisplayName(),
			'{http://sabredav.org/ns}sync-token' => $this->getSyncToken(),
			'{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => $this->getSyncTokenForSabre(),
			'{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => $this->getSupportedCalendarComponentSet(),
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $this->getSharedByPrincipal(),
			// TODO: implement read-write sharing
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only' => 1
		];
	}
}
