<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\PreviewService;
use OCP\IPreview;
use OCP\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @group DB
 */
#[CoversClass(PreviewService::class)]
class PreviewServiceTest extends TestCase {
	private PreviewService $previewService;

	protected function setUp(): void {
		$this->previewService = Server::get(PreviewService::class);
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->previewService->deleteAll();
	}

	public function tearDown(): void {
		$this->previewService->deleteAll();
	}

	public function testGetAvailableFileIds(): void {
		foreach (range(1, 20) as $i) {
			$preview = new Preview();
			$preview->setFileId($i % 10);
			$preview->setStorageId(1);
			$preview->setWidth($i);
			$preview->setHeight($i);
			$preview->setMax(true);
			$preview->setSourceMimetype(1);
			$preview->setCropped(true);
			$preview->setEncrypted(false);
			$preview->setMimetype(IPreview::MIMETYPE_JPEG);
			$preview->setEtag('abc');
			$preview->setMtime((new \DateTime())->getTimestamp());
			$preview->setSize(0);
			$this->previewMapper->insert($preview);
		}

		$files = iterator_to_array($this->previewService->getAvailableFileIds());
		$this->assertCount(1, $files);
		$this->assertCount(10, $files[0]['fileIds']);
	}
}
