<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\AppFramework;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\DataResponse;


class ChildOCSController extends OCSController {}


class OCSControllerTest extends \Test\TestCase {

	private $controller;

	public function testCors() {
		$request = new Request(
			[
				'server' => [
					'HTTP_ORIGIN' => 'test',
				],
			],
			$this->getMock('\OCP\Security\ISecureRandom'),
			$this->getMock('\OCP\IConfig')
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


	public function testXML() {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->getMock('\OCP\Security\ISecureRandom'),
			$this->getMock('\OCP\IConfig')
		));
		$expected = "<?xml version=\"1.0\"?>\n" .
		"<ocs>\n" .
		" <meta>\n" .
		"  <status>failure</status>\n" .
		"  <statuscode>400</statuscode>\n" .
		"  <message>OK</message>\n" .
		"  <totalitems></totalitems>\n" .
		"  <itemsperpage></itemsperpage>\n" .
		" </meta>\n" .
		" <data>\n" .
		"  <test>hi</test>\n" .
		" </data>\n" .
		"</ocs>\n";

		$params = [
			'data' => [
				'test' => 'hi'
			],
			'statuscode' => 400
		];

		$out = $controller->buildResponse($params, 'xml')->render();
		$this->assertEquals($expected, $out);
	}


	public function testXMLDataResponse() {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->getMock('\OCP\Security\ISecureRandom'),
			$this->getMock('\OCP\IConfig')
		));
		$expected = "<?xml version=\"1.0\"?>\n" .
		"<ocs>\n" .
		" <meta>\n" .
		"  <status>failure</status>\n" .
		"  <statuscode>400</statuscode>\n" .
		"  <message>OK</message>\n" .
		"  <totalitems></totalitems>\n" .
		"  <itemsperpage></itemsperpage>\n" .
		" </meta>\n" .
		" <data>\n" .
		"  <test>hi</test>\n" .
		" </data>\n" .
		"</ocs>\n";

		$params = new DataResponse([
			'data' => [
				'test' => 'hi'
			],
			'statuscode' => 400
		]);

		$out = $controller->buildResponse($params, 'xml')->render();
		$this->assertEquals($expected, $out);
	}


	public function testJSON() {
		$controller = new ChildOCSController('app', new Request(
			[],
			$this->getMock('\OCP\Security\ISecureRandom'),
			$this->getMock('\OCP\IConfig')
		));
		$expected = '{"ocs":{"meta":{"status":"failure","statuscode":400,"message":"OK",' .
		            '"totalitems":"","itemsperpage":""},"data":{"test":"hi"}}}';
		$params = [
			'data' => [
				'test' => 'hi'
			],
			'statuscode' => 400
		];

		$out = $controller->buildResponse($params, 'json')->render();
		$this->assertEquals($expected, $out);
	}


}
