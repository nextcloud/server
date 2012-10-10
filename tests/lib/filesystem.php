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

use \OC\Files\Filesystem as Filesystem;

class Test_Filesystem extends UnitTestCase {
	/**
	 * @var array tmpDirs
	 */
	private $tmpDirs=array();

	/**
	 * @return array
	 */
	private function getStorageData() {
		$dir = OC_Helper::tmpFolder();
		$this->tmpDirs[] = $dir;
		return array('datadir' => $dir);
	}

	public function tearDown() {
		foreach ($this->tmpDirs as $dir) {
			OC_Helper::rmdirr($dir);
		}
	}

	public function setUp() {
		Filesystem::clearMounts();
	}

	public function testMount() {
		Filesystem::mount('\OC\Files\Storage\Local',self::getStorageData(),'/');
		$this->assertEqual('/',Filesystem::getMountPoint('/'));
		$this->assertEqual('/',Filesystem::getMountPoint('/some/folder'));
		$this->assertEqual('',Filesystem::getInternalPath('/'));
		$this->assertEqual('some/folder',Filesystem::getInternalPath('/some/folder'));

		Filesystem::mount('\OC\Files\Storage\Local',self::getStorageData(),'/some');
		$this->assertEqual('/',Filesystem::getMountPoint('/'));
		$this->assertEqual('/some/',Filesystem::getMountPoint('/some/folder'));
		$this->assertEqual('/some/',Filesystem::getMountPoint('/some/'));
		$this->assertEqual('/',Filesystem::getMountPoint('/some'));
		$this->assertEqual('folder',Filesystem::getInternalPath('/some/folder'));
	}

	public function testNormalize() {
		$this->assertEqual('/path', Filesystem::normalizePath('/path/'));
		$this->assertEqual('/path/', Filesystem::normalizePath('/path/', false));
		$this->assertEqual('/path', Filesystem::normalizePath('path'));
		$this->assertEqual('/path', Filesystem::normalizePath('\path'));
		$this->assertEqual('/foo/bar', Filesystem::normalizePath('/foo//bar/'));
		$this->assertEqual('/foo/bar', Filesystem::normalizePath('/foo////bar'));
		if (class_exists('Normalizer')) {
			$this->assertEqual("/foo/bar\xC3\xBC", Filesystem::normalizePath("/foo/baru\xCC\x88"));
		}
	}

	public function testHooks() {
		if(Filesystem::getView()){
			$user = OC_User::getUser();
		}else{
			$user=uniqid();
			Filesystem::init('/'.$user.'/files');
		}
		OC_Hook::clear('OC_Filesystem');
		OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		Filesystem::mount('OC\Files\Storage\Temporary', array(), '/');

		$rootView=new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		Filesystem::file_put_contents('/foo', 'foo');
		Filesystem::mkdir('/bar');
		Filesystem::file_put_contents('/bar//foo', 'foo');

		$tmpFile = OC_Helper::tmpFile();
		file_put_contents($tmpFile, 'foo');
		$fh = fopen($tmpFile, 'r');
		Filesystem::file_put_contents('/bar//foo', $fh);
	}

	public function dummyHook($arguments) {
		$path = $arguments['path'];
		$this->assertEqual($path, Filesystem::normalizePath($path)); //the path passed to the hook should already be normalized
	}
}
