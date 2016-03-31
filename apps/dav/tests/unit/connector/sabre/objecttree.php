<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\DAV\Tests\Unit\Connector\Sabre;


use OC\Files\FileInfo;
use OC\Files\Storage\Temporary;

class TestDoubleFileView extends \OC\Files\View {

	public function __construct($updatables, $deletables, $canRename = true) {
		$this->updatables = $updatables;
		$this->deletables = $deletables;
		$this->canRename = $canRename;
	}

	public function isUpdatable($path) {
		return $this->updatables[$path];
	}

	public function isCreatable($path) {
		return $this->updatables[$path];
	}

	public function isDeletable($path) {
		return $this->deletables[$path];
	}

	public function rename($path1, $path2) {
		return $this->canRename;
	}

	public function getRelativePath($path) {
		return $path;
	}
}

/**
 * Class ObjectTree
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre
 */
class ObjectTree extends \Test\TestCase {

	/**
	 * @dataProvider moveFailedProvider
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testMoveFailed($source, $destination, $updatables, $deletables) {
		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	/**
	 * @dataProvider moveSuccessProvider
	 */
	public function testMoveSuccess($source, $destination, $updatables, $deletables) {
		$this->moveTest($source, $destination, $updatables, $deletables);
		$this->assertTrue(true);
	}

