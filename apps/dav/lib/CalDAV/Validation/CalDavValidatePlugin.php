<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Validation;

use OCA\DAV\AppInfo\Application;
use OCP\IConfig;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class CalDavValidatePlugin extends ServerPlugin {

	public function __construct(
		private IConfig $config
	) {
	}

	public function initialize(Server $server): void {
		$server->on('beforeMethod:PUT', [$this, 'beforePut']);
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): bool {
		// evaluate if card size exceeds defined limit
		$eventSizeLimit = $this->config->getAppValue(Application::APP_ID, 'event_size_limit', '10485760');
		if ((int) $request->getRawServerValue('CONTENT_LENGTH') > $eventSizeLimit) {
			throw new Forbidden("VEvent or VTodo object exceeds $eventSizeLimit bytes");
		}
		// all tests passed return true
		return true;
	}

}
