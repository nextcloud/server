<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\OC\Connector\Sabre;


use OC_Connector_Sabre_Directory;
use PHPUnit_Framework_TestCase;
use Sabre_DAV_Exception_Forbidden;

class TestDoubleFileView extends \OC\Files\View{

	public function __construct($updatables, $canRename = true) {
		$this->updatables = $updatables;
		$this->canRename = $canRename;
	}

	public function isUpdatable($path) {
		return $this->updatables[$path];
	}

	public function rename($path1, $path2) {
		return $this->canRename;
	}
}

class ObjectTree extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider moveFailedProvider
	 * @expectedException Sabre_DAV_Exception_Forbidden
	 */
	public function testMoveFailed($source, $dest, $updatables) {
		$this->moveTest($source, $dest, $updatables);
	}

	/**
	 * @dataProvider moveSuccessProvider
	 */
	public function testMoveSuccess($source, $dest, $updatables) {
		$this->moveTest($source, $dest, $updatables);
		$this->assertTrue(true);
	}

	function moveFailedProvider() {
		return array(
			array('a/b', 'a/c', array('a' => false, 'a/b' => false, 'a/c' => false)),
			array('a/b', 'b/b', array('a' => false, 'a/b' => false, 'b' => false, 'b/b' => false)),
			array('a/b', 'b/b', array('a' => false, 'a/b' => true, 'b' => false, 'b/b' => false)),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => false, 'b/b' => false)),
		);
	}

	function moveSuccessProvider() {
		return array(
			array('a/b', 'a/c', array('a' => false, 'a/b' => true, 'a/c' => false)),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false)),
		);
	}

	/**
	 * @param $source
	 * @param $dest
	 * @param $updatables
	 */
	private function moveTest($source, $dest, $updatables) {
		$rootDir = new OC_Connector_Sabre_Directory('');
		$objectTree = $this->getMock('\OC\Connector\Sabre\ObjectTree',
			array('nodeExists', 'getNodeForPath'),
			array($rootDir));

		$objectTree->expects($this->once())
			->method('getNodeForPath')
			->with($this->identicalTo($source))
			->will($this->returnValue(false));

		/** @var $objectTree \OC\Connector\Sabre\ObjectTree */
		$objectTree->fileView = new TestDoubleFileView($updatables);
		$objectTree->move($source, $dest);
	}

}
