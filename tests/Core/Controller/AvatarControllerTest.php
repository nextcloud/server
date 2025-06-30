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
use OC\Core\Controller\AvatarController;
use OC\Core\Controller\GuestAvatarController;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\ICache;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * Class AvatarControllerTest
 *
 * @package OC\Core\Controller
 */
class AvatarControllerTest extends \Test\TestCase {
	/** @var AvatarController */
	private $avatarController;
	/** @var GuestAvatarController */
	private $guestAvatarController;

	/** @var IAvatar|\PHPUnit\Framework\MockObject\MockObject */
	private $avatarMock;
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $userMock;
	/** @var ISimpleFile|\PHPUnit\Framework\MockObject\MockObject */
	private $avatarFile;
	/** @var IAvatarManager|\PHPUnit\Framework\MockObject\MockObject */
	private $avatarManager;
	/** @var ICache|\PHPUnit\Framework\MockObject\MockObject */
	private $cache;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var TimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->avatarManager = $this->getMockBuilder('OCP\IAvatarManager')->getMock();
		$this->cache = $this->getMockBuilder('OCP\ICache')
			->disableOriginalConstructor()->getMock();
		$this->l = $this->getMockBuilder(IL10N::class)->getMock();
		$this->l->method('t')->willReturnArgument(0);
		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->timeFactory = $this->getMockBuilder('OC\AppFramework\Utility\TimeFactory')->getMock();

		$this->avatarMock = $this->getMockBuilder('OCP\IAvatar')->getMock();
		$this->userMock = $this->getMockBuilder(IUser::class)->getMock();

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
			$this->cache,
			$this->l,
			$this->userManager,
			$this->rootFolder,
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

		$this->avatarFile = $this->getMockBuilder(ISimpleFile::class)->getMock();
		$this->avatarFile->method('getContent')->willReturn('image data');
		$this->avatarFile->method('getMimeType')->willReturn('image type');
		$this->avatarFile->method('getEtag')->willReturn('my etag');
		$this->avatarFile->method('getName')->willReturn('my name');
		$this->avatarFile->method('getMTime')->willReturn(42);
	}

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
	 * Trying to get a tmp avatar when it is not available. 404
	 */
	public function testTmpAvatarNoTmp(): void {
		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	/**
	 * Fetch tmp avatar
	 */
	public function testTmpAvatarValid(): void {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));

		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
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

		//Create file in cache
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));

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

		//Create file in cache
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.gif'));

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
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder->method('get')->willReturn($file);

		//Create request return
		$response = $this->avatarController->postAvatar('avatar.jpg');

		//On correct upload always respond with the notsquare message
		$this->assertEquals('notsquare', $response->getData()['data']);
	}

	/**
	 * Test posting avatar from existing folder
	 */
	public function testPostAvatarFromNoFile(): void {
		$file = $this->getMockBuilder('OCP\Files\Node')->getMock();
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder
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
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder->method('get')->willReturn($file);

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
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder->method('get')->willReturn($file);

		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'The selected file cannot be read.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}

	/**
	 * Test what happens if the upload of the avatar fails
	 */
	public function testPostAvatarException(): void {
		$this->cache->expects($this->once())
			->method('set')
			->willThrowException(new \Exception('foo'));
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->once())
			->method('getContent')
			->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$file->expects($this->once())
			->method('getMimeType')
			->willReturn('image/jpeg');
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder->method('get')->willReturn($file);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception('foo'), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}


	/**
	 * Test invalid crop argument
	 */
	public function testPostCroppedAvatarInvalidCrop(): void {
		$response = $this->avatarController->postCroppedAvatar([]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test no tmp avatar to crop
	 */
	public function testPostCroppedAvatarNoTmpAvatar(): void {
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test with non square crop
	 */
	public function testPostCroppedAvatarNoSquareCrop(): void {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->willThrowException(new \OC\NotSquareException);
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testPostCroppedAvatarValidCrop(): void {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('success', $response->getData()['status']);
	}

	/**
	 * Test what happens if the cropping of the avatar fails
	 */
	public function testPostCroppedAvatarException(): void {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->willThrowException(new \Exception('foo'));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception('foo'), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]));
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
