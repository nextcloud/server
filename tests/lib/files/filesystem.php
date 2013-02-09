<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files;

class Filesystem extends \PHPUnit_Framework_TestCase {
	/**
	 * @var array tmpDirs
	 */
	private $tmpDirs=array();

	/**
	 * @return array
	 */
	private function getStorageData() {
		$dir = \OC_Helper::tmpFolder();
		$this->tmpDirs[] = $dir;
		return array('datadir' => $dir);
	}

	public function tearDown() {
		foreach ($this->tmpDirs as $dir) {
			\OC_Helper::rmdirr($dir);
		}
	}

	public function setUp() {
		\OC\Files\Filesystem::clearMounts();
	}

	public function testMount() {
		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local',self::getStorageData(),'/');
		$this->assertEquals('/',\OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/',\OC\Files\Filesystem::getMountPoint('/some/folder'));
		list( , $internalPath)=\OC\Files\Filesystem::resolvePath('/');
		$this->assertEquals('',$internalPath);
		list( , $internalPath)=\OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('some/folder',$internalPath);

		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local',self::getStorageData(),'/some');
		$this->assertEquals('/',\OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/some/',\OC\Files\Filesystem::getMountPoint('/some/folder'));
		$this->assertEquals('/some/',\OC\Files\Filesystem::getMountPoint('/some/'));
		$this->assertEquals('/some/',\OC\Files\Filesystem::getMountPoint('/some'));
		list( , $internalPath)=\OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('folder',$internalPath);
	}

	public function testNormalize() {
		$this->assertEquals('/path', \OC\Files\Filesystem::normalizePath('/path/'));
		$this->assertEquals('/path/', \OC\Files\Filesystem::normalizePath('/path/', false));
		$this->assertEquals('/path', \OC\Files\Filesystem::normalizePath('path'));
		$this->assertEquals('/path', \OC\Files\Filesystem::normalizePath('\path'));
		$this->assertEquals('/foo/bar', \OC\Files\Filesystem::normalizePath('/foo//bar/'));
		$this->assertEquals('/foo/bar', \OC\Files\Filesystem::normalizePath('/foo////bar'));
		if (class_exists('Normalizer')) {
			$this->assertEquals("/foo/bar\xC3\xBC", \OC\Files\Filesystem::normalizePath("/foo/baru\xCC\x88"));
		}
	}

	public function testHooks() {
		if(\OC\Files\Filesystem::getView()){
			$user = \OC_User::getUser();
		}else{
			$user=uniqid();
			\OC\Files\Filesystem::init('/'.$user.'/files');
		}
		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		\OC\Files\Filesystem::mount('OC\Files\Storage\Temporary', array(), '/');

		$rootView=new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		\OC\Files\Filesystem::file_put_contents('/foo', 'foo');
		\OC\Files\Filesystem::mkdir('/bar');
		\OC\Files\Filesystem::file_put_contents('/bar//foo', 'foo');

		$tmpFile = \OC_Helper::tmpFile();
		file_put_contents($tmpFile, 'foo');
		$fh = fopen($tmpFile, 'r');
		\OC\Files\Filesystem::file_put_contents('/bar//foo', $fh);
	}

	public function dummyHook($arguments) {
		$path = $arguments['path'];
		$this->assertEquals($path, \OC\Files\Filesystem::normalizePath($path)); //the path passed to the hook should already be normalized
	}
}
