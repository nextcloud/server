<?php

declare(strict_types=1);

/**
 * @copyright 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\CalDAV\PushSync;

use Closure;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\PushSync\Xml\PushTransports;
use OCA\DAV\Db\PushKeyMapper;
use OCA\DAV\Push\IPushTransportProvider;
use OCA\DAV\Push\PushTransportManager;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class Plugin extends ServerPlugin {
	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';
	public const PROPERTY_PUSH_TRANSPORTS = '{' . self::NS_CALENDARSERVER . '}push-transports';
	public const PROPERTY_PUSHKEY = '{' . self::NS_CALENDARSERVER . '}pushkey';

	public function __construct(private PushKeyMapper $pushKeyMapper, private PushTransportManager $pushTransportManager) { }

	public function initialize(Server $server): void {
		$server->on('propFind', Closure::fromCallable([$this, 'propFind']));
	}

	private function propFind(
		PropFind $propFind,
		INode $node): void {
		if ($node instanceof CalendarHome) {
			$pushTransportProviders = $this->pushTransportManager->getPushTransportProviders();
			$propFind->handle(self::PROPERTY_PUSH_TRANSPORTS, function () use ($node, $pushTransportProviders) {
				return new PushTransports(array_map(function (IPushTransportProvider $pushTransportProvider) {
					return $pushTransportProvider->getPushTransport();
				}, $pushTransportProviders));
			});
			$propFind->handle(self::PROPERTY_PUSHKEY, function () use ($node) {
				return $this->pushKeyMapper->getForPrincipal($node->getOwner());
			});
		}
		if ($node instanceof Calendar) {
			$propFind->handle(self::PROPERTY_PUSHKEY, function () use ($node) {
				return $this->pushKeyMapper->getForUri($node->getOwner(), $node->getName());
			});
		}
	}

	public function getFeatures(): array {
		return ['nc-calendar-push-sync'];
	}

	public function getPluginName(): string {
		return 'nc-calendar-push-sync';
	}
}
