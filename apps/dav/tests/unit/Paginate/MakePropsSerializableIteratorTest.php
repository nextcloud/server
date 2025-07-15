<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\tests\unit\Paginate;

use OCA\DAV\Paginate\ArrayWriter;
use OCA\DAV\Paginate\MakePropsSerializableIterator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MakePropsSerializableIteratorTest extends TestCase {

	private ArrayWriter&MockObject $writerMock;

	protected function setUp(): void {
		parent::setUp();

		$this->writerMock = $this->createMock(ArrayWriter::class);
	}

	public function testCurrent(): void {
		$fileProperties = [
			[
				200 => [
					'simple' => 'property',
					'complex' => new \stdClass()
				],
				404 => [],
				'href' => 'file'
			]
		];

		$expectedProperties = [
			[
				200 => [
					'simple' => 'property',
					'complex' => 'complex-property'
				],
				404 => [],
				'href' => 'file'
			]
		];

		$propertyChecks = 1;
		$filePropertiesIterator = new \ArrayIterator($fileProperties);

		$this->writerMock->expects($this->exactly($propertyChecks))
			->method('openMemory');
		$this->writerMock->expects($this->exactly($propertyChecks))
			->method('startElement')
			->with('root');
		$this->writerMock->expects($this->exactly($propertyChecks))
			->method('getDocument')
			->willReturn(
				[['value' => 'complex-property']],
			);
		$this->writerMock->expects($this->exactly($propertyChecks))
			->method('endElement');

		$iterator = new MakePropsSerializableIterator($filePropertiesIterator, $this->writerMock);
		$this->assertEquals($expectedProperties[0], $iterator->current());
	}
}
