<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_OC_Connector_Sabre_File extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException Sabre_DAV_Exception
	 */
	public function testSimplePutFails() {
		// setup
		$file = new OC_Connector_Sabre_File('/test.txt');
		$file->fileView = $this->getMock('\OC\Files\View', array('file_put_contents'), array(), '', FALSE);
		$file->fileView->expects($this->any())->method('file_put_contents')->withAnyParameters()->will($this->returnValue(false));

		// action
		$etag = $file->put('test data');
	}

	/**
	 * @expectedException Sabre_DAV_Exception
	 */
	public function testSimplePutFailsOnRename() {
		// setup
		$file = new OC_Connector_Sabre_File('/test.txt');
		$file->fileView = $this->getMock('\OC\Files\View', array('file_put_contents', 'rename'), array(), '', FALSE);
		$file->fileView->expects($this->any())->method('file_put_contents')->withAnyParameters()->will($this->returnValue(true));
		$file->fileView->expects($this->any())->method('rename')->withAnyParameters()->will($this->returnValue(false));

		// action
		$etag = $file->put('test data');
	}

	/**
	 * @expectedException Sabre_DAV_Exception_Forbidden
	 */
	public function testDeleteSharedFails() {
		$file = new OC_Connector_Sabre_File('Shared');
		$file->delete();
	}
}
