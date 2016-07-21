<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Upload;

class AssemblyStreamTest extends \Test\TestCase {

	/**
	 * @dataProvider providesNodes()
	 */
	public function testGetContents($expected, $nodes) {
		$stream = \OCA\DAV\Upload\AssemblyStream::wrap($nodes);
		$content = stream_get_contents($stream);

		$this->assertEquals($expected, $content);
	}

	function providesNodes() {
		return[
			'one node only' => ['1234567890', [
				$this->buildNode('0', '1234567890')
			]],
			'two nodes' => ['1234567890', [
				$this->buildNode('1', '67890'),
				$this->buildNode('0', '12345')
			]]
		];
	}

	private function buildNode($name, $data) {
		$node = $this->getMockBuilder('\Sabre\DAV\File')
			->setMethods(['getName', 'get', 'getSize'])
			->getMockForAbstractClass();

		$node->expects($this->any())
			->method('getName')
			->willReturn($name);

		$node->expects($this->any())
			->method('get')
			->willReturn($data);

		$node->expects($this->any())
			->method('getSize')
			->willReturn(strlen($data));

		return $node;
	}
}

