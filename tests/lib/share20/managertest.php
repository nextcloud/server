<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace Test\Share20;

use OC\Share20\Manager;
use OC\Share20\Exception;


use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IAppConfig;
use OCP\Files\Folder;
use OCP\Share20\IShareProvider;

class ManagerTest extends \Test\TestCase {

	/** @var Manager */
	protected $manager;

	/** @var IUser */
	protected $user;

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var ILogger */
	protected $logger;

	/** @var IAppConfig */
	protected $appConfig;

	/** @var Folder */
	protected $userFolder;

	/** @var IShareProvider */
	protected $defaultProvider;

	public function setUp() {
		
		$this->user = $this->getMock('\OCP\IUser');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->groupManager = $this->getMock('\OCP\IGroupManager');
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->appConfig = $this->getMock('\OCP\IAppConfig');
		$this->userFolder = $this->getMock('\OCP\Files\Folder');
		$this->defaultProvider = $this->getMock('\OC\Share20\IShareProvider');

		$this->manager = new Manager(
			$this->user,
			$this->userManager,
			$this->groupManager,
			$this->logger,
			$this->appConfig,
			$this->userFolder,
			$this->defaultProvider
		);
	}

	/**
	 * @expectedException OC\Share20\Exception\ShareNotFound
	 */
	public function testDeleteNoShareId() {
		$share = $this->getMock('\OC\Share20\IShare');

		$share
			->expects($this->once())
			->method('getId')
			->with()
			->willReturn(null);

		$this->manager->deleteShare($share);
	}

	public function testDelete() {
		$share = $this->getMock('\OC\Share20\IShare');

		$share
			->expects($this->once())
			->method('getId')
			->with()
			->willReturn(42);
		$this->defaultProvider
			->expects($this->once())
			->method('delete')
			->with($share);

		$this->manager->deleteShare($share);
	}

	/**
	 * @expectedException OC\Share20\Exception\ShareNotFound
	 */
	public function testGetShareByIdNotFoundInBackend() {
		$this->defaultProvider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$this->manager->getShareById(42);
	}

	/**
	 * @expectedException OC\Share20\Exception\ShareNotFound
	 */
	public function testGetShareByIdNotAuthorized() {
		$otherUser1 = $this->getMock('\OCP\IUser');
		$otherUser2 = $this->getMock('\OCP\IUser');
		$otherUser3 = $this->getMock('\OCP\IUser');

		$share = $this->getMock('\OC\Share20\IShare');
		$share
			->expects($this->once())
			->method('getSharedWith')
			->with()
			->willReturn($otherUser1);
		$share
			->expects($this->once())
			->method('getSharedBy')
			->with()
			->willReturn($otherUser2);
		$share
			->expects($this->once())
			->method('getShareOwner')
			->with()
			->willReturn($otherUser3);

		$this->defaultProvider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);

		$this->manager->getShareById(42);
	}

	public function dataGetShareById() {
		return [
			['getSharedWith'],
			['getSharedBy'],
			['getShareOwner'],
		];
	}

	/**
	 * @dataProvider dataGetShareById
	 */
	public function testGetShareById($currentUserIs) {
		$otherUser1 = $this->getMock('\OCP\IUser');
		$otherUser2 = $this->getMock('\OCP\IUser');
		$otherUser3 = $this->getMock('\OCP\IUser');

		$share = $this->getMock('\OC\Share20\IShare');
		$share
			->method('getSharedWith')
			->with()
			->willReturn($currentUserIs === 'getSharedWith' ? $this->user : $otherUser1);
		$share
			->method('getSharedBy')
			->with()
			->willReturn($currentUserIs === 'getSharedBy' ? $this->user : $otherUser2);
		$share
			->method('getShareOwner')
			->with()
			->willReturn($currentUserIs === 'getShareOwner' ? $this->user : $otherUser3);

		$this->defaultProvider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);

		$this->assertEquals($share, $this->manager->getShareById(42));
	}
}
