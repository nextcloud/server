<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Controller;

/**
 * Overwrite is_uploaded_file in the OC\Core\Controller namespace to allow
 * proper unit testing of the postAvatar call.
 */
function is_uploaded_file($filename) {
	return file_exists($filename);
}

namespace Tests\Core\Controller;

use OC\AppFramework\Utility\TimeFactory;
use OC\Avatar\AvatarManager;
use OC\Core\Controller\AvatarController;
use OC\Core\Controller\GuestAvatarController;
use OCP\AppFramework\Http;
use OCP\Files\IUserFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IAvatar;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class AvatarControllerTest
 *
 * @package OC\Core\Controller
 */
class AvatarControllerTest extends \Test\TestCase {
	private AvatarController $avatarController;
	private GuestAvatarController $guestAvatarController;

	private IAvatar&MockObject $avatarMock;
	private IUser&MockObject $userMock;
	private ISimpleFile&MockObject $avatarFile;
	private AvatarManager&MockObject $avatarManager;
	private IL10N&MockObject $l;
	private IUserManager&MockObject $userManager;
	private IUserFolder&MockObject $userFolder;
	private LoggerInterface&MockObject $logger;
	private IRequest&MockObject $request;
	private TimeFactory&MockObject $timeFactory;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->avatarManager = $this->createMock(AvatarManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnArgument(0);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->userFolder = $this->createMock(IUserFolder::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(TimeFactory::class);

		$this->avatarMock = $this->createMock(IAvatar::class);
		$this->userMock = $this->createMock(IUser::class);

		$this->guestAvatarController = new GuestAvatarController(
			'core',
			$this->request,
			$this->avatarManager,
			$this->logger
		);

		$this->avatarController = new AvatarController(
			'core',
			$this->request,
			$this->avatarManager,
			$this->l,
			$this->userManager,
			$this->userFolder,
			$this->logger,
			'userid',
			$this->timeFactory,
			$this->guestAvatarController,
		);

		// Configure userMock
		$this->userMock->method('getDisplayName')->willReturn('displayName');
		$this->userMock->method('getUID')->willReturn('userId');
		$this->userManager->method('get')
			->willReturnMap([['userId', $this->userMock]]);

		$this->avatarFile = $this->createMock(ISimpleFile::class);
		$this->avatarFile->method('getContent')->willReturn('image data');
		$this->avatarFile->method('getMimeType')->willReturn('image type');
		$this->avatarFile->method('getEtag')->willReturn('my etag');
		$this->avatarFile->method('getName')->willReturn('my name');
		$this->avatarFile->method('getMTime')->willReturn(42);
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Fetch an avatar if a user has no avatar
	 */
	public function testGetAvatarNoAvatar(): void {
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$this->avatarMock->method('getFile')->willThrowException(new NotFoundException());
		$response = $this->avatarController->getAvatar('userId', 32);

		//Comment out until JS is fixed
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	/**
	 * Fetch the user's avatar
	 */
	public function testGetAvatar(): void {
		$this->avatarMock->method('getFile')->willReturn($this->avatarFile);
		$this->avatarManager->method('getAvatar')->with('userId')->willReturn($this->avatarMock);
		$this->avatarMock->expects($this->once())
			->method('isCustomAvatar')
			->willReturn(true);

		$response = $this->avatarController->getAvatar('userId', 32);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertArrayHasKey('Content-Type', $response->getHeaders());
		$this->assertEquals('image type', $response->getHeaders()['Content-Type']);
		$this->assertArrayHasKey('X-NC-IsCustomAvatar', $response->getHeaders());
		$this->assertEquals('1', $response->getHeaders()['X-NC-IsCustomAvatar']);

		$this->assertEquals('my etag', $response->getETag());
	}

	/**
	 * Fetch the user's avatar
	 */
	public function testGetGeneratedAvatar(): void {
		$this->avatarMock->method('getFile')->willReturn($this->avatarFile);
		$this->avatarManager->method('getAvatar')->with('userId')->willReturn($this->avatarMock);

		$response = $this->avatarController->getAvatar('userId', 32);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertArrayHasKey('Content-Type', $response->getHeaders());
		$this->assertEquals('image type', $response->getHeaders()['Content-Type']);
		$this->assertArrayHasKey('X-NC-IsCustomAvatar', $response->getHeaders());
		$this->assertEquals('0', $response->getHeaders()['X-NC-IsCustomAvatar']);

		$this->assertEquals('my etag', $response->getETag());
	}

	/**
	 * Fetch the avatar of a non-existing user
	 */
	public function testGetAvatarNoUser(): void {
		$this->avatarManager
			->method('getAvatar')
			->with('userDoesNotExist')
			->willThrowException(new \Exception('user does not exist'));

		$response = $this->avatarController->getAvatar('userDoesNotExist', 32);

		//Comment out until JS is fixed
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public static function dataCacheWindow(): array {
		return [
			'no version, so the client has nothing that tracks changes' => ['', true, 'private, max-age=86400, immutable'],
			// 60 days plus this user's share of the 30 day spread
			'version, and the avatar is the same for every viewer' => ['7', true, 'private, max-age=7461068, immutable'],
			'version, but the avatar depends on the viewer' => ['7', false, 'private, max-age=86400, immutable'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCacheWindow')]
	public function testCacheWindow(string $version, bool $cacheable, string $expected): void {
		$this->avatarMock->method('getFile')->willReturn($this->avatarFile);
		$this->avatarManager->method('getAvatar')->with('userId')->willReturn($this->avatarMock);
		$this->avatarManager->method('canCacheAvatarLongTerm')->with('userId')->willReturn($cacheable);

		$light = $this->avatarController->getAvatar('userId', 64, false, $version);
		$dark = $this->avatarController->getAvatarDark('userId', 64, false, $version);

		$this->assertEquals($expected, $light->getHeaders()['Cache-Control'], 'light avatar');
		$this->assertEquals($expected, $dark->getHeaders()['Cache-Control'], 'dark avatar');
	}

	public function testGetAvatarSize64(): void {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(64))
			->willReturn($this->avatarFile);

		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->never())
			->method('debug');

		$this->avatarController->getAvatar('userId', 64);
	}

	public function testGetAvatarSize512(): void {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(512))
			->willReturn($this->avatarFile);

		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->never())
			->method('debug');

		$this->avatarController->getAvatar('userId', 512);
	}

	/**
	 * Small sizes return 64 and generate a log
	 */
	public function testGetAvatarSizeTooSmall(): void {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(64))
			->willReturn($this->avatarFile);

		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('debug')
			->with('Avatar requested in deprecated size 32');

		$this->avatarController->getAvatar('userId', 32);
	}

	/**
	 * Avatars between 64 and 512 are upgraded to 512
	 */
	public function testGetAvatarSizeBetween(): void {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(512))
			->willReturn($this->avatarFile);

		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('debug')
			->with('Avatar requested in deprecated size 65');

		$this->avatarController->getAvatar('userId', 65);
	}

	/**
	 * We do not support avatars larger than 512
	 */
	public function testGetAvatarSizeTooBig(): void {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(512))
			->willReturn($this->avatarFile);

		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('debug')
			->with('Avatar requested in deprecated size 513');

		$this->avatarController->getAvatar('userId', 513);
	}

