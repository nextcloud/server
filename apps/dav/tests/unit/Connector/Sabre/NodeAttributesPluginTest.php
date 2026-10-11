<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use NCU\Files\NodeAttributes\NodeAttributeList;
use OC\Files\NodeAttributes\NodeAttributeRegistry;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\NodeAttributeListXml;
use OCA\DAV\Connector\Sabre\NodeAttributesPlugin;
use OCP\Files\Folder;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\Xml\Service;
use Test\TestCase;

class NodeAttributesPluginTest extends TestCase {
	private const HAS_PREVIEW = '{http://nextcloud.org/ns}has-preview';
	private const FAVORITE = '{http://owncloud.org/ns}favorite';
	private const SHARE_TYPES = '{http://owncloud.org/ns}share-types';

	private NodeAttributeRegistry&MockObject $registry;
	private INodeAttributeProvider&MockObject $provider;
	private Server $server;
	private ?NodeAttributeContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(NodeAttributeRegistry::class);
		$this->provider = $this->createMock(INodeAttributeProvider::class);
		$this->registry->method('getAttributeNames')->willReturn([self::HAS_PREVIEW, self::FAVORITE, self::SHARE_TYPES]);
		$this->registry->method('getProvidersFor')->willReturnCallback(fn (array $attributes): array => $attributes === []
			? []
			: [['provider' => $this->provider, 'attributes' => array_values($attributes)]]);

		$user = $this->createMock(IUser::class);
		$this->context = NodeAttributeContext::forUser($user);

		$this->server = new Server();
		$this->server->addPlugin(new NodeAttributesPlugin($this->registry, fn (): ?NodeAttributeContext => $this->context));
	}

	/**
	 * @template T of File|Directory
	 * @param class-string<T> $class
	 * @return T&MockObject
	 */
	private function davNode(string $class, int $id): File|Directory {
		$node = $this->createMock($class === Directory::class ? Folder::class : \OCP\Files\File::class);
		$node->method('getId')->willReturn($id);
		$davNode = $this->createMock($class);
		$davNode->method('getNode')->willReturn($node);
		$davNode->method('getId')->willReturn($id);
		return $davNode;
	}

	public function testPropFindServesTypedValuesAsDavText(): void {
		$davNode = $this->davNode(File::class, 11);
		$this->provider->expects(self::once())->method('preload');
		$this->provider->method('getAttributes')->willReturn([
			self::HAS_PREVIEW => true,
			self::FAVORITE => 1,
		]);

		$propFind = new PropFind('/file.txt', [self::HAS_PREVIEW, self::FAVORITE, '{http://example.com/ns}other'], 0);
		$this->server->emit('propFind', [$propFind, $davNode]);

		self::assertSame('true', $propFind->get(self::HAS_PREVIEW));
		self::assertSame(1, $propFind->get(self::FAVORITE));
		self::assertNull($propFind->get('{http://example.com/ns}other'));
	}

	public function testCollectionIsPreloadedOnce(): void {
		$child1 = $this->davNode(File::class, 21);
		$child2 = $this->davNode(File::class, 22);
		$directory = $this->davNode(Directory::class, 20);
		$directory->method('getChildren')->willReturn([$child1, $child2]);

		$this->provider->expects(self::once())
			->method('preload')
			->with($this->context, [$directory->getNode(), $child1->getNode(), $child2->getNode()], [self::FAVORITE]);
		$this->provider->method('getAttributes')->willReturn([self::FAVORITE => 0]);

		$propFind = new PropFind('/dir', [self::FAVORITE], 1);
		$this->server->emit('preloadCollection', [$propFind, $directory]);
		foreach ([$directory, $child1, $child2] as $node) {
			$nodePropFind = new PropFind('/dir', [self::FAVORITE], 0);
			$this->server->emit('propFind', [$nodePropFind, $node]);
			self::assertSame(0, $nodePropFind->get(self::FAVORITE));
		}
	}

	public function testSearchResultsArePreloaded(): void {
		$result1 = $this->davNode(File::class, 31);
		$result2 = $this->davNode(File::class, 32);

		$this->provider->expects(self::once())
			->method('preload')
			->with($this->context, [$result1->getNode(), $result2->getNode()], [self::HAS_PREVIEW]);

		$this->server->emit('preloadProperties', [[$result1, $result2], [self::HAS_PREVIEW, '{DAV:}getetag']]);
	}

	public function testAllPropsDoesNotIncludeAttributes(): void {
		$this->provider->expects(self::never())->method('getAttributes');

		$propFind = new PropFind('/file.txt', [], 0, PropFind::ALLPROPS);
		$this->server->emit('propFind', [$propFind, $this->davNode(File::class, 11)]);

		self::assertNull($propFind->get(self::HAS_PREVIEW));
	}

	public function testNothingWithoutContext(): void {
		$this->context = null;
		$this->provider->expects(self::never())->method('preload');
		$this->provider->expects(self::never())->method('getAttributes');

		$propFind = new PropFind('/file.txt', [self::HAS_PREVIEW], 0);
		$this->server->emit('propFind', [$propFind, $this->davNode(File::class, 11)]);

		self::assertNull($propFind->get(self::HAS_PREVIEW));
	}

	public function testListsAreSerializedAsChildElements(): void {
		$davNode = $this->davNode(File::class, 11);
		$this->provider->method('getAttributes')->willReturn([
			self::SHARE_TYPES => new NodeAttributeList('share-type', [0, 3]),
		]);

		$propFind = new PropFind('/file.txt', [self::SHARE_TYPES], 0);
		$this->server->emit('propFind', [$propFind, $davNode]);
		$value = $propFind->get(self::SHARE_TYPES);
		self::assertInstanceOf(NodeAttributeListXml::class, $value);

		$document = new \DOMDocument();
		$document->loadXML((new Service())->write('{DAV:}prop', [self::SHARE_TYPES => $value]));
		$children = $document->getElementsByTagNameNS('http://owncloud.org/ns', 'share-type');
		self::assertSame(['0', '3'], array_map(static fn (\DOMElement $element): string => $element->textContent, iterator_to_array($children)));
		self::assertSame('share-types', $children->item(0)->parentNode->localName);
	}
}
