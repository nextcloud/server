<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\files_external\tests\Config;

use OCA\Files_External\Config\UserPlaceholderHandler;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class UserPlaceholderHandlerTest extends \Test\TestCase {
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $session;

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var UserPlaceholderHandler */
	protected $handler;

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

	protected function setUser() {
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
	}

	public function optionProvider() {
		return [
			['/foo/bar/$user/foobar', '/foo/bar/alice/foobar'],
			[['/foo/bar/$user/foobar'], ['/foo/bar/alice/foobar']],
			[['/FOO/BAR/$USER/FOOBAR'], ['/FOO/BAR/alice/FOOBAR']],
		];
	}

	/**
	 * @dataProvider optionProvider
	 */
	public function testHandle($option, $expected): void {
		$this->setUser();
		$this->assertSame($expected, $this->handler->handle($option));
	}

	/**
	 * @dataProvider optionProvider
	 */
	public function testHandleNoUser($option): void {
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->willThrowException(new ShareNotFound());
		$this->assertSame($option, $this->handler->handle($option));
	}
}
