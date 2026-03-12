<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\IUserSession;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class UserIdHeaderPlugin extends \Sabre\DAV\ServerPlugin {
	public function __construct(
		private readonly IUserSession $userSession,
	) {
	}

	public function initialize(\Sabre\DAV\Server $server): void {
		$server->on('beforeMethod:*', [$this, 'beforeMethod']);
	}

	/**
	 * Add the request id as a header in the response
	 *
	 * @param RequestInterface $request request
	 * @param ResponseInterface $response response
	 */
	public function beforeMethod(RequestInterface $request, ResponseInterface $response): void {
		if ($user = $this->userSession->getUser()) {
			$response->setHeader('X-User-Id', $user->getUID());
		}
	}
}
