<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
			$this->timeFactory
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
	public function testGetAvatarNoAvatar() {
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$this->avatarMock->method('getFile')->will($this->throwException(new NotFoundException()));
		$response = $this->avatarController->getAvatar('userId', 32);

		//Comment out until JS is fixed
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	/**
	 * Fetch the user's avatar
	 */
	public function testGetAvatar() {
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
	public function testGetGeneratedAvatar() {
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
	public function testGetAvatarNoUser() {
		$this->avatarManager
			->method('getAvatar')
			->with('userDoesNotExist')
			->will($this->throwException(new \Exception('user does not exist')));

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
	public function testDeleteAvatar() {
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->deleteAvatar();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	/**
	 * Test what happens if the removing of the avatar fails
	 */
	public function testDeleteAvatarException() {
		$this->avatarMock->method('remove')->will($this->throwException(new \Exception("foo")));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception("foo"), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->deleteAvatar());
	}

	/**
	 * Trying to get a tmp avatar when it is not available. 404
	 */
	public function testTmpAvatarNoTmp() {
		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	/**
	 * Fetch tmp avatar
	 */
	public function testTmpAvatarValid() {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}


	/**
	 * When trying to post a new avatar a path or image should be posted.
	 */
	public function testPostAvatarNoPathOrImage() {
		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test a correct post of an avatar using POST
	 */
	public function testPostAvatarFile() {
		//Create temp file
		$fileName = tempnam('', "avatarTest");
		$copyRes = copy(\OC::$SERVERROOT.'/tests/data/testimage.jpg', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [filesize(\OC::$SERVERROOT.'/tests/data/testimage.jpg')]];
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
	public function testPostAvatarInvalidFile() {
		//Create request return
		$reqRet = ['error' => [1], 'tmp_name' => ['foo']];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check what happens when we upload a GIF
	 */
	public function testPostAvatarFileGif() {
		//Create temp file
		$fileName = tempnam('', "avatarTest");
		$copyRes = copy(\OC::$SERVERROOT.'/tests/data/testimage.gif', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.gif'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [filesize(\OC::$SERVERROOT.'/tests/data/testimage.gif')]];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals('Unknown filetype', $response->getData()['data']['message']);

		//File should be deleted
		$this->assertFalse(file_exists($fileName));
	}

	/**
	 * Test posting avatar from existing file
	 */
	public function testPostAvatarFromFile() {
		//Mock node API call
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->once())
			->method('getContent')
			->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));
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
	public function testPostAvatarFromNoFile() {
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

	public function testPostAvatarInvalidType() {
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

	public function testPostAvatarNotPermittedException() {
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
	public function testPostAvatarException() {
		$this->cache->expects($this->once())
			->method('set')
			->will($this->throwException(new \Exception("foo")));
		$file = $this->getMockBuilder('OCP\Files\File')
			->disableOriginalConstructor()->getMock();
		$file->expects($this->once())
			->method('getContent')
			->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$file->expects($this->once())
			->method('getMimeType')
			->willReturn('image/jpeg');
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder->method('getUserFolder')->with('userid')->willReturn($userFolder);
		$userFolder->method('get')->willReturn($file);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception("foo"), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_OK);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}


	/**
	 * Test invalid crop argument
	 */
	public function testPostCroppedAvatarInvalidCrop() {
		$response = $this->avatarController->postCroppedAvatar([]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test no tmp avatar to crop
	 */
	public function testPostCroppedAvatarNoTmpAvatar() {
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Test with non square crop
	 */
	public function testPostCroppedAvatarNoSquareCrop() {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->will($this->throwException(new \OC\NotSquareException));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testPostCroppedAvatarValidCrop() {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('success', $response->getData()['status']);
	}

	/**
	 * Test what happens if the cropping of the avatar fails
	 */
	public function testPostCroppedAvatarException() {
		$this->cache->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->will($this->throwException(new \Exception('foo')));
		$this->avatarManager->method('getAvatar')->willReturn($this->avatarMock);

		$this->logger->expects($this->once())
			->method('error')
			->with('foo', ['exception' => new \Exception("foo"), 'app' => 'core']);
		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]));
	}


	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testFileTooBig() {
		$fileName = \OC::$SERVERROOT.'/tests/data/testimage.jpg';
		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [21 * 1024 * 1024]];
		$this->request->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals('File is too big', $response->getData()['data']['message']);
	}
}
