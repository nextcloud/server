<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Controller;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequestId;

class ChildOCSController extends OCSController {
}


class OCSControllerTest extends \Test\TestCase {
	public function testCors(): void {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test',
				],
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$controller = new ChildOCSController('app', $request, 'verbs',
			'headers', 100);

		$response = $controller->preflightedCors();

		$headers = $response->getHeaders();

		$this->assertEquals('test', $headers['Access-Control-Allow-Origin']);
		$this->assertEquals('verbs', $headers['Access-Control-Allow-Methods']);
		$this->assertEquals('headers', $headers['Access-Control-Allow-Headers']);
		$this->assertEquals('false', $headers['Access-Control-Allow-Credentials']);
		$this->assertEquals(100, $headers['Access-Control-Max-Age']);
	}


	public function testXML(): void {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		));
		$controller->setOCSVersion(1);

		$expected = "<?xml version=\"1.0\"?>\n" .
		"<ocs>\n" .
		" <meta>\n" .
		"  <status>ok</status>\n" .
		"  <statuscode>100</statuscode>\n" .
		"  <message>OK</message>\n" .
		"  <totalitems></totalitems>\n" .
		"  <itemsperpage></itemsperpage>\n" .
		" </meta>\n" .
		" <data>\n" .
		"  <test>hi</test>\n" .
		" </data>\n" .
		"</ocs>\n";

		$params = new DataResponse(['test' => 'hi']);

		$response = $controller->buildResponse($params, 'xml');
		$this->assertSame(EmptyContentSecurityPolicy::class, get_class($response->getContentSecurityPolicy()));
		$this->assertEquals($expected, $response->render());
	}

	public function testJSON(): void {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		));
		$controller->setOCSVersion(1);
		$expected = '{"ocs":{"meta":{"status":"ok","statuscode":100,"message":"OK",' .
					'"totalitems":"","itemsperpage":""},"data":{"test":"hi"}}}';
		$params = new DataResponse(['test' => 'hi']);

		$response = $controller->buildResponse($params, 'json');
		$this->assertSame(EmptyContentSecurityPolicy::class, get_class($response->getContentSecurityPolicy()));
		$this->assertEquals($expected, $response->render());
		$this->assertEquals($expected, $response->render());
	}

	public function testXMLV2(): void {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		));
		$controller->setOCSVersion(2);

		$expected = "<?xml version=\"1.0\"?>\n" .
			"<ocs>\n" .
			" <meta>\n" .
			"  <status>ok</status>\n" .
			"  <statuscode>200</statuscode>\n" .
			"  <message>OK</message>\n" .
			" </meta>\n" .
			" <data>\n" .
			"  <test>hi</test>\n" .
			" </data>\n" .
			"</ocs>\n";

		$params = new DataResponse(['test' => 'hi']);

		$response = $controller->buildResponse($params, 'xml');
		$this->assertSame(EmptyContentSecurityPolicy::class, get_class($response->getContentSecurityPolicy()));
		$this->assertEquals($expected, $response->render());
	}

	public function testJSONV2(): void {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		));
		$controller->setOCSVersion(2);
		$expected = '{"ocs":{"meta":{"status":"ok","statuscode":200,"message":"OK"},"data":{"test":"hi"}}}';
		$params = new DataResponse(['test' => 'hi']);

		$response = $controller->buildResponse($params, 'json');
		$this->assertSame(EmptyContentSecurityPolicy::class, get_class($response->getContentSecurityPolicy()));
		$this->assertEquals($expected, $response->render());
	}
}
