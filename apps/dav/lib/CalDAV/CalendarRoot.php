<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\Connector\Sabre\Principal;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Backend;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAVACL\PrincipalBackend;

class CalendarRoot extends \Sabre\CalDAV\CalendarRoot {
	private array $returnCachedSubscriptions = [];

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		Backend\BackendInterface $caldavBackend,
		$principalPrefix,
		private LoggerInterface $logger,
	) {
		parent::__construct($principalBackend, $caldavBackend, $principalPrefix);
	}

	public function getChildForPrincipal(array $principal) {
		return new CalendarHome(
			$this->caldavBackend,
			$principal,
			$this->logger,
			array_key_exists($principal['uri'], $this->returnCachedSubscriptions)
		);
	}

	public function getName() {
		if ($this->principalPrefix === 'principals/calendar-resources'
			|| $this->principalPrefix === 'principals/calendar-rooms') {
			$parts = explode('/', $this->principalPrefix);

			return $parts[1];
		}

		return parent::getName();
	}

	public function enableReturnCachedSubscriptions(string $principalUri): void {
		$this->returnCachedSubscriptions['principals/users/' . $principalUri] = true;
	}

	public function childExists($name) {
		if (!($this->principalBackend instanceof Principal)) {
			return parent::childExists($name);
		}

		// Fetch the most shallow version of the principal just to determine if it exists
		$principalInfo = $this->principalBackend->getPrincipalPropertiesByPath(
			$this->principalPrefix . '/' . $name,
			[],
		);
		if ($principalInfo === null) {
			return false;
		}

		try {
			return $this->getChildForPrincipal($principalInfo) !== null;
		} catch (NotFound $e) {
			return false;
		}
	}
}
