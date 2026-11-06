<?php

// SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\DAV\CalDAV;

use OCA\DAV\DAV\Sharing\CanChangeSharingTrait;
use OCP\IAppConfig;
use Override;
use Sabre\CalDAV\Xml\Property\AllowedSharingModes;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class SharingPlugin extends ServerPlugin {
	use CanChangeSharingTrait;

	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

	protected Server $server;

	public function __construct(
		private readonly IAppConfig $config,
	) {
	}

	#[Override]
	public function getFeatures(): array {
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
			$propFind->handle('{' . self::NS_CALENDARSERVER . '}allowed-sharing-modes', function () use ($propFind, $node) {
				$canShare = !$node->isSubscription() && $this->canChangeSharing($propFind->getPath());
				$canPublish = $canShare && $this->config->getValueBool('core', 'shareapi_allow_links', true);

				return new AllowedSharingModes($canShare, $canPublish);
			});
		}
	}
}
