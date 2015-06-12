<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre;

use Test\HookHelper;
use OC\Files\Filesystem;

class File extends \Test\TestCase {

	/**
	 * @var string
	 */
	private $user;

	public function setUp() {
		parent::setUp();

		\OC_Hook::clear();

		$this->user = $this->getUniqueID('user_');
		$userManager = \OC::$server->getUserManager();
		$userManager->createUser($this->user, 'pass');

		$this->loginAsUser($this->user);
	}

	public function tearDown() {
		$userManager = \OC::$server->getUserManager();
		$userManager->get($this->user)->delete();

		parent::tearDown();
	}

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
		$view = $this->getMock('\OC\Files\View', array('getRelativePath', 'resolvePath'), array());
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

	private function doPut($path, $viewRoot = null) {
		$view = \OC\Files\Filesystem::getView();
		if (!is_null($viewRoot)) {
			$view = new \OC\Files\View($viewRoot);
		} else {
			$viewRoot = '/' . $this->user . '/files';
		}

		$info = new \OC\Files\FileInfo(
			$viewRoot . '/' . ltrim($path, '/'),
			null,
			null,
			['permissions' => \OCP\Constants::PERMISSION_ALL],
			null
		);

		$file = new \OC\Connector\Sabre\File($view, $info);

		$this->assertNotEmpty($file->put($this->getStream('test data')));
	}

	/**
	 * Test putting a single file
	 */
	public function testPutSingleFile() {
		$this->doPut('/foo.txt');
	}

	/**
	 * Test that putting a file triggers create hooks
	 */
	public function testPutSingleFileTriggersHooks() {
		HookHelper::setUpHooks();

		$this->doPut('/foo.txt');

		$this->assertCount(4, HookHelper::$hookCalls);
		$this->assertHookCall(
			HookHelper::$hookCalls[0],
			Filesystem::signal_create,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[1],
			Filesystem::signal_write,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[2],
			Filesystem::signal_post_create,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[3],
			Filesystem::signal_post_write,
			'/foo.txt'
		);
	}

	/**
	 * Test that putting a file triggers update hooks
	 */
	public function testPutOverwriteFileTriggersHooks() {
		$view = \OC\Files\Filesystem::getView();
		$view->file_put_contents('/foo.txt', 'some content that will be replaced');

		HookHelper::setUpHooks();

		$this->doPut('/foo.txt');

		$this->assertCount(4, HookHelper::$hookCalls);
		$this->assertHookCall(
			HookHelper::$hookCalls[0],
			Filesystem::signal_update,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[1],
			Filesystem::signal_write,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[2],
			Filesystem::signal_post_update,
			'/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[3],
			Filesystem::signal_post_write,
			'/foo.txt'
		);
	}

	/**
	 * Test that putting a file triggers hooks with the correct path
	 * if the passed view was chrooted (can happen with public webdav
	 * where the root is the share root)
	 */
	public function testPutSingleFileTriggersHooksDifferentRoot() {
		$view = \OC\Files\Filesystem::getView();
		$view->mkdir('noderoot');

		HookHelper::setUpHooks();

		// happens with public webdav where the view root is the share root
		$this->doPut('/foo.txt', '/' . $this->user . '/files/noderoot');

		$this->assertCount(4, HookHelper::$hookCalls);
		$this->assertHookCall(
			HookHelper::$hookCalls[0],
			Filesystem::signal_create,
			'/noderoot/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[1],
			Filesystem::signal_write,
			'/noderoot/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[2],
			Filesystem::signal_post_create,
			'/noderoot/foo.txt'
		);
		$this->assertHookCall(
			HookHelper::$hookCalls[3],
			Filesystem::signal_post_write,
			'/noderoot/foo.txt'
		);
	}

	public static function cancellingHook($params) {
		self::$hookCalls[] = array(
			'signal' => Filesystem::signal_post_create,
			'params' => $params
		);
	}

	/**
	 * Test put file with cancelled hook
	 *
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testPutSingleFileCancelPreHook() {
		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_create,
			'\Test\HookHelper',
			'cancellingCallback'
		);

		$this->doPut('/foo.txt');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 */
	public function testSimplePutFailsOnRename() {
		// setup
		$view = $this->getMock('\OC\Files\View',
			array('rename', 'getRelativePath', 'filesize'));
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
		$view = $this->getMock('\OC\Files\View', array('getRelativePath'));
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
			array('rename', 'getRelativePath', 'filesize'));
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

	/**
	 * Asserts hook call
	 *
	 * @param array $callData hook call data to check
	 * @param string $signal signal name
	 * @param string $hookPath hook path
	 */
	protected function assertHookCall($callData, $signal, $hookPath) {
		$this->assertEquals($signal, $callData['signal']);
		$params = $callData['params'];
		$this->assertEquals(
			$hookPath,
			$params[Filesystem::signal_param_path]
		);
	}

	/**
	 * Test whether locks are set before and after the operation
	 */
	public function testPutLocking() {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$path = 'test-locking.txt';
		$info = new \OC\Files\FileInfo(
			'/' . $this->user . '/files/' . $path,
			null,
			null,
			['permissions' => \OCP\Constants::PERMISSION_ALL],
			null
		);

		$file = new \OC\Connector\Sabre\File($view, $info);

		$this->assertFalse(
			$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED),
			'File unlocked before put'
		);
		$this->assertFalse(
			$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE),
			'File unlocked before put'
		);

		$wasLockedPre = false;
		$wasLockedPost = false;
		$eventHandler = $this->getMockBuilder('\stdclass')
			->setMethods(['writeCallback', 'postWriteCallback'])
			->getMock();

		// both pre and post hooks might need access to the file,
		// so only shared lock is acceptable
		$eventHandler->expects($this->once())
			->method('writeCallback')
			->will($this->returnCallback(
				function() use ($view, $path, &$wasLockedPre){
					$wasLockedPre = $this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED);
					$wasLockedPre = $wasLockedPre && !$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE);
				}
			));
		$eventHandler->expects($this->once())
			->method('postWriteCallback')
			->will($this->returnCallback(
				function() use ($view, $path, &$wasLockedPost){
					$wasLockedPost = $this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED);
					$wasLockedPost = $wasLockedPost && !$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE);
				}
			));

		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_write,
			$eventHandler,
			'writeCallback'
		);
		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_write,
			$eventHandler,
			'postWriteCallback'
		);

		$this->assertNotEmpty($file->put($this->getStream('test data')));

		$this->assertTrue($wasLockedPre, 'File was locked during pre-hooks');
		$this->assertTrue($wasLockedPost, 'File was locked during post-hooks');

		$this->assertFalse(
			$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED),
			'File unlocked after put'
		);
		$this->assertFalse(
			$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE),
			'File unlocked after put'
		);
	}

}
