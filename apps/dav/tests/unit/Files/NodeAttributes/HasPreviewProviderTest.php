<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\NodeAttributes;

use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Files\NodeAttributes\HasPreviewProvider;
use OCP\Files\Node;
use OCP\IPreview;
use OCP\IUser;
use OCP\Share\IShare;
use Test\TestCase;

class HasPreviewProviderTest extends TestCase {
	public static function dataContexts(): array {
		return [['user'], ['share']];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataContexts')]
	public function testHasPreview(string $contextType): void {
		$context = $contextType === 'user'
			? NodeAttributeContext::forUser($this->createMock(IUser::class))
			: NodeAttributeContext::forShare($this->createMock(IShare::class));
		$image = $this->createMock(Node::class);
		$text = $this->createMock(Node::class);
		$previewManager = $this->createMock(IPreview::class);
		$previewManager->method('isAvailable')->willReturnCallback(static fn ($node): bool => $node === $image);
		$provider = new HasPreviewProvider($previewManager);

		self::assertSame([FilesPlugin::HAS_PREVIEW_PROPERTYNAME => true], $provider->getAttributes($context, $image, [FilesPlugin::HAS_PREVIEW_PROPERTYNAME]));
		self::assertSame([FilesPlugin::HAS_PREVIEW_PROPERTYNAME => false], $provider->getAttributes($context, $text, [FilesPlugin::HAS_PREVIEW_PROPERTYNAME]));
	}
}
