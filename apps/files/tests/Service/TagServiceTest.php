<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Service;

use OCA\Files\Service\TagService;
use OCP\Activity\IManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TagServiceTest
 *
 * @group DB
 *
 * @package OCA\Files
 */
class TagServiceTest extends \Test\TestCase {
	private string $user;
	private IUserSession&MockObject $userSession;
	private IManager&MockObject $activityManager;
	private Folder $root;
	private TagService&MockObject $tagService;
	private ITags $tagger;

	protected function setUp(): void {
		parent::setUp();
		$this->user = static::getUniqueID('user');
		$this->activityManager = $this->createMock(IManager::class);
		Server::get(IUserManager::class)->createUser($this->user, 'test');
		\OC_User::setUserId($this->user);
		\OC_Util::setupFS($this->user);
		$user = $this->createMock(IUser::class);
		/**
		 * @var IUserSession
		 */
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->withAnyParameters()
			->willReturn($user);

		$this->root = \OCP\Server::get(IRootFolder::class)->getUserFolder($this->user);

		$this->tagger = Server::get(ITagManager::class)->load('files');
		$this->tagService = $this->getTagService();
	}

	protected function getTagService(array $methods = []): TagService&MockObject {
		return $this->getMockBuilder(TagService::class)
			->setConstructorArgs([
				$this->userSession,
				$this->activityManager,
				$this->tagger,
				$this->root,
			])
			->onlyMethods($methods)
			->getMock();
	}

	protected function tearDown(): void {
		\OC_User::setUserId('');
		$user = Server::get(IUserManager::class)->get($this->user);
		if ($user !== null) {
			$user->delete();
		}

		parent::tearDown();
	}

	public function testUpdateFileTags(): void {
		$tag1 = 'tag1';
		$tag2 = 'tag2';

		$subdir = $this->root->newFolder('subdir');
		$testFile = $subdir->newFile('test.txt');
		$testFile->putContent('test contents');

		$fileId = $testFile->getId();

		// set tags
		$this->tagService->updateFileTags('subdir/test.txt', [$tag1, $tag2]);

		$this->assertEquals([$fileId], $this->tagger->getIdsForTag($tag1));
		$this->assertEquals([$fileId], $this->tagger->getIdsForTag($tag2));

		// remove tag
		$this->tagService->updateFileTags('subdir/test.txt', [$tag2]);
		$this->assertEquals([], $this->tagger->getIdsForTag($tag1));
		$this->assertEquals([$fileId], $this->tagger->getIdsForTag($tag2));

		// clear tags
		$this->tagService->updateFileTags('subdir/test.txt', []);
		$this->assertEquals([], $this->tagger->getIdsForTag($tag1));
		$this->assertEquals([], $this->tagger->getIdsForTag($tag2));

		// non-existing file
		$caught = false;
		try {
			$this->tagService->updateFileTags('subdir/unexist.txt', [$tag1]);
		} catch (NotFoundException $e) {
			$caught = true;
		}
		$this->assertTrue($caught);

		$subdir->delete();
	}
}
