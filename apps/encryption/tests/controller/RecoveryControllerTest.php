<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 */


namespace OC\apps\encryption\tests\lib\controller;


use OCA\Encryption\Controller\RecoveryController;
use Test\TestCase;

class RecoveryControllerTest extends TestCase {
	/**
	 * @var RecoveryController
	 */
	private $controller;
	private $appName;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $requestMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $configMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $l10nMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $recoveryMock;

	public function testAdminRecovery() {

		$recoveryPassword = 'test';
		$enableRecovery = '1';

		$this->recoveryMock->expects($this->any())
			->method('enableAdminRecovery')
			->willReturn(true);

		$response = $this->controller->adminRecovery($recoveryPassword,
			$recoveryPassword,
			$enableRecovery)->getData();


		$this->assertEquals('Recovery key successfully enabled',
			$response['data']['message']);

		$response = $this->controller->adminRecovery('',
			$recoveryPassword,
			$enableRecovery)->getData();

		$this->assertEquals('Missing recovery key password',
			$response['data']['message']);

		$response = $this->controller->adminRecovery($recoveryPassword,
			'',
			$enableRecovery)->getData();

		$this->assertEquals('Please repeat the recovery key password',
			$response['data']['message']);

		$response = $this->controller->adminRecovery($recoveryPassword,
			'something that doesn\'t match',
			$enableRecovery)->getData();

		$this->assertEquals('Repeated recovery key password does not match the provided recovery key password',
			$response['data']['message']);

		$this->recoveryMock->expects($this->once())
			->method('disableAdminRecovery')
			->willReturn(true);

		$response = $this->controller->adminRecovery($recoveryPassword,
			$recoveryPassword,
			'0')->getData();

		$this->assertEquals('Recovery key successfully disabled',
			$response['data']['message']);
	}

	public function testChangeRecoveryPassword() {
		$password = 'test';
		$oldPassword = 'oldtest';

		$data = $this->controller->changeRecoveryPassword($password,
			$oldPassword,
			$password)->getData();

		$this->assertEquals('Could not change the password. Maybe the old password was not correct.',
			$data['data']['message']);

		$this->recoveryMock->expects($this->once())
			->method('changeRecoveryKeyPassword')
			->with($password, $oldPassword)
			->willReturn(true);

		$data = $this->controller->changeRecoveryPassword($password,
			$oldPassword,
			$password)->getData();

		$this->assertEquals('Password successfully changed.',
			$data['data']['message']);

		$data = $this->controller->changeRecoveryPassword($password,
			$oldPassword,
			'not match')->getData();

		$this->assertEquals('Repeated recovery key password does not match the provided recovery key password',
			$data['data']['message']);

		$data = $this->controller->changeRecoveryPassword('',
			$oldPassword,
			$password)->getData();

		$this->assertEquals('Please provide a new recovery password',
			$data['data']['message']);

		$data = $this->controller->changeRecoveryPassword($password,
			'',
			$password)->getData();

		$this->assertEquals('Please provide the old recovery password',
			$data['data']['message']);
	}

	public function testUserSetRecovery() {
		$this->recoveryMock->expects($this->exactly(2))
			->method('setRecoveryForUser')
			->willReturnOnConsecutiveCalls(true, false);

		$data = $this->controller->userSetRecovery('1')->getData();

		$this->assertEquals('Recovery Key enabled', $data['data']['message']);

		$data = $this->controller->userSetRecovery('1')->getData();

		$this->assertEquals('Could not enable the recovery key, please try again or contact your administrator',
			$data['data']['message']);

	}

	protected function setUp() {
		parent::setUp();

		$this->appName = 'encryption';
		$this->requestMock = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();

		$this->configMock = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();

		// Make l10n work in our tests
		$this->l10nMock->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->recoveryMock = $this->getMockBuilder('OCA\Encryption\Recovery')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new RecoveryController($this->appName,
			$this->requestMock,
			$this->configMock,
			$this->l10nMock,
			$this->recoveryMock);
	}

}