	/**
	 * @dataProvider moveFailedInvalidCharsProvider
	 * @expectedException \OCA\DAV\Connector\Sabre\Exception\InvalidPath
	 */
	public function testMoveFailedInvalidChars($source, $destination, $updatables, $deletables) {
		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	function moveFailedInvalidCharsProvider() {
		return array(
			array('a/b', 'a/*', array('a' => true, 'a/b' => true, 'a/c*' => false), array()),
		);
	}

	function moveFailedProvider() {
		return array(
			array('a/b', 'a/c', array('a' => false, 'a/b' => false, 'a/c' => false), array()),
			array('a/b', 'b/b', array('a' => false, 'a/b' => false, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => false, 'a/b' => true, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => false, 'b/b' => false), array()),
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false), array('a/b' => false)),
			array('a/b', 'a/c', array('a' => false, 'a/b' => true, 'a/c' => false), array()),
		);
	}

	function moveSuccessProvider() {
		return array(
			array('a/b', 'b/b', array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false), array('a/b' => true)),
			// older files with special chars can still be renamed to valid names
			array('a/b*', 'b/b', array('a' => true, 'a/b*' => true, 'b' => true, 'b/b' => false), array('a/b*' => true)),
		);
	}

	/**
	 * @param $source
	 * @param $destination
	 * @param $updatables
	 */
	private function moveTest($source, $destination, $updatables, $deletables) {
		$view = new TestDoubleFileView($updatables, $deletables);

		$info = new FileInfo('', null, null, array(), null);

		$rootDir = new \OCA\DAV\Connector\Sabre\Directory($view, $info);
		$objectTree = $this->getMock('\OCA\DAV\Connector\Sabre\ObjectTree',
			array('nodeExists', 'getNodeForPath'),
			array($rootDir, $view));

		$objectTree->expects($this->once())
			->method('getNodeForPath')
			->with($this->identicalTo($source))
			->will($this->returnValue(false));

		/** @var $objectTree \OCA\DAV\Connector\Sabre\ObjectTree */
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->move($source, $destination);
	}

	/**
	 * @dataProvider nodeForPathProvider
	 */
	public function testGetNodeForPath(
		$inputFileName,
		$fileInfoQueryPath,
		$outputFileName,
		$type,
		$enableChunkingHeader
	) {

		if ($enableChunkingHeader) {
			$_SERVER['HTTP_OC_CHUNKED'] = true;
		}

		$rootNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$mountManager = $this->getMock('\OC\Files\Mount\Manager');
		$view = $this->getMock('\OC\Files\View');
		$fileInfo = $this->getMock('\OCP\Files\FileInfo');
		$fileInfo->expects($this->once())
			->method('getType')
			->will($this->returnValue($type));
		$fileInfo->expects($this->once())
			->method('getName')
			->will($this->returnValue($outputFileName));

		$view->expects($this->once())
			->method('getFileInfo')
			->with($fileInfoQueryPath)
			->will($this->returnValue($fileInfo));

		$tree = new \OCA\DAV\Connector\Sabre\ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$node = $tree->getNodeForPath($inputFileName);

		$this->assertNotNull($node);
		$this->assertEquals($outputFileName, $node->getName());

		if ($type === 'file') {
			$this->assertTrue($node instanceof \OCA\DAV\Connector\Sabre\File);
		} else {
			$this->assertTrue($node instanceof \OCA\DAV\Connector\Sabre\Directory);
		}

		unset($_SERVER['HTTP_OC_CHUNKED']);
	}

	function nodeForPathProvider() {
		return array(
			// regular file
			array(
				'regularfile.txt',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				false
			),
			// regular directory
			array(
				'regulardir',
				'regulardir',
				'regulardir',
				'dir',
				false
			),
			// regular file with chunking
			array(
				'regularfile.txt',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				true
			),
			// regular directory with chunking
			array(
				'regulardir',
				'regulardir',
				'regulardir',
				'dir',
				true
			),
			// file with chunky file name
			array(
				'regularfile.txt-chunking-123566789-10-1',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				true
			),
			// regular file in subdir
			array(
				'subdir/regularfile.txt',
				'subdir/regularfile.txt',
				'regularfile.txt',
				'file',
				false
			),
			// regular directory in subdir
			array(
				'subdir/regulardir',
				'subdir/regulardir',
				'regulardir',
				'dir',
				false
			),
			// file with chunky file name in subdir
			array(
				'subdir/regularfile.txt-chunking-123566789-10-1',
				'subdir/regularfile.txt',
				'regularfile.txt',
				'file',
				true
			),
		);
	}

	/**
	 * @expectedException \OCA\DAV\Connector\Sabre\Exception\InvalidPath
	 */
	public function testGetNodeForPathInvalidPath() {
		$path = '/foo\bar';


		$storage = new Temporary([]);

		$view = $this->getMock('\OC\Files\View', ['resolvePath']);
		$view->expects($this->once())
			->method('resolvePath')
			->will($this->returnCallback(function($path) use ($storage){
			return [$storage, ltrim($path, '/')];
		}));

		$rootNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$mountManager = $this->getMock('\OC\Files\Mount\Manager');

		$tree = new \OCA\DAV\Connector\Sabre\ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$tree->getNodeForPath($path);
	}

	public function testGetNodeForPathRoot() {
		$path = '/';


		$storage = new Temporary([]);

		$view = $this->getMock('\OC\Files\View', ['resolvePath']);
		$view->expects($this->any())
			->method('resolvePath')
			->will($this->returnCallback(function ($path) use ($storage) {
				return [$storage, ltrim($path, '/')];
			}));

		$rootNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$mountManager = $this->getMock('\OC\Files\Mount\Manager');

		$tree = new \OCA\DAV\Connector\Sabre\ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$this->assertInstanceOf('\Sabre\DAV\INode', $tree->getNodeForPath($path));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Could not copy directory nameOfSourceNode, target exists
	 */
	public function testFailingMove() {
		$source = 'a/b';
		$destination = 'b/b';
		$updatables = array('a' => true, 'a/b' => true, 'b' => true, 'b/b' => false);
		$deletables = array('a/b' => true);

		$view = new TestDoubleFileView($updatables, $deletables);

		$info = new FileInfo('', null, null, array(), null);

		$rootDir = new \OCA\DAV\Connector\Sabre\Directory($view, $info);
		$objectTree = $this->getMock('\OCA\DAV\Connector\Sabre\ObjectTree',
			array('nodeExists', 'getNodeForPath'),
			array($rootDir, $view));

		$sourceNode = $this->getMockBuilder('\Sabre\DAV\ICollection')
			->disableOriginalConstructor()
			->getMock();
		$sourceNode->expects($this->once())
			->method('getName')
			->will($this->returnValue('nameOfSourceNode'));

		$objectTree->expects($this->once())
			->method('nodeExists')
			->with($this->identicalTo($destination))
			->will($this->returnValue(true));
		$objectTree->expects($this->once())
			->method('getNodeForPath')
			->with($this->identicalTo($source))
			->will($this->returnValue($sourceNode));

		/** @var $objectTree \OCA\DAV\Connector\Sabre\ObjectTree */
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->move($source, $destination);
	}
}
