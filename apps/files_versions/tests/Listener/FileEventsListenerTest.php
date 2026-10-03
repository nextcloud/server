<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Tests\Listener;

use OCA\Files_Versions\Listener\FileEventsListener;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FileEventsListenerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private IVersionManager&MockObject $versionManager;
	private IMimeTypeLoader&MockObject $mimeTypeLoader;
	private IUserSession&MockObject $userSession;
	private LoggerInterface&MockObject $logger;
	private FileEventsListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->versionManager = $this->createMock(IVersionManager::class);
		$this->mimeTypeLoader = $this->createMock(IMimeTypeLoader::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new FileEventsListener(
			$this->rootFolder,
			$this->versionManager,
			$this->mimeTypeLoader,
			$this->userSession,
			$this->logger,
		);
	}

	private function createUnresolvableFile(): File&MockObject {
		$this->userSession->method('getUser')->willReturn(null);

		$node = $this->createMock(File::class);
		$node->method('getOwner')->willReturn(null);
		$node->method('getPath')->willReturn('/test.txt');
		$node->method('getId')->willReturn(42);
		$node->method('getSize')->willReturn(100);
		$node->method('getMTime')->willReturn(1234567890);

		return $node;
	}

	private function getPrivateProperty(string $property): mixed {
		$ref = new \ReflectionProperty(FileEventsListener::class, $property);
		return $ref->getValue($this->listener);
	}

	public function testGetPathForNodeReturnsNullWhenUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->logger->expects($this->once())
			->method('debug')
			->with('Failed to compute path for node', $this->anything());

		$method = new \ReflectionMethod(FileEventsListener::class, 'getPathForNode');

		$this->assertNull($method->invoke($this->listener, $node));
	}

	public function testWriteHookSkipsWhenPathUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->listener->write_hook($node);

		$this->assertSame([], $this->getPrivateProperty('writeHookInfo'));
	}

	public function testPreRemoveHookSkipsWhenPathUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->listener->pre_remove_hook($node);

		$this->assertSame([], $this->getPrivateProperty('versionsDeleted'));
	}
}
