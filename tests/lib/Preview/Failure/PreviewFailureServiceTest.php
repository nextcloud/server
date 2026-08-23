<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview\Failure;

use OC\Preview\Failure\PreviewFailure;
use OC\Preview\Failure\PreviewFailureMapper;
use OC\Preview\Failure\PreviewFailureService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PreviewFailureServiceTest extends TestCase {
	private PreviewFailureMapper&MockObject $mapper;
	private ITimeFactory&MockObject $time;
	private PreviewFailureService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->mapper = $this->createMock(PreviewFailureMapper::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')->willReturn(1_700_000_000);
		$config = $this->createMock(IConfig::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRoute')->willReturn('/files/42');
		$logger = $this->createMock(LoggerInterface::class);
		$this->service = new PreviewFailureService($this->mapper, $this->time, $config, $urlGenerator, $logger);
	}

	public function testRecordInsertsWhenMissing(): void {
		$file = $this->file();
		$this->mapper->method('findByFileId')->willThrowException(new DoesNotExistException('missing'));
		$this->mapper->expects($this->once())->method('insert')->with($this->callback(function (PreviewFailure $failure) {
			$this->assertSame(42, $failure->getFileId());
			$this->assertSame('image/heic', $failure->getMime());
			$this->assertSame('OC\\Preview\\Imaginary', $failure->getProvider());
			$this->assertStringNotContainsString('secret', $failure->getError());
			return true;
		}));

		$this->service->record($file, 'image/heic', 'OC\\Preview\\Imaginary', 'failed key=secret https://imaginary/internal');
	}

	public function testRecordIncrementsExisting(): void {
		$file = $this->file();
		$existing = new PreviewFailure();
		$existing->setFileId(42);
		$existing->setAttempts(2);
		$existing->setCreatedAt(1);
		$this->mapper->method('findByFileId')->willReturn($existing);
		$this->mapper->expects($this->once())->method('update')->with($this->callback(function (PreviewFailure $failure) {
			$this->assertSame(3, $failure->getAttempts());
			$this->assertSame(1_700_000_000, $failure->getLastAttempt());
			return true;
		}));
		$this->mapper->expects($this->never())->method('insert');

		$this->service->record($file, 'image/jpeg', 'OC\\Preview\\JPEG', 'broken');
	}

	public function testClearForFileDeletesRow(): void {
		$this->mapper->expects($this->once())->method('deleteByFileId')->with(42);
		$this->service->clearForFile(42);
	}

	private function file(): File {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getOwner')->willReturn($user);
		$file->method('getPath')->willReturn('/alice/files/photo.jpg');
		return $file;
	}
}
