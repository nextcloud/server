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
namespace OC\Core\Avatar;

use OC;
use OC\Core\Application;
use OCP\AppFramework\IAppContainer;
use OCP\Security\ISecureRandom;
use OC\Files\Filesystem;
use OCP\AppFramework\Http;
use OCP\Image;

/**
 * Overwrite is_uploaded_file in this namespace to allow proper unit testing of 
 * the postAvatar call.
 */
function is_uploaded_file($filename) {
	return file_exists($filename);
}

/**
 * Class AvatarControllerTest
 *
 * @package OC\Core\Avatar
 */
class AvatarControllerTest extends \Test\TestCase {

	/** @var IAppContainer */
	private $container;
	/** @var string */
	private $user;
	/** @var string */
	private $oldUser; 
	/** @var AvatarController */
	private $avatarController;
	
	private $avatarMock;

	private $userMock;

	protected function setUp() {
		$app = new Application;
		$this->container = $app->getContainer();
		$this->container['AppName'] = 'core';
		$this->container['AvatarManager'] = $this->getMockBuilder('OCP\IAvatarManager')
			->disableOriginalConstructor()->getMock();
		$this->container['Cache'] = $this->getMockBuilder('OCP\ICache')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N'] = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N']->method('t')->will($this->returnArgument(0));
		$this->container['UserManager'] = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession'] = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()->getMock();
		$this->container['Request'] = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()->getMock();

	
		$this->avatarMock = $this->getMockBuilder('OCP\IAvatar')
			->disableOriginalConstructor()->getMock();
		$this->userMock = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()->getMock();

		$this->avatarController = $this->container['AvatarController'];

		// Store current User	
		$this->oldUser = \OC_User::getUser();

		// Create a dummy user
		$this->user = \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(12, ISecureRandom::CHAR_LOWER);

		OC::$server->getUserManager()->createUser($this->user, $this->user);
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();
		\OC_User::setUserId($this->user);
		\OC_Util::setupFS($this->user);

		// Create Cache dir
		$view = new \OC\Files\View('/'.$this->user);
		$view->mkdir('cache');

