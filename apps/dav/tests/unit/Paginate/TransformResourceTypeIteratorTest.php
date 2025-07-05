<?php

namespace OCA\DAV\Tests\unit\Paginate;

use OCA\DAV\Paginate\TransformResourceTypeIterator;
use Sabre\DAV\Xml\Property\ResourceType;
use Test\TestCase;

class TransformResourceTypeIteratorTest extends TestCase {

	public function testCurrent(): void {
		$fileProperties = [
			[
				200 => [
					'{DAV:}displayname' => 'Media',
					'{DAV:}resourcetype' => new ResourceType(['{DAV:}collection']),
				],
				404 => [],
				'href' => 'files/user/Media'
			],
			[
				200 => [
					'{DAV:}displayname' => 'file.txt',
				],
				404 => [],
				'href' => 'files/user/Media'
			],
		];


		$expectedProperties = [
			[
				200 => [
					'{DAV:}displayname' => 'Media',
					'{DAV:}resourcetype' => ['{DAV:}collection' => null],
				],
				404 => [],
				'href' => 'files/user/Media'
			],
			[
				200 => [
					'{DAV:}displayname' => 'file.txt',
				],
				404 => [],
				'href' => 'files/user/Media'
			],
		];

		$filePropertiesIterator = new \ArrayIterator($fileProperties);
		$iterator = new TransformResourceTypeIterator($filePropertiesIterator);
		$i = 0;
		foreach ($iterator as $value) {
			$this->assertSame($expectedProperties[$i++], $value);
		}

		$this->assertGreaterThan(0, $i, 'Iterator was empty!');
	}
}
