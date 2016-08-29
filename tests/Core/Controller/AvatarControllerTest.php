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

use OC\Core\Application;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAvatar;
use OCP\IUser;
use Test\Traits\UserTrait;

/**
 * Class AvatarControllerTest
 *
 * @group DB
 *
 * @package OC\Core\Controller
 */
class AvatarControllerTest extends \Test\TestCase {
	use UserTrait;

	/** @var IAppContainer */
	private $container;
	/** @var \OC\Core\Controller\AvatarController */
	private $avatarController;
	/** @var IAvatar */
	private $avatarMock;
	/** @var IUser */
	private $userMock;
	/** @var File */
	private $avatarFile;
	
	protected function setUp() {
		parent::setUp();
		$this->createUser('userid', 'pass');
		$this->loginAsUser('userid');
		
		$app = new Application;
		$this->container = $app->getContainer();
		$this->container['AppName'] = 'core';
		$this->container['AvatarManager'] = $this->getMockBuilder('OCP\IAvatarManager')->getMock();
		$this->container['Cache'] = $this->getMockBuilder('OC\Cache\File')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N'] = $this->getMockBuilder('OCP\IL10N')->getMock();
		$this->container['L10N']->method('t')->will($this->returnArgument(0));
		$this->container['UserManager'] = $this->getMockBuilder('OCP\IUserManager')->getMock();
		$this->container['UserSession'] = $this->getMockBuilder('OCP\IUserSession')->getMock();
		$this->container['Request'] = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->container['UserFolder'] = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->container['Logger'] = $this->getMockBuilder('OCP\ILogger')->getMock();

		$this->avatarMock = $this->getMockBuilder('OCP\IAvatar')->getMock();
		$this->userMock = $this->getMockBuilder('OCP\IUser')->getMock();

		$this->avatarController = $this->container['AvatarController'];

		// Configure userMock
		$this->userMock->method('getDisplayName')->willReturn('displayName');
		$this->userMock->method('getUID')->willReturn('userId');
		$this->container['UserManager']->method('get')
			->willReturnMap([['userId', $this->userMock]]);
		$this->container['UserSession']->method('getUser')->willReturn($this->userMock);

		$this->avatarFile = $this->getMockBuilder('OCP\Files\File')->getMock();
		$this->avatarFile->method('getContent')->willReturn('image data');
		$this->avatarFile->method('getMimeType')->willReturn('image type');
		$this->avatarFile->method('getEtag')->willReturn('my etag');
	}

	public function tearDown() {
		$this->logout();
		parent::tearDown();
	}

	/**
	 * Fetch an avatar if a user has no avatar
	 */
	public function testGetAvatarNoAvatar() {
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$this->avatarMock->method('getFile')->will($this->throwException(new NotFoundException()));
		$response = $this->avatarController->getAvatar('userId', 32);

		//Comment out until JS is fixed
		//$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('displayName', $response->getData()['data']['displayname']);
	}

	/**
	 * Fetch the user's avatar
	 */
	public function testGetAvatar() {
		$this->avatarMock->method('getFile')->willReturn($this->avatarFile);
		$this->container['AvatarManager']->method('getAvatar')->with('userId')->willReturn($this->avatarMock);

		$response = $this->avatarController->getAvatar('userId', 32);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertArrayHasKey('Content-Type', $response->getHeaders());
		$this->assertEquals('image type', $response->getHeaders()['Content-Type']);

		$this->assertEquals('my etag', $response->getEtag());
	}

	/**
	 * Fetch the avatar of a non-existing user
	 */
	public function testGetAvatarNoUser() {
		$this->container['AvatarManager']
			->method('getAvatar')
			->with('userDoesNotExist')
			->will($this->throwException(new \Exception('user does not exist')));

		$response = $this->avatarController->getAvatar('userDoesNotExist', 32);

		//Comment out until JS is fixed
		//$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('', $response->getData()['data']['displayname']);
	}

	/**
	 * Make sure we get the correct size
	 */
	public function testGetAvatarSize() {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(32))
			->willReturn($this->avatarFile);

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar('userId', 32);
	}

	/**
	 * We cannot get avatars that are 0 or negative
	 */
	public function testGetAvatarSizeMin() {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(64))
			->willReturn($this->avatarFile);

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar('userId', 0);
	}

	/**
	 * We do not support avatars larger than 2048*2048
	 */
	public function testGetAvatarSizeMax() {
		$this->avatarMock->expects($this->once())
			->method('getFile')
			->with($this->equalTo(2048))
			->willReturn($this->avatarFile);

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar('userId', 2049);
	}

	/**
	 * Remove an avatar
	 */
	public function testDeleteAvatar() {
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->deleteAvatar();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	/**
	 * Test what happens if the removing of the avatar fails
	 */
	public function testDeleteAvatarException() {
		$this->avatarMock->method('remove')->will($this->throwException(new \Exception("foo")));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->container['Logger']->expects($this->once())
			->method('logException')
			->with(new \Exception("foo"));
		$expectedResponse = new Http\DataResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
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
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

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
		$fileName = tempnam(null, "avatarTest");
		$copyRes = copy(\OC::$SERVERROOT.'/tests/data/testimage.jpg', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [filesize(\OC::$SERVERROOT.'/tests/data/testimage.jpg')]];
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

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
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check what happens when we upload a GIF
	 */
	public function testPostAvatarFileGif() {
		//Create temp file
		$fileName = tempnam(null, "avatarTest");
		$copyRes = copy(\OC::$SERVERROOT.'/tests/data/testimage.gif', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.gif'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => filesize(\OC::$SERVERROOT.'/tests/data/testimage.gif')];
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

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
		$this->container['UserFolder']->method('get')->willReturn($file);

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
		$this->container['UserFolder']
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
		$this->container['UserFolder']->method('get')->willReturn($file);

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
		$this->container['UserFolder']->method('get')->willReturn($file);

		$expectedResponse = new Http\JSONResponse(['data' => ['message' => 'The selected file cannot be read.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postAvatar('avatar.jpg'));
	}

	/**
	 * Test what happens if the upload of the avatar fails
	 */
	public function testPostAvatarException() {
		$this->container['Cache']->expects($this->once())
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
		$this->container['UserFolder']->method('get')->willReturn($file);

		$this->container['Logger']->expects($this->once())
			->method('logException')
			->with(new \Exception("foo"));
		$expectedResponse = new Http\DataResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_OK);
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
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->will($this->throwException(new \OC\NotSquareException));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]);

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testPostCroppedAvatarValidCrop() {
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('success', $response->getData()['status']);
	}

	/**
	 * Test what happens if the cropping of the avatar fails
	 */
	public function testPostCroppedAvatarException() {
		$this->container['Cache']->method('get')->willReturn(file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->will($this->throwException(new \Exception('foo')));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->container['Logger']->expects($this->once())
			->method('logException')
			->with(new \Exception('foo'));
		$expectedResponse = new Http\DataResponse(['data' => ['message' => 'An error occurred. Please contact your admin.']], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expectedResponse, $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]));
	}


	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testFileTooBig() {
		$fileName = \OC::$SERVERROOT.'/tests/data/testimage.jpg';
		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName], 'size' => [21*1024*1024]];
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals('File is too big', $response->getData()['data']['message']);
	}

}
