<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\RetentionService;
use OCP\IRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use function array_slice;
use function implode;

class Plugin extends ServerPlugin {
	public const PROPERTY_DELETED_AT = '{http://nextcloud.com/ns}deleted-at';
	public const PROPERTY_CALENDAR_URI = '{http://nextcloud.com/ns}calendar-uri';
	public const PROPERTY_RETENTION_DURATION = '{http://nextcloud.com/ns}trash-bin-retention-duration';

	/** @var bool */
	private $disableTrashbin;

	/** @var Server */
	private $server;

	public function __construct(
		IRequest $request,
		private RetentionService $retentionService,
	) {
		$this->disableTrashbin = $request->getHeader('X-NC-CalDAV-No-Trashbin') === '1';
	}

	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('beforeMethod:*', [$this, 'beforeMethod']);
		$server->on('propFind', Closure::fromCallable([$this, 'propFind']));
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response): void {
		if (!$this->disableTrashbin) {
			return;
		}

		$path = $request->getPath();
		$pathParts = explode('/', ltrim($path, '/'));
		if (\count($pathParts) < 3) {
			// We are looking for a path like calendars/username/calendarname
			return;
		}

		// $calendarPath will look like calendars/username/calendarname
		$calendarPath = implode(
			'/',
			array_slice($pathParts, 0, 3)
		);
		try {
			$calendar = $this->server->tree->getNodeForPath($calendarPath);
			if (!($calendar instanceof Calendar)) {
				// This is odd
				return;
			}

			/** @var Calendar $calendar */
			$calendar->disableTrashbin();
		} catch (NotFound $ex) {
			return;
		}
	}

	private function propFind(
		PropFind $propFind,
		INode $node): void {
		if ($node instanceof DeletedCalendarObject) {
			$propFind->handle(self::PROPERTY_DELETED_AT, function () use ($node) {
				$ts = $node->getDeletedAt();
				if ($ts === null) {
					return null;
				}

				return (new DateTimeImmutable())
					->setTimestamp($ts)
					->format(DateTimeInterface::ATOM);
			});
			$propFind->handle(self::PROPERTY_CALENDAR_URI, function () use ($node) {
				return $node->getCalendarUri();
			});
		}
		if ($node instanceof TrashbinHome) {
			$propFind->handle(self::PROPERTY_RETENTION_DURATION, function () use ($node) {
				return $this->retentionService->getDuration();
			});
		}
	}

	public function getFeatures(): array {
		return ['nc-calendar-trashbin'];
	}

	public function getPluginName(): string {
		return 'nc-calendar-trashbin';
	}
}
