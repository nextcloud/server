<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\CalDAV\Federation\FederatedCalendarFactory;
use OCA\DAV\CalDAV\Federation\RemoteUserCalendarHome;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCP\IConfig;
use OCP\IL10N;
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
		private IL10N $l10n,
		private IConfig $config,
		private FederatedCalendarFactory $federatedCalendarFactory,
	) {
		parent::__construct($principalBackend, $caldavBackend, $principalPrefix);
	}

	public function getChildForPrincipal(array $principal) {
		[$prefix] = \Sabre\Uri\split($principal['uri']);
		if ($prefix === RemoteUserPrincipalBackend::PRINCIPAL_PREFIX) {
			return new RemoteUserCalendarHome(
				$this->caldavBackend,
				$principal,
				$this->l10n,
				$this->config,
				$this->logger,
			);
		}

		return new CalendarHome(
			$this->caldavBackend,
			$principal,
			$this->logger,
			$this->federatedCalendarFactory,
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
