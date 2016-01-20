<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Cloutier <vincent1cloutier@gmail.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
use OC\Share20\Exception\ShareNotFound;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ISession;
use OCP\Security\ISecureRandom;
use OCP\IURLGenerator;

/**
 * @group DB
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ShareControllerTest extends \Test\TestCase {

	/** @var string */
	private $user;
	/** @var string */
	private $oldUser;

	/** @var string */
	private $appName = 'files_sharing';
	/** @var ShareController */
	private $shareController;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ISession | \PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var \OCP\IPreview | \PHPUnit_Framework_MockObject_MockObject */
	private $previewManager;
	/** @var \OCP\IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var  \OC\Share20\Manager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;

	protected function setUp() {
		$this->appName = 'files_sharing';

		$this->shareManager = $this->getMockBuilder('\OC\Share20\Manager')->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');
		$this->session = $this->getMock('\OCP\ISession');
		$this->previewManager = $this->getMock('\OCP\IPreview');
		$this->config = $this->getMock('\OCP\IConfig');

		$this->shareController = new \OCA\Files_Sharing\Controllers\ShareController(
			$this->appName,
			$this->getMock('\OCP\IRequest'),
			$this->config,
			$this->urlGenerator,
			$this->getMock('\OCP\IUserManager'),
			$this->getMock('\OCP\ILogger'),
			$this->getMock('\OCP\Activity\IManager'),
			$this->shareManager,
			$this->session,
			$this->previewManager,
			$this->getMock('\OCP\Files\IRootFolder')
		);


		// Store current user
		$this->oldUser = \OC_User::getUser();

		// Create a dummy user
		$this->user = \OC::$server->getSecureRandom()->generate(12, ISecureRandom::CHAR_LOWER);

		\OC::$server->getUserManager()->createUser($this->user, $this->user);
		\OC_Util::tearDownFS();
		$this->loginAsUser($this->user);
	}

	protected function tearDown() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();
		$user = \OC::$server->getUserManager()->get($this->user);
		if ($user !== null) { $user->delete(); }
		\OC_User::setIncognitoMode(false);

		\OC::$server->getSession()->set('public_link_authenticated', '');

		// Set old user
		\OC_User::setUserId($this->oldUser);
		\OC_Util::setupFS($this->oldUser);
	}

	public function testShowAuthenticateNotAuthenticated() {
		$share = $this->getMock('\OC\Share20\IShare');

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$response = $this->shareController->showAuthenticate('token');
		$expectedResponse =  new TemplateResponse($this->appName, 'authenticate', [], 'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowAuthenticateAuthenticatedForDifferentShare() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn(1);

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('2');

		$response = $this->shareController->showAuthenticate('token');
		$expectedResponse =  new TemplateResponse($this->appName, 'authenticate', [], 'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowAuthenticateCorrectShare() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn(1);

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('1');

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('redirect');

		$response = $this->shareController->showAuthenticate('token');
		$expectedResponse =  new RedirectResponse('redirect');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testAutehnticateInvalidToken() {
		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$response = $this->shareController->authenticate('token');
		$expectedResponse =  new NotFoundResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testAuthenticateValidPassword() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn(42);

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('checkPassword')
			->with($share, 'validpassword')
			->willReturn(true);

		$this->session
			->expects($this->once())
			->method('set')
			->with('public_link_authenticated', '42');

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files_sharing.sharecontroller.showShare', ['token'=>'token'])
			->willReturn('redirect');

		$response = $this->shareController->authenticate('token', 'validpassword');
		$expectedResponse =  new RedirectResponse('redirect');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testAuthenticateInvalidPassword() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn(42);

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('checkPassword')
			->with($share, 'invalidpassword')
			->willReturn(false);

		$this->session
			->expects($this->never())
			->method('set');

		$response = $this->shareController->authenticate('token', 'invalidpassword');
		$expectedResponse =  new TemplateResponse($this->appName, 'authenticate', array('wrongpw' => true), 'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowShareInvalidToken() {
		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('invalidtoken')
			->will($this->throwException(new ShareNotFound()));

		// Test without a not existing token
		$response = $this->shareController->showShare('invalidtoken');
		$expectedResponse =  new NotFoundResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testShowShareNotAuthenticated() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getPassword')->willReturn('password');

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('validtoken')
			->willReturn($share);

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files_sharing.sharecontroller.authenticate', ['token' => 'validtoken'])
			->willReturn('redirect');

		// Test without a not existing token
		$response = $this->shareController->showShare('validtoken');
		$expectedResponse = new RedirectResponse('redirect');
		$this->assertEquals($expectedResponse, $response);
	}


	public function testShowShare() {
		$owner = $this->getMock('OCP\IUser');
		$owner->method('getDisplayName')->willReturn('ownerDisplay');
		$owner->method('getUID')->willReturn('ownerUID');

		$file = $this->getMock('OCP\Files\File');
		$file->method('getName')->willReturn('file1.txt');
		$file->method('getMimetype')->willReturn('text/plain');
		$file->method('getSize')->willReturn(33);

		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn('42');
		$share->method('getPassword')->willReturn('password');
		$share->method('getShareOwner')->willReturn($owner);
		$share->method('getPath')->willReturn($file);
		$share->method('getTarget')->willReturn('/file1.txt');

		$this->session->method('exists')->with('public_link_authenticated')->willReturn(true);
		$this->session->method('get')->with('public_link_authenticated')->willReturn('42');

		$this->previewManager->method('isMimeSupported')->with('text/plain')->willReturn(true);

		$this->config->method('getSystemValue')
			->willReturnMap(
				[
					['max_filesize_animated_gifs_public_sharing', 10, 10],
					['enable_previews', true, true],
				]
			);
		$shareTmpl['maxSizeAnimateGif'] = $this->config->getSystemValue('max_filesize_animated_gifs_public_sharing', 10);
		$shareTmpl['previewEnabled'] = $this->config->getSystemValue('enable_previews', true);

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$response = $this->shareController->showShare('token');
		$sharedTmplParams = array(
			'displayName' => 'ownerDisplay',
			'owner' => 'ownerUID',
			'filename' => 'file1.txt',
			'directory_path' => '/file1.txt',
			'mimetype' => 'text/plain',
			'dirToken' => 'token',
			'sharingToken' => 'token',
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
		$expectedResponse = new TemplateResponse($this->appName, 'public', $sharedTmplParams, 'base');
		$expectedResponse->setContentSecurityPolicy($csp);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testDownloadShare() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getPassword')->willReturn('password');

		$this->shareManager
			->expects($this->once())
			->method('getShareByToken')
			->with('validtoken')
			->willReturn($share);

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files_sharing.sharecontroller.authenticate', ['token' => 'validtoken'])
			->willReturn('redirect');

		// Test with a password protected share and no authentication
		$response = $this->shareController->downloadShare('validtoken');
		$expectedResponse = new RedirectResponse('redirect');
		$this->assertEquals($expectedResponse, $response);
	}

}
