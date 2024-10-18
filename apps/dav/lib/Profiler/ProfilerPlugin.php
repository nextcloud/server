<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Profiler;

use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ProfilerPlugin extends \Sabre\DAV\ServerPlugin {
	public function __construct(
		private IRequest $request,
	) {
	}

	/** @return void */
	public function initialize(Server $server) {
		$server->on('afterMethod:*', [$this, 'afterMethod']);
	}

	/** @return void */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$response->addHeader('X-Debug-Token', $this->request->getId());
	}
}
