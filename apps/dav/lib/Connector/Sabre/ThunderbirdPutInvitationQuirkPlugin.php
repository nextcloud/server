<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader as VObjectReader;

/**
 * When Thunderbird accepts or declines an invitation before the calendar has synced, it PUTs to a
 * guessed object name that collides with the already-stored invitation (same UID) and fails. For
 * such a request this plugin rewrites the URI to the existing object so the PUT updates it.
 */
class ThunderbirdPutInvitationQuirkPlugin extends ServerPlugin {
	private ?Server $server = null;

	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	public function initialize(Server $server) {
		$this->server = $server;

		// Before the ACL plugin (priority 20) so its PUT check sees the rewritten object.
		$server->on('beforeMethod:PUT', $this->beforePut(...), 19);
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): void {
		$userAgent = $request->getHeader('User-Agent');
		if (!$userAgent || !$this->isThunderbirdUserAgent($userAgent)) {
			return;
		}

		// calendars/{principal}/{calendar}/{object}
		$pathParts = explode('/', $request->getPath());
		if (count($pathParts) !== 4 || $pathParts[0] !== 'calendars') {
			return;
		}
		[, , $calendarUri, $requestedObjectUri] = $pathParts;

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

		// Same calendar as the request, real calendar objects only, nothing trashed.
		$qb = $this->db->getQueryBuilder();
		$qb->select('co.uri', 'co.calendardata')
			->from('calendarobjects', 'co')
			->join('co', 'calendars', 'c', $qb->expr()->eq('co.calendarid', 'c.id'))
			->where(
				$qb->expr()->eq(
					'c.principaluri',
					$qb->createNamedParameter($currentUserPrincipal, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				$qb->expr()->eq(
					'c.uri',
					$qb->createNamedParameter($calendarUri, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				$qb->expr()->eq(
					'co.uid',
					$qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				$qb->expr()->eq(
					'co.calendartype',
					$qb->createNamedParameter(CalDavBackend::CALENDAR_TYPE_CALENDAR, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->isNull('co.deleted_at'),
				$qb->expr()->isNull('c.deleted_at'),
			);
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		if (count($rows) !== 1) {
			// Either no collision or too many collisions
			return;
		}

		$objectUri = $rows[0]['uri'];
		if ($objectUri === $requestedObjectUri) {
			// Already synced: Thunderbird targets the real URI, leave the request untouched.
			return;
		}

		// Restore SCHEDULE-AGENT from the stored organizer so server-side scheduling still runs.
		$storedData = $rows[0]['calendardata'];
		if (is_resource($storedData)) {
			$storedData = stream_get_contents($storedData);
		}
		$storedScheduleAgent = null;
		$storedHasOrganizer = false;
		try {
			$storedVCalendar = VObjectReader::read((string)$storedData);
			if ($storedVCalendar instanceof VCalendar) {
				$storedOrganizer = $storedVCalendar->getBaseComponent('VEVENT')?->ORGANIZER;
				if ($storedOrganizer !== null) {
					$storedHasOrganizer = true;
					$storedScheduleAgent = isset($storedOrganizer['SCHEDULE-AGENT'])
						? $storedOrganizer['SCHEDULE-AGENT']->getValue()
						: null;
				}
			}
		} catch (\Throwable $e) {
			$storedHasOrganizer = false;
		}

		$organizerRestored = false;
		if ($storedHasOrganizer) {
			foreach ($vCalendar->getComponents() as $component) {
				if ($component->name !== 'VEVENT') {
					continue;
				}
				$organizer = $component->ORGANIZER ?? null;
				if ($organizer === null) {
					continue;
				}
				$incomingScheduleAgent = isset($organizer['SCHEDULE-AGENT'])
					? $organizer['SCHEDULE-AGENT']->getValue()
					: null;
				if ($incomingScheduleAgent === $storedScheduleAgent) {
					continue;
				}
				if ($storedScheduleAgent === null) {
					unset($organizer['SCHEDULE-AGENT']);
				} else {
					$organizer['SCHEDULE-AGENT'] = $storedScheduleAgent;
				}
				$organizerRestored = true;
			}
		}
		if ($organizerRestored) {
			$request->setBody($vCalendar->serialize());
		}

		[$prefix] = \Sabre\Uri\split($request->getUrl());
		$request->setUrl("$prefix/$objectUri");

		// "If-None-Match: *" from the attempted create would fail with 412 after the rewrite
		$request->removeHeader('If-None-Match');
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
