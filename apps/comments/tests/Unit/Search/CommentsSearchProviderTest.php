<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments\Tests\Unit\Search;

use OC\Comments\Comment;
use OCA\Comments\Search\CommentsSearchProvider;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IFilter;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Test\TestCase;

class CommentsSearchProviderTest extends TestCase {

	private IUserManager&MockObject $userManager;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private ICommentsManager&MockObject $commentsManager;
	private IRootFolder&MockObject $rootFolder;
	private CommentsSearchProvider $provider;


	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturnCallback(function (int $id) {
			if ($id % 4 === 0) {
				// Returning null for every fourth file to simulate a file not found case.
				return null;
			}
			$node = $this->createMock(File::class);
			$node->method('getId')->willReturn($id);
			$node->method('getPath')->willReturn('/' . $id . '.txt');
			return $node;
		});
		$userFolder->method('getRelativePath')->willReturnArgument(0);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->userManager->method('userExists')->willReturn(true);

		$this->l10n->method('t')->willReturnArgument(0);

		$this->provider = new CommentsSearchProvider(
			$this->userManager,
			$this->l10n,
			$this->urlGenerator,
			$this->commentsManager,
			$this->rootFolder,
			new NullLogger(),
		);
	}

	public function testGetId(): void {
		$this->assertEquals('comments', $this->provider->getId());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Comments')
			->willReturnArgument(0);

		$this->assertEquals('Comments', $this->provider->getName());
	}

	public function testSearch(): void {
		$this->commentsManager->method('search')->willReturnCallback(function (string $search, string $objectType, string $objectId, string $verb, int $offset, int $limit = 50) {
			// The search method is call until 50 comments are found or there are no more comments to search.
			$comments = [];
			for ($i = 1; $i <= $limit; $i++) {
				$comments[] = $this->mockComment(($offset + $i));
			}
			return $comments;
		});
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$searchTermFilter = $this->createMock(IFilter::class);
		$searchTermFilter->method('get')->willReturn('search term');
		$searchQuery = $this->createMock(ISearchQuery::class);
		$searchQuery->method('getFilter')->willReturnCallback(function ($name) use ($searchTermFilter) {
			return match ($name) {
				'term' => $searchTermFilter,
				default => null,
			};
		});

		$result = $this->provider->search($user, $searchQuery);
		$data = $result->jsonSerialize();

		$this->assertCount(50, $data['entries']);
	}

	public function testSearchNoMoreComments(): void {
		$this->commentsManager->method('search')->willReturnCallback(function (string $search, string $objectType, string $objectId, string $verb, int $offset, int $limit = 50) {
			// Decrease the limit to simulate no more comments to search -> the break case.
			if ($offset > 0) {
				$limit--;
			}
			$comments = [];
			for ($i = 1; $i <= $limit; $i++) {
				$comments[] = $this->mockComment(($offset + $i));
			}
			return $comments;
		});
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$searchTermFilter = $this->createMock(IFilter::class);
		$searchTermFilter->method('get')->willReturn('search term');
		$searchQuery = $this->createMock(ISearchQuery::class);
		$searchQuery->method('getFilter')->willReturnCallback(function ($name) use ($searchTermFilter) {
			return match ($name) {
				'term' => $searchTermFilter,
				default => null,
			};
		});


		$result = $this->provider->search($user, $searchQuery);
		$data = $result->jsonSerialize();

		$this->assertCount(46, $data['entries']);
	}

	private function mockComment(int $id): IComment {
		return new Comment([
			'id' => (string)$id,
			'parent_id' => '0',
			'topmost_parent_id' => '0',
			'children_count' => 0,
			'actor_type' => 'users',
			'actor_id' => 'user' . $id,
			'message' => 'Comment ' . $id,
			'verb' => 'comment',
			'creation_timestamp' => new \DateTime(),
			'latest_child_timestamp' => null,
			'object_type' => 'files',
			'object_id' => (string)$id
		]);
	}

}
