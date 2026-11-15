<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Tests\Service;

use OC\FilesMetadata\Model\FilesMetadata;
use OCA\Files\Service\LivePhotosService;
use OCP\Files\Node;
use OCP\FilesMetadata\IFilesMetadataManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class LivePhotosServiceTest extends TestCase {
	private function getMetadataManager(array $peers): IFilesMetadataManager {
		$manager = $this->createMock(IFilesMetadataManager::class);
		$manager->method('getMetadataForNode')
			->willReturnCallback(function (Node $node) use ($peers) {
				$id = $node->getId();
				$metadata = new FilesMetadata($id);
				if (isset($peers[$id])) {
					$metadata->setString('files-live-photo', (string)$peers[$id]);
				}
				return $metadata;
			});
		$manager->method('getMetadataForFiles')
			->willReturnCallback(function (array $ids) use ($peers) {
				$result = [];
				foreach ($ids as $id) {
					$metadata = new FilesMetadata($id);
					if (isset($peers[$id])) {
						$metadata->setString('files-live-photo', (string)$peers[$id]);
					}
					$result[$id] = $metadata;
				}
				return $result;
			});
		return $manager;
	}

	public static function getPeerIdProvider(): array {
		return [
			[[1 => 2, 2 => 1], 1, 2],
			[[1 => 2], 1, null],
			[[1 => 2], 2, null],
		];
	}

	#[DataProvider('getPeerIdProvider')]
	public function testGetPeerId(array $peers, int $fileId, ?int $expected): void {
		$metadataManager = $this->getMetadataManager($peers);
		$livePhotoService = new LivePhotosService($metadataManager);
		$file = $this->createMock(Node::class);
		$file->method('getId')->willReturn($fileId);
		$this->assertEquals($expected, $livePhotoService->getLivePhotoPeerId($file));
	}

	public static function getPeerIdsProvider(): array {
		return [
			[[1 => 2, 2 => 1], [1], [1 => 2]],
			[[1 => 2], [1], []],
			[[1 => 2], [2], []],
		];
	}

	#[DataProvider('getPeerIdsProvider')]
	public function testGetPeerIds(array $peers, array $fileIds, array $expected): void {
		$metadataManager = $this->getMetadataManager($peers);
		$livePhotoService = new LivePhotosService($metadataManager);
		$this->assertEquals($expected, $livePhotoService->getLivePhotoPeerIds($fileIds));
	}
}
