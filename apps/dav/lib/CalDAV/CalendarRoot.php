<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Backend;
use Sabre\DAVACL\PrincipalBackend;

class CalendarRoot extends \Sabre\CalDAV\CalendarRoot {
	private array $returnCachedSubscriptions = [];

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		Backend\BackendInterface $caldavBackend,
		string $principalPrefix = 'principals',
		private LoggerInterface $logger,
	) {
		parent::__construct($principalBackend, $caldavBackend, $principalPrefix);
	}

	/**
	 * Returns the name of the node.
	 */
	public function getName(): string {
		if ($this->principalPrefix === 'principals') {
			return parent::getName();
		}
		$parts = explode('/', $this->principalPrefix);
		return $parts[1];
	}

	/**
	 * Returns a node for a principal.
	 */
	public function getChildForPrincipal(array $principal): \Sabre\DAV\INode {
		return new CalendarHome(
			$this->caldavBackend,
			$principal,
			$this->logger,
			array_key_exists($principal['uri'], $this->returnCachedSubscriptions)
		);
	}

	public function enableReturnCachedSubscriptions(string $principalUri): void {
		$this->returnCachedSubscriptions['principals/users/' . $principalUri] = true;
	}
}
