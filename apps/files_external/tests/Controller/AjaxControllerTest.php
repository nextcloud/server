<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Controller;

use OC\Settings\AuthorizedGroupMapper;
use OCA\Files_External\Controller\AjaxController;
use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCA\Files_External\Settings\Admin;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AjaxControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private RSA&MockObject $rsa;
	private GlobalAuth&MockObject $globalAuth;
	private IUserSession&MockObject $userSession;
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;
	private IL10N&MockObject $l10n;
	private AuthorizedGroupMapper&MockObject $authorizedGroupMapper;
	private AjaxController $ajaxController;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->rsa = $this->createMock(RSA::class);
		$this->globalAuth = $this->createMock(GlobalAuth::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->authorizedGroupMapper = $this->createMock(AuthorizedGroupMapper::class);

		$this->ajaxController = new AjaxController(
			'files_external',
			$this->request,
			$this->rsa,
			$this->globalAuth,
			$this->userSession,
			$this->groupManager,
			$this->userManager,
			$this->l10n,
			$this->authorizedGroupMapper,
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

	public function testGetApplicableEntitiesReturnsGroupsAndUsers(): void {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('group1');
		$group->method('getDisplayName')->willReturn('Group One');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');
		$user->method('getDisplayName')->willReturn('User One');

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('test', 10, 0)
			->willReturn([$group]);
		$this->userManager
			->expects($this->once())
			->method('searchDisplayName')
			->with('test', 10, 0)
			->willReturn([$user]);

		$response = $this->ajaxController->getApplicableEntities('test', 10, 0);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['group1' => 'Group One'], $response->getData()['groups']);
		$this->assertSame(['user1' => 'User One'], $response->getData()['users']);
	}

	public function testGetApplicableEntitiesWithNoResults(): void {
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('', null, null)
			->willReturn([]);
		$this->userManager
			->expects($this->once())
			->method('searchDisplayName')
			->with('', null, null)
			->willReturn([]);

		$response = $this->ajaxController->getApplicableEntities();
		$this->assertSame(200, $response->getStatus());
		$this->assertSame([], $response->getData()['groups']);
		$this->assertSame([], $response->getData()['users']);
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

	public function testSaveGlobalCredentialsAsAdminForGlobal(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('MyAdminUid');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('MyAdminUid')
			->willReturn(true);
		$this->authorizedGroupMapper
			->expects($this->never())
			->method('findAllClassesForUser');
		$this->globalAuth
			->expects($this->once())
			->method('saveAuth')
			->with('', 'test', 'password');

		$response = $this->ajaxController->saveGlobalCredentials('', 'test', 'password');
		$this->assertSame(200, $response->getStatus());
	}

	public function testSaveGlobalCredentialsAsDelegatedAdminForGlobal(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('DelegatedUid');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('DelegatedUid')
			->willReturn(false);
		$this->authorizedGroupMapper
			->expects($this->once())
			->method('findAllClassesForUser')
			->with($user)
			->willReturn([Admin::class]);
		$this->globalAuth
			->expects($this->once())
			->method('saveAuth')
			->with('', 'test', 'password');

		$response = $this->ajaxController->saveGlobalCredentials('', 'test', 'password');
		$this->assertSame(200, $response->getStatus());
	}

	public function testSaveGlobalCredentialsAsDelegatedAdminForAnotherUser(): void {
		// Delegated admins may only set global (uid='') credentials, not impersonate other users.
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('DelegatedUid');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager
			->expects($this->never())
			->method('isAdmin');
		$this->authorizedGroupMapper
			->expects($this->never())
			->method('findAllClassesForUser');
		$this->globalAuth
			->expects($this->never())
			->method('saveAuth');

		$response = $this->ajaxController->saveGlobalCredentials('OtherUserUid', 'test', 'password');
		$this->assertSame(403, $response->getStatus());
		$this->assertSame('Permission denied', $response->getData()['message']);
	}

	public function testSaveGlobalCredentialsAsNormalUserForGlobal(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('NormalUid');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('NormalUid')
			->willReturn(false);
		$this->authorizedGroupMapper
			->expects($this->once())
			->method('findAllClassesForUser')
			->with($user)
			->willReturn([]);
		$this->globalAuth
			->expects($this->never())
			->method('saveAuth');

		$response = $this->ajaxController->saveGlobalCredentials('', 'test', 'password');
		$this->assertSame(403, $response->getStatus());
		$this->assertSame('Permission denied', $response->getData()['message']);
	}
}
