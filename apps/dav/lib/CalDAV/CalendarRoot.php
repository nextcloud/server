<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\CalDAV\Federation\FederatedCalendarFactory;
use OCA\DAV\CalDAV\Federation\RemoteUserCalendarHome;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use Sabre\CalDAV\Backend;
use Sabre\DAVACL\PrincipalBackend;

class CalendarRoot extends \Sabre\CalDAV\CalendarRoot {
	private array $returnCachedSubscriptions = [];

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		Backend\BackendInterface $caldavBackend,
		$principalPrefix,
		private FederatedCalendarFactory $federatedCalendarFactory,
		private readonly CalendarFactory $calendarFactory,
	) {
		parent::__construct($principalBackend, $caldavBackend, $principalPrefix);
	}

	public function getChildForPrincipal(array $principal) {
		[$prefix] = \Sabre\Uri\split($principal['uri']);
		if ($prefix === RemoteUserPrincipalBackend::PRINCIPAL_PREFIX) {
			return new RemoteUserCalendarHome(
				$this->caldavBackend,
				$principal,
				$this->calendarFactory,
			);
		}

		return new CalendarHome(
			$this->caldavBackend,
			$principal,
			$this->federatedCalendarFactory,
			$this->calendarFactory,
			array_key_exists($principal['uri'], $this->returnCachedSubscriptions)
		);
	}

	public function getName() {
		if ($this->principalPrefix === 'principals/calendar-resources'
			|| $this->principalPrefix === 'principals/calendar-rooms') {
			$parts = explode('/', $this->principalPrefix);

			return $parts[1];
		}

		if ($this->principalPrefix === RemoteUserPrincipalBackend::PRINCIPAL_PREFIX) {
			return 'remote-calendars';
		}

		return parent::getName();
	}

	public function enableReturnCachedSubscriptions(string $principalUri): void {
		$this->returnCachedSubscriptions['principals/users/' . $principalUri] = true;
	}
}
