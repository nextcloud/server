<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\OCS;

use OC\AppFramework\OCS\BaseResponse;

class ArrayValue implements \JsonSerializable {
	public function __construct(
		private array $array,
	) {
	}

	public function jsonSerialize(): mixed {
		return $this->array;
	}
}

class BaseResponseTest extends \Test\TestCase {
	public function testToXml(): void {
		/** @var BaseResponse $response */
		$response = $this->createMock(BaseResponse::class);

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent(false);
		$writer->startDocument();

		$data = [
			'hello' => 'hello',
			'information' => [
				'@test' => 'some data',
				'someElement' => 'withAttribute',
			],
			'value without key',
			'object' => new \stdClass(),
		];

		$this->invokePrivate($response, 'toXml', [$data, $writer]);
		$writer->endDocument();

		$this->assertEquals(
			"<?xml version=\"1.0\"?>\n<hello>hello</hello><information test=\"some data\"><someElement>withAttribute</someElement></information><element>value without key</element><object/>\n",
			$writer->outputMemory(true)
		);
	}
	
	public function testToXmlJsonSerializable(): void {
		/** @var BaseResponse $response */
		$response = $this->createMock(BaseResponse::class);

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent(false);
		$writer->startDocument();

		$data = [
			'hello' => 'hello',
			'information' => new ArrayValue([
				'@test' => 'some data',
				'someElement' => 'withAttribute',
			]),
			'value without key',
			'object' => new \stdClass(),
		];

		$this->invokePrivate($response, 'toXml', [$data, $writer]);
		$writer->endDocument();

		$this->assertEquals(
			"<?xml version=\"1.0\"?>\n<hello>hello</hello><information test=\"some data\"><someElement>withAttribute</someElement></information><element>value without key</element><object/>\n",
			$writer->outputMemory(true)
		);
	}
}
