<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\IRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class RequestIdHeaderPlugin extends \Sabre\DAV\ServerPlugin {
	public function __construct(
		private IRequest $request,
	) {
	}

	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('afterMethod:*', [$this, 'afterMethod']);
	}

	/**
	 * Add the request id as a header in the response
	 *
	 * @param RequestInterface $request request
	 * @param ResponseInterface $response response
	 */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$response->setHeader('X-Request-Id', $this->request->getId());
	}
}
