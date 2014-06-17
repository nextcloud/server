<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\OC\Connector\Sabre;


use OC\Files\FileInfo;
use OC_Connector_Sabre_Directory;
use PHPUnit_Framework_TestCase;

class TestDoubleFileView extends \OC\Files\View{

	public function __construct($updatables, $deletables, $canRename = true) {
		$this->updatables = $updatables;
		$this->deletables = $deletables;
		$this->canRename = $canRename;
	}

	public function isUpdatable($path) {
		return $this->updatables[$path];
	}

	public function isDeletable($path) {
		return $this->deletables[$path];
	}

	public function rename($path1, $path2) {
		return $this->canRename;
	}

	public function getRelativePath($path){
		return $path;
	}
}

class ObjectTree extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider moveFailedProvider
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testMoveFailed($source, $dest, $updatables, $deletables) {
		$this->moveTest($source, $dest, $updatables, $deletables);
	}

	/**
	 * @dataProvider moveSuccessProvider
	 */
	public function testMoveSuccess($source, $dest, $updatables, $deletables) {
		$this->moveTest($source, $dest, $updatables, $deletables);
		$this->assertTrue(true);
	}

	/**
	 * @dataProvider moveFailedInvalidCharsProvider
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testMoveFailedInvalidChars($source, $dest, $updatables, $deletables) {
		$this->moveTest($source, $dest, $updatables, $deletables);
	}

	function moveFailedInvalidCharsProvider() {
		return array(
			array('a/b', 'a/c*', array('a' => false, 'a/b' => true, 'a/c*' => false), array()),
		);
	}

	function moveFailedProvider() {
		return array(
			array('a/b', 'a/c', array('a' => false, 'a/b' => false, 'a/c' => false), array()),
			array('a/b', 'b/b', array('a' => false, 'a/b' => false, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => false, 'a/b' => true, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false), array('a/b' => false)),
		);
	}

	function moveSuccessProvider() {
		return array(
			array('a/b', 'a/c', array('a' => false, 'a/b' => true, 'a/c' => false), array()),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false), array('a/b' => true)),
			// older files with special chars can still be renamed to valid names
			array('a/b*', 'b/b', array('a' => true, 'a/b*' => true, 'b' => true, 'b/b' => false), array('a/b*' => true)),
		);
	}

	/**
	 * @param $source
	 * @param $dest
	 * @param $updatables
	 */
	private function moveTest($source, $dest, $updatables, $deletables) {
		$view = new TestDoubleFileView($updatables, $deletables);

		$info = new FileInfo('', null, null, array());

		$rootDir = new OC_Connector_Sabre_Directory($view, $info);
		$objectTree = $this->getMock('\OC\Connector\Sabre\ObjectTree',
			array('nodeExists', 'getNodeForPath'),
			array($rootDir, $view));

		$objectTree->expects($this->once())
			->method('getNodeForPath')
			->with($this->identicalTo($source))
			->will($this->returnValue(false));

		/** @var $objectTree \OC\Connector\Sabre\ObjectTree */
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->move($source, $dest);
	}

}
