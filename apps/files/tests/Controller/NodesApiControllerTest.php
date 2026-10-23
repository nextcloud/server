<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Controller;

use NCU\Files\NodeAttributes\INodeSerializer;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCA\Files\Controller\NodesApiController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NodesApiControllerTest extends TestCase {
	private IUserFolder&MockObject $userFolder;
	private INodeSerializer&MockObject $serializer;
	private NodesApiController $controller;
	/** @var array<int, list<Node>> nodes by id */
	private array $nodes = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->userFolder = $this->createMock(IUserFolder::class);
		$this->userFolder->method('getPath')->willReturn('/alice/files');
		$this->userFolder->method('getById')->willReturnCallback(fn (int $id): array => $this->nodes[$id] ?? []);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('alice')->willReturn($this->userFolder);

		$this->serializer = $this->createMock(INodeSerializer::class);
		$this->controller = new NodesApiController('files', $this->createMock(IRequest::class), $rootFolder, $this->serializer, $userSession);
	}

	/**
	 * @param class-string<File|Folder> $class
	 */
	private function addNode(int $id, string $path, string $class = File::class): Node {
		$node = $this->createMock($class);
		$node->method('getId')->willReturn($id);
		$node->method('getPath')->willReturn($path);
		$this->nodes[$id][] = $node;
		return $node;
	}

	public function testNodesAreSerializedForTheUser(): void {
		$file = $this->addNode(1, '/alice/files/report.md');
		$folder = $this->addNode(2, '/alice/files/Projects', Folder::class);

		$this->serializer->expects(self::once())
			->method('serialize')
			->with(
				self::callback(static fn (NodeAttributeContext $context): bool => $context->getUser()?->getUID() === 'alice'),
				[$file, $folder],
				['{http://owncloud.org/ns}favorite'],
			)
			->willReturn([['id' => 1], ['id' => 2]]);

		$response = $this->controller->getNodes([1, 2, 404], null, ['{http://owncloud.org/ns}favorite']);
		self::assertSame([['id' => 1], ['id' => 2]], $response->getData());
	}

	public function testOnlyPathsWithinTheFolderAreKept(): void {
		$folder = $this->addNode(10, '/alice/files/Projects', Folder::class);
		$inside = $this->addNode(11, '/alice/files/Projects/X/plan.md');
		// The same file reached through a second share
		$this->addNode(11, '/alice/files/Y/plan.md');
		$this->addNode(12, '/alice/files/ProjectsOld/notes.md');
		$this->addNode(13, '/alice/files/notes.md');

		$this->serializer->expects(self::once())
			->method('serialize')
			->with(self::anything(), [$folder, $inside], null)
			->willReturn([]);

		$this->controller->getNodes([10, 11, 12, 13], '/Projects/');
	}

	public function testEveryPathOfAFileIsKeptWithoutFolder(): void {
		$first = $this->addNode(11, '/alice/files/X/Y/plan.md');
		$second = $this->addNode(11, '/alice/files/Y/plan.md');

		$this->serializer->expects(self::once())
			->method('serialize')
			->with(self::anything(), [$first, $second], null)
			->willReturn([]);

		$this->controller->getNodes([11, 11]);
	}

	public function testTooManyIds(): void {
		$this->serializer->expects(self::never())->method('serialize');

		$this->expectException(OCSBadRequestException::class);
		$this->controller->getNodes(range(1, NodesApiController::MAX_IDS + 1));
	}
}
