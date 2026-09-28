<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Controller;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\ApiController;
use OCP\IConfig;
use OCP\IRequestId;

class ChildApiController extends ApiController {
};


class ApiControllerTest extends \Test\TestCase {
	/** @var ChildApiController */
	protected $controller;

	public function testCors(): void {
		$request = new Request(
			['server' => ['HTTP_ORIGIN' => 'test']],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->controller = new ChildApiController('app', $request, 'verbs',
			'headers', 100);

		$response = $this->controller->preflightedCors();

		$headers = $response->getHeaders();

		$this->assertEquals('test', $headers['Access-Control-Allow-Origin']);
		$this->assertEquals('verbs', $headers['Access-Control-Allow-Methods']);
		$this->assertEquals('headers', $headers['Access-Control-Allow-Headers']);
		$this->assertEquals('false', $headers['Access-Control-Allow-Credentials']);
		$this->assertEquals(100, $headers['Access-Control-Max-Age']);
	}
}
