<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\AppFramework\Http\Request;
use OC\Files\Filesystem;
use OC\Files\Storage\Local;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\File;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IRequestId;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\HookHelper;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class File
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class FileTest extends TestCase {
	use MountProviderTrait;
	use UserTrait;

	/**
	 * @var string
	 */
	private $user;

	/** @var IConfig|MockObject */
	protected $config;

	/** @var IRequestId|MockObject */
	protected $requestId;

	protected function setUp(): void {
		parent::setUp();

		\OC_Hook::clear();

		$this->user = 'test_user';
		$this->createUser($this->user, 'pass');

		$this->loginAsUser($this->user);

		$this->config = $this->createMock(IConfig::class);
		$this->requestId = $this->createMock(IRequestId::class);
	}

	protected function tearDown(): void {
		$userManager = \OCP\Server::get(IUserManager::class);
		$userManager->get($this->user)->delete();

		parent::tearDown();
	}

	private function getMockStorage(): MockObject&IStorage {
		$storage = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()
			->getMock();
		$storage->method('getId')
			->willReturn('home::someuser');
		return $storage;
	}

	private function getStream(string $string) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $string);
		fseek($stream, 0);
		return $stream;
	}


	public function fopenFailuresProvider() {
		return [
			[
				// return false
				null,
				'\Sabre\Dav\Exception',
				false
			],
			[
				new \OCP\Files\NotPermittedException(),
				'Sabre\DAV\Exception\Forbidden'
			],
			[
				new \OCP\Files\EntityTooLargeException(),
				'OCA\DAV\Connector\Sabre\Exception\EntityTooLarge'
			],
			[
				new \OCP\Files\InvalidContentException(),
				'OCA\DAV\Connector\Sabre\Exception\UnsupportedMediaType'
			],
			[
				new \OCP\Files\InvalidPathException(),
				'Sabre\DAV\Exception\Forbidden'
			],
			[
				new \OCP\Files\ForbiddenException('', true),
				'OCA\DAV\Connector\Sabre\Exception\Forbidden'
			],
			[
				new \OCP\Files\LockNotAcquiredException('/test.txt', 1),
				'OCA\DAV\Connector\Sabre\Exception\FileLocked'
			],
			[
				new \OCP\Lock\LockedException('/test.txt'),
				'OCA\DAV\Connector\Sabre\Exception\FileLocked'
			],
			[
				new \OCP\Encryption\Exceptions\GenericEncryptionException(),
				'Sabre\DAV\Exception\ServiceUnavailable'
			],
			[
				new \OCP\Files\StorageNotAvailableException(),
				'Sabre\DAV\Exception\ServiceUnavailable'
			],
			[
				new \Sabre\DAV\Exception('Generic sabre exception'),
				'Sabre\DAV\Exception',
				false
			],
			[
				new \Exception('Generic exception'),
				'Sabre\DAV\Exception'
			],
		];
	}

	/**
	 * @dataProvider fopenFailuresProvider
	 */
	public function testSimplePutFails($thrownException, $expectedException, $checkPreviousClass = true): void {
		// setup
		$storage = $this->getMockBuilder(Local::class)
			->onlyMethods(['writeStream'])
			->setConstructorArgs([['datadir' => \OCP\Server::get(ITempManager::class)->getTemporaryFolder()]])
			->getMock();
		\OC\Files\Filesystem::mount($storage, [], $this->user . '/');
		/** @var View | MockObject $view */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['getRelativePath', 'resolvePath'])
			->getMock();
		$view->expects($this->atLeastOnce())
			->method('resolvePath')
			->willReturnCallback(
				function ($path) use ($storage) {
					return [$storage, $path];
				}
			);

		if ($thrownException !== null) {
			$storage->expects($this->once())
				->method('writeStream')
				->will($this->throwException($thrownException));
		} else {
			$storage->expects($this->once())
				->method('writeStream')
				->willReturn(0);
		}

		$view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$caughtException = null;
		try {
			$file->put('test data');
		} catch (\Exception $e) {
			$caughtException = $e;
		}

		$this->assertInstanceOf($expectedException, $caughtException);
		if ($checkPreviousClass) {
			$this->assertInstanceOf(get_class($thrownException), $caughtException->getPrevious());
		}

		$this->assertEmpty($this->listPartFiles($view, ''), 'No stray part files');
	}

	/**
	 * Simulate putting a file to the given path.
	 *
	 * @param string $path path to put the file into
	 * @param string $viewRoot root to use for the view
	 * @param null|Request $request the HTTP request
	 *
	 * @return null|string of the PUT operation which is usually the etag
	 */
	private function doPut($path, $viewRoot = null, ?Request $request = null) {
		$view = \OC\Files\Filesystem::getView();
		if (!is_null($viewRoot)) {
			$view = new \OC\Files\View($viewRoot);
		} else {
			$viewRoot = '/' . $this->user . '/files';
		}

		$info = new \OC\Files\FileInfo(
			$viewRoot . '/' . ltrim($path, '/'),
			$this->getMockStorage(),
			null,
			[
				'permissions' => \OCP\Constants::PERMISSION_ALL,
				'type' => FileInfo::TYPE_FOLDER,
			],
			null
		);

		/** @var \OCA\DAV\Connector\Sabre\File | MockObject $file */
		$file = $this->getMockBuilder(\OCA\DAV\Connector\Sabre\File::class)
			->setConstructorArgs([$view, $info, null, $request])
			->onlyMethods(['header'])
			->getMock();

		// beforeMethod locks
		$view->lockFile($path, ILockingProvider::LOCK_SHARED);

		$result = $file->put($this->getStream('test data'));

		// afterMethod unlocks
		$view->unlockFile($path, ILockingProvider::LOCK_SHARED);

		return $result;
	}

	/**
	 * Test putting a single file
	 */
	public function testPutSingleFile(): void {
		$this->assertNotEmpty($this->doPut('/foo.txt'));
	}

	public function legalMtimeProvider() {
		return [
			'string' => [
				'HTTP_X_OC_MTIME' => 'string',
				'expected result' => null
			],
			'castable string (int)' => [
				'HTTP_X_OC_MTIME' => '987654321',
				'expected result' => 987654321
			],
			'castable string (float)' => [
				'HTTP_X_OC_MTIME' => '123456789.56',
				'expected result' => 123456789
			],
			'float' => [
				'HTTP_X_OC_MTIME' => 123456789.56,
				'expected result' => 123456789
			],
			'zero' => [
				'HTTP_X_OC_MTIME' => 0,
				'expected result' => null
			],
			'zero string' => [
				'HTTP_X_OC_MTIME' => '0',
				'expected result' => null
			],
			'negative zero string' => [
				'HTTP_X_OC_MTIME' => '-0',
				'expected result' => null
			],
			'string starting with number following by char' => [
				'HTTP_X_OC_MTIME' => '2345asdf',
				'expected result' => null
			],
			'string castable hex int' => [
				'HTTP_X_OC_MTIME' => '0x45adf',
				'expected result' => null
			],
			'string that looks like invalid hex int' => [
				'HTTP_X_OC_MTIME' => '0x123g',
				'expected result' => null
			],
			'negative int' => [
				'HTTP_X_OC_MTIME' => -34,
				'expected result' => null
			],
			'negative float' => [
				'HTTP_X_OC_MTIME' => -34.43,
				'expected result' => null
			],
		];
	}

	/**
	 * Test putting a file with string Mtime
	 * @dataProvider legalMtimeProvider
	 */
	public function testPutSingleFileLegalMtime($requestMtime, $resultMtime): void {
		$request = new Request([
			'server' => [
				'HTTP_X_OC_MTIME' => (string)$requestMtime,
			]
		], $this->requestId, $this->config, null);
		$file = 'foo.txt';

		if ($resultMtime === null) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$this->doPut($file, null, $request);

		if ($resultMtime !== null) {
			$this->assertEquals($resultMtime, $this->getFileInfos($file)['mtime']);
		}
	}

	/**
	 * Test that putting a file triggers create hooks
	 */
	public function testPutSingleFileTriggersHooks(): void {
		HookHelper::setUpHooks();

		$this->assertNotEmpty($this->doPut('/foo.txt'));

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
	public function testPutOverwriteFileTriggersHooks(): void {
		$view = \OC\Files\Filesystem::getView();
		$view->file_put_contents('/foo.txt', 'some content that will be replaced');

		HookHelper::setUpHooks();

		$this->assertNotEmpty($this->doPut('/foo.txt'));

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
	public function testPutSingleFileTriggersHooksDifferentRoot(): void {
		$view = \OC\Files\Filesystem::getView();
		$view->mkdir('noderoot');

		HookHelper::setUpHooks();

		// happens with public webdav where the view root is the share root
		$this->assertNotEmpty($this->doPut('/foo.txt', '/' . $this->user . '/files/noderoot'));

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

	public static function cancellingHook($params): void {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_post_create,
			'params' => $params
		];
	}

	/**
	 * Test put file with cancelled hook
	 */
	public function testPutSingleFileCancelPreHook(): void {
		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_create,
			'\Test\HookHelper',
			'cancellingCallback'
		);

		// action
		$thrown = false;
		try {
			$this->doPut('/foo.txt');
		} catch (\Sabre\DAV\Exception $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertEmpty($this->listPartFiles(), 'No stray part files');
	}

	/**
	 * Test exception when the uploaded size did not match
	 */
	public function testSimplePutFailsSizeCheck(): void {
		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['rename', 'getRelativePath', 'filesize'])
			->getMock();
		$view->expects($this->any())
			->method('rename')
			->withAnyParameters()
			->willReturn(false);
		$view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);

		$view->expects($this->any())
			->method('filesize')
			->willReturn(123456);

		$request = new Request([
			'server' => [
				'CONTENT_LENGTH' => '123456',
			],
			'method' => 'PUT',
		], $this->requestId, $this->config, null);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info, null, $request);

		// action
		$thrown = false;
		try {
			// beforeMethod locks
			$file->acquireLock(ILockingProvider::LOCK_SHARED);

			$file->put($this->getStream('test data'));

			// afterMethod unlocks
			$file->releaseLock(ILockingProvider::LOCK_SHARED);
		} catch (\Sabre\DAV\Exception\BadRequest $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertEmpty($this->listPartFiles($view, ''), 'No stray part files');
	}

	/**
	 * Test exception during final rename in simple upload mode
	 */
	public function testSimplePutFailsMoveFromStorage(): void {
		$view = new \OC\Files\View('/' . $this->user . '/files');

		// simulate situation where the target file is locked
		$view->lockFile('/test.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$info = new \OC\Files\FileInfo('/' . $this->user . '/files/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$thrown = false;
		try {
			// beforeMethod locks
			$view->lockFile($info->getPath(), ILockingProvider::LOCK_SHARED);

			$file->put($this->getStream('test data'));

			// afterMethod unlocks
			$view->unlockFile($info->getPath(), ILockingProvider::LOCK_SHARED);
		} catch (\OCA\DAV\Connector\Sabre\Exception\FileLocked $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertEmpty($this->listPartFiles($view, ''), 'No stray part files');
	}

	/**
	 * Test put file with invalid chars
	 */
	public function testSimplePutInvalidChars(): void {
		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['getRelativePath'])
			->getMock();
		$view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);

		$info = new \OC\Files\FileInfo("/i\nvalid", $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);
		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$thrown = false;
		try {
			// beforeMethod locks
			$view->lockFile($info->getPath(), ILockingProvider::LOCK_SHARED);

			$file->put($this->getStream('test data'));

			// afterMethod unlocks
			$view->unlockFile($info->getPath(), ILockingProvider::LOCK_SHARED);
		} catch (\OCA\DAV\Connector\Sabre\Exception\InvalidPath $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertEmpty($this->listPartFiles($view, ''), 'No stray part files');
	}

	/**
	 * Test setting name with setName() with invalid chars
	 *
	 */
	public function testSetNameInvalidChars(): void {
		$this->expectException(\OCA\DAV\Connector\Sabre\Exception\InvalidPath::class);

		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['getRelativePath'])
			->getMock();

		$view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);

		$info = new \OC\Files\FileInfo('/valid', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);
		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		$file->setName("/i\nvalid");
	}


	public function testUploadAbort(): void {
		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['rename', 'getRelativePath', 'filesize'])
			->getMock();
		$view->expects($this->any())
			->method('rename')
			->withAnyParameters()
			->willReturn(false);
		$view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);
		$view->expects($this->any())
			->method('filesize')
			->willReturn(123456);

		$request = new Request([
			'server' => [
				'CONTENT_LENGTH' => '123456',
			],
			'method' => 'PUT',
		], $this->requestId, $this->config, null);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info, null, $request);

		// action
		$thrown = false;
		try {
			// beforeMethod locks
			$view->lockFile($info->getPath(), ILockingProvider::LOCK_SHARED);

			$file->put($this->getStream('test data'));

			// afterMethod unlocks
			$view->unlockFile($info->getPath(), ILockingProvider::LOCK_SHARED);
		} catch (\Sabre\DAV\Exception\BadRequest $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertEmpty($this->listPartFiles($view, ''), 'No stray part files');
	}


	public function testDeleteWhenAllowed(): void {
		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->getMock();

		$view->expects($this->once())
			->method('unlink')
			->willReturn(true);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}


	public function testDeleteThrowsWhenDeletionNotAllowed(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->getMock();

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => 0,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}


	public function testDeleteThrowsWhenDeletionFailed(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->getMock();

		// but fails
		$view->expects($this->once())
			->method('unlink')
			->willReturn(false);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// action
		$file->delete();
	}


	public function testDeleteThrowsWhenDeletionThrows(): void {
		$this->expectException(\OCA\DAV\Connector\Sabre\Exception\Forbidden::class);

		// setup
		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->getMock();

		// but fails
		$view->expects($this->once())
			->method('unlink')
			->willThrowException(new ForbiddenException('', true));

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

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
	public function testPutLocking(): void {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$path = 'test-locking.txt';
		$info = new \OC\Files\FileInfo(
			'/' . $this->user . '/files/' . $path,
			$this->getMockStorage(),
			null,
			[
				'permissions' => \OCP\Constants::PERMISSION_ALL,
				'type' => FileInfo::TYPE_FOLDER,
			],
			null
		);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

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
		$eventHandler = $this->getMockBuilder(\stdclass::class)
			->addMethods(['writeCallback', 'postWriteCallback'])
			->getMock();

		// both pre and post hooks might need access to the file,
		// so only shared lock is acceptable
		$eventHandler->expects($this->once())
			->method('writeCallback')
			->willReturnCallback(
				function () use ($view, $path, &$wasLockedPre): void {
					$wasLockedPre = $this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED);
					$wasLockedPre = $wasLockedPre && !$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE);
				}
			);
		$eventHandler->expects($this->once())
			->method('postWriteCallback')
			->willReturnCallback(
				function () use ($view, $path, &$wasLockedPost): void {
					$wasLockedPost = $this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_SHARED);
					$wasLockedPost = $wasLockedPost && !$this->isFileLocked($view, $path, \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE);
				}
			);

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

		// beforeMethod locks
		$view->lockFile($path, ILockingProvider::LOCK_SHARED);

		$this->assertNotEmpty($file->put($this->getStream('test data')));

		// afterMethod unlocks
		$view->unlockFile($path, ILockingProvider::LOCK_SHARED);

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

	/**
	 * Returns part files in the given path
	 *
	 * @param \OC\Files\View view which root is the current user's "files" folder
	 * @param string $path path for which to list part files
	 *
	 * @return array list of part files
	 */
	private function listPartFiles(?\OC\Files\View $userView = null, $path = '') {
		if ($userView === null) {
			$userView = \OC\Files\Filesystem::getView();
		}
		$files = [];
		[$storage, $internalPath] = $userView->resolvePath($path);
		if ($storage instanceof Local) {
			$realPath = $storage->getSourcePath($internalPath);
			$dh = opendir($realPath);
			while (($file = readdir($dh)) !== false) {
				if (str_ends_with($file, '.part')) {
					$files[] = $file;
				}
			}
			closedir($dh);
		}
		return $files;
	}

	/**
	 * returns an array of file information filesize, mtime, filetype,  mimetype
	 *
	 * @param string $path
	 * @param View $userView
	 * @return array
	 */
	private function getFileInfos($path = '', ?View $userView = null) {
		if ($userView === null) {
			$userView = Filesystem::getView();
		}
		return [
			'filesize' => $userView->filesize($path),
			'mtime' => $userView->filemtime($path),
			'filetype' => $userView->filetype($path),
			'mimetype' => $userView->getMimeType($path)
		];
	}


	public function testGetFopenFails(): void {
		$this->expectException(\Sabre\DAV\Exception\ServiceUnavailable::class);

		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['fopen'])
			->getMock();
		$view->expects($this->atLeastOnce())
			->method('fopen')
			->willReturn(false);

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FILE,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		$file->get();
	}


	public function testGetFopenThrows(): void {
		$this->expectException(\OCA\DAV\Connector\Sabre\Exception\Forbidden::class);

		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['fopen'])
			->getMock();
		$view->expects($this->atLeastOnce())
			->method('fopen')
			->willThrowException(new ForbiddenException('', true));

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_ALL,
			'type' => FileInfo::TYPE_FILE,
		], null);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		$file->get();
	}


	public function testGetThrowsIfNoPermission(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		/** @var View|MockObject */
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['fopen'])
			->getMock();
		$view->expects($this->never())
			->method('fopen');

		$info = new \OC\Files\FileInfo('/test.txt', $this->getMockStorage(), null, [
			'permissions' => \OCP\Constants::PERMISSION_CREATE, // no read perm
			'type' => FileInfo::TYPE_FOLDER,
		], null);

		$file = new  \OCA\DAV\Connector\Sabre\File($view, $info);

		$file->get();
	}

	public function testSimplePutNoCreatePermissions(): void {
		$this->logout();

		$storage = new Temporary([]);
		$storage->file_put_contents('file.txt', 'old content');
		$noCreateStorage = new PermissionsMask([
			'storage' => $storage,
			'mask' => Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE
		]);

		$this->registerMount($this->user, $noCreateStorage, '/' . $this->user . '/files/root');

		$this->loginAsUser($this->user);

		$view = new View('/' . $this->user . '/files');

		$info = $view->getFileInfo('root/file.txt');

		$file = new File($view, $info);

		// beforeMethod locks
		$view->lockFile('root/file.txt', ILockingProvider::LOCK_SHARED);

		$file->put($this->getStream('new content'));

		// afterMethod unlocks
		$view->unlockFile('root/file.txt', ILockingProvider::LOCK_SHARED);

		$this->assertEquals('new content', $view->file_get_contents('root/file.txt'));
	}

	public function testPutLockExpired(): void {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$path = 'test-locking.txt';
		$info = new \OC\Files\FileInfo(
			'/' . $this->user . '/files/' . $path,
			$this->getMockStorage(),
			null,
			[
				'permissions' => \OCP\Constants::PERMISSION_ALL,
				'type' => FileInfo::TYPE_FOLDER,
			],
			null
		);

		$file = new \OCA\DAV\Connector\Sabre\File($view, $info);

		// don't lock before the PUT to simulate an expired shared lock
		$this->assertNotEmpty($file->put($this->getStream('test data')));

		// afterMethod unlocks
		$view->unlockFile($path, ILockingProvider::LOCK_SHARED);
	}
}
