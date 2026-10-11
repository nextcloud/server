<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\NodeAttributes;

use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCA\DAV\Files\NodeAttributes\FavoriteProvider;
use OCP\Files\Node;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUser;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FavoriteProviderTest extends TestCase {
	private const ATTRIBUTES = [TagsPlugin::FAVORITE_PROPERTYNAME];

	private ITagManager&MockObject $tagManager;
	private ITags&MockObject $tags;
	private FavoriteProvider $provider;
	private NodeAttributeContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->tags = $this->createMock(ITags::class);
		$this->tagManager = $this->createMock(ITagManager::class);
		$this->tagManager->method('load')->with('files', [], false, 'alice')->willReturn($this->tags);
		$this->provider = new FavoriteProvider($this->tagManager);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->context = NodeAttributeContext::forUser($user);
	}

	private function node(int $id): Node&MockObject {
		$node = $this->createMock(Node::class);
		$node->method('getId')->willReturn($id);
		return $node;
	}

	public function testFavoritesOfAllNodesAreLoadedInOneQuery(): void {
		$nodes = [$this->node(1), $this->node(2), $this->node(3)];
		$this->tags->expects(self::once())
			->method('getTagsForObjects')
			->with([1, 2, 3])
			->willReturn([
				1 => ['work', TagsPlugin::TAG_FAVORITE],
				2 => ['work'],
			]);

		$this->provider->preload($this->context, $nodes, self::ATTRIBUTES);

		self::assertSame([TagsPlugin::FAVORITE_PROPERTYNAME => 1], $this->provider->getAttributes($this->context, $nodes[0], self::ATTRIBUTES));
		self::assertSame([TagsPlugin::FAVORITE_PROPERTYNAME => 0], $this->provider->getAttributes($this->context, $nodes[1], self::ATTRIBUTES));
		self::assertSame([TagsPlugin::FAVORITE_PROPERTYNAME => 0], $this->provider->getAttributes($this->context, $nodes[2], self::ATTRIBUTES));
	}

	public function testPreloadingTwiceOnlyQueriesNewNodes(): void {
		$this->tags->expects(self::exactly(2))
			->method('getTagsForObjects')
			->willReturnCallback(static fn (array $ids): array => array_fill_keys($ids, []));

		$this->provider->preload($this->context, [$this->node(1), $this->node(2)], self::ATTRIBUTES);
		$this->provider->preload($this->context, [$this->node(1), $this->node(2)], self::ATTRIBUTES);
		$this->provider->preload($this->context, [$this->node(2), $this->node(3)], self::ATTRIBUTES);
	}

	public function testNodeWithoutPreload(): void {
		$node = $this->node(7);
		$this->tags->expects(self::once())
			->method('getTagsForObjects')
			->with([7])
			->willReturn([7 => [TagsPlugin::TAG_FAVORITE]]);

		self::assertSame([TagsPlugin::FAVORITE_PROPERTYNAME => 1], $this->provider->getAttributes($this->context, $node, self::ATTRIBUTES));
	}

	public function testNoFavoritesOnPublicShares(): void {
		$context = NodeAttributeContext::forShare($this->createMock(IShare::class));
		$this->tagManager->expects(self::never())->method('load');

		$this->provider->preload($context, [$this->node(1)], self::ATTRIBUTES);
		self::assertSame([], $this->provider->getAttributes($context, $this->node(1), self::ATTRIBUTES));
	}
}
