<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace Test\Encryption;


use OC\Encryption\Update;
use Test\TestCase;

class UpdateTest extends TestCase {

	/** @var \OC\Encryption\Update */
	private $update;

	/** @var string */
	private $uid;

	/** @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject */
	private $view;

	/** @var \OC\Encryption\Util | \PHPUnit_Framework_MockObject_MockObject */
	private $util;

	/** @var \OC\Files\Mount\Manager | \PHPUnit_Framework_MockObject_MockObject */
	private $mountManager;

	/** @var \OC\Encryption\Manager | \PHPUnit_Framework_MockObject_MockObject */
	private $encryptionManager;

	/** @var \OCP\Encryption\IEncryptionModule | \PHPUnit_Framework_MockObject_MockObject */
	private $encryptionModule;

	/** @var \OC\Encryption\File | \PHPUnit_Framework_MockObject_MockObject */
	private $fileHelper;

	protected function setUp() {
		parent::setUp();

		$this->view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OC\Encryption\Util')
			->disableOriginalConstructor()->getMock();
		$this->mountManager = $this->getMockBuilder('\OC\Files\Mount\Manager')
			->disableOriginalConstructor()->getMock();
		$this->encryptionManager = $this->getMockBuilder('\OC\Encryption\Manager')
			->disableOriginalConstructor()->getMock();
		$this->fileHelper = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()->getMock();
		$this->encryptionModule = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()->getMock();

		$this->encryptionManager->expects($this->once())
			->method('getEncryptionModule')
			->willReturn($this->encryptionModule);

		$this->uid = 'testUser1';

		$this->update = new Update(
			$this->view,
			$this->util,
			$this->mountManager,
			$this->encryptionManager,
			$this->fileHelper,
			$this->uid);
	}

	/**
	 * @dataProvider dataTestUpdate
	 *
	 * @param string $path
	 * @param boolean $isDir
	 * @param array $allFiles
	 * @param integer $numberOfFiles
	 */
	public function testUpdate($path, $isDir, $allFiles, $numberOfFiles) {

		$this->view->expects($this->once())
			->method('is_dir')
			->willReturn($isDir);

		if($isDir) {
			$this->util->expects($this->once())
				->method('getAllFiles')
				->willReturn($allFiles);
		}

		$this->fileHelper->expects($this->exactly($numberOfFiles))
			->method('getAccessList')
			->willReturn(['users' => [], 'public' => false]);

		$this->encryptionModule->expects($this->exactly($numberOfFiles))
			->method('update')
			->willReturn(true);

		$this->update->update($path);
	}

	/**
	 * data provider for testUpdate()
	 *
	 * @return array
	 */
	public function dataTestUpdate() {
		return array(
			array('/user/files/foo', true, ['/user/files/foo/file1.txt', '/user/files/foo/file1.txt'], 2),
			array('/user/files/test.txt', false, [], 1),
		);
	}

}
