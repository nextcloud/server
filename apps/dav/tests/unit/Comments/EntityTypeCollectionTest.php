<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Comments;

use OCA\DAV\Comments\EntityCollection as EntityCollectionImplemantation;
use OCA\DAV\Comments\EntityTypeCollection;
use OCP\Comments\ICommentsManager;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class EntityTypeCollectionTest extends TestCase {

	/** @var ICommentsManager|MockObject */
	protected $commentsManager;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var EntityTypeCollection */
	protected $collection;
	/** @var IUserSession|MockObject */
	protected $userSession;

	protected $childMap = [];

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$instance = $this;

		$this->collection = new EntityTypeCollection(
			'files',
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->logger,
			function ($child) use ($instance) {
				return !empty($instance->childMap[$child]);
			}
		);
	}

	public function testChildExistsYes() {
		$this->childMap[17] = true;
		$this->assertTrue($this->collection->childExists('17'));
	}

	public function testChildExistsNo() {
		$this->assertFalse($this->collection->childExists('17'));
	}

	/**
	 * @throws NotFound
	 */
	public function testGetChild() {
		$this->childMap[17] = true;

		$ec = $this->collection->getChild('17');
		$this->assertTrue($ec instanceof EntityCollectionImplemantation);
	}


	public function testGetChildException() {
		$this->expectException(NotFound::class);

		$this->collection->getChild('17');
	}


	public function testGetChildren() {
		$this->expectException(MethodNotAllowed::class);

		$this->collection->getChildren();
	}
}
