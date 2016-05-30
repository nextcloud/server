<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2014 Bernhard Posselt <dev@bernhard-posselt.com>
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


namespace Test\AppFramework\Http;


use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;


class DataResponseTest extends \Test\TestCase {

	/**
	 * @var DataResponse
	 */
	private $response;

	protected function setUp() {
		parent::setUp();
		$this->response = new DataResponse();
	}


	public function testSetData() {
		$params = array('hi', 'yo');
		$this->response->setData($params);

		$this->assertEquals(array('hi', 'yo'), $this->response->getData());
	}


	public function testConstructorAllowsToSetData() {
		$data = array('hi');
		$code = 300;
		$response = new DataResponse($data, $code);

		$this->assertEquals($data, $response->getData());
		$this->assertEquals($code, $response->getStatus());
	}


	public function testConstructorAllowsToSetHeaders() {
		$data = array('hi');
		$code = 300;
		$headers = array('test' => 'something');
		$response = new DataResponse($data, $code, $headers);

		$expectedHeaders = [
			'Cache-Control' => 'no-cache, must-revalidate',
			'Content-Security-Policy' => "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self';connect-src 'self';media-src 'self'",
		];
		$expectedHeaders = array_merge($expectedHeaders, $headers);

		$this->assertEquals($data, $response->getData());
		$this->assertEquals($code, $response->getStatus());
		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}


	public function testChainability() {
		$params = array('hi', 'yo');
		$this->response->setData($params)
			->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->response->getStatus());
		$this->assertEquals(array('hi', 'yo'), $this->response->getData());
	}


}
