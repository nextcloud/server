<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\LostPassword\Controller;
use OC\Core\Application;
use OCP\AppFramework\Http\TemplateResponse;

/**
 * Class LostControllerTest
 *
 * @package OC\Core\LostPassword\Controller
 */
class LostControllerTest extends \PHPUnit_Framework_TestCase {

	private $container;
	/** @var LostController */
	private $lostController;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['AppName'] = 'core';
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N'] = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N']
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->container['Defaults'] = $this->getMockBuilder('\OC_Defaults')
			->disableOriginalConstructor()->getMock();
		$this->container['UserManager'] = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['URLGenerator'] = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()->getMock();
		$this->container['Mailer'] = $this->getMockBuilder('\OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->container['SecureRandom'] = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$this->container['IsEncryptionEnabled'] = true;
		$this->lostController = $this->container['LostController'];
	}

	public function testResetFormUnsuccessful() {
		$userId = 'admin';
		$token = 'MySecretToken';

		$this->container['URLGenerator']
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.setPassword', array('userId' => 'admin', 'token' => 'MySecretToken'))
			->will($this->returnValue('https://ownCloud.com/index.php/lostpassword/'));

		$response = $this->lostController->resetform($token, $userId);
		$expectedResponse = new TemplateResponse('core/lostpassword',
			'resetpassword',
			array(
				'link' => 'https://ownCloud.com/index.php/lostpassword/',
			),
			'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailUnsucessful() {
		$existingUser = 'ExistingUser';
		$nonExistingUser = 'NonExistingUser';
		$this->container['UserManager']
			->expects($this->any())
			->method('userExists')
			->will($this->returnValueMap(array(
				array(true, $existingUser),
				array(false, $nonExistingUser)
			)));

		// With a non existing user
		$response = $this->lostController->email($nonExistingUser);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t send reset email. Please make sure your username is correct.'
		];
		$this->assertSame($expectedResponse, $response);

		// With no mail address
		$this->container['Config']
			->expects($this->any())
			->method('getUserValue')
			->with($existingUser, 'settings', 'email')
			->will($this->returnValue(null));
		$response = $this->lostController->email($existingUser);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t send reset email. Please make sure your username is correct.'
		];
		$this->assertSame($expectedResponse, $response);
	}

	public function testEmailSuccessful() {
		$randomToken = $this->container['SecureRandom'];
		$this->container['SecureRandom']
			->expects($this->once())
			->method('generate')
			->with('21')
			->will($this->returnValue('ThisIsMaybeANotSoSecretToken!'));
		$this->container['UserManager']
			->expects($this->once())
			->method('userExists')
			->with('ExistingUser')
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getUserValue')
			->with('ExistingUser', 'settings', 'email')
			->will($this->returnValue('test@example.com'));
		$this->container['SecureRandom']
			->expects($this->once())
			->method('getMediumStrengthGenerator')
			->will($this->returnValue($randomToken));
		$this->container['Config']
			->expects($this->once())
			->method('setUserValue')
			->with('ExistingUser', 'owncloud', 'lostpassword', 'ThisIsMaybeANotSoSecretToken!');
		$this->container['URLGenerator']
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', array('userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'))
			->will($this->returnValue('https://ownCloud.com/index.php/lostpassword/'));
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['test@example.com' => 'ExistingUser']);
		$message
			->expects($this->at(1))
			->method('setSubject')
			->with(' password reset');
		$message
			->expects($this->at(2))
			->method('setPlainBody')
			->with('Use the following link to reset your password: https://ownCloud.com/index.php/lostpassword/');
		$message
			->expects($this->at(3))
			->method('setFrom')
			->with(['lostpassword-noreply@localhost' => null]);
		$this->container['Mailer']
			->expects($this->at(0))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->container['Mailer']
			->expects($this->at(1))
			->method('send')
			->with($message);

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = array('status' => 'success');
		$this->assertSame($expectedResponse, $response);
	}

	public function testEmailCantSendException() {
		$randomToken = $this->container['SecureRandom'];
		$this->container['SecureRandom']
			->expects($this->once())
			->method('generate')
			->with('21')
			->will($this->returnValue('ThisIsMaybeANotSoSecretToken!'));
		$this->container['UserManager']
			->expects($this->once())
			->method('userExists')
			->with('ExistingUser')
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getUserValue')
			->with('ExistingUser', 'settings', 'email')
			->will($this->returnValue('test@example.com'));
		$this->container['SecureRandom']
			->expects($this->once())
			->method('getMediumStrengthGenerator')
			->will($this->returnValue($randomToken));
		$this->container['Config']
			->expects($this->once())
			->method('setUserValue')
			->with('ExistingUser', 'owncloud', 'lostpassword', 'ThisIsMaybeANotSoSecretToken!');
		$this->container['URLGenerator']
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', array('userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'))
			->will($this->returnValue('https://ownCloud.com/index.php/lostpassword/'));
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['test@example.com' => 'ExistingUser']);
		$message
			->expects($this->at(1))
			->method('setSubject')
			->with(' password reset');
		$message
			->expects($this->at(2))
			->method('setPlainBody')
			->with('Use the following link to reset your password: https://ownCloud.com/index.php/lostpassword/');
		$message
			->expects($this->at(3))
			->method('setFrom')
			->with(['lostpassword-noreply@localhost' => null]);
		$this->container['Mailer']
			->expects($this->at(0))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->container['Mailer']
			->expects($this->at(1))
			->method('send')
			->with($message)
			->will($this->throwException(new \Exception()));

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = ['status' => 'error', 'msg' => 'Couldn\'t send reset email. Please contact your administrator.'];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordUnsuccessful() {
		$this->container['Config']
			->expects($this->once())
			->method('getUserValue')
			->with('InvalidTokenUser', 'owncloud', 'lostpassword', null)
			->will($this->returnValue('TheOnlyAndOnlyOneTokenToResetThePassword'));

		// With an invalid token
		$userName = 'InvalidTokenUser';
		$response = $this->lostController->setPassword('wrongToken', $userName, 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is invalid'
		];
		$this->assertSame($expectedResponse, $response);

		// With a valid token and no proceed
		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword!', $userName, 'NewPassword', false);
		$expectedResponse = ['status' => 'error', 'msg' => '', 'encryption' => true];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordSuccessful() {
		$this->container['Config']
			->expects($this->once())
			->method('getUserValue')
			->with('ValidTokenUser', 'owncloud', 'lostpassword', null)
			->will($this->returnValue('TheOnlyAndOnlyOneTokenToResetThePassword'));
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()->getMock();
		$user->expects($this->once())
			->method('setPassword')
			->with('NewPassword')
			->will($this->returnValue(true));
		$this->container['UserManager']
			->expects($this->once())
			->method('get')
			->with('ValidTokenUser')
			->will($this->returnValue($user));
		$this->container['Config']
			->expects($this->once())
			->method('deleteUserValue')
			->with('ValidTokenUser', 'owncloud', 'lostpassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = array('status' => 'success');
		$this->assertSame($expectedResponse, $response);
	}

	public function testIsSetPasswordWithoutTokenFailing() {
		$this->container['Config']
			->expects($this->once())
			->method('getUserValue')
			->with('ValidTokenUser', 'owncloud', 'lostpassword', null)
			->will($this->returnValue(null));

		$response = $this->lostController->setPassword('', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is invalid'
			];
		$this->assertSame($expectedResponse, $response);
	}

}