	/**
	 * Remove an avatar
	 */
	public function testDeleteAvatar(): void {
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->deleteAvatar();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	/**
	 * Test what happens if the removing of the avatar fails
	 */
	public function testDeleteAvatarException(): void {
		$this->avatarMock->method('remove')->willThrowException(new \Exception('foo'));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception('foo'), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->deleteAvatar());
	}

	/**
	 * When trying to post a new avatar a path or image should be posted.
	 */
	public function testPostAvatarNoPathOrImage(): void {
		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test a correct post of an avatar using POST
	 */
	public function testPostAvatarFile(): void {
		//Create temp file
		$fileName = tempnam('', 'avatarTest');
		$copyRes = copy(\OC::$SERVERROOT . '/tests/data/testimage.jpg', $fileName);
		$this->assertTrue($copyRes);

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [filesize(\OC::$SERVERROOT . '/tests/data/testimage.jpg')]];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		//On correct upload always respond with the notsquare message
		$this->assertEquals('notsquare', $response->getData()['data']);

		//File should be deleted
		$this->assertFalse(file_exists($fileName));
	}

	/**
	 * Test invalid post os an avatar using POST
	 */
	public function testPostAvatarInvalidFile(): void {
		//Create request return
		$reqRet = ['error' => [1], 'tmp_name' => ['foo']];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check what happens when we upload a GIF
	 */
	public function testPostAvatarFileGif(): void {
		//Create temp file
		$fileName = tempnam('', 'avatarTest');
		$copyRes = copy(\OC::$SERVERROOT . '/tests/data/testimage.gif', $fileName);
		$this->assertTrue($copyRes);

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [filesize(\OC::$SERVERROOT . '/tests/data/testimage.gif')]];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals('Unknown filetype', $response->getData()['data']['message']);

		//File should be deleted
		$this->assertFalse(file_exists($fileName));
	}

	/**
	 * Test posting avatar from existing file
	 */
	public function testPostAvatarFromFile(): void {
		//Mock node API call
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->once())
			->method('getContent')
			->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$file->expects($this->once())
			->method('getMimeType')
			->willReturn('image/jpeg');
		$this->userFolder->method('get')->willReturn($file);

		//Create request return
		$response = $this->avatarController->postAvatar('avatar.jpg');

		//On correct upload always respond with the notsquare message
		$this->assertEquals('notsquare', $response->getData()['data']);
	}

	/**
	 * Test posting avatar from existing folder
	 */
	public function testPostAvatarFromNoFile(): void {
		$file = $this->createMock('OCP\Files\Node');
		$this->userFolder
			->method('get')
			->with('folder')
			->willReturn($file);

		//Create request return
		$response = $this->avatarController->postAvatar('folder');

		//On correct upload always respond with the notsquare message
		$this->assertEquals(['data' => ['message' => 'Please select a file.']], $response->getData());
	}

	public function testPostAvatarInvalidType(): void {
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->never())
			->method('getContent');
		$file->expects($this->exactly(2))
			->method('getMimeType')
			->willReturn('text/plain');

		$this->userFolder->method('get')->willReturn($file);

		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'The selected file is not an image.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}

	public function testPostAvatarNotPermittedException(): void {
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->once())
			->method('getContent')
			->willThrowException(new NotPermittedException());
		$file->expects($this->once())
			->method('getMimeType')
			->willReturn('image/jpeg');

		$this->userFolder->method('get')->willReturn($file);

		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'The selected file cannot be read.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}

	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testFileTooBig(): void {
		$fileName = \OC::$SERVERROOT . '/tests/data/testimage.jpg';
		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [21 * 1024 * 1024]];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals('File is too big', $response->getData()['data']['message']);
	}
}
