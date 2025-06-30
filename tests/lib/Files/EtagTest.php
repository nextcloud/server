<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\Filesystem;
use OC\Files\Utils\Scanner;
use OC\Share\Share;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class EtagTest
 *
 * @group DB
 *
 * @package Test\Files
 */
class EtagTest extends \Test\TestCase {
	private $datadir;

	private $tmpDir;

	/**
	 * @var \Test\Util\User\Dummy $userBackend
	 */
	private $userBackend;

	protected function setUp(): void {
		parent::setUp();

		\OC_Hook::clear('OC_Filesystem', 'setup');
		// init files sharing
		new Application();

		Share::registerBackend('file', 'OCA\Files_Sharing\ShareBackend\File');
		Share::registerBackend('folder', 'OCA\Files_Sharing\ShareBackend\Folder', 'file');

		$config = Server::get(IConfig::class);
		$this->datadir = $config->getSystemValueString('datadirectory');
		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$config->setSystemValue('datadirectory', $this->tmpDir);

		$this->userBackend = new \Test\Util\User\Dummy();
		Server::get(IUserManager::class)->registerBackend($this->userBackend);
	}

	protected function tearDown(): void {
		Server::get(IConfig::class)->setSystemValue('datadirectory', $this->datadir);

		$this->logout();
		parent::tearDown();
	}

	public function testNewUser(): void {
		$user1 = $this->getUniqueID('user_');
		$this->userBackend->createUser($user1, '');

		$this->loginAsUser($user1);
		Filesystem::mkdir('/folder');
		Filesystem::mkdir('/folder/subfolder');
		Filesystem::file_put_contents('/foo.txt', 'asd');
		Filesystem::file_put_contents('/folder/bar.txt', 'fgh');
		Filesystem::file_put_contents('/folder/subfolder/qwerty.txt', 'jkl');

		$files = ['/foo.txt', '/folder/bar.txt', '/folder/subfolder', '/folder/subfolder/qwerty.txt'];
		$originalEtags = $this->getEtags($files);

		$scanner = new Scanner($user1, Server::get(IDBConnection::class), Server::get(IEventDispatcher::class), Server::get(LoggerInterface::class));
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
		$etags = [];
		foreach ($files as $file) {
			$info = Filesystem::getFileInfo($file);
			$etags[$file] = $info['etag'];
		}
		return $etags;
	}
}
