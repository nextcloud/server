<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Config;

use OCA\Files_External\Config\UserPlaceholderHandler;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;

class UserPlaceholderHandlerTest extends \Test\TestCase {
	protected IUser&MockObject $user;
	protected IUserSession&MockObject $session;
	protected IManager&MockObject $shareManager;
	protected IRequest&MockObject $request;
	protected IUserManager&MockObject $userManager;
	protected UserPlaceholderHandler $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUid')
			->willReturn('alice');
		$this->session = $this->createMock(IUserSession::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->handler = new UserPlaceholderHandler($this->session, $this->shareManager, $this->request, $this->userManager);
	}

	protected function setUser(): void {
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
	}

	public static function optionProvider(): array {
		return [
			['/foo/bar/$user/foobar', '/foo/bar/alice/foobar'],
			[['/foo/bar/$user/foobar'], ['/foo/bar/alice/foobar']],
			[['/FOO/BAR/$USER/FOOBAR'], ['/FOO/BAR/alice/FOOBAR']],
		];
	}

	/**
	 * @dataProvider optionProvider
	 */
	public function testHandle(string|array $option, string|array $expected): void {
		$this->setUser();
		$this->assertSame($expected, $this->handler->handle($option));
	}

	/**
	 * @dataProvider optionProvider
	 */
	public function testHandleNoUser(string|array $option): void {
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willThrowException(new ShareNotFound());
		$this->assertSame($option, $this->handler->handle($option));
	}
}
