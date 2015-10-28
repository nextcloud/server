<?php

class AssemblyStreamTest extends \PHPUnit_Framework_TestCase {

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

