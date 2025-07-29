<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Validation;

use OCA\DAV\AppInfo\Application;
use OCP\IAppConfig;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class CardDavValidatePlugin extends ServerPlugin {

	public function __construct(
		private IAppConfig $config,
	) {
	}

	public function initialize(Server $server): void {
		$server->on('beforeMethod:PUT', [$this, 'beforePut']);
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): bool {
		// evaluate if card size exceeds defined limit
		$cardSizeLimit = $this->config->getValueInt(Application::APP_ID, 'card_size_limit', 5242880);
		if ((int)$request->getRawServerValue('CONTENT_LENGTH') > $cardSizeLimit) {
			throw new Forbidden("VCard object exceeds $cardSizeLimit bytes");
		}
		// all tests passed return true
		return true;
	}

}
