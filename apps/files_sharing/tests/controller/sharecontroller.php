<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Cloutier <vincent1cloutier@gmail.com>
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

namespace OCA\Files_Sharing\Controllers;

use OC\Files\Filesystem;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Security\ISecureRandom;
use OC\Files\View;
use OCP\Share;
use OC\URLGenerator;

/**
 * @package OCA\Files_Sharing\Controllers
 */
class ShareControllerTest extends \Test\TestCase {

	/** @var IAppContainer */
	private $container;
	/** @var string */
	private $user;
	/** @var string */
	private $token;
	/** @var string */
	private $oldUser;
	/** @var ShareController */
	private $shareController;
	/** @var URLGenerator */
	private $urlGenerator;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['AppName'] = 'files_sharing';
		$this->container['UserSession'] = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()->getMock();
		$this->container['URLGenerator'] = $this->getMockBuilder('\OC\URLGenerator')
			->disableOriginalConstructor()->getMock();
		$this->container['UserManager'] = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->container['URLGenerator'];
		$this->shareController = $this->container['ShareController'];

		// Store current user
		$this->oldUser = \OC_User::getUser();

		// Create a dummy user
		$this->user = \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(12, ISecureRandom::CHAR_LOWER);

		\OC_User::createUser($this->user, $this->user);
		\OC_Util::tearDownFS();
		$this->loginAsUser($this->user);

		// Create a dummy shared file
		$view = new View('/'. $this->user . '/files');
		$view->file_put_contents('file1.txt', 'I am such an awesome shared file!');
		$this->token = \OCP\Share::shareItem(
			Filesystem::getFileInfo('file1.txt')->getType(),
			Filesystem::getFileInfo('file1.txt')->getId(),
			\OCP\Share::SHARE_TYPE_LINK,
			'IAmPasswordProtected!',
			1
		);
	}

	protected function tearDown() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();
		\OC_User::deleteUser($this->user);
		\OC_User::setIncognitoMode(false);

		\OC::$server->getSession()->set('public_link_authenticated', '');

		// Set old user
		\OC_User::setUserId($this->oldUser);
		\OC_Util::setupFS($this->oldUser);
	}

	public function testShowAuthenticate() {
		$linkItem = \OCP\Share::getShareByToken($this->token, false);

		// Test without being authenticated
		$response = $this->shareController->showAuthenticate($this->token);
		$expectedResponse =  new TemplateResponse($this->container['AppName'], 'authenticate', array(), 'guest');
		$this->assertEquals($expectedResponse, $response);

		// Test with being authenticated for another file
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']-1);
		$response = $this->shareController->showAuthenticate($this->token);
		$expectedResponse =  new TemplateResponse($this->container['AppName'], 'authenticate', array(), 'guest');
		$this->assertEquals($expectedResponse, $response);

		// Test with being authenticated for the correct file
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
		$response = $this->shareController->showAuthenticate($this->token);
		$expectedResponse =  new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $this->token)));
		$this->assertEquals($expectedResponse, $response);
	}

	public function testAuthenticate() {
		// Test without a not existing token
		$response = $this->shareController->authenticate('ThisTokenShouldHopefullyNeverExistSoThatTheUnitTestWillAlwaysPass :)');
		$expectedResponse =  new NotFoundResponse();
		$this->assertEquals($expectedResponse, $response);

		// Test with a valid password
		$response = $this->shareController->authenticate($this->token, 'IAmPasswordProtected!');
		$expectedResponse =  new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $this->token)));
		$this->assertEquals($expectedResponse, $response);

		// Test with a invalid password
		$response = $this->shareController->authenticate($this->token, 'WrongPw!');
		$expectedResponse =  new TemplateResponse($this->container['AppName'], 'authenticate', array('wrongpw' => true), 'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowShare() {
		$this->container['UserManager']->expects($this->exactly(2))
			->method('userExists')
			->with($this->user)
			->will($this->returnValue(true));

		// Test without a not existing token
		$response = $this->shareController->showShare('ThisTokenShouldHopefullyNeverExistSoThatTheUnitTestWillAlwaysPass :)');
		$expectedResponse =  new NotFoundResponse();
		$this->assertEquals($expectedResponse, $response);

		// Test with a password protected share and no authentication
		$response = $this->shareController->showShare($this->token);
		$expectedResponse = new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate', array('token' => $this->token)));
		$this->assertEquals($expectedResponse, $response);

		// Test with password protected share and authentication
		$linkItem = Share::getShareByToken($this->token, false);
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
		$response = $this->shareController->showShare($this->token);
		$sharedTmplParams = array(
			'displayName' => $this->user,
			'filename' => 'file1.txt',
			'directory_path' => '/file1.txt',
			'mimetype' => 'text/plain',
			'dirToken' => $this->token,
			'sharingToken' => $this->token,
			'server2serversharing' => true,
			'protected' => 'true',
			'dir' => '',
			'downloadURL' => null,
			'fileSize' => '33 B',
			'nonHumanFileSize' => 33,
			'maxSizeAnimateGif' => 10,
			'previewSupported' => true,
			'previewEnabled' => true,
		);

		$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');
		$expectedResponse = new TemplateResponse($this->container['AppName'], 'public', $sharedTmplParams, 'base');
		$expectedResponse->setContentSecurityPolicy($csp);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testDownloadShare() {
		// Test with a password protected share and no authentication
		$response = $this->shareController->downloadShare($this->token);
		$expectedResponse = new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate',
			array('token' => $this->token)));
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage No file found belonging to file.
	 */
	public function testShowShareWithDeletedFile() {
		$this->container['UserManager']->expects($this->once())
			->method('userExists')
			->with($this->user)
			->will($this->returnValue(true));

		$view = new View('/'. $this->user . '/files');
		$view->unlink('file1.txt');
		$linkItem = Share::getShareByToken($this->token, false);
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
		$this->shareController->showShare($this->token);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage No file found belonging to file.
	 */
	public function testDownloadShareWithDeletedFile() {
		$this->container['UserManager']->expects($this->once())
			->method('userExists')
			->with($this->user)
			->will($this->returnValue(true));

		$view = new View('/'. $this->user . '/files');
		$view->unlink('file1.txt');
		$linkItem = Share::getShareByToken($this->token, false);
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
		$this->shareController->downloadShare($this->token);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Owner of the share does not exist anymore
	 */
	public function testShowShareWithNotExistingUser() {
		$this->container['UserManager']->expects($this->once())
			->method('userExists')
			->with($this->user)
			->will($this->returnValue(false));

		$linkItem = Share::getShareByToken($this->token, false);
		\OC::$server->getSession()->set('public_link_authenticated', $linkItem['id']);
		$this->shareController->showShare($this->token);
	}

}
