<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\DAV\Tests\Unit\Comments;

use OCA\DAV\Comments\EntityCollection as EntityCollectionImplemantation;

class EntityTypeCollection extends \Test\TestCase {

	protected $commentsManager;
	protected $folder;
	protected $userManager;
	protected $logger;
	protected $collection;
	protected $userSession;

	public function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMock('\OCP\Comments\ICommentsManager');
		$this->folder = $this->getMock('\OCP\Files\Folder');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->userSession = $this->getMock('\OCP\IUserSession');
		$this->logger = $this->getMock('\OCP\ILogger');

		$this->collection = new \OCA\DAV\Comments\EntityTypeCollection(
			'files',
			$this->commentsManager,
			$this->folder,
			$this->userManager,
			$this->userSession,
			$this->logger
		);
	}

	public function testChildExistsYes() {
		$this->folder->expects($this->once())
			->method('getById')
			->with('17')
			->will($this->returnValue([$this->getMock('\OCP\Files\Node')]));
		$this->assertTrue($this->collection->childExists('17'));
	}

	public function testChildExistsNo() {
		$this->folder->expects($this->once())
			->method('getById')
			->will($this->returnValue([]));
		$this->assertFalse($this->collection->childExists('17'));
	}

	public function testGetChild() {
		$this->folder->expects($this->once())
			->method('getById')
			->with('17')
			->will($this->returnValue([$this->getMock('\OCP\Files\Node')]));

		$ec = $this->collection->getChild('17');
		$this->assertTrue($ec instanceof EntityCollectionImplemantation);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildException() {
		$this->folder->expects($this->once())
			->method('getById')
			->with('17')
			->will($this->returnValue([]));

		$this->collection->getChild('17');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testGetChildren() {
		$this->collection->getChildren();
	}
}
