<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class JSONResponseTest extends \Test\TestCase {
	/**
	 * @var JSONResponse
	 */
	private $json;

	protected function setUp(): void {
		parent::setUp();
		$this->json = new JSONResponse();
	}


	public function testHeader(): void {
		$headers = $this->json->getHeaders();
		$this->assertEquals('application/json; charset=utf-8', $headers['Content-Type']);
	}


	public function testSetData(): void {
		$params = ['hi', 'yo'];
		$this->json->setData($params);

		$this->assertEquals(['hi', 'yo'], $this->json->getData());
	}


	public function testSetRender(): void {
		$params = ['test' => 'hi'];
		$this->json->setData($params);

		$expected = '{"test":"hi"}';

		$this->assertEquals($expected, $this->json->render());
	}

	/**
	 * @return array
	 */
	public function renderDataProvider() {
		return [
			[
				['test' => 'hi'], '{"test":"hi"}',
			],
			[
				['<h1>test' => '<h1>hi'], '{"\u003Ch1\u003Etest":"\u003Ch1\u003Ehi"}',
			],
		];
	}

	/**
	 * @dataProvider renderDataProvider
	 * @param array $input
	 * @param string $expected
	 */
	public function testRender(array $input, $expected): void {
		$this->json->setData($input);
		$this->assertEquals($expected, $this->json->render());
	}


	public function testRenderWithNonUtf8Encoding(): void {
		$this->expectException(\JsonException::class);
		$this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

		$params = ['test' => hex2bin('e9')];
		$this->json->setData($params);
		$this->json->render();
	}

	public function testConstructorAllowsToSetData(): void {
		$data = ['hi'];
		$code = 300;
		$response = new JSONResponse($data, $code);

		$expected = '["hi"]';
		$this->assertEquals($expected, $response->render());
		$this->assertEquals($code, $response->getStatus());
	}

	public function testChainability(): void {
		$params = ['hi', 'yo'];
		$this->json->setData($params)
			->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->json->getStatus());
		$this->assertEquals(['hi', 'yo'], $this->json->getData());
	}
}
