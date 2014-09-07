<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files;

use OC\Files\Filesystem;
use OCP\Share;

class EtagTest extends \PHPUnit_Framework_TestCase {
	private $datadir;

	private $tmpDir;

	private $uid;

	/**
	 * @var \OC_User_Dummy $userBackend
	 */
	private $userBackend;

	public function setUp() {
		\OC_Hook::clear('OC_Filesystem', 'setup');
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');
		\OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
		\OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');

		$this->datadir = \OC_Config::getValue('datadirectory');
		$this->tmpDir = \OC_Helper::tmpFolder();
		\OC_Config::setValue('datadirectory', $this->tmpDir);
		$this->uid = \OC_User::getUser();
		\OC_User::setUserId(null);

		$this->userBackend = new \OC_User_Dummy();
		\OC_User::useBackend($this->userBackend);
		\OC_Util::tearDownFS();
	}

	public function tearDown() {
		\OC_Config::setValue('datadirectory', $this->datadir);
		\OC_User::setUserId($this->uid);
		\OC_Util::setupFS($this->uid);
	}

	public function testNewUser() {
		$user1 = uniqid('user_');
		$this->userBackend->createUser($user1, '');

		\OC_Util::tearDownFS();
		\OC_User::setUserId($user1);
		\OC_Util::setupFS($user1);
		Filesystem::mkdir('/folder');
		Filesystem::mkdir('/folder/subfolder');
		Filesystem::file_put_contents('/foo.txt', 'asd');
		Filesystem::file_put_contents('/folder/bar.txt', 'fgh');
		Filesystem::file_put_contents('/folder/subfolder/qwerty.txt', 'jkl');

		$files = array('/foo.txt', '/folder/bar.txt', '/folder/subfolder', '/folder/subfolder/qwerty.txt');
		$originalEtags = $this->getEtags($files);

		$scanner = new \OC\Files\Utils\Scanner($user1, \OC::$server->getDatabaseConnection());
		$scanner->backgroundScan('/');

		$newEtags = $this->getEtags($files);
		// loop over array and use assertSame over assertEquals to prevent false positives
		foreach ($originalEtags as $file => $originalEtag) {
			$this->assertSame($originalEtag, $newEtags[$file]);
		}
	}

	/**
	 * @param string[] $files
	 */
	private function getEtags($files) {
		$etags = array();
		foreach ($files as $file) {
			$info = Filesystem::getFileInfo($file);
			$etags[$file] = $info['etag'];
		}
		return $etags;
	}
}
