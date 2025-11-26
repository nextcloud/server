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
use OCP\Server;
use OCP\Snowflake\IGenerator;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreviewService::class)]
#[\PHPUnit\Framework\Attributes\Group('DB')]
class PreviewServiceTest extends TestCase {
	private PreviewService $previewService;
	private PreviewMapper $previewMapper;
	private IGenerator $snowflakeGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->previewService = Server::get(PreviewService::class);
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->snowflakeGenerator = Server::get(IGenerator::class);
		$this->previewService->deleteAll();
	}

	public function tearDown(): void {
		$this->previewService->deleteAll();
		parent::tearDown();
	}

	public function testGetAvailableFileIds(): void {
		foreach (range(1, 20) as $i) {
			$preview = new Preview();
			$preview->setId($this->snowflakeGenerator->nextId());
			$preview->setFileId($i % 10);
			$preview->setStorageId(1);
			$preview->setWidth($i);
			$preview->setHeight($i);
			$preview->setMax(true);
			$preview->setSourceMimeType('image/jpeg');
			$preview->setCropped(true);
			$preview->setEncrypted(false);
			$preview->setMimetype('image/jpeg');
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
