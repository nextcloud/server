<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_OC_Connector_Sabre_File extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testSimplePutFails() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'getRelativePath'), array(), '', false);
		$view->expects($this->any())
			->method('file_put_contents')
			->will($this->returnValue(false));

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/test.txt'));

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions'=>\OCP\PERMISSION_ALL
		));

		$file = new OC_Connector_Sabre_File($view, $info);

		// action
		$etag = $file->put('test data');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testSimplePutFailsOnRename() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'rename', 'getRelativePath'), array(), '', false);
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

		$info = new \OC\Files\FileInfo('/test.txt', null, null, array(
			'permissions' => \OCP\PERMISSION_ALL
		));

		$file = new OC_Connector_Sabre_File($view, $info);

		// action
		$etag = $file->put('test data');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testSimplePutInvalidChars() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('file_put_contents', 'getRelativePath'), array(), '', false);
		$view->expects($this->any())
			->method('file_put_contents')
			->will($this->returnValue(false));

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/super*star.txt'));

		$info = new \OC\Files\FileInfo('/super*star.txt', null, null, array(
			'permissions' => \OCP\PERMISSION_ALL
		));
		$file = new OC_Connector_Sabre_File($view, $info);

		// action
		$etag = $file->put('test data');
	}

	/**
	 * Test setting name with setName() with invalid chars
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testSetNameInvalidChars() {
		// setup
		$view = $this->getMock('\OC\Files\View', array('getRelativePath'), array(), '', false);

		$view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue('/super*star.txt'));

		$info = new \OC\Files\FileInfo('/super*star.txt', null, null, array(
			'permissions' => \OCP\PERMISSION_ALL
		));
		$file = new OC_Connector_Sabre_File($view, $info);
		$file->setName('/super*star.txt');
	}
}
