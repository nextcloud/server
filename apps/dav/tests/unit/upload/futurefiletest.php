<?php

class FutureFileTest extends \PHPUnit_Framework_TestCase {

	public function testGetContentType() {
		$f = $this->mockFutureFile();
		$this->assertEquals('application/octet-stream', $f->getContentType());
	}

	public function testGetETag() {
		$f = $this->mockFutureFile();
		$this->assertEquals('1234567890', $f->getETag());
	}

	public function testGetName() {
		$f = $this->mockFutureFile();
		$this->assertEquals('foo.txt', $f->getName());
	}

	public function testGetLastModified() {
		$f = $this->mockFutureFile();
		$this->assertEquals(12121212, $f->getLastModified());
	}

	public function testGetSize() {
		$f = $this->mockFutureFile();
		$this->assertEquals(0, $f->getSize());
	}

	public function testGet() {
		$f = $this->mockFutureFile();
		$stream = $f->get();
		$this->assertTrue(is_resource($stream));
	}

	public function testDelete() {
		$d = $this->getMockBuilder('OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->setMethods(['delete'])
			->getMock();

		$d->expects($this->once())
			->method('delete');

		$f = new \OCA\DAV\Upload\FutureFile($d, 'foo.txt');
		$f->delete();
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testPut() {
		$f = $this->mockFutureFile();
		$f->put('');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testSetName() {
		$f = $this->mockFutureFile();
		$f->setName('');
	}

	/**
	 * @return \OCA\DAV\Upload\FutureFile
	 */
	private function mockFutureFile() {
		$d = $this->getMockBuilder('OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->setMethods(['getETag', 'getLastModified', 'getChildren'])
			->getMock();

		$d->expects($this->any())
			->method('getETag')
			->willReturn('1234567890');

		$d->expects($this->any())
			->method('getLastModified')
			->willReturn(12121212);

		$d->expects($this->any())
			->method('getChildren')
			->willReturn([]);

		return new \OCA\DAV\Upload\FutureFile($d, 'foo.txt');
	}
}

