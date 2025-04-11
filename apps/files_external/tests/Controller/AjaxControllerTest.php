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
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class AjaxControllerTest extends TestCase {
	/** @var IRequest */
	private $request;
	/** @var RSA */
	private $rsa;
	/** @var GlobalAuth */
	private $globalAuth;
	/** @var IUserSession */
	private $userSession;
	/** @var IGroupManager */
	private $groupManager;
	/** @var AjaxController */
	private $ajaxController;
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->rsa = $this->getMockBuilder('\\OCA\\Files_External\\Lib\\Auth\\PublicKey\\RSA')
			->disableOriginalConstructor()
			->getMock();
		$this->globalAuth = $this->getMockBuilder('\\OCA\\Files_External\\Lib\\Auth\\Password\GlobalAuth')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->ajaxController = new AjaxController(
			'files_external',
			$this->request,
			$this->rsa,
			$this->globalAuth,
			$this->userSession,
			$this->groupManager,
			$this->l10n,
		);

		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				if (!is_array($args)) {
					$args = [$args];
				}
				return vsprintf($string, $args);
			});

		parent::setUp();
	}

	public function testGetSshKeys(): void {
		$this->rsa
			->expects($this->once())
			->method('createKey')
			->willReturn([
				'privatekey' => 'MyPrivateKey',
				'publickey' => 'MyPublicKey',
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

		$response = $this->ajaxController->saveGlobalCredentials('UidOfTestUser', 'test', 'password');
		$this->assertSame($response->getStatus(), 403);
		$this->assertSame('Permission denied', $response->getData()['message']);
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

		$response = $this->ajaxController->saveGlobalCredentials('MyAdminUid', 'test', 'password');
		$this->assertSame($response->getStatus(), 200);
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

		$response = $this->ajaxController->saveGlobalCredentials('MyUserUid', 'test', 'password');
		$this->assertSame($response->getStatus(), 200);
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

		$response = $this->ajaxController->saveGlobalCredentials('AnotherUserUid', 'test', 'password');
		$this->assertSame($response->getStatus(), 403);
		$this->assertSame('Permission denied', $response->getData()['message']);
	}
}
