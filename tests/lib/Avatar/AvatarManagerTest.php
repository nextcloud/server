<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
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

namespace Test\Avatar;

use OC\Avatar\GuestAvatar;
use OC\Avatar\UserAvatar;
use OC\Avatar\AvatarManager;
use OC\User\Manager;
use OC\User\User;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AvatarManagerTest
 */
class AvatarManagerTest extends \Test\TestCase {
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	private $appData;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var AvatarManager | \PHPUnit_Framework_MockObject_MockObject */
	private $avatarManager;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(Manager::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);

		$this->avatarManager = new AvatarManager(
			$this->userManager,
			$this->appData,
			$this->l10n,
			$this->logger,
			$this->config
		);
	}

	/**
	 * Asserts a GuestAvatar is returned for unknown users.
	 *
	 * @return void
	 */
	public function testGetGuestAvatar() {
		$this->userManager->method('get')
			->with('unknownUser')
			->willReturn(null);

		$avatar = $this->avatarManager->getAvatar('unknownUser');
		self::assertInstanceOf(GuestAvatar::class, $avatar);
		/* @var $avatar GuestAvatar */
		self::assertEquals('unknownUser', $avatar->getDisplayName());
	}

	/**
	 * Returns user names / ids test data.
	 *
	 * The format is: [0]: requested user name, [1] user id
	 *
	 * @return array
	 */
	public function getUserNamesAndIds() {
		return [
			['valid-user', 'valid-user'],
			['vaLid-USER', 'valid-user'],
		];
	}

	/**
	 * Asserts that an UserAvatar is returned for known users.
	 *
	 * @param string $userName The requested user name
	 * @param string $userId The user id for the requested user
	 * @return void
	 * @dataProvider getUserNamesAndIds
	 */
	public function testGetUserAvatar(string $userName, string $userId) {
		/* @var MockObject|User $user */
		$user = $this->createMock(User::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with($userName)
			->willReturn($user);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($userId);

		/* @var MockObject|ISimpleFolder $folder */
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with($userId)
			->willReturn($folder);

		$expected = new UserAvatar($folder, $this->l10n, $user, $this->logger, $this->config);
		$this->assertEquals($expected, $this->avatarManager->getAvatar($userName));
	}
}
