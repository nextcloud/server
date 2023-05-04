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

	/** @var RetentionService */
	private $retentionService;

	/** @var Server */
	private $server;

	public function __construct(IRequest $request,
								RetentionService $retentionService) {
		$this->disableTrashbin = $request->getHeader('X-NC-CalDAV-No-Trashbin') === '1';
		$this->retentionService = $retentionService;
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
