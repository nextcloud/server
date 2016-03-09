<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_sharing\Tests;

use OC\Files\View;
use OCP\IGroupManager;
use OCP\IGroup;
use OCP\IUser;
use OCP\Share;
use OCA\Files_Sharing\Propagation\GroupPropagationManager;
use OCA\Files_Sharing\Propagation\PropagationManager;

class GroupPropagationManagerTest extends TestCase {

	/**
	 * @var GroupPropagationManager
	 */
	private $groupPropagationManager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var PropagationManager
	 */
	private $propagationManager;

	/**
	 * @var IGroup
	 */
	private $recipientGroup;

	/**
	 * @var IUser
	 */
	private $recipientUser;

	/**
	 * @var array
	 */
	private $fileInfo;

	protected function setUp() {
		parent::setUp();

		$user = $this->getMockBuilder('\OCP\IUser')
		             ->disableOriginalConstructor()
		             ->getMock();
		$user->method('getUID')->willReturn(self::TEST_FILES_SHARING_API_USER1);
		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$userSession->method('getUser')->willReturn(self::TEST_FILES_SHARING_API_USER1);

		$this->propagationManager = $this->getMockBuilder('OCA\Files_Sharing\Propagation\PropagationManager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = \OC::$server->getGroupManager();
		$this->groupPropagationManager = new GroupPropagationManager(
			$userSession,
			$this->groupManager,
			$this->propagationManager
		);
		$this->groupPropagationManager->globalSetup();

		// since the sharing code is not mockable, we have to create a real folder
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$view1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$view1->mkdir('/folder');

		$this->fileInfo = $view1->getFileInfo('/folder');

		$this->recipientGroup = $this->groupManager->get(self::TEST_FILES_SHARING_API_GROUP1);
		$this->recipientUser = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER3);

		Share::shareItem(
			'folder',
			$this->fileInfo['fileid'],
			Share::SHARE_TYPE_GROUP,
			$this->recipientGroup->getGID(),
			\OCP\Constants::PERMISSION_READ
		);

		$this->loginAsUser($this->recipientUser->getUID());
	}

	protected function tearDown() {
		$this->groupPropagationManager->tearDown();
		$this->recipientGroup->removeUser($this->recipientUser);
		parent::tearDown();
	}

	public function testPropagateWhenAddedToGroup() {
		$this->propagationManager->expects($this->once())
			->method('propagateSharesToUser')
			->with($this->callback(function($shares) {
				if (count($shares) !== 1) {
					return false;
				}
				$share = array_values($shares)[0];
				return $share['file_source'] === $this->fileInfo['fileid'] &&
					$share['share_with'] === $this->recipientGroup->getGID() &&
					$share['file_target'] === '/folder';
			}), $this->recipientUser->getUID());

		$this->recipientGroup->addUser($this->recipientUser);
	}

	public function testPropagateWhenRemovedFromGroup() {
		$this->recipientGroup->addUser($this->recipientUser);

		$this->propagationManager->expects($this->once())
			->method('propagateSharesToUser')
			->with($this->callback(function($shares) {
				if (count($shares) !== 1) {
					return false;
				}
				$share = array_values($shares)[0];
				return $share['file_source'] === $this->fileInfo['fileid'] &&
					$share['share_with'] === $this->recipientGroup->getGID() &&
					$share['file_target'] === '/folder';
			}), $this->recipientUser->getUID());

		$this->recipientGroup->removeUser($this->recipientUser);
	}

	public function testPropagateWhenRemovedFromGroupWithSubdirTarget() {
		$this->recipientGroup->addUser($this->recipientUser);

		// relogin to refresh mount points
		$this->loginAsUser($this->recipientUser->getUID());
		$recipientView = new View('/' . $this->recipientUser->getUID() . '/files');

		$this->assertTrue($recipientView->mkdir('sub'));
		$this->assertTrue($recipientView->rename('folder', 'sub/folder'));

		$this->propagationManager->expects($this->once())
			->method('propagateSharesToUser')
			->with($this->callback(function($shares) {
				if (count($shares) !== 1) {
					return false;
				}
				$share = array_values($shares)[0];
				return $share['file_source'] === $this->fileInfo['fileid'] &&
					$share['share_with'] === $this->recipientGroup->getGID() &&
					$share['file_target'] === '/sub/folder';
			}), $this->recipientUser->getUID());

		$this->recipientGroup->removeUser($this->recipientUser);
	}
}
