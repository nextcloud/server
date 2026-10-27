<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\NodeAttributes;

use NCU\Files\NodeAttributes\NodeAttributeContext;
use OC\Share20\ShareAttributes;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Files\NodeAttributes\ShareAttributesProvider;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\Share\IShare;
use Test\TestCase;

class ShareAttributesProviderTest extends TestCase {
	private const ATTRIBUTES = [FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME];

	private ShareAttributesProvider $provider;
	private NodeAttributeContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->provider = new ShareAttributesProvider();
		$this->context = NodeAttributeContext::forUser($this->createMock(IUser::class));
	}

	private function sharedNode(?ShareAttributes $attributes): File {
		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')->willReturn($attributes);
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('instanceOfStorage')->willReturn(true);
		$storage->method('getShare')->willReturn($share);

		$node = $this->createMock(File::class);
		$node->method('getStorage')->willReturn($storage);
		return $node;
	}

	public function testAttributesOfTheReceivedShare(): void {
		$attributes = new ShareAttributes();
		$attributes->setAttribute('permissions', 'download', false);

		self::assertSame(
			[FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => '[{"scope":"permissions","key":"download","value":false}]'],
			$this->provider->getAttributes($this->context, $this->sharedNode($attributes), self::ATTRIBUTES),
		);
	}

	public function testReceivedShareWithoutAttributes(): void {
		self::assertSame(
			[FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => '[]'],
			$this->provider->getAttributes($this->context, $this->sharedNode(null), self::ATTRIBUTES),
		);
	}

	public function testNodeThatIsNotShared(): void {
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')->willReturn(false);
		$node = $this->createMock(File::class);
		$node->method('getStorage')->willReturn($storage);

		self::assertSame(
			[FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => '[]'],
			$this->provider->getAttributes($this->context, $node, self::ATTRIBUTES),
		);
	}

	public function testNodeWithoutStorage(): void {
		$node = $this->createMock(File::class);
		$node->method('getStorage')->willThrowException(new NotFoundException());

		self::assertSame(
			[FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => '[]'],
			$this->provider->getAttributes($this->context, $node, self::ATTRIBUTES),
		);
	}

	public function testPublicShareVisitorsGetTheSameValue(): void {
		$context = NodeAttributeContext::forShare($this->createMock(IShare::class));
		$attributes = new ShareAttributes();
		$attributes->setAttribute('permissions', 'download', true);

		self::assertSame(
			[FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => '[{"scope":"permissions","key":"download","value":true}]'],
			$this->provider->getAttributes($context, $this->sharedNode($attributes), self::ATTRIBUTES),
		);
	}
}
