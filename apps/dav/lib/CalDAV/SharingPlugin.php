<?php
/**
 * @copyright Copyright (c) 2022 Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use Sabre\CalDAV\Xml\Property\AllowedSharingModes;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class SharingPlugin extends ServerPlugin {
	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

	protected Server $server;
	protected IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures(): array {
		// May have to be changed to be detected
		return ['calendarserver-sharing'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName(): string {
		return 'oc-calendar-sharing';
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node) {
		if ($node instanceof Calendar) {
			$propFind->handle('{'.self::NS_CALENDARSERVER.'}allowed-sharing-modes', function () use ($node) {
				$canShare = (!$node->isSubscription() && $node->canWrite());
				$canPublish = (!$node->isSubscription() && $node->canWrite());

				if ($this->config->getAppValue('dav', 'limitAddressBookAndCalendarSharingToOwner', 'no') === 'yes') {
					$canShare = $canShare && ($node->getOwner() === $node->getPrincipalURI());
					$canPublish = $canPublish && ($node->getOwner() === $node->getPrincipalURI());
				}

				if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
					$canPublish = false;
				}

				return new AllowedSharingModes($canShare, $canPublish);
			});
		}
	}
}
