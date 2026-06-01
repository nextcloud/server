<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Comments;

use OC\Comments\Comment;
use OC\Comments\Manager;
use OC\EmojiHelper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsEventHandler;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IInitialStateService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class ManagerTest
 */
#[Group(name: 'DB')]
class ManagerTest extends TestCase {
	private IDBConnection $connection;
	private IRootFolder&MockObject $rootFolder;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		/** @psalm-suppress DeprecatedMethod */
		$sql = $this->connection->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*comments`');
		$this->connection->prepare($sql)->execute();
		/** @psalm-suppress DeprecatedMethod */
		$sql = $this->connection->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*reactions`');
		$this->connection->prepare($sql)->execute();
	}

	protected function addDatabaseEntry(?string $parentId, ?string $topmostParentId, ?\DateTimeInterface $creationDT = null, ?\DateTimeInterface $latestChildDT = null, $objectId = null, ?\DateTimeInterface $expireDate = null): string {
		$creationDT ??= new \DateTime();
		$latestChildDT ??= new \DateTime('yesterday');
		$objectId ??= 'file64';

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('comments')
			->values([
				'parent_id' => $qb->createNamedParameter($parentId),
				'topmost_parent_id' => $qb->createNamedParameter($topmostParentId),
				'children_count' => $qb->createNamedParameter(2),
				'actor_type' => $qb->createNamedParameter('users'),
				'actor_id' => $qb->createNamedParameter('alice'),
				'message' => $qb->createNamedParameter('nice one'),
				'verb' => $qb->createNamedParameter('comment'),
				'creation_timestamp' => $qb->createNamedParameter($creationDT, IQueryBuilder::PARAM_DATETIME_MUTABLE),
				'latest_child_timestamp' => $qb->createNamedParameter($latestChildDT, IQueryBuilder::PARAM_DATETIME_MUTABLE),
				'object_type' => $qb->createNamedParameter('files'),
				'object_id' => $qb->createNamedParameter($objectId),
				'expire_date' => $qb->createNamedParameter($expireDate, IQueryBuilder::PARAM_DATETIME_MUTABLE),
				'reference_id' => $qb->createNamedParameter('referenceId'),
				'meta_data' => $qb->createNamedParameter(json_encode(['last_edit_actor_id' => 'admin'])),
			])
			->executeStatement();

		return (string)$qb->getLastInsertId();
	}

	protected function getManager(): Manager {
		/** @psalm-suppress DeprecatedInterface No way around at the moment */
		return new Manager(
			$this->connection,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IConfig::class),
			$this->createMock(ITimeFactory::class),
			new EmojiHelper($this->connection),
			$this->createMock(IInitialStateService::class),
			$this->rootFolder,
			$this->createMock(IEventDispatcher::class),
		);
	}


	public function testGetCommentNotFound(): void {
		$this->expectException(NotFoundException::class);

		$manager = $this->getManager();
		$manager->get('22');
	}


	public function testGetCommentNotFoundInvalidInput(): void {
		$this->expectException(\InvalidArgumentException::class);

		$manager = $this->getManager();
		$manager->get('unexisting22');
	}

	public function testGetComment(): void {
		$manager = $this->getManager();

		$creationDT = new \DateTime('yesterday');
		$latestChildDT = new \DateTime();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('comments')
			->values([
				'parent_id' => $qb->createNamedParameter('2'),
				'topmost_parent_id' => $qb->createNamedParameter('1'),
				'children_count' => $qb->createNamedParameter(2),
				'actor_type' => $qb->createNamedParameter('users'),
				'actor_id' => $qb->createNamedParameter('alice'),
				'message' => $qb->createNamedParameter('nice one'),
				'verb' => $qb->createNamedParameter('comment'),
				'creation_timestamp' => $qb->createNamedParameter($creationDT, 'datetime'),
				'latest_child_timestamp' => $qb->createNamedParameter($latestChildDT, 'datetime'),
				'object_type' => $qb->createNamedParameter('files'),
				'object_id' => $qb->createNamedParameter('file64'),
				'reference_id' => $qb->createNamedParameter('referenceId'),
				'meta_data' => $qb->createNamedParameter(json_encode(['last_edit_actor_id' => 'admin'])),
			])
			->executeStatement();

		$id = (string)$qb->getLastInsertId();

		$comment = $manager->get($id);
		$this->assertInstanceOf(IComment::class, $comment);
		$this->assertSame($id, $comment->getId());
		$this->assertSame('2', $comment->getParentId());
		$this->assertSame('1', $comment->getTopmostParentId());
		$this->assertSame(2, $comment->getChildrenCount());
		$this->assertSame('users', $comment->getActorType());
		$this->assertSame('alice', $comment->getActorId());
		$this->assertSame('nice one', $comment->getMessage());
		$this->assertSame('comment', $comment->getVerb());
		$this->assertSame('files', $comment->getObjectType());
		$this->assertSame('file64', $comment->getObjectId());
		$this->assertEquals($creationDT->getTimestamp(), $comment->getCreationDateTime()->getTimestamp());
		$this->assertEquals($latestChildDT->getTimestamp(), $comment->getLatestChildDateTime()->getTimestamp());
		$this->assertEquals('referenceId', $comment->getReferenceId());
		$this->assertEquals(['last_edit_actor_id' => 'admin'], $comment->getMetaData());
	}

	public function testGetTreeNotFound(): void {
		$this->expectException(NotFoundException::class);

		$manager = $this->getManager();
		$manager->getTree('22');
	}

	public function testGetTreeNotFoundInvalidIpnut(): void {
		$this->expectException(\InvalidArgumentException::class);

		$manager = $this->getManager();
		$manager->getTree('unexisting22');
	}

	public function testGetTree(): void {
		$headId = $this->addDatabaseEntry('0', '0');

		$this->addDatabaseEntry($headId, $headId, new \DateTime('-3 hours'));
		$this->addDatabaseEntry($headId, $headId, new \DateTime('-2 hours'));
		$id = $this->addDatabaseEntry($headId, $headId, new \DateTime('-1 hour'));

		$manager = $this->getManager();
		$tree = $manager->getTree($headId);

		// Verifying the root comment
		$this->assertArrayHasKey('comment', $tree);
		$this->assertInstanceOf(IComment::class, $tree['comment']);
		$this->assertSame($headId, $tree['comment']->getId());
		$this->assertArrayHasKey('replies', $tree);
		$this->assertCount(3, $tree['replies']);

		// one level deep
		foreach ($tree['replies'] as $reply) {
			$this->assertInstanceOf(IComment::class, $reply['comment']);
			$this->assertSame((string)$id, $reply['comment']->getId());
			$this->assertCount(0, $reply['replies']);
			$id--;
		}
	}

	public function testGetTreeNoReplies(): void {
		$id = $this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();
		$tree = $manager->getTree($id);

		// Verifying the root comment
		$this->assertArrayHasKey('comment', $tree);
		$this->assertInstanceOf(IComment::class, $tree['comment']);
		$this->assertSame($id, $tree['comment']->getId());
		$this->assertArrayHasKey('replies', $tree);
		$this->assertCount(0, $tree['replies']);
	}

	public function testGetTreeWithLimitAndOffset(): void {
		$headId = $this->addDatabaseEntry('0', '0');

		$this->addDatabaseEntry($headId, $headId, new \DateTime('-3 hours'));
		$this->addDatabaseEntry($headId, $headId, new \DateTime('-2 hours'));
		$this->addDatabaseEntry($headId, $headId, new \DateTime('-1 hour'));
		$idToVerify = $this->addDatabaseEntry($headId, $headId, new \DateTime());

		$manager = $this->getManager();

		for ($offset = 0; $offset < 3; $offset += 2) {
			$tree = $manager->getTree($headId, 2, $offset);

			// Verifying the root comment
			$this->assertArrayHasKey('comment', $tree);
			$this->assertInstanceOf(IComment::class, $tree['comment']);
			$this->assertSame($headId, $tree['comment']->getId());
			$this->assertArrayHasKey('replies', $tree);
			$this->assertCount(2, $tree['replies']);

			// one level deep
			foreach ($tree['replies'] as $reply) {
				$this->assertInstanceOf(IComment::class, $reply['comment']);
				$this->assertSame((string)$idToVerify, (string)$reply['comment']->getId());
				$this->assertCount(0, $reply['replies']);
				$idToVerify--;
			}
		}
	}

	public function testGetForObject(): void {
		$this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();
		$comments = $manager->getForObject('files', 'file64');

		$this->assertIsArray($comments);
		$this->assertCount(1, $comments);
		$this->assertInstanceOf(IComment::class, $comments[0]);
		$this->assertSame('nice one', $comments[0]->getMessage());
	}

	public function testGetForObjectWithLimitAndOffset(): void {
		$this->addDatabaseEntry('0', '0', new \DateTime('-6 hours'));
		$this->addDatabaseEntry('0', '0', new \DateTime('-5 hours'));
		$this->addDatabaseEntry('1', '1', new \DateTime('-4 hours'));
		$this->addDatabaseEntry('0', '0', new \DateTime('-3 hours'));
		$this->addDatabaseEntry('2', '2', new \DateTime('-2 hours'));
		$this->addDatabaseEntry('2', '2', new \DateTime('-1 hours'));
		$idToVerify = $this->addDatabaseEntry('3', '1', new \DateTime());

		$manager = $this->getManager();
		$offset = 0;
		do {
			$comments = $manager->getForObject('files', 'file64', 3, $offset);

			$this->assertIsArray($comments);
			foreach ($comments as $key => $comment) {
				$this->assertInstanceOf(IComment::class, $comment);
				$this->assertSame('nice one', $comment->getMessage());
				$this->assertSame((string)$idToVerify, $comment->getId(), 'ID wrong for comment ' . $key . ' on offset: ' . $offset);
				$idToVerify--;
			}
			$offset += 3;
		} while (count($comments) > 0);
	}

	public function testGetForObjectWithDateTimeConstraint(): void {
		$this->addDatabaseEntry('0', '0', new \DateTime('-6 hours'));
		$this->addDatabaseEntry('0', '0', new \DateTime('-5 hours'));
		$id1 = $this->addDatabaseEntry('0', '0', new \DateTime('-3 hours'));
		$id2 = $this->addDatabaseEntry('2', '2', new \DateTime('-2 hours'));

		$manager = $this->getManager();
		$comments = $manager->getForObject('files', 'file64', 0, 0, new \DateTime('-4 hours'));

		$this->assertCount(2, $comments);
		$this->assertSame($id2, $comments[0]->getId());
		$this->assertSame($id1, $comments[1]->getId());
	}

	public function testGetForObjectWithLimitAndOffsetAndDateTimeConstraint(): void {
		$this->addDatabaseEntry('0', '0', new \DateTime('-7 hours'));
		$this->addDatabaseEntry('0', '0', new \DateTime('-6 hours'));
		$this->addDatabaseEntry('1', '1', new \DateTime('-5 hours'));
		$this->addDatabaseEntry('0', '0', new \DateTime('-3 hours'));
		$this->addDatabaseEntry('2', '2', new \DateTime('-2 hours'));
		$this->addDatabaseEntry('2', '2', new \DateTime('-1 hours'));
		$idToVerify = $this->addDatabaseEntry('3', '1', new \DateTime());

		$manager = $this->getManager();
		$offset = 0;
		do {
			$comments = $manager->getForObject('files', 'file64', 3, $offset, new \DateTime('-4 hours'));

			$this->assertIsArray($comments);
			foreach ($comments as $comment) {
				$this->assertInstanceOf(IComment::class, $comment);
				$this->assertSame('nice one', $comment->getMessage());
				$this->assertSame((string)$idToVerify, $comment->getId());
				$this->assertGreaterThanOrEqual(4, $comment->getId());
				$idToVerify--;
			}
			$offset += 3;
		} while (count($comments) > 0);
	}

	public function testGetNumberOfCommentsForObject(): void {
		for ($i = 1; $i < 5; $i++) {
			$this->addDatabaseEntry('0', '0');
		}

		$manager = $this->getManager();

		$amount = $manager->getNumberOfCommentsForObject('untype', '00');
		$this->assertSame(0, $amount);

		$amount = $manager->getNumberOfCommentsForObject('files', 'file64');
		$this->assertSame(4, $amount);
	}

	public function testGetNumberOfUnreadCommentsForFolder(): void {
		$folder = $this->createMock(Folder::class);
		$fileIds = range(1111, 1114);
		$children = array_map(function (int $id) {
			$file = $this->createMock(Folder::class);
			$file->method('getId')
				->willReturn($id);
			return $file;
		}, $fileIds);
		$folder->method('getId')->willReturn(1000);
		$folder->method('getDirectoryListing')->willReturn($children);
		$this->rootFolder->method('getFirstNodeById')
			->with($folder->getId())
			->willReturn($folder);

		// 2 comment for 1111 with 1 before read marker
		// 2 comments for 1112 with no read marker
		// 1 comment for 1113 before read marker
		// 1 comment for 1114 with no read marker
		$this->addDatabaseEntry('0', '0', null, null, $fileIds[1]);
		for ($i = 0; $i < 4; $i++) {
			$this->addDatabaseEntry('0', '0', null, null, $fileIds[$i]);
		}
		$this->addDatabaseEntry('0', '0', (new \DateTime())->modify('-2 days'), null, $fileIds[0]);
		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('comment_test');

		$manager = $this->getManager();

		$manager->setReadMark('files', (string)$fileIds[0], (new \DateTime())->modify('-1 days'), $user);
		$manager->setReadMark('files', (string)$fileIds[2], (new \DateTime()), $user);

		$amount = $manager->getNumberOfUnreadCommentsForFolder($folder->getId(), $user);
		$this->assertEquals([
			$fileIds[0] => 1,
			$fileIds[1] => 2,
			$fileIds[3] => 1,
		], $amount);
	}

	#[DataProvider(methodName: 'dataGetForObjectSince')]
	public function testGetForObjectSince(?int $lastKnown, string $order, int $limit, int $resultFrom, int $resultTo): void {
		$ids = [];
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();
		$comments = $manager->getForObjectSince('files', 'file64', ($lastKnown === null ? 0 : (int)$ids[$lastKnown]), $order, $limit);

		$expected = array_slice($ids, $resultFrom, $resultTo - $resultFrom + 1);
		if ($order === 'desc') {
			$expected = array_reverse($expected);
		}

		$this->assertSame($expected, array_map(static fn (IComment $c): string => $c->getId(), $comments));
	}

	public static function dataGetForObjectSince(): array {
		return [
			[null, 'asc', 20, 0, 4],
			[null, 'asc', 2, 0, 1],
			[null, 'desc', 20, 0, 4],
			[null, 'desc', 2, 3, 4],
			[1, 'asc', 20, 2, 4],
			[1, 'asc', 2, 2, 3],
			[3, 'desc', 20, 0, 2],
			[3, 'desc', 2, 1, 2],
		];
	}

	public static function invalidCreateArgsProvider(): array {
		return [
			['', 'aId-1', 'oType-1', 'oId-1'],
			['aType-1', '', 'oType-1', 'oId-1'],
			['aType-1', 'aId-1', '', 'oId-1'],
			['aType-1', 'aId-1', 'oType-1', ''],
			[1, 'aId-1', 'oType-1', 'oId-1'],
			['aType-1', 1, 'oType-1', 'oId-1'],
			['aType-1', 'aId-1', 1, 'oId-1'],
			['aType-1', 'aId-1', 'oType-1', 1],
		];
	}

	#[DataProvider(methodName: 'invalidCreateArgsProvider')]
	public function testCreateCommentInvalidArguments(string|int $aType, string|int $aId, string|int $oType, string|int $oId): void {
		$this->expectException(\InvalidArgumentException::class);

		$manager = $this->getManager();
		$manager->create($aType, $aId, $oType, $oId);
	}

	public function testCreateComment(): void {
		$actorType = 'bot';
		$actorId = 'bob';
		$objectType = 'weather';
		$objectId = 'bielefeld';

		$comment = $this->getManager()->create($actorType, $actorId, $objectType, $objectId);
		$this->assertInstanceOf(IComment::class, $comment);
		$this->assertSame($actorType, $comment->getActorType());
		$this->assertSame($actorId, $comment->getActorId());
		$this->assertSame($objectType, $comment->getObjectType());
		$this->assertSame($objectId, $comment->getObjectId());
	}


	public function testDelete(): void {
		$this->expectException(NotFoundException::class);

		$manager = $this->getManager();

		$done = $manager->delete('404');
		$this->assertFalse($done);

		$done = $manager->delete('%');
		$this->assertFalse($done);

		$done = $manager->delete('');
		$this->assertFalse($done);

		$id = $this->addDatabaseEntry('0', '0');
		$comment = $manager->get($id);
		$this->assertInstanceOf(IComment::class, $comment);
		$done = $manager->delete($id);
		$this->assertTrue($done);
		$manager->get($id);
	}

	#[DataProvider(methodName: 'providerTestSave')]
	public function testSave(string $message, string $actorId, string $verb, ?string $parentId, ?string $id = ''): IComment {
		$manager = $this->getManager();
		$comment = new Comment();
		$comment
			->setId($id)
			->setActor('users', $actorId)
			->setObject('files', 'file64')
			->setMessage($message)
			->setVerb($verb);
		if ($parentId) {
			$comment->setParentId($parentId);
		}

		$saveSuccessful = $manager->save($comment);
		$this->assertTrue($saveSuccessful, 'Comment saving was not successful');
		$this->assertNotEquals('', $comment->getId(), 'Comment ID should not be empty');
		$this->assertNotEquals('0', $comment->getId(), 'Comment ID should not be string \'0\'');
		$this->assertNotNull($comment->getCreationDateTime(), 'Comment creation date should not be null');

		$loadedComment = $manager->get($comment->getId());
		$this->assertSame($comment->getMessage(), $loadedComment->getMessage(), 'Comment message should match');
		$this->assertEquals($comment->getCreationDateTime()->getTimestamp(), $loadedComment->getCreationDateTime()->getTimestamp(), 'Comment creation date should match');
		return $comment;
	}

	public static function providerTestSave(): array {
		return [
			['very beautiful, I am impressed!', 'alice', 'comment', null],
		];
	}

	public function testSaveUpdate(): void {
		$manager = $this->getManager();
		$comment = new Comment();
		$comment
			->setActor('users', 'alice')
			->setObject('files', 'file64')
			->setMessage('very beautiful, I am impressed!')
			->setVerb('comment')
			->setExpireDate(new \DateTime('+2 hours'));

		$manager->save($comment);

		$loadedComment = $manager->get($comment->getId());
		// Compare current object with database values
		$this->assertSame($comment->getMessage(), $loadedComment->getMessage());
		$this->assertSame(
			$comment->getExpireDate()->format('Y-m-d H:i:s'),
			$loadedComment->getExpireDate()->format('Y-m-d H:i:s')
		);

		// Preserve the original comment to compare after update
		$original = clone $comment;

		// Update values
		$comment->setMessage('very beautiful, I am really so much impressed!')
			->setExpireDate(new \DateTime('+1 hours'));
		$manager->save($comment);

		$loadedComment = $manager->get($comment->getId());
		// Compare current object with database values
		$this->assertSame($comment->getMessage(), $loadedComment->getMessage());
		$this->assertSame(
			$comment->getExpireDate()->format('Y-m-d H:i:s'),
			$loadedComment->getExpireDate()->format('Y-m-d H:i:s')
		);

		// Compare original object with database values
		$this->assertNotSame($original->getMessage(), $loadedComment->getMessage());
		$this->assertNotSame(
			$original->getExpireDate()->format('Y-m-d H:i:s'),
			$loadedComment->getExpireDate()->format('Y-m-d H:i:s')
		);
	}


	public function testSaveUpdateException(): void {
		$manager = $this->getManager();
		$comment = new Comment();
		$comment
			->setActor('users', 'alice')
			->setObject('files', 'file64')
			->setMessage('very beautiful, I am impressed!')
			->setVerb('comment');

		$manager->save($comment);

		$manager->delete($comment->getId());

		$comment->setMessage('very beautiful, I am really so much impressed!');
		$this->expectException(NotFoundException::class);
		$manager->save($comment);
	}


	public function testSaveIncomplete(): void {

		$manager = $this->getManager();
		$comment = new Comment();
		$comment->setMessage('from no one to nothing');

		$this->expectException(\UnexpectedValueException::class);
		$manager->save($comment);
	}

	public function testSaveAsChild(): void {
		$id = $this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();

		for ($i = 0; $i < 3; $i++) {
			$comment = new Comment();
			$comment
				->setActor('users', 'alice')
				->setObject('files', 'file64')
				->setParentId($id)
				->setMessage('full ack')
				->setVerb('comment')
				// setting the creation time avoids using sleep() while making sure to test with different timestamps
				->setCreationDateTime(new \DateTime('+' . $i . ' minutes'));

			$manager->save($comment);

			$this->assertSame($id, $comment->getTopmostParentId());
			$parentComment = $manager->get($id);
			$this->assertSame($i + 1, $parentComment->getChildrenCount());
			$this->assertEquals($comment->getCreationDateTime()->getTimestamp(), $parentComment->getLatestChildDateTime()->getTimestamp());
		}
	}

	public static function invalidActorArgsProvider(): array {
		return
			[
				['', ''],
				[1, 'alice'],
				['users', 1],
			];
	}

	#[DataProvider(methodName: 'invalidActorArgsProvider')]
	public function testDeleteReferencesOfActorInvalidInput(string|int $type, string|int $id): void {
		$this->expectException(\InvalidArgumentException::class);

		$manager = $this->getManager();
		$manager->deleteReferencesOfActor($type, $id);
	}

	public function testDeleteReferencesOfActor(): void {
		$ids = [];
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();

		// just to make sure they are really set, with correct actor data
		$comment = $manager->get($ids[1]);
		$this->assertSame('users', $comment->getActorType());
		$this->assertSame('alice', $comment->getActorId());

		$wasSuccessful = $manager->deleteReferencesOfActor('users', 'alice');
		$this->assertTrue($wasSuccessful);

		foreach ($ids as $id) {
			$comment = $manager->get($id);
			$this->assertSame(ICommentsManager::DELETED_USER, $comment->getActorType());
			$this->assertSame(ICommentsManager::DELETED_USER, $comment->getActorId());
		}

		// actor info is gone from DB, but when database interaction is alright,
		// we still expect to get true back
		$wasSuccessful = $manager->deleteReferencesOfActor('users', 'alice');
		$this->assertTrue($wasSuccessful);
	}

	public function testDeleteReferencesOfActorWithUserManagement(): void {
		$user = Server::get(IUserManager::class)->createUser('xenia', 'NotAnEasyPassword123456+');
		$this->assertInstanceOf(IUser::class, $user);

		$manager = Server::get(ICommentsManager::class);
		$comment = $manager->create('users', $user->getUID(), 'files', 'file64');
		$comment
			->setMessage('Most important comment I ever left on the Internet.')
			->setVerb('comment');
		$status = $manager->save($comment);
		$this->assertTrue($status);

		$commentID = $comment->getId();
		$user->delete();

		$comment = $manager->get($commentID);
		$this->assertSame(ICommentsManager::DELETED_USER, $comment->getActorType());
		$this->assertSame(ICommentsManager::DELETED_USER, $comment->getActorId());
	}

	public static function invalidObjectArgsProvider(): array {
		return
			[
				['', ''],
				[1, 'file64'],
				['files', 1],
			];
	}

	#[DataProvider(methodName: 'invalidObjectArgsProvider')]
	public function testDeleteCommentsAtObjectInvalidInput(string|int $type, string|int $id): void {
		$this->expectException(\InvalidArgumentException::class);

		$manager = $this->getManager();
		$manager->deleteCommentsAtObject($type, $id);
	}

	public function testDeleteCommentsAtObject(): void {
		$ids = [];
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');
		$ids[] = $this->addDatabaseEntry('0', '0');

		$manager = $this->getManager();

		// just to make sure they are really set, with correct actor data
		$comment = $manager->get($ids[1]);
		$this->assertSame('files', $comment->getObjectType());
		$this->assertSame('file64', $comment->getObjectId());

		$wasSuccessful = $manager->deleteCommentsAtObject('files', 'file64');
		$this->assertTrue($wasSuccessful);

		$verified = 0;
		foreach ($ids as $id) {
			try {
				$manager->get($id);
			} catch (NotFoundException) {
				$verified++;
			}
		}
		$this->assertSame(3, $verified);

		// actor info is gone from DB, but when database interaction is alright,
		// we still expect to get true back
		$wasSuccessful = $manager->deleteCommentsAtObject('files', 'file64');
		$this->assertTrue($wasSuccessful);
	}

	public function testDeleteCommentsExpiredAtObjectTypeAndId(): void {
		$ids = [];
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('+2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('+2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('+2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('-2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('-2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, null, new \DateTime('-2 hours'));

		/** @psalm-suppress DeprecatedInterface No way around at the moment */
		$manager = new Manager(
			$this->connection,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IConfig::class),
			Server::get(ITimeFactory::class),
			new EmojiHelper($this->connection),
			$this->createMock(IInitialStateService::class),
			$this->rootFolder,
			$this->createMock(IEventDispatcher::class)
		);

		// just to make sure they are really set, with correct actor data
		$comment = $manager->get($ids[1]);
		$this->assertSame('files', $comment->getObjectType());
		$this->assertSame('file64', $comment->getObjectId());

		$deleted = $manager->deleteCommentsExpiredAtObject('files', 'file64');
		$this->assertTrue($deleted);

		$deleted = 0;
		$exists = 0;
		foreach ($ids as $id) {
			try {
				$manager->get($id);
				$exists++;
			} catch (NotFoundException) {
				$deleted++;
			}
		}
		$this->assertSame(3, $exists);
		$this->assertSame(3, $deleted);

		// actor info is gone from DB, but when database interaction is alright,
		// we still expect to get true back
		$deleted = $manager->deleteCommentsExpiredAtObject('files', 'file64');
		$this->assertFalse($deleted);
	}

	public function testDeleteCommentsExpiredAtObjectType(): void {
		$ids = [];
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file1', new \DateTime('-2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file2', new \DateTime('-2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file3', new \DateTime('-2 hours'));
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file3', new \DateTime());
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file3', new \DateTime());
		$ids[] = $this->addDatabaseEntry('0', '0', null, null, 'file3', new \DateTime());

		/** @psalm-suppress DeprecatedInterface No way around at the moment */
		$manager = new Manager(
			$this->connection,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IConfig::class),
			Server::get(ITimeFactory::class),
			new EmojiHelper($this->connection),
			$this->createMock(IInitialStateService::class),
			$this->rootFolder,
			$this->createMock(IEventDispatcher::class)
		);

		$deleted = $manager->deleteCommentsExpiredAtObject('files');
		$this->assertTrue($deleted);

		$deleted = 0;
		$exists = 0;
		foreach ($ids as $id) {
			try {
				$manager->get($id);
				$exists++;
			} catch (NotFoundException) {
				$deleted++;
			}
		}
		$this->assertSame(0, $exists);
		$this->assertSame(6, $deleted);

		// actor info is gone from DB, but when database interaction is alright,
		// we still expect to get true back
		$deleted = $manager->deleteCommentsExpiredAtObject('files');
		$this->assertFalse($deleted);
	}

	public function testSetMarkRead(): void {
		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$dateTimeSet = new \DateTime();

		$manager = $this->getManager();
		$manager->setReadMark('robot', '36', $dateTimeSet, $user);

		$dateTimeGet = $manager->getReadMark('robot', '36', $user);

		$this->assertEquals($dateTimeSet->getTimestamp(), $dateTimeGet->getTimestamp());
	}

	public function testSetMarkReadUpdate(): void {
		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$dateTimeSet = new \DateTime('yesterday');

		$manager = $this->getManager();
		$manager->setReadMark('robot', '36', $dateTimeSet, $user);

		$dateTimeSet = new \DateTime('today');
		$manager->setReadMark('robot', '36', $dateTimeSet, $user);

		$dateTimeGet = $manager->getReadMark('robot', '36', $user);

		$this->assertEquals($dateTimeSet, $dateTimeGet);
	}

	public function testReadMarkDeleteUser(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$dateTimeSet = new \DateTime();

		$manager = $this->getManager();
		$manager->setReadMark('robot', '36', $dateTimeSet, $user);

		$manager->deleteReadMarksFromUser($user);
		$dateTimeGet = $manager->getReadMark('robot', '36', $user);

		$this->assertNull($dateTimeGet);
	}

	public function testReadMarkDeleteObject(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$dateTimeSet = new \DateTime();

		$manager = $this->getManager();
		$manager->setReadMark('robot', '36', $dateTimeSet, $user);

		$manager->deleteReadMarksOnObject('robot', '36');
		$dateTimeGet = $manager->getReadMark('robot', '36', $user);

		$this->assertNull($dateTimeGet);
	}

	public function testSendEvent(): void {
		/** @psalm-suppress DeprecatedInterface Test for deprecated interface */
		$handler1 = $this->createMock(ICommentsEventHandler::class);
		$handler1->expects($this->exactly(4))
			->method('handle');

		/** @psalm-suppress DeprecatedInterface Test for deprecated interface */
		$handler2 = $this->createMock(ICommentsEventHandler::class);
		$handler1->expects($this->exactly(4))
			->method('handle');

		$manager = $this->getManager();
		$manager->registerEventHandler(function () use ($handler1) {
			return $handler1;
		});
		$manager->registerEventHandler(function () use ($handler2) {
			return $handler2;
		});

		$comment = new Comment();
		$comment
			->setActor('users', 'alice')
			->setObject('files', 'file64')
			->setMessage('very beautiful, I am impressed!')
			->setVerb('comment');

		// Add event
		$manager->save($comment);

		// Update event
		$comment->setMessage('Different topic');
		$manager->save($comment);

		// Delete event
		$manager->delete($comment->getId());
	}

	public function testResolveDisplayName(): void {
		$manager = $this->getManager();

		$planetClosure = function ($name) {
			return ucfirst($name);
		};

		$galaxyClosure = function ($name) {
			return strtoupper($name);
		};

		$manager->registerDisplayNameResolver('planet', $planetClosure);
		$manager->registerDisplayNameResolver('galaxy', $galaxyClosure);

		$this->assertSame('Neptune', $manager->resolveDisplayName('planet', 'neptune'));
		$this->assertSame('SOMBRERO', $manager->resolveDisplayName('galaxy', 'sombrero'));
	}


	public function testRegisterResolverDuplicate(): void {
		$this->expectException(\OutOfBoundsException::class);

		$manager = $this->getManager();

		$planetClosure = static fn (string $name): string => ucfirst($name);
		$manager->registerDisplayNameResolver('planet', $planetClosure);
		$manager->registerDisplayNameResolver('planet', $planetClosure);
	}


	public function testResolveDisplayNameUnregisteredType(): void {
		$this->expectException(\OutOfBoundsException::class);

		$manager = $this->getManager();

		$planetClosure = static fn (string $name): string => ucfirst($name);
		$manager->registerDisplayNameResolver('planet', $planetClosure);
		$manager->resolveDisplayName('galaxy', 'sombrero');
	}

	public function testResolveDisplayNameDirtyResolver(): void {
		$manager = $this->getManager();

		$planetClosure = static fn (): null => null;
		$manager->registerDisplayNameResolver('planet', $planetClosure);
		$this->assertIsString($manager->resolveDisplayName('planet', 'neptune'));
	}

	private function skipIfNotSupport4ByteUTF(): void {
		if (!$this->getManager()->supportReactions()) {
			$this->markTestSkipped('MySQL doesn\'t support 4 byte UTF-8');
		}
	}

	#[DataProvider(methodName: 'providerTestReactionAddAndDelete')]
	public function testReactionAddAndDelete(array $comments, array $reactionsExpected): void {
		$this->skipIfNotSupport4ByteUTF();
		$manager = $this->getManager();

		$processedComments = $this->proccessComments($comments);
		$comment = end($processedComments);
		if ($comment->getParentId()) {
			$parent = $manager->get($comment->getParentId());
			$this->assertEqualsCanonicalizing($reactionsExpected, $parent->getReactions());
		}
	}

	public static function providerTestReactionAddAndDelete(): array {
		return[
			[
				[
					['message', 'alice', 'comment', null],
				], [],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
				], ['👍' => 1],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'alice', 'reaction', 'message#alice'],
				], ['👍' => 1],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
				], ['👍' => 2],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction_deleted', 'message#alice'],
				], ['👍' => 1],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
					['👍', 'alice', 'reaction_deleted', 'message#alice'],
					['👍', 'frank', 'reaction_deleted', 'message#alice'],
				], [],
			],
		];
	}

	/**
	 * @param array $data
	 * @return array<string, IComment>
	 */
	private function proccessComments(array $data): array {
		$this->connection->beginTransaction();
		/** @var array<string, IComment> $comments */
		$comments = [];
		foreach ($data as $comment) {
			[$message, $actorId, $verb, $parentText] = $comment;
			$parentId = null;
			if ($parentText) {
				$parentId = $comments[$parentText]->getId();
			}
			$id = '';
			if ($verb === 'reaction_deleted') {
				$id = $comments[$message . '#' . $actorId]->getId();
			}
			$comment = $this->testSave($message, $actorId, $verb, $parentId, $id);
			$comments[$comment->getMessage() . '#' . $comment->getActorId()] = $comment;
		}
		$this->connection->commit();
		return $comments;
	}

	#[DataProvider(methodName: 'providerTestRetrieveAllReactions')]
	public function testRetrieveAllReactions(array $comments, array $expected): void {
		$this->skipIfNotSupport4ByteUTF();
		$manager = $this->getManager();

		$processedComments = $this->proccessComments($comments);
		$comment = reset($processedComments);
		$all = $manager->retrieveAllReactions((int)$comment->getId());
		$actual = array_map(static function (IComment $row): array {
			return [
				$row->getActorId(),
				$row->getMessage(),
			];
		}, $all);

		usort($actual, static fn (array $a, array $b): int => $a[1] <=> $b[1]);
		usort($expected, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

		$this->assertEqualsCanonicalizing($expected, $actual);
	}

	public static function providerTestRetrieveAllReactions(): array {
		return [
			[
				[
					['message', 'alice', 'comment', null],
				],
				[],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
				],
				[
					['👍', 'alice'],
					['👍', 'frank'],
				],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
				],
				[
					['👍', 'alice'],
					['👍', 'frank'],
				],
			],
			[# 600 reactions to cover chunk size when retrieve comments of reactions.
				[
					['message', 'alice', 'comment', null],
					['😀', 'alice', 'reaction', 'message#alice'],
					['😃', 'alice', 'reaction', 'message#alice'],
					['😄', 'alice', 'reaction', 'message#alice'],
					['😁', 'alice', 'reaction', 'message#alice'],
					['😆', 'alice', 'reaction', 'message#alice'],
					['😅', 'alice', 'reaction', 'message#alice'],
					['😂', 'alice', 'reaction', 'message#alice'],
					['🤣', 'alice', 'reaction', 'message#alice'],
					['🥲', 'alice', 'reaction', 'message#alice'],
					['🥹', 'alice', 'reaction', 'message#alice'],
					['☺️', 'alice', 'reaction', 'message#alice'],
					['😊', 'alice', 'reaction', 'message#alice'],
					['😇', 'alice', 'reaction', 'message#alice'],
					['🙂', 'alice', 'reaction', 'message#alice'],
					['🙃', 'alice', 'reaction', 'message#alice'],
					['😉', 'alice', 'reaction', 'message#alice'],
					['😌', 'alice', 'reaction', 'message#alice'],
					['😍', 'alice', 'reaction', 'message#alice'],
					['🥰', 'alice', 'reaction', 'message#alice'],
					['😘', 'alice', 'reaction', 'message#alice'],
					['😗', 'alice', 'reaction', 'message#alice'],
					['😙', 'alice', 'reaction', 'message#alice'],
					['😚', 'alice', 'reaction', 'message#alice'],
					['😋', 'alice', 'reaction', 'message#alice'],
					['😛', 'alice', 'reaction', 'message#alice'],
					['😝', 'alice', 'reaction', 'message#alice'],
					['😜', 'alice', 'reaction', 'message#alice'],
					['🤪', 'alice', 'reaction', 'message#alice'],
					['🤨', 'alice', 'reaction', 'message#alice'],
					['🧐', 'alice', 'reaction', 'message#alice'],
					['🤓', 'alice', 'reaction', 'message#alice'],
					['😎', 'alice', 'reaction', 'message#alice'],
					['🥸', 'alice', 'reaction', 'message#alice'],
					['🤩', 'alice', 'reaction', 'message#alice'],
					['🥳', 'alice', 'reaction', 'message#alice'],
					['😏', 'alice', 'reaction', 'message#alice'],
					['😒', 'alice', 'reaction', 'message#alice'],
					['😞', 'alice', 'reaction', 'message#alice'],
					['😔', 'alice', 'reaction', 'message#alice'],
					['😟', 'alice', 'reaction', 'message#alice'],
					['😕', 'alice', 'reaction', 'message#alice'],
					['🙁', 'alice', 'reaction', 'message#alice'],
					['☹️', 'alice', 'reaction', 'message#alice'],
					['😣', 'alice', 'reaction', 'message#alice'],
					['😖', 'alice', 'reaction', 'message#alice'],
					['😫', 'alice', 'reaction', 'message#alice'],
					['😩', 'alice', 'reaction', 'message#alice'],
					['🥺', 'alice', 'reaction', 'message#alice'],
					['😢', 'alice', 'reaction', 'message#alice'],
					['😭', 'alice', 'reaction', 'message#alice'],
					['😮‍💨', 'alice', 'reaction', 'message#alice'],
					['😤', 'alice', 'reaction', 'message#alice'],
					['😠', 'alice', 'reaction', 'message#alice'],
					['😡', 'alice', 'reaction', 'message#alice'],
					['🤬', 'alice', 'reaction', 'message#alice'],
					['🤯', 'alice', 'reaction', 'message#alice'],
					['😳', 'alice', 'reaction', 'message#alice'],
					['🥵', 'alice', 'reaction', 'message#alice'],
					['🥶', 'alice', 'reaction', 'message#alice'],
					['😱', 'alice', 'reaction', 'message#alice'],
					['😨', 'alice', 'reaction', 'message#alice'],
					['😰', 'alice', 'reaction', 'message#alice'],
					['😥', 'alice', 'reaction', 'message#alice'],
					['😓', 'alice', 'reaction', 'message#alice'],
					['🫣', 'alice', 'reaction', 'message#alice'],
					['🤗', 'alice', 'reaction', 'message#alice'],
					['🫡', 'alice', 'reaction', 'message#alice'],
					['🤔', 'alice', 'reaction', 'message#alice'],
					['🫢', 'alice', 'reaction', 'message#alice'],
					['🤭', 'alice', 'reaction', 'message#alice'],
					['🤫', 'alice', 'reaction', 'message#alice'],
					['🤥', 'alice', 'reaction', 'message#alice'],
					['😶', 'alice', 'reaction', 'message#alice'],
					['😶‍🌫️', 'alice', 'reaction', 'message#alice'],
					['😐', 'alice', 'reaction', 'message#alice'],
					['😑', 'alice', 'reaction', 'message#alice'],
					['😬', 'alice', 'reaction', 'message#alice'],
					['🫠', 'alice', 'reaction', 'message#alice'],
					['🙄', 'alice', 'reaction', 'message#alice'],
					['😯', 'alice', 'reaction', 'message#alice'],
					['😦', 'alice', 'reaction', 'message#alice'],
					['😧', 'alice', 'reaction', 'message#alice'],
					['😮', 'alice', 'reaction', 'message#alice'],
					['😲', 'alice', 'reaction', 'message#alice'],
					['🥱', 'alice', 'reaction', 'message#alice'],
					['😴', 'alice', 'reaction', 'message#alice'],
					['🤤', 'alice', 'reaction', 'message#alice'],
					['😪', 'alice', 'reaction', 'message#alice'],
					['😵', 'alice', 'reaction', 'message#alice'],
					['😵‍💫', 'alice', 'reaction', 'message#alice'],
					['🫥', 'alice', 'reaction', 'message#alice'],
					['🤐', 'alice', 'reaction', 'message#alice'],
					['🥴', 'alice', 'reaction', 'message#alice'],
					['🤢', 'alice', 'reaction', 'message#alice'],
					['🤮', 'alice', 'reaction', 'message#alice'],
					['🤧', 'alice', 'reaction', 'message#alice'],
					['😷', 'alice', 'reaction', 'message#alice'],
					['🤒', 'alice', 'reaction', 'message#alice'],
					['🤕', 'alice', 'reaction', 'message#alice'],
					['🤑', 'alice', 'reaction', 'message#alice'],
					['🤠', 'alice', 'reaction', 'message#alice'],
					['😈', 'alice', 'reaction', 'message#alice'],
					['👿', 'alice', 'reaction', 'message#alice'],
					['👹', 'alice', 'reaction', 'message#alice'],
					['👺', 'alice', 'reaction', 'message#alice'],
					['🤡', 'alice', 'reaction', 'message#alice'],
					['💩', 'alice', 'reaction', 'message#alice'],
					['👻', 'alice', 'reaction', 'message#alice'],
					['💀', 'alice', 'reaction', 'message#alice'],
					['☠️', 'alice', 'reaction', 'message#alice'],
					['👽', 'alice', 'reaction', 'message#alice'],
					['👾', 'alice', 'reaction', 'message#alice'],
					['🤖', 'alice', 'reaction', 'message#alice'],
					['🎃', 'alice', 'reaction', 'message#alice'],
					['😺', 'alice', 'reaction', 'message#alice'],
					['😸', 'alice', 'reaction', 'message#alice'],
					['😹', 'alice', 'reaction', 'message#alice'],
					['😻', 'alice', 'reaction', 'message#alice'],
					['😼', 'alice', 'reaction', 'message#alice'],
					['😽', 'alice', 'reaction', 'message#alice'],
					['🙀', 'alice', 'reaction', 'message#alice'],
					['😿', 'alice', 'reaction', 'message#alice'],
					['😾', 'alice', 'reaction', 'message#alice'],
					['👶', 'alice', 'reaction', 'message#alice'],
					['👧', 'alice', 'reaction', 'message#alice'],
					['🧒', 'alice', 'reaction', 'message#alice'],
					['👦', 'alice', 'reaction', 'message#alice'],
					['👩', 'alice', 'reaction', 'message#alice'],
					['🧑', 'alice', 'reaction', 'message#alice'],
					['👨', 'alice', 'reaction', 'message#alice'],
					['👩‍🦱', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦱', 'alice', 'reaction', 'message#alice'],
					['👨‍🦱', 'alice', 'reaction', 'message#alice'],
					['👩‍🦰', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦰', 'alice', 'reaction', 'message#alice'],
					['👨‍🦰', 'alice', 'reaction', 'message#alice'],
					['👱‍♀️', 'alice', 'reaction', 'message#alice'],
					['👱', 'alice', 'reaction', 'message#alice'],
					['👱‍♂️', 'alice', 'reaction', 'message#alice'],
					['👩‍🦳', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦳', 'alice', 'reaction', 'message#alice'],
					['👨‍🦳', 'alice', 'reaction', 'message#alice'],
					['👩‍🦲', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦲', 'alice', 'reaction', 'message#alice'],
					['👨‍🦲', 'alice', 'reaction', 'message#alice'],
					['🧔‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧔', 'alice', 'reaction', 'message#alice'],
					['🧔‍♂️', 'alice', 'reaction', 'message#alice'],
					['👵', 'alice', 'reaction', 'message#alice'],
					['🧓', 'alice', 'reaction', 'message#alice'],
					['👴', 'alice', 'reaction', 'message#alice'],
					['👲', 'alice', 'reaction', 'message#alice'],
					['👳‍♀️', 'alice', 'reaction', 'message#alice'],
					['👳', 'alice', 'reaction', 'message#alice'],
					['👳‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧕', 'alice', 'reaction', 'message#alice'],
					['👮‍♀️', 'alice', 'reaction', 'message#alice'],
					['👮', 'alice', 'reaction', 'message#alice'],
					['👮‍♂️', 'alice', 'reaction', 'message#alice'],
					['👷‍♀️', 'alice', 'reaction', 'message#alice'],
					['👷', 'alice', 'reaction', 'message#alice'],
					['👷‍♂️', 'alice', 'reaction', 'message#alice'],
					['💂‍♀️', 'alice', 'reaction', 'message#alice'],
					['💂', 'alice', 'reaction', 'message#alice'],
					['💂‍♂️', 'alice', 'reaction', 'message#alice'],
					['🕵️‍♀️', 'alice', 'reaction', 'message#alice'],
					['🕵️', 'alice', 'reaction', 'message#alice'],
					['🕵️‍♂️', 'alice', 'reaction', 'message#alice'],
					['👩‍⚕️', 'alice', 'reaction', 'message#alice'],
					['🧑‍⚕️', 'alice', 'reaction', 'message#alice'],
					['👨‍⚕️', 'alice', 'reaction', 'message#alice'],
					['👩‍🌾', 'alice', 'reaction', 'message#alice'],
					['🧑‍🌾', 'alice', 'reaction', 'message#alice'],
					['👨‍🌾', 'alice', 'reaction', 'message#alice'],
					['👩‍🍳', 'alice', 'reaction', 'message#alice'],
					['🧑‍🍳', 'alice', 'reaction', 'message#alice'],
					['👨‍🍳', 'alice', 'reaction', 'message#alice'],
					['👩‍🎓', 'alice', 'reaction', 'message#alice'],
					['🧑‍🎓', 'alice', 'reaction', 'message#alice'],
					['👨‍🎓', 'alice', 'reaction', 'message#alice'],
					['👩‍🎤', 'alice', 'reaction', 'message#alice'],
					['🧑‍🎤', 'alice', 'reaction', 'message#alice'],
					['👨‍🎤', 'alice', 'reaction', 'message#alice'],
					['👩‍🏫', 'alice', 'reaction', 'message#alice'],
					['🧑‍🏫', 'alice', 'reaction', 'message#alice'],
					['👨‍🏫', 'alice', 'reaction', 'message#alice'],
					['👩‍🏭', 'alice', 'reaction', 'message#alice'],
					['🧑‍🏭', 'alice', 'reaction', 'message#alice'],
					['👨‍🏭', 'alice', 'reaction', 'message#alice'],
					['👩‍💻', 'alice', 'reaction', 'message#alice'],
					['🧑‍💻', 'alice', 'reaction', 'message#alice'],
					['👨‍💻', 'alice', 'reaction', 'message#alice'],
					['👩‍💼', 'alice', 'reaction', 'message#alice'],
					['🧑‍💼', 'alice', 'reaction', 'message#alice'],
					['👨‍💼', 'alice', 'reaction', 'message#alice'],
					['👩‍🔧', 'alice', 'reaction', 'message#alice'],
					['🧑‍🔧', 'alice', 'reaction', 'message#alice'],
					['👨‍🔧', 'alice', 'reaction', 'message#alice'],
					['👩‍🔬', 'alice', 'reaction', 'message#alice'],
					['🧑‍🔬', 'alice', 'reaction', 'message#alice'],
					['👨‍🔬', 'alice', 'reaction', 'message#alice'],
					['👩‍🎨', 'alice', 'reaction', 'message#alice'],
					['🧑‍🎨', 'alice', 'reaction', 'message#alice'],
					['👨‍🎨', 'alice', 'reaction', 'message#alice'],
					['👩‍🚒', 'alice', 'reaction', 'message#alice'],
					['🧑‍🚒', 'alice', 'reaction', 'message#alice'],
					['👨‍🚒', 'alice', 'reaction', 'message#alice'],
					['👩‍✈️', 'alice', 'reaction', 'message#alice'],
					['🧑‍✈️', 'alice', 'reaction', 'message#alice'],
					['👨‍✈️', 'alice', 'reaction', 'message#alice'],
					['👩‍🚀', 'alice', 'reaction', 'message#alice'],
					['🧑‍🚀', 'alice', 'reaction', 'message#alice'],
					['👨‍🚀', 'alice', 'reaction', 'message#alice'],
					['👩‍⚖️', 'alice', 'reaction', 'message#alice'],
					['🧑‍⚖️', 'alice', 'reaction', 'message#alice'],
					['👨‍⚖️', 'alice', 'reaction', 'message#alice'],
					['👰‍♀️', 'alice', 'reaction', 'message#alice'],
					['👰', 'alice', 'reaction', 'message#alice'],
					['👰‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤵‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤵', 'alice', 'reaction', 'message#alice'],
					['🤵‍♂️', 'alice', 'reaction', 'message#alice'],
					['👸', 'alice', 'reaction', 'message#alice'],
					['🫅', 'alice', 'reaction', 'message#alice'],
					['🤴', 'alice', 'reaction', 'message#alice'],
					['🥷', 'alice', 'reaction', 'message#alice'],
					['🦸‍♀️', 'alice', 'reaction', 'message#alice'],
					['🦸', 'alice', 'reaction', 'message#alice'],
					['🦸‍♂️', 'alice', 'reaction', 'message#alice'],
					['🦹‍♀️', 'alice', 'reaction', 'message#alice'],
					['🦹', 'alice', 'reaction', 'message#alice'],
					['🦹‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤶', 'alice', 'reaction', 'message#alice'],
					['🧑‍🎄', 'alice', 'reaction', 'message#alice'],
					['🎅', 'alice', 'reaction', 'message#alice'],
					['🧙‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧙', 'alice', 'reaction', 'message#alice'],
					['🧙‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧝‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧝', 'alice', 'reaction', 'message#alice'],
					['🧝‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧛‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧛', 'alice', 'reaction', 'message#alice'],
					['🧛‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧟‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧟', 'alice', 'reaction', 'message#alice'],
					['🧟‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧞‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧞', 'alice', 'reaction', 'message#alice'],
					['🧞‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧜‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧜', 'alice', 'reaction', 'message#alice'],
					['🧜‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧚‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧚', 'alice', 'reaction', 'message#alice'],
					['🧚‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧌', 'alice', 'reaction', 'message#alice'],
					['👼', 'alice', 'reaction', 'message#alice'],
					['🤰', 'alice', 'reaction', 'message#alice'],
					['🫄', 'alice', 'reaction', 'message#alice'],
					['🫃', 'alice', 'reaction', 'message#alice'],
					['🤱', 'alice', 'reaction', 'message#alice'],
					['👩‍🍼', 'alice', 'reaction', 'message#alice'],
					['🧑‍🍼', 'alice', 'reaction', 'message#alice'],
					['👨‍🍼', 'alice', 'reaction', 'message#alice'],
					['🙇‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙇', 'alice', 'reaction', 'message#alice'],
					['🙇‍♂️', 'alice', 'reaction', 'message#alice'],
					['💁‍♀️', 'alice', 'reaction', 'message#alice'],
					['💁', 'alice', 'reaction', 'message#alice'],
					['💁‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙅‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙅', 'alice', 'reaction', 'message#alice'],
					['🙅‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙆‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙆', 'alice', 'reaction', 'message#alice'],
					['🙆‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙋‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙋', 'alice', 'reaction', 'message#alice'],
					['🙋‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧏‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧏', 'alice', 'reaction', 'message#alice'],
					['🧏‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤦‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤦', 'alice', 'reaction', 'message#alice'],
					['🤦‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤷‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤷', 'alice', 'reaction', 'message#alice'],
					['🤷‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙎‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙎', 'alice', 'reaction', 'message#alice'],
					['🙎‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙍‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙍', 'alice', 'reaction', 'message#alice'],
					['🙍‍♂️', 'alice', 'reaction', 'message#alice'],
					['💇‍♀️', 'alice', 'reaction', 'message#alice'],
					['💇', 'alice', 'reaction', 'message#alice'],
					['💇‍♂️', 'alice', 'reaction', 'message#alice'],
					['💆‍♀️', 'alice', 'reaction', 'message#alice'],
					['💆', 'alice', 'reaction', 'message#alice'],
					['💆‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧖‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧖', 'alice', 'reaction', 'message#alice'],
					['🧖‍♂️', 'alice', 'reaction', 'message#alice'],
					['💅', 'alice', 'reaction', 'message#alice'],
					['🤳', 'alice', 'reaction', 'message#alice'],
					['💃', 'alice', 'reaction', 'message#alice'],
					['🕺', 'alice', 'reaction', 'message#alice'],
					['👯‍♀️', 'alice', 'reaction', 'message#alice'],
					['👯', 'alice', 'reaction', 'message#alice'],
					['👯‍♂️', 'alice', 'reaction', 'message#alice'],
					['🕴', 'alice', 'reaction', 'message#alice'],
					['👩‍🦽', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦽', 'alice', 'reaction', 'message#alice'],
					['👨‍🦽', 'alice', 'reaction', 'message#alice'],
					['👩‍🦼', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦼', 'alice', 'reaction', 'message#alice'],
					['👨‍🦼', 'alice', 'reaction', 'message#alice'],
					['🚶‍♀️', 'alice', 'reaction', 'message#alice'],
					['🚶', 'alice', 'reaction', 'message#alice'],
					['🚶‍♂️', 'alice', 'reaction', 'message#alice'],
					['👩‍🦯', 'alice', 'reaction', 'message#alice'],
					['🧑‍🦯', 'alice', 'reaction', 'message#alice'],
					['👨‍🦯', 'alice', 'reaction', 'message#alice'],
					['🧎‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧎', 'alice', 'reaction', 'message#alice'],
					['🧎‍♂️', 'alice', 'reaction', 'message#alice'],
					['🏃‍♀️', 'alice', 'reaction', 'message#alice'],
					['🏃', 'alice', 'reaction', 'message#alice'],
					['🏃‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧍‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧍', 'alice', 'reaction', 'message#alice'],
					['🧍‍♂️', 'alice', 'reaction', 'message#alice'],
					['👭', 'alice', 'reaction', 'message#alice'],
					['🧑‍🤝‍🧑', 'alice', 'reaction', 'message#alice'],
					['👬', 'alice', 'reaction', 'message#alice'],
					['👫', 'alice', 'reaction', 'message#alice'],
					['👩‍❤️‍👩', 'alice', 'reaction', 'message#alice'],
					['💑', 'alice', 'reaction', 'message#alice'],
					['👨‍❤️‍👨', 'alice', 'reaction', 'message#alice'],
					['👩‍❤️‍👨', 'alice', 'reaction', 'message#alice'],
					['👩‍❤️‍💋‍👩', 'alice', 'reaction', 'message#alice'],
					['💏', 'alice', 'reaction', 'message#alice'],
					['👨‍❤️‍💋‍👨', 'alice', 'reaction', 'message#alice'],
					['👩‍❤️‍💋‍👨', 'alice', 'reaction', 'message#alice'],
					['👪', 'alice', 'reaction', 'message#alice'],
					['👨‍👩‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👩‍👧', 'alice', 'reaction', 'message#alice'],
					['👨‍👩‍👧‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👩‍👦‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👩‍👧‍👧', 'alice', 'reaction', 'message#alice'],
					['👨‍👨‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👨‍👧', 'alice', 'reaction', 'message#alice'],
					['👨‍👨‍👧‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👨‍👦‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👨‍👧‍👧', 'alice', 'reaction', 'message#alice'],
					['👩‍👩‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👩‍👧', 'alice', 'reaction', 'message#alice'],
					['👩‍👩‍👧‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👩‍👦‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👩‍👧‍👧', 'alice', 'reaction', 'message#alice'],
					['👨‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👦‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👧', 'alice', 'reaction', 'message#alice'],
					['👨‍👧‍👦', 'alice', 'reaction', 'message#alice'],
					['👨‍👧‍👧', 'alice', 'reaction', 'message#alice'],
					['👩‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👦‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👧', 'alice', 'reaction', 'message#alice'],
					['👩‍👧‍👦', 'alice', 'reaction', 'message#alice'],
					['👩‍👧‍👧', 'alice', 'reaction', 'message#alice'],
					['🗣', 'alice', 'reaction', 'message#alice'],
					['👤', 'alice', 'reaction', 'message#alice'],
					['👥', 'alice', 'reaction', 'message#alice'],
					['🫂', 'alice', 'reaction', 'message#alice'],
					['👋🏽', 'alice', 'reaction', 'message#alice'],
					['🤚🏽', 'alice', 'reaction', 'message#alice'],
					['🖐🏽', 'alice', 'reaction', 'message#alice'],
					['✋🏽', 'alice', 'reaction', 'message#alice'],
					['🖖🏽', 'alice', 'reaction', 'message#alice'],
					['👌🏽', 'alice', 'reaction', 'message#alice'],
					['🤌🏽', 'alice', 'reaction', 'message#alice'],
					['🤏🏽', 'alice', 'reaction', 'message#alice'],
					['✌🏽', 'alice', 'reaction', 'message#alice'],
					['🤞🏽', 'alice', 'reaction', 'message#alice'],
					['🫰🏽', 'alice', 'reaction', 'message#alice'],
					['🤟🏽', 'alice', 'reaction', 'message#alice'],
					['🤘🏽', 'alice', 'reaction', 'message#alice'],
					['🤙🏽', 'alice', 'reaction', 'message#alice'],
					['🫵🏽', 'alice', 'reaction', 'message#alice'],
					['🫱🏽', 'alice', 'reaction', 'message#alice'],
					['🫲🏽', 'alice', 'reaction', 'message#alice'],
					['🫳🏽', 'alice', 'reaction', 'message#alice'],
					['🫴🏽', 'alice', 'reaction', 'message#alice'],
					['👈🏽', 'alice', 'reaction', 'message#alice'],
					['👉🏽', 'alice', 'reaction', 'message#alice'],
					['👆🏽', 'alice', 'reaction', 'message#alice'],
					['🖕🏽', 'alice', 'reaction', 'message#alice'],
					['👇🏽', 'alice', 'reaction', 'message#alice'],
					['☝🏽', 'alice', 'reaction', 'message#alice'],
					['👍🏽', 'alice', 'reaction', 'message#alice'],
					['👎🏽', 'alice', 'reaction', 'message#alice'],
					['✊🏽', 'alice', 'reaction', 'message#alice'],
					['👊🏽', 'alice', 'reaction', 'message#alice'],
					['🤛🏽', 'alice', 'reaction', 'message#alice'],
					['🤜🏽', 'alice', 'reaction', 'message#alice'],
					['👏🏽', 'alice', 'reaction', 'message#alice'],
					['🫶🏽', 'alice', 'reaction', 'message#alice'],
					['🙌🏽', 'alice', 'reaction', 'message#alice'],
					['👐🏽', 'alice', 'reaction', 'message#alice'],
					['🤲🏽', 'alice', 'reaction', 'message#alice'],
					['🙏🏽', 'alice', 'reaction', 'message#alice'],
					['✍🏽', 'alice', 'reaction', 'message#alice'],
					['💅🏽', 'alice', 'reaction', 'message#alice'],
					['🤳🏽', 'alice', 'reaction', 'message#alice'],
					['💪🏽', 'alice', 'reaction', 'message#alice'],
					['🦵🏽', 'alice', 'reaction', 'message#alice'],
					['🦶🏽', 'alice', 'reaction', 'message#alice'],
					['👂🏽', 'alice', 'reaction', 'message#alice'],
					['🦻🏽', 'alice', 'reaction', 'message#alice'],
					['👃🏽', 'alice', 'reaction', 'message#alice'],
					['👶🏽', 'alice', 'reaction', 'message#alice'],
					['👧🏽', 'alice', 'reaction', 'message#alice'],
					['🧒🏽', 'alice', 'reaction', 'message#alice'],
					['👦🏽', 'alice', 'reaction', 'message#alice'],
					['👩🏽', 'alice', 'reaction', 'message#alice'],
					['🧑🏽', 'alice', 'reaction', 'message#alice'],
					['👨🏽', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🦱', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🦱', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🦱', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🦰', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🦰', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🦰', 'alice', 'reaction', 'message#alice'],
					['👱🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['👱🏽', 'alice', 'reaction', 'message#alice'],
					['👱🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🦳', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🦳', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🦳', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🦲', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🦲', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🦲', 'alice', 'reaction', 'message#alice'],
					['🧔🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧔🏽', 'alice', 'reaction', 'message#alice'],
					['🧔🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👵🏽', 'alice', 'reaction', 'message#alice'],
					['🧓🏽', 'alice', 'reaction', 'message#alice'],
					['👴🏽', 'alice', 'reaction', 'message#alice'],
					['👲🏽', 'alice', 'reaction', 'message#alice'],
					['👳🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['👳🏽', 'alice', 'reaction', 'message#alice'],
					['👳🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧕🏽', 'alice', 'reaction', 'message#alice'],
					['👮🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['👮🏽', 'alice', 'reaction', 'message#alice'],
					['👮🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👷🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['👷🏽', 'alice', 'reaction', 'message#alice'],
					['👷🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['💂🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['💂🏽', 'alice', 'reaction', 'message#alice'],
					['💂🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🕵🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🕵🏽', 'alice', 'reaction', 'message#alice'],
					['🕵🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍⚕️', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍⚕️', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍⚕️', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🌾', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🌾', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🌾', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🍳', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🍳', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🍳', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🎓', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🎓', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🎓', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🎤', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🎤', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🎤', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🏫', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🏫', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🏫', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🏭', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🏭', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🏭', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍💻', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍💻', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍💻', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍💼', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍💼', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍💼', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🔧', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🔧', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🔧', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🔬', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🔬', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🔬', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🎨', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🎨', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🎨', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🚒', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🚒', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🚒', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍✈️', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍✈️', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍✈️', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🚀', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🚀', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🚀', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍⚖️', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍⚖️', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍⚖️', 'alice', 'reaction', 'message#alice'],
					['👰🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['👰🏽', 'alice', 'reaction', 'message#alice'],
					['👰🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤵🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤵🏽', 'alice', 'reaction', 'message#alice'],
					['🤵🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👸🏽', 'alice', 'reaction', 'message#alice'],
					['🫅🏽', 'alice', 'reaction', 'message#alice'],
					['🤴🏽', 'alice', 'reaction', 'message#alice'],
					['🥷🏽', 'alice', 'reaction', 'message#alice'],
					['🦸🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🦸🏽', 'alice', 'reaction', 'message#alice'],
					['🦸🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🦹🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🦹🏽', 'alice', 'reaction', 'message#alice'],
					['🦹🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤶🏽', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🎄', 'alice', 'reaction', 'message#alice'],
					['🎅🏽', 'alice', 'reaction', 'message#alice'],
					['🧙🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧙🏽', 'alice', 'reaction', 'message#alice'],
					['🧙🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧝🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧝🏽', 'alice', 'reaction', 'message#alice'],
					['🧝🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧛🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧛🏽', 'alice', 'reaction', 'message#alice'],
					['🧛🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧜🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧜🏽', 'alice', 'reaction', 'message#alice'],
					['🧜🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧚🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧚🏽', 'alice', 'reaction', 'message#alice'],
					['🧚🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['👼🏽', 'alice', 'reaction', 'message#alice'],
					['🤰🏽', 'alice', 'reaction', 'message#alice'],
					['🫄🏽', 'alice', 'reaction', 'message#alice'],
					['🫃🏽', 'alice', 'reaction', 'message#alice'],
					['🤱🏽', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🍼', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🍼', 'alice', 'reaction', 'message#alice'],
					['👨🏽‍🍼', 'alice', 'reaction', 'message#alice'],
					['🙇🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙇🏽', 'alice', 'reaction', 'message#alice'],
					['🙇🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['💁🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['💁🏽', 'alice', 'reaction', 'message#alice'],
					['💁🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙅🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙅🏽', 'alice', 'reaction', 'message#alice'],
					['🙅🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙆🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙆🏽', 'alice', 'reaction', 'message#alice'],
					['🙆🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙋🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙋🏽', 'alice', 'reaction', 'message#alice'],
					['🙋🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧏🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧏🏽', 'alice', 'reaction', 'message#alice'],
					['🧏🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤦🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤦🏽', 'alice', 'reaction', 'message#alice'],
					['🤦🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🤷🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🤷🏽', 'alice', 'reaction', 'message#alice'],
					['🤷🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙎🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙎🏽', 'alice', 'reaction', 'message#alice'],
					['🙎🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🙍🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🙍🏽', 'alice', 'reaction', 'message#alice'],
					['🙍🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['💇🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['💇🏽', 'alice', 'reaction', 'message#alice'],
					['💇🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['💆🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['💆🏽', 'alice', 'reaction', 'message#alice'],
					['💆🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['🧖🏽‍♀️', 'alice', 'reaction', 'message#alice'],
					['🧖🏽', 'alice', 'reaction', 'message#alice'],
					['🧖🏽‍♂️', 'alice', 'reaction', 'message#alice'],
					['💃🏽', 'alice', 'reaction', 'message#alice'],
					['🕺🏽', 'alice', 'reaction', 'message#alice'],
					['🕴🏽', 'alice', 'reaction', 'message#alice'],
					['👩🏽‍🦽', 'alice', 'reaction', 'message#alice'],
					['🧑🏽‍🦽', 'alice', 'reaction', 'message#alice'],
				],
				[
					['😀', 'alice'],
					['😃', 'alice'],
					['😄', 'alice'],
					['😁', 'alice'],
					['😆', 'alice'],
					['😅', 'alice'],
					['😂', 'alice'],
					['🤣', 'alice'],
					['🥲', 'alice'],
					['🥹', 'alice'],
					['☺️', 'alice'],
					['😊', 'alice'],
					['😇', 'alice'],
					['🙂', 'alice'],
					['🙃', 'alice'],
					['😉', 'alice'],
					['😌', 'alice'],
					['😍', 'alice'],
					['🥰', 'alice'],
					['😘', 'alice'],
					['😗', 'alice'],
					['😙', 'alice'],
					['😚', 'alice'],
					['😋', 'alice'],
					['😛', 'alice'],
					['😝', 'alice'],
					['😜', 'alice'],
					['🤪', 'alice'],
					['🤨', 'alice'],
					['🧐', 'alice'],
					['🤓', 'alice'],
					['😎', 'alice'],
					['🥸', 'alice'],
					['🤩', 'alice'],
					['🥳', 'alice'],
					['😏', 'alice'],
					['😒', 'alice'],
					['😞', 'alice'],
					['😔', 'alice'],
					['😟', 'alice'],
					['😕', 'alice'],
					['🙁', 'alice'],
					['☹️', 'alice'],
					['😣', 'alice'],
					['😖', 'alice'],
					['😫', 'alice'],
					['😩', 'alice'],
					['🥺', 'alice'],
					['😢', 'alice'],
					['😭', 'alice'],
					['😮‍💨', 'alice'],
					['😤', 'alice'],
					['😠', 'alice'],
					['😡', 'alice'],
					['🤬', 'alice'],
					['🤯', 'alice'],
					['😳', 'alice'],
					['🥵', 'alice'],
					['🥶', 'alice'],
					['😱', 'alice'],
					['😨', 'alice'],
					['😰', 'alice'],
					['😥', 'alice'],
					['😓', 'alice'],
					['🫣', 'alice'],
					['🤗', 'alice'],
					['🫡', 'alice'],
					['🤔', 'alice'],
					['🫢', 'alice'],
					['🤭', 'alice'],
					['🤫', 'alice'],
					['🤥', 'alice'],
					['😶', 'alice'],
					['😶‍🌫️', 'alice'],
					['😐', 'alice'],
					['😑', 'alice'],
					['😬', 'alice'],
					['🫠', 'alice'],
					['🙄', 'alice'],
					['😯', 'alice'],
					['😦', 'alice'],
					['😧', 'alice'],
					['😮', 'alice'],
					['😲', 'alice'],
					['🥱', 'alice'],
					['😴', 'alice'],
					['🤤', 'alice'],
					['😪', 'alice'],
					['😵', 'alice'],
					['😵‍💫', 'alice'],
					['🫥', 'alice'],
					['🤐', 'alice'],
					['🥴', 'alice'],
					['🤢', 'alice'],
					['🤮', 'alice'],
					['🤧', 'alice'],
					['😷', 'alice'],
					['🤒', 'alice'],
					['🤕', 'alice'],
					['🤑', 'alice'],
					['🤠', 'alice'],
					['😈', 'alice'],
					['👿', 'alice'],
					['👹', 'alice'],
					['👺', 'alice'],
					['🤡', 'alice'],
					['💩', 'alice'],
					['👻', 'alice'],
					['💀', 'alice'],
					['☠️', 'alice'],
					['👽', 'alice'],
					['👾', 'alice'],
					['🤖', 'alice'],
					['🎃', 'alice'],
					['😺', 'alice'],
					['😸', 'alice'],
					['😹', 'alice'],
					['😻', 'alice'],
					['😼', 'alice'],
					['😽', 'alice'],
					['🙀', 'alice'],
					['😿', 'alice'],
					['😾', 'alice'],
					['👶', 'alice'],
					['👧', 'alice'],
					['🧒', 'alice'],
					['👦', 'alice'],
					['👩', 'alice'],
					['🧑', 'alice'],
					['👨', 'alice'],
					['👩‍🦱', 'alice'],
					['🧑‍🦱', 'alice'],
					['👨‍🦱', 'alice'],
					['👩‍🦰', 'alice'],
					['🧑‍🦰', 'alice'],
					['👨‍🦰', 'alice'],
					['👱‍♀️', 'alice'],
					['👱', 'alice'],
					['👱‍♂️', 'alice'],
					['👩‍🦳', 'alice'],
					['🧑‍🦳', 'alice'],
					['👨‍🦳', 'alice'],
					['👩‍🦲', 'alice'],
					['🧑‍🦲', 'alice'],
					['👨‍🦲', 'alice'],
					['🧔‍♀️', 'alice'],
					['🧔', 'alice'],
					['🧔‍♂️', 'alice'],
					['👵', 'alice'],
					['🧓', 'alice'],
					['👴', 'alice'],
					['👲', 'alice'],
					['👳‍♀️', 'alice'],
					['👳', 'alice'],
					['👳‍♂️', 'alice'],
					['🧕', 'alice'],
					['👮‍♀️', 'alice'],
					['👮', 'alice'],
					['👮‍♂️', 'alice'],
					['👷‍♀️', 'alice'],
					['👷', 'alice'],
					['👷‍♂️', 'alice'],
					['💂‍♀️', 'alice'],
					['💂', 'alice'],
					['💂‍♂️', 'alice'],
					['🕵️‍♀️', 'alice'],
					['🕵️', 'alice'],
					['🕵️‍♂️', 'alice'],
					['👩‍⚕️', 'alice'],
					['🧑‍⚕️', 'alice'],
					['👨‍⚕️', 'alice'],
					['👩‍🌾', 'alice'],
					['🧑‍🌾', 'alice'],
					['👨‍🌾', 'alice'],
					['👩‍🍳', 'alice'],
					['🧑‍🍳', 'alice'],
					['👨‍🍳', 'alice'],
					['👩‍🎓', 'alice'],
					['🧑‍🎓', 'alice'],
					['👨‍🎓', 'alice'],
					['👩‍🎤', 'alice'],
					['🧑‍🎤', 'alice'],
					['👨‍🎤', 'alice'],
					['👩‍🏫', 'alice'],
					['🧑‍🏫', 'alice'],
					['👨‍🏫', 'alice'],
					['👩‍🏭', 'alice'],
					['🧑‍🏭', 'alice'],
					['👨‍🏭', 'alice'],
					['👩‍💻', 'alice'],
					['🧑‍💻', 'alice'],
					['👨‍💻', 'alice'],
					['👩‍💼', 'alice'],
					['🧑‍💼', 'alice'],
					['👨‍💼', 'alice'],
					['👩‍🔧', 'alice'],
					['🧑‍🔧', 'alice'],
					['👨‍🔧', 'alice'],
					['👩‍🔬', 'alice'],
					['🧑‍🔬', 'alice'],
					['👨‍🔬', 'alice'],
					['👩‍🎨', 'alice'],
					['🧑‍🎨', 'alice'],
					['👨‍🎨', 'alice'],
					['👩‍🚒', 'alice'],
					['🧑‍🚒', 'alice'],
					['👨‍🚒', 'alice'],
					['👩‍✈️', 'alice'],
					['🧑‍✈️', 'alice'],
					['👨‍✈️', 'alice'],
					['👩‍🚀', 'alice'],
					['🧑‍🚀', 'alice'],
					['👨‍🚀', 'alice'],
					['👩‍⚖️', 'alice'],
					['🧑‍⚖️', 'alice'],
					['👨‍⚖️', 'alice'],
					['👰‍♀️', 'alice'],
					['👰', 'alice'],
					['👰‍♂️', 'alice'],
					['🤵‍♀️', 'alice'],
					['🤵', 'alice'],
					['🤵‍♂️', 'alice'],
					['👸', 'alice'],
					['🫅', 'alice'],
					['🤴', 'alice'],
					['🥷', 'alice'],
					['🦸‍♀️', 'alice'],
					['🦸', 'alice'],
					['🦸‍♂️', 'alice'],
					['🦹‍♀️', 'alice'],
					['🦹', 'alice'],
					['🦹‍♂️', 'alice'],
					['🤶', 'alice'],
					['🧑‍🎄', 'alice'],
					['🎅', 'alice'],
					['🧙‍♀️', 'alice'],
					['🧙', 'alice'],
					['🧙‍♂️', 'alice'],
					['🧝‍♀️', 'alice'],
					['🧝', 'alice'],
					['🧝‍♂️', 'alice'],
					['🧛‍♀️', 'alice'],
					['🧛', 'alice'],
					['🧛‍♂️', 'alice'],
					['🧟‍♀️', 'alice'],
					['🧟', 'alice'],
					['🧟‍♂️', 'alice'],
					['🧞‍♀️', 'alice'],
					['🧞', 'alice'],
					['🧞‍♂️', 'alice'],
					['🧜‍♀️', 'alice'],
					['🧜', 'alice'],
					['🧜‍♂️', 'alice'],
					['🧚‍♀️', 'alice'],
					['🧚', 'alice'],
					['🧚‍♂️', 'alice'],
					['🧌', 'alice'],
					['👼', 'alice'],
					['🤰', 'alice'],
					['🫄', 'alice'],
					['🫃', 'alice'],
					['🤱', 'alice'],
					['👩‍🍼', 'alice'],
					['🧑‍🍼', 'alice'],
					['👨‍🍼', 'alice'],
					['🙇‍♀️', 'alice'],
					['🙇', 'alice'],
					['🙇‍♂️', 'alice'],
					['💁‍♀️', 'alice'],
					['💁', 'alice'],
					['💁‍♂️', 'alice'],
					['🙅‍♀️', 'alice'],
					['🙅', 'alice'],
					['🙅‍♂️', 'alice'],
					['🙆‍♀️', 'alice'],
					['🙆', 'alice'],
					['🙆‍♂️', 'alice'],
					['🙋‍♀️', 'alice'],
					['🙋', 'alice'],
					['🙋‍♂️', 'alice'],
					['🧏‍♀️', 'alice'],
					['🧏', 'alice'],
					['🧏‍♂️', 'alice'],
					['🤦‍♀️', 'alice'],
					['🤦', 'alice'],
					['🤦‍♂️', 'alice'],
					['🤷‍♀️', 'alice'],
					['🤷', 'alice'],
					['🤷‍♂️', 'alice'],
					['🙎‍♀️', 'alice'],
					['🙎', 'alice'],
					['🙎‍♂️', 'alice'],
					['🙍‍♀️', 'alice'],
					['🙍', 'alice'],
					['🙍‍♂️', 'alice'],
					['💇‍♀️', 'alice'],
					['💇', 'alice'],
					['💇‍♂️', 'alice'],
					['💆‍♀️', 'alice'],
					['💆', 'alice'],
					['💆‍♂️', 'alice'],
					['🧖‍♀️', 'alice'],
					['🧖', 'alice'],
					['🧖‍♂️', 'alice'],
					['💅', 'alice'],
					['🤳', 'alice'],
					['💃', 'alice'],
					['🕺', 'alice'],
					['👯‍♀️', 'alice'],
					['👯', 'alice'],
					['👯‍♂️', 'alice'],
					['🕴', 'alice'],
					['👩‍🦽', 'alice'],
					['🧑‍🦽', 'alice'],
					['👨‍🦽', 'alice'],
					['👩‍🦼', 'alice'],
					['🧑‍🦼', 'alice'],
					['👨‍🦼', 'alice'],
					['🚶‍♀️', 'alice'],
					['🚶', 'alice'],
					['🚶‍♂️', 'alice'],
					['👩‍🦯', 'alice'],
					['🧑‍🦯', 'alice'],
					['👨‍🦯', 'alice'],
					['🧎‍♀️', 'alice'],
					['🧎', 'alice'],
					['🧎‍♂️', 'alice'],
					['🏃‍♀️', 'alice'],
					['🏃', 'alice'],
					['🏃‍♂️', 'alice'],
					['🧍‍♀️', 'alice'],
					['🧍', 'alice'],
					['🧍‍♂️', 'alice'],
					['👭', 'alice'],
					['🧑‍🤝‍🧑', 'alice'],
					['👬', 'alice'],
					['👫', 'alice'],
					['👩‍❤️‍👩', 'alice'],
					['💑', 'alice'],
					['👨‍❤️‍👨', 'alice'],
					['👩‍❤️‍👨', 'alice'],
					['👩‍❤️‍💋‍👩', 'alice'],
					['💏', 'alice'],
					['👨‍❤️‍💋‍👨', 'alice'],
					['👩‍❤️‍💋‍👨', 'alice'],
					['👪', 'alice'],
					['👨‍👩‍👦', 'alice'],
					['👨‍👩‍👧', 'alice'],
					['👨‍👩‍👧‍👦', 'alice'],
					['👨‍👩‍👦‍👦', 'alice'],
					['👨‍👩‍👧‍👧', 'alice'],
					['👨‍👨‍👦', 'alice'],
					['👨‍👨‍👧', 'alice'],
					['👨‍👨‍👧‍👦', 'alice'],
					['👨‍👨‍👦‍👦', 'alice'],
					['👨‍👨‍👧‍👧', 'alice'],
					['👩‍👩‍👦', 'alice'],
					['👩‍👩‍👧', 'alice'],
					['👩‍👩‍👧‍👦', 'alice'],
					['👩‍👩‍👦‍👦', 'alice'],
					['👩‍👩‍👧‍👧', 'alice'],
					['👨‍👦', 'alice'],
					['👨‍👦‍👦', 'alice'],
					['👨‍👧', 'alice'],
					['👨‍👧‍👦', 'alice'],
					['👨‍👧‍👧', 'alice'],
					['👩‍👦', 'alice'],
					['👩‍👦‍👦', 'alice'],
					['👩‍👧', 'alice'],
					['👩‍👧‍👦', 'alice'],
					['👩‍👧‍👧', 'alice'],
					['🗣', 'alice'],
					['👤', 'alice'],
					['👥', 'alice'],
					['🫂', 'alice'],
					['👋🏽', 'alice'],
					['🤚🏽', 'alice'],
					['🖐🏽', 'alice'],
					['✋🏽', 'alice'],
					['🖖🏽', 'alice'],
					['👌🏽', 'alice'],
					['🤌🏽', 'alice'],
					['🤏🏽', 'alice'],
					['✌🏽', 'alice'],
					['🤞🏽', 'alice'],
					['🫰🏽', 'alice'],
					['🤟🏽', 'alice'],
					['🤘🏽', 'alice'],
					['🤙🏽', 'alice'],
					['🫵🏽', 'alice'],
					['🫱🏽', 'alice'],
					['🫲🏽', 'alice'],
					['🫳🏽', 'alice'],
					['🫴🏽', 'alice'],
					['👈🏽', 'alice'],
					['👉🏽', 'alice'],
					['👆🏽', 'alice'],
					['🖕🏽', 'alice'],
					['👇🏽', 'alice'],
					['☝🏽', 'alice'],
					['👍🏽', 'alice'],
					['👎🏽', 'alice'],
					['✊🏽', 'alice'],
					['👊🏽', 'alice'],
					['🤛🏽', 'alice'],
					['🤜🏽', 'alice'],
					['👏🏽', 'alice'],
					['🫶🏽', 'alice'],
					['🙌🏽', 'alice'],
					['👐🏽', 'alice'],
					['🤲🏽', 'alice'],
					['🙏🏽', 'alice'],
					['✍🏽', 'alice'],
					['💅🏽', 'alice'],
					['🤳🏽', 'alice'],
					['💪🏽', 'alice'],
					['🦵🏽', 'alice'],
					['🦶🏽', 'alice'],
					['👂🏽', 'alice'],
					['🦻🏽', 'alice'],
					['👃🏽', 'alice'],
					['👶🏽', 'alice'],
					['👧🏽', 'alice'],
					['🧒🏽', 'alice'],
					['👦🏽', 'alice'],
					['👩🏽', 'alice'],
					['🧑🏽', 'alice'],
					['👨🏽', 'alice'],
					['👩🏽‍🦱', 'alice'],
					['🧑🏽‍🦱', 'alice'],
					['👨🏽‍🦱', 'alice'],
					['👩🏽‍🦰', 'alice'],
					['🧑🏽‍🦰', 'alice'],
					['👨🏽‍🦰', 'alice'],
					['👱🏽‍♀️', 'alice'],
					['👱🏽', 'alice'],
					['👱🏽‍♂️', 'alice'],
					['👩🏽‍🦳', 'alice'],
					['🧑🏽‍🦳', 'alice'],
					['👨🏽‍🦳', 'alice'],
					['👩🏽‍🦲', 'alice'],
					['🧑🏽‍🦲', 'alice'],
					['👨🏽‍🦲', 'alice'],
					['🧔🏽‍♀️', 'alice'],
					['🧔🏽', 'alice'],
					['🧔🏽‍♂️', 'alice'],
					['👵🏽', 'alice'],
					['🧓🏽', 'alice'],
					['👴🏽', 'alice'],
					['👲🏽', 'alice'],
					['👳🏽‍♀️', 'alice'],
					['👳🏽', 'alice'],
					['👳🏽‍♂️', 'alice'],
					['🧕🏽', 'alice'],
					['👮🏽‍♀️', 'alice'],
					['👮🏽', 'alice'],
					['👮🏽‍♂️', 'alice'],
					['👷🏽‍♀️', 'alice'],
					['👷🏽', 'alice'],
					['👷🏽‍♂️', 'alice'],
					['💂🏽‍♀️', 'alice'],
					['💂🏽', 'alice'],
					['💂🏽‍♂️', 'alice'],
					['🕵🏽‍♀️', 'alice'],
					['🕵🏽', 'alice'],
					['🕵🏽‍♂️', 'alice'],
					['👩🏽‍⚕️', 'alice'],
					['🧑🏽‍⚕️', 'alice'],
					['👨🏽‍⚕️', 'alice'],
					['👩🏽‍🌾', 'alice'],
					['🧑🏽‍🌾', 'alice'],
					['👨🏽‍🌾', 'alice'],
					['👩🏽‍🍳', 'alice'],
					['🧑🏽‍🍳', 'alice'],
					['👨🏽‍🍳', 'alice'],
					['👩🏽‍🎓', 'alice'],
					['🧑🏽‍🎓', 'alice'],
					['👨🏽‍🎓', 'alice'],
					['👩🏽‍🎤', 'alice'],
					['🧑🏽‍🎤', 'alice'],
					['👨🏽‍🎤', 'alice'],
					['👩🏽‍🏫', 'alice'],
					['🧑🏽‍🏫', 'alice'],
					['👨🏽‍🏫', 'alice'],
					['👩🏽‍🏭', 'alice'],
					['🧑🏽‍🏭', 'alice'],
					['👨🏽‍🏭', 'alice'],
					['👩🏽‍💻', 'alice'],
					['🧑🏽‍💻', 'alice'],
					['👨🏽‍💻', 'alice'],
					['👩🏽‍💼', 'alice'],
					['🧑🏽‍💼', 'alice'],
					['👨🏽‍💼', 'alice'],
					['👩🏽‍🔧', 'alice'],
					['🧑🏽‍🔧', 'alice'],
					['👨🏽‍🔧', 'alice'],
					['👩🏽‍🔬', 'alice'],
					['🧑🏽‍🔬', 'alice'],
					['👨🏽‍🔬', 'alice'],
					['👩🏽‍🎨', 'alice'],
					['🧑🏽‍🎨', 'alice'],
					['👨🏽‍🎨', 'alice'],
					['👩🏽‍🚒', 'alice'],
					['🧑🏽‍🚒', 'alice'],
					['👨🏽‍🚒', 'alice'],
					['👩🏽‍✈️', 'alice'],
					['🧑🏽‍✈️', 'alice'],
					['👨🏽‍✈️', 'alice'],
					['👩🏽‍🚀', 'alice'],
					['🧑🏽‍🚀', 'alice'],
					['👨🏽‍🚀', 'alice'],
					['👩🏽‍⚖️', 'alice'],
					['🧑🏽‍⚖️', 'alice'],
					['👨🏽‍⚖️', 'alice'],
					['👰🏽‍♀️', 'alice'],
					['👰🏽', 'alice'],
					['👰🏽‍♂️', 'alice'],
					['🤵🏽‍♀️', 'alice'],
					['🤵🏽', 'alice'],
					['🤵🏽‍♂️', 'alice'],
					['👸🏽', 'alice'],
					['🫅🏽', 'alice'],
					['🤴🏽', 'alice'],
					['🥷🏽', 'alice'],
					['🦸🏽‍♀️', 'alice'],
					['🦸🏽', 'alice'],
					['🦸🏽‍♂️', 'alice'],
					['🦹🏽‍♀️', 'alice'],
					['🦹🏽', 'alice'],
					['🦹🏽‍♂️', 'alice'],
					['🤶🏽', 'alice'],
					['🧑🏽‍🎄', 'alice'],
					['🎅🏽', 'alice'],
					['🧙🏽‍♀️', 'alice'],
					['🧙🏽', 'alice'],
					['🧙🏽‍♂️', 'alice'],
					['🧝🏽‍♀️', 'alice'],
					['🧝🏽', 'alice'],
					['🧝🏽‍♂️', 'alice'],
					['🧛🏽‍♀️', 'alice'],
					['🧛🏽', 'alice'],
					['🧛🏽‍♂️', 'alice'],
					['🧜🏽‍♀️', 'alice'],
					['🧜🏽', 'alice'],
					['🧜🏽‍♂️', 'alice'],
					['🧚🏽‍♀️', 'alice'],
					['🧚🏽', 'alice'],
					['🧚🏽‍♂️', 'alice'],
					['👼🏽', 'alice'],
					['🤰🏽', 'alice'],
					['🫄🏽', 'alice'],
					['🫃🏽', 'alice'],
					['🤱🏽', 'alice'],
					['👩🏽‍🍼', 'alice'],
					['🧑🏽‍🍼', 'alice'],
					['👨🏽‍🍼', 'alice'],
					['🙇🏽‍♀️', 'alice'],
					['🙇🏽', 'alice'],
					['🙇🏽‍♂️', 'alice'],
					['💁🏽‍♀️', 'alice'],
					['💁🏽', 'alice'],
					['💁🏽‍♂️', 'alice'],
					['🙅🏽‍♀️', 'alice'],
					['🙅🏽', 'alice'],
					['🙅🏽‍♂️', 'alice'],
					['🙆🏽‍♀️', 'alice'],
					['🙆🏽', 'alice'],
					['🙆🏽‍♂️', 'alice'],
					['🙋🏽‍♀️', 'alice'],
					['🙋🏽', 'alice'],
					['🙋🏽‍♂️', 'alice'],
					['🧏🏽‍♀️', 'alice'],
					['🧏🏽', 'alice'],
					['🧏🏽‍♂️', 'alice'],
					['🤦🏽‍♀️', 'alice'],
					['🤦🏽', 'alice'],
					['🤦🏽‍♂️', 'alice'],
					['🤷🏽‍♀️', 'alice'],
					['🤷🏽', 'alice'],
					['🤷🏽‍♂️', 'alice'],
					['🙎🏽‍♀️', 'alice'],
					['🙎🏽', 'alice'],
					['🙎🏽‍♂️', 'alice'],
					['🙍🏽‍♀️', 'alice'],
					['🙍🏽', 'alice'],
					['🙍🏽‍♂️', 'alice'],
					['💇🏽‍♀️', 'alice'],
					['💇🏽', 'alice'],
					['💇🏽‍♂️', 'alice'],
					['💆🏽‍♀️', 'alice'],
					['💆🏽', 'alice'],
					['💆🏽‍♂️', 'alice'],
					['🧖🏽‍♀️', 'alice'],
					['🧖🏽', 'alice'],
					['🧖🏽‍♂️', 'alice'],
					['💃🏽', 'alice'],
					['🕺🏽', 'alice'],
					['🕴🏽', 'alice'],
					['👩🏽‍🦽', 'alice'],
					['🧑🏽‍🦽', 'alice'],
				],
			],
		];
	}

	#[DataProvider(methodName: 'providerTestRetrieveAllReactionsWithSpecificReaction')]
	public function testRetrieveAllReactionsWithSpecificReaction(array $comments, string $reaction, array $expected): void {
		$this->skipIfNotSupport4ByteUTF();
		$manager = $this->getManager();

		$processedComments = $this->proccessComments($comments);
		$comment = reset($processedComments);
		$all = $manager->retrieveAllReactionsWithSpecificReaction((int)$comment->getId(), $reaction);
		$actual = array_map(static function (IComment $row): array {
			return [
				$row->getActorId(),
				$row->getMessage(),
			];
		}, $all);
		$this->assertEqualsCanonicalizing($expected, $actual);
	}

	public static function providerTestRetrieveAllReactionsWithSpecificReaction(): array {
		return [
			[
				[
					['message', 'alice', 'comment', null],
				],
				'👎',
				[],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
				],
				'👍',
				[
					['👍', 'alice'],
					['👍', 'frank'],
				],
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
					['👎', 'alice', 'reaction', 'message#alice'],
					['👍', 'frank', 'reaction', 'message#alice'],
				],
				'👎',
				[
					['👎', 'alice'],
				],
			],
		];
	}

	#[DataProvider(methodName: 'providerTestGetReactionComment')]
	public function testGetReactionComment(array $comments, array $expected, bool $notFound): void {
		$this->skipIfNotSupport4ByteUTF();
		$manager = $this->getManager();

		$processedComments = $this->proccessComments($comments);

		$keys = ['message', 'actorId', 'verb', 'parent'];
		$expected = array_combine($keys, $expected);

		if ($notFound) {
			$this->expectException(NotFoundException::class);
		}
		$comment = $processedComments[$expected['message'] . '#' . $expected['actorId']];
		$actual = $manager->getReactionComment((int)$comment->getParentId(), $comment->getActorType(), $comment->getActorId(), $comment->getMessage());
		if (!$notFound) {
			$this->assertEquals($expected['message'], $actual->getMessage());
			$this->assertEquals($expected['actorId'], $actual->getActorId());
			$this->assertEquals($expected['verb'], $actual->getVerb());
			$this->assertEquals($processedComments[$expected['parent']]->getId(), $actual->getParentId());
		}
	}

	public static function providerTestGetReactionComment(): array {
		return [
			[
				[
					['message', 'Matthew', 'comment', null],
					['👍', 'Matthew', 'reaction', 'message#Matthew'],
					['👍', 'Mark', 'reaction', 'message#Matthew'],
					['👍', 'Luke', 'reaction', 'message#Matthew'],
					['👍', 'John', 'reaction', 'message#Matthew'],
				],
				['👍', 'Matthew', 'reaction', 'message#Matthew'],
				false,
			],
			[
				[
					['message', 'Matthew', 'comment', null],
					['👍', 'Matthew', 'reaction', 'message#Matthew'],
					['👍', 'Mark', 'reaction', 'message#Matthew'],
					['👍', 'Luke', 'reaction', 'message#Matthew'],
					['👍', 'John', 'reaction', 'message#Matthew'],
				],
				['👍', 'Mark', 'reaction', 'message#Matthew'],
				false,
			],
			[
				[
					['message', 'Matthew', 'comment', null],
					['👎', 'Matthew', 'reaction', 'message#Matthew'],
				],
				['👎', 'Matthew', 'reaction', 'message#Matthew'],
				false,
			],
			[
				[
					['message', 'Matthew', 'comment', null],
					['👎', 'Matthew', 'reaction', 'message#Matthew'],
					['👎', 'Matthew', 'reaction_deleted', 'message#Matthew'],
				],
				['👎', 'Matthew', 'reaction', 'message#Matthew'],
				true,
			],
		];
	}

	#[DataProvider(methodName: 'providerTestReactionMessageSize')]
	public function testReactionMessageSize(string $reactionString, bool $valid): void {
		$this->skipIfNotSupport4ByteUTF();
		if (!$valid) {
			$this->expectException(\UnexpectedValueException::class);
		}

		$manager = $this->getManager();
		$comment = new Comment();
		$comment->setMessage($reactionString)
			->setVerb('reaction')
			->setActor('users', 'alice')
			->setObject('files', 'file64');
		$status = $manager->save($comment);
		$this->assertTrue($status);
	}

	public static function providerTestReactionMessageSize(): array {
		return [
			['a', false],
			['1', false],
			['👍', true],
			['👍👍', false],
			['👍🏽', true],
			['👨🏽‍💻', true],
			['👨🏽‍💻👍', false],
		];
	}

	#[DataProvider(methodName: 'providerTestReactionsSummarizeOrdered')]
	public function testReactionsSummarizeOrdered(array $comments, array $expected, bool $isFullMatch): void {
		$this->skipIfNotSupport4ByteUTF();
		$manager = $this->getManager();


		$processedComments = $this->proccessComments($comments);
		$comment = end($processedComments);
		$actual = $manager->get($comment->getParentId());

		if ($isFullMatch) {
			$this->assertSame($expected, $actual->getReactions());
		} else {
			$subResult = array_slice($actual->getReactions(), 0, count($expected));
			$this->assertSame($expected, $subResult);
		}
	}

	public static function providerTestReactionsSummarizeOrdered(): array {
		return [
			[
				[
					['message', 'alice', 'comment', null],
					['👍', 'alice', 'reaction', 'message#alice'],
				],
				['👍' => 1],
				true,
			],
			[
				[
					['message', 'alice', 'comment', null],
					['👎', 'John', 'reaction', 'message#alice'],
					['💼', 'Luke', 'reaction', 'message#alice'],
					['📋', 'Luke', 'reaction', 'message#alice'],
					['🚀', 'Luke', 'reaction', 'message#alice'],
					['🖤', 'Luke', 'reaction', 'message#alice'],
					['😜', 'Luke', 'reaction', 'message#alice'],
					['🌖', 'Luke', 'reaction', 'message#alice'],
					['💖', 'Luke', 'reaction', 'message#alice'],
					['📥', 'Luke', 'reaction', 'message#alice'],
					['🐉', 'Luke', 'reaction', 'message#alice'],
					['☕', 'Luke', 'reaction', 'message#alice'],
					['🐄', 'Luke', 'reaction', 'message#alice'],
					['🐕', 'Luke', 'reaction', 'message#alice'],
					['🐈', 'Luke', 'reaction', 'message#alice'],
					['🛂', 'Luke', 'reaction', 'message#alice'],
					['🕸', 'Luke', 'reaction', 'message#alice'],
					['🏰', 'Luke', 'reaction', 'message#alice'],
					['⚙️', 'Luke', 'reaction', 'message#alice'],
					['🚨', 'Luke', 'reaction', 'message#alice'],
					['👥', 'Luke', 'reaction', 'message#alice'],
					['👍', 'Paul', 'reaction', 'message#alice'],
					['👍', 'Peter', 'reaction', 'message#alice'],
					['💜', 'Matthew', 'reaction', 'message#alice'],
					['💜', 'Mark', 'reaction', 'message#alice'],
					['💜', 'Luke', 'reaction', 'message#alice'],
				],
				[
					'💜' => 3,
					'👍' => 2,
				],
				false,
			],
		];
	}
}
