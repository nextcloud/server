<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre;

class File extends \Test\TestCase {

	private function getStream($string) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $string);
		fseek($stream, 0);
		return $stream;
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testSimplePutFails() {
		// setup
		$storage = $this->getMock('\OC\Files\Storage\Local', ['fopen'], [['datadir' => \OC::$server->getTempManager()->getTemporaryFolder()]]);
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'getRelativePath', 'resolvePath'), array());
		$view->expects($this->any())
			->method('resolvePath')
			->will($this->returnValue(array($storage, '')));
		$storage->expects($this->once())
			->method('fopen')
			->will($this->returnValue(false));

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/test.txt'));

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->put('test data');
	}

	public function testPutSingleFileShare() {
		// setup
		$stream = fopen('php://temp', 'w+');
		$storage = $this->getMock('\OC\Files\Storage\Local', ['fopen'], [['datadir' => \OC::$server->getTempManager()->getTemporaryFolder()]]);
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'getRelativePath', 'resolvePath'), array());
		$view->expects($this->any())
			->method('resolvePath')
			->will($this->returnValue(array($storage, '')));
		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue(''));
		$view->expects($this->any())
			->method('file_put_contents')
			->with('')
			->will($this->returnValue(true));
		$storage->expects($this->once())
			->method('fopen')
			->will($this->returnValue($stream));

		$info = new \OC\Files\FileInfo('/foo.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		$this->assertNotEmpty($file->put($this->getStream('test data')));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testSimplePutFailsOnRename() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array('file_put_contents', 'rename', 'getRelativePath', 'filesize'));
		$view->expects($this->any())
			->method('file_put_contents')
			->withAnyParameters()
			->will($this->returnValue(true));
		$view->expects($this->any())
			->method('rename')
			->withAnyParameters()
			->will($this->returnValue(false));
		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/test.txt'));
		$view->expects($this->any())
			->method('filesize')
			->will($this->returnValue(123456));

		$_SERVER['CONTENT_LENGTH'] = 123456;
		$_SERVER['REQUEST_METHOD'] = 'PUT';

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->put($this->getStream('test data'));
	}

	/**
	 * @expectedException \OC\Connector\Sabre\Exception\InvalidPath
	 */
	public function testSimplePutInvalidChars() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'getRelativePath'));
		$view->expects($this->any())
			->method('file_put_contents')
			->will($this->returnValue(false));

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/*'));

		$info = new \OC\Files\FileInfo('/*', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);
		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->put($this->getStream('test data'));
	}

	/**
	 * Test setting name with setName() with invalid chars
	 *
	 * @expectedException \OC\Connector\Sabre\Exception\InvalidPath
	 */
	public function testSetNameInvalidChars() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('getRelativePath'));

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/*'));

		$info = new \OC\Files\FileInfo('/*', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);
		$file = new \OC\Connector\Sabre\File($view, $info);
		$file->setName('/super*star.txt');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testUploadAbort() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array('file_put_contents', 'rename', 'getRelativePath', 'filesize'));
		$view->expects($this->any())
			->method('file_put_contents')
			->withAnyParameters()
			->will($this->returnValue(true));
		$view->expects($this->any())
			->method('rename')
			->withAnyParameters()
			->will($this->returnValue(false));
		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/test.txt'));
		$view->expects($this->any())
			->method('filesize')
			->will($this->returnValue(123456));

		$_SERVER['CONTENT_LENGTH'] = 12345;
		$_SERVER['REQUEST_METHOD'] = 'PUT';

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->put($this->getStream('test data'));
	}

	/**
	 *
	 */
	public function testDeleteWhenAllowed() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array());

		$view->expects($this->once())
			->method('unlink')
			->will($this->returnValue(true));

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteThrowsWhenDeletionNotAllowed() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array());

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => 0
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteThrowsWhenDeletionFailed() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array());

		// but fails
		$view->expects($this->once())
			->method('unlink')
			->will($this->returnValue(false));

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\Constants::PERMISSION_ALL
		), null);

		$file = new \OC\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}
}
