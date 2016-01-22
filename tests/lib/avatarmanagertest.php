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
use OC\AvatarManager;
use OCP\Files\IRootFolder;
use OCP\IUserManager;

class AvatarManagerTest extends \Test\TestCase {
	/** @var  IRootFolder */
	private $rootFolder;

	/** @var  AvatarManager */
	private $avatarManager;

	/** @var  IUserManager */
	private $userManager;

	public function setUp() {
		parent::setUp();

		$this->rootFolder = $this->getMock('\OCP\Files\IRootFolder');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$l = $this->getMock('\OCP\IL10N');
		$l->method('t')->will($this->returnArgument(0));
		$this->avatarManager = new \OC\AvatarManager(
				$this->userManager,
				$this->rootFolder,
				$l);;
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage user does not exist
	 */
	public function testGetAvatarInvalidUser() {
		$this->avatarManager->getAvatar('invalidUser');
	}

	public function testGetAvatarValidUser() {
		$this->userManager->expects($this->once())
			->method('get')
			->with('validUser')
			->willReturn(true);

		$folder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('validUser')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getParent')
			->will($this->returnSelf());

		$this->avatarManager->getAvatar('validUser');

	}

}
