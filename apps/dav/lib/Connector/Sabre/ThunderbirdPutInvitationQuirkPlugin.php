<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader as VObjectReader;

/**
 * Consider the following situation: A user is invited and tries to accept or decline from the
 * invitation email in Thunderbird before the personal calendar was synced.
 *
 * Thunderbird attempts to PUT the accepted ics to an invalid name, because it doesn't know the name
 * on the remote Nextcloud/CalDAV server (yet). The Nextcloud server responds with an error, as the
 * UID is already existing because the invitation was already added to the invitees personal
 * calendar by Sabre.
 *
 * If Thunderbird knows about the URI of the user's own copy of the event, it will PUT the correct
 * event directly.
 *
 * This plugin attempts to handle this situation gracefully by simply replacing the URI of the event
 * with the actual one before handing off the request to the CalDAV server.
 */
class ThunderbirdPutInvitationQuirkPlugin extends ServerPlugin {
	private ?Server $server = null;

	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function initialize(Server $server) {
		$this->server = $server;

		// Run right after the ACL plugin to make sure that the current user principal is available
		$server->on('beforeMethod:PUT', $this->beforePut(...), 21);
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): void {
		$userAgent = $request->getHeader('User-Agent');
		if (!$userAgent || !$this->isThunderbirdUserAgent($userAgent)) {
			return;
		}

		if (!str_starts_with($request->getPath(), 'calendars/')) {
			return;
		}

		if (!str_contains($request->getHeader('Content-Type') ?? '', 'text/calendar')) {
			return;
		}

		$currentUserPrincipal = $this->getCurrentUserPrincipal();
		if ($currentUserPrincipal === null) {
			return;
		}

		// Need to set the body again here so that other handlers are able to read it afterward
		$requestBody = $request->getBodyAsString();
		$request->setBody($requestBody);

		try {
			$vCalendar = VObjectReader::read($requestBody);
		} catch (\Throwable $e) {
			return;
		}
		if (!($vCalendar instanceof VCalendar)) {
			return;
		}

		/** @var string|null $uid */
		$uid = $vCalendar->getBaseComponent('VEVENT')?->UID?->getValue();
		if ($uid === null) {
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('co.uri')
			->from('calendarobjects', 'co')
			->join('co', 'calendars', 'c', $qb->expr()->eq('co.calendarid', 'c.id'))
			->where(
				$qb->expr()->eq(
					'c.principaluri',
					$qb->createNamedParameter($currentUserPrincipal, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				$qb->expr()->eq(
					'co.uid',
					$qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		if (count($rows) !== 1) {
			// Either no collision or too many collisions
			return;
		}

		$requestUrl = $request->getUrl();
		[$prefix] = \Sabre\Uri\split($requestUrl);
		$objectUri = $rows[0]['uri'];
		$request->setUrl("$prefix/$objectUri");
	}

	private function isThunderbirdUserAgent(string $userAgent): bool {
		return str_contains($userAgent, 'Thunderbird/');
	}

	private function getCurrentUserPrincipal(): ?string {
		/** @var \Sabre\DAV\Auth\Plugin $authPlugin */
		$authPlugin = $this->server?->getPlugin('auth');
		return $authPlugin?->getCurrentPrincipal();
	}
}