		// Configure userMock
		$this->userMock->method('getDisplayName')->willReturn($this->user);
		$this->userMock->method('getUID')->willReturn($this->user);
		$this->container['UserManager']->method('get')->willReturn($this->userMock);
		$this->container['UserSession']->method('getUser')->willReturn($this->userMock);

	}

	public function tearDown() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();
		OC::$server->getUserManager()->get($this->user)->delete();
		\OC_User::setIncognitoMode(false);
		
		\OC::$server->getSession()->set('public_link_authenticated', '');

		// Set old user
		\OC_User::setUserId($this->oldUser);
		\OC_Util::setupFS($this->oldUser);
	}

	/**
	 * Fetch an avatar if a user has no avatar
	 */
	public function testGetAvatarNoAvatar() {
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->getAvatar($this->user, 32);

		//Comment out unitl JS is fixed
		//$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
		$this->assertEquals($response->getData()['data']['displayname'], $this->user);
	}

	/**
	 * Fetch the users avatar
	 */
	public function testGetAvatar() {
		$image = new Image(OC::$SERVERROOT.'/tests/data/testimage.jpg');
		$this->avatarMock->method('get')->willReturn($image);
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->getAvatar($this->user, 32);

		$this->assertEquals($response->getStatus(), Http::STATUS_OK);

		$image2 = new Image($response->getData());
		$this->assertEquals($image->mimeType(), $image2->mimeType());
		$this->assertEquals(crc32($response->getData()), $response->getEtag());
	}

	/**
	 * Make sure we get the correct size
	 */
	public function testGetAvatarSize() {
		$this->avatarMock->expects($this->once())
						 ->method('get')
						 ->with($this->equalTo(32));

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar($this->user, 32);
	}

	/**
	 * We cannot get avatars that are 0 or negative
	 */
	public function testGetAvatarSizeMin() {
		$this->avatarMock->expects($this->once())
						 ->method('get')
						 ->with($this->equalTo(64));

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar($this->user, 0);
	}

	/**
	 * We do not support avatars larger than 2048*2048
	 */
	public function testGetAvatarSizeMax() {
		$this->avatarMock->expects($this->once())
						 ->method('get')
						 ->with($this->equalTo(2048));

		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$this->avatarController->getAvatar($this->user, 2049);
	}

	/**
	 * Remove an avatar
	 */
	public function testDeleteAvatar() {
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->deleteAvatar();
		$this->assertEquals($response->getStatus(), Http::STATUS_OK);
	}

	/**
	 * Test what happens if the removing of the avatar fails
	 */
	public function testDeleteAvatarException() {
		$this->avatarMock->method('remove')->will($this->throwException(new \Exception("foo")));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);

		$response = $this->avatarController->deleteAvatar();
		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Trying to get a tmp avatar when it is not available. 404
	 */
	public function testTmpAvatarNoTmp() {
		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals($response->getStatus(), Http::STATUS_NOT_FOUND);
	}

	/**
	 * Fetch tmp avatar
	 */
	public function testTmpAvatarValid() {
		$this->container['Cache']->method('get')->willReturn(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$response = $this->avatarController->getTmpAvatar();
		$this->assertEquals($response->getStatus(), Http::STATUS_OK);
	}


	/**
	 * When trying to post a new avatar a path or image should be posted.
	 */
	public function testPostAvatarNoPathOrImage() {
		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Test a correct post of an avatar using POST
	 */
	public function testPostAvatarFile() {
		//Create temp file
		$fileName = tempnam(null, "avatarTest");
		$copyRes = copy(OC::$SERVERROOT.'/tests/data/testimage.jpg', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$view = new \OC\Files\View('/'.$this->user.'/cache');
		$view->file_put_contents('avatar_upload', file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName]];
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		//On correct upload always respond with the notsquare message
		$this->assertEquals($response->getData()['data'], 'notsquare');

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

		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Check what happens when we upload a GIF
	 */
	public function testPostAvatarFileGif() {
		//Create temp file
		$fileName = tempnam(null, "avatarTest");
		$copyRes = copy(OC::$SERVERROOT.'/tests/data/testimage.gif', $fileName);
		$this->assertTrue($copyRes);

		//Create file in cache
		$view = new \OC\Files\View('/'.$this->user.'/cache');
		$view->file_put_contents('avatar_upload', file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif'));

		//Create request return
		$reqRet = ['error' => [0], 'tmp_name' => [$fileName]];
		$this->container['Request']->method('getUploadedFile')->willReturn($reqRet);

		$response = $this->avatarController->postAvatar(null);

		$this->assertEquals($response->getData()['data']['message'], 'Unknown filetype');

		//File should be deleted
		$this->assertFalse(file_exists($fileName));
	}

	/**
	 * Test posting avatar from existing file
	 */
	public function testPostAvatarFromFile() {
		//Create file in cache
		$view = new \OC\Files\View('/'.$this->user.'/files');
		$view->file_put_contents('avatar.jpg', file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		//Create request return
		$response = $this->avatarController->postAvatar('avatar.jpg');

		//On correct upload always respond with the notsquare message
		$this->assertEquals($response->getData()['data'], 'notsquare');
	}

	/**
	 * Test invalid crop argment
	 */
	public function testPostCroppedAvatarInvalidCrop() {
		$response = $this->avatarController->postCroppedAvatar([]);

		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Test no tmp avatar to crop
	 */
	public function testPostCroppedAvatarNoTmpAvatar() {
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Test with non square crop
	 */
	public function testPostCroppedAvatarNoSquareCrop() {
		$this->container['Cache']->method('get')->willReturn(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));

		$this->avatarMock->method('set')->will($this->throwException(new \OC\NotSquareException));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 11]);

		$this->assertEquals($response->getStatus(), Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Check for proper reply on proper crop argument
	 */
	public function testPostCroppedAvatarValidCrop() {
		$this->container['Cache']->method('get')->willReturn(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->container['AvatarManager']->method('getAvatar')->willReturn($this->avatarMock);
		$response = $this->avatarController->postCroppedAvatar(['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]);

		$this->assertEquals($response->getStatus(), Http::STATUS_OK);
		$this->assertEquals($response->getData()['status'], 'success');
	}

}
