<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class Sapi {
	/**
	 * @var \Sabre\HTTP\Response
	 */
	private $response;

	/**
	 * This static method will create a new Request object, based on the
	 * current PHP request.
	 *
	 * @return \Sabre\HTTP\Request
	 */
	public function getRequest() {
		return $this->request;
	}

	public function __construct(
		private Request $request,
	) {
	}

	/**
	 * @param \Sabre\HTTP\Response $response
	 * @return void
	 */
	public function sendResponse(Response $response): void {
		// we need to copy the body since we close the source stream
		$copyStream = fopen('php://temp', 'r+');
		if (is_string($response->getBody())) {
			fwrite($copyStream, $response->getBody());
		} elseif (is_resource($response->getBody())) {
			stream_copy_to_stream($response->getBody(), $copyStream);
		}
		rewind($copyStream);
		$this->response = new Response($response->getStatus(), $response->getHeaders(), $copyStream);
	}

	/**
	 * @return \Sabre\HTTP\Response
	 */
	public function getResponse() {
		return $this->response;
	}
}
