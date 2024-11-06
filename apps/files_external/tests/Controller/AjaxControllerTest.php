<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Controller;

use OCA\Files_External\Controller\AjaxController;
use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use phpseclib3\Crypt\RSA as CryptRSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AjaxControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private RSA&MockObject $rsa;
	private GlobalAuth&MockObject $globalAuth;
	private IUserSession&MockObject $userSession;
	private IGroupManager&MockObject $groupManager;

	private AjaxController $ajaxController;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->rsa = $this->getMockBuilder(RSA::class)
			->disableOriginalConstructor()
			->getMock();
		$this->globalAuth = $this->getMockBuilder(GlobalAuth::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->ajaxController = new AjaxController(
			'files_external',
			$this->request,
			$this->rsa,
			$this->globalAuth,
			$this->userSession,
			$this->groupManager
		);

		parent::setUp();
	}

	public function testGetSshKeys(): void {
		$keyImpl = $this->createMock(CryptRSA::class);
		$keyImpl->expects(self::once())
			->method('toString')
			->with('OpenSSH')
			->willReturn('MyPublicKey');

		$publicKey = $this->createMock(PublicKey::class);
		$publicKey->expects(self::once())
			->method('getPublicKey')
			->willReturn($keyImpl);

		$privateKey = $this->createMock(PrivateKey::class);
		$privateKey->expects(self::once())
			->method('toString')
			->with('PKCS1')
			->willReturn('MyPrivatekey');

		$this->rsa
			->expects($this->once())
			->method('createKey')
			->willReturn([
				'privatekey' => $privateKey,
				'publickey' => $publicKey,
			]);

		$expected = new JSONResponse(
			[
				'data' => [
					'private_key' => 'MyPrivateKey',
					'public_key' => 'MyPublicKey',
				],
				'status' => 'success',
			]
		);
		$this->assertEquals($expected, $this->ajaxController->getSshKeys());
	}

	public function testSaveGlobalCredentialsAsAdminForAnotherUser(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyAdminUid');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->globalAuth
			->expects($this->never())
			->method('saveAuth');

		$this->assertSame(false, $this->ajaxController->saveGlobalCredentials('UidOfTestUser', 'test', 'password'));
	}

	public function testSaveGlobalCredentialsAsAdminForSelf(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyAdminUid');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->globalAuth
			->expects($this->once())
			->method('saveAuth')
			->with('MyAdminUid', 'test', 'password');

		$this->assertSame(true, $this->ajaxController->saveGlobalCredentials('MyAdminUid', 'test', 'password'));
	}

	public function testSaveGlobalCredentialsAsNormalUserForSelf(): void {
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('MyUserUid');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$this->globalAuth
			->method('saveAuth')
			->with('MyUserUid', 'test', 'password');

		$this->assertSame(true, $this->ajaxController->saveGlobalCredentials('MyUserUid', 'test', 'password'));
	}

	public function testSaveGlobalCredentialsAsNormalUserForAnotherUser(): void {
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('MyUserUid');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$this->globalAuth
			->expects($this->never())
			->method('saveAuth');

		$this->assertSame(false, $this->ajaxController->saveGlobalCredentials('AnotherUserUid', 'test', 'password'));
	}
}
