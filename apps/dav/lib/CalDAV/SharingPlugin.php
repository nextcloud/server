<?php

// SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\DAV\CalDAV;

use OCP\IAppConfig;
use OCP\IConfig;
use Override;
use Sabre\CalDAV\Xml\Property\AllowedSharingModes;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class SharingPlugin extends ServerPlugin {
	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

	protected Server $server;

	public function __construct(
		private readonly IAppConfig $config,
	) {
	}

	#[Override]
	public function getFeatures(): array {
		// May have to be changed to be detected
		return ['calendarserver-sharing'];
	}

	#[Override]
	public function getPluginName(): string {
		return 'oc-calendar-sharing';
	}

	#[Override]
	public function initialize(Server $server): void {
		$this->server = $server;

		$this->server->on('propFind', $this->propFind(...));
	}

	public function propFind(PropFind $propFind, INode $node): void {
		if ($node instanceof Calendar) {
			$propFind->handle('{' . self::NS_CALENDARSERVER . '}allowed-sharing-modes', function () use ($node) {
				$canShare = (!$node->isSubscription() && $node->canWrite());
				$canPublish = (!$node->isSubscription() && $node->canWrite());

				if ($this->config->getValueBool('dav', 'limitAddressBookAndCalendarSharingToOwner')) {
					$canShare = $canShare && ($node->getOwner() === $node->getPrincipalURI());
					$canPublish = $canPublish && ($node->getOwner() === $node->getPrincipalURI());
				}

				if (!$this->config->getValueBool('core', 'shareapi_allow_links', true)) {
					$canPublish = false;
				}

				return new AllowedSharingModes($canShare, $canPublish);
			});
		}
	}
}
