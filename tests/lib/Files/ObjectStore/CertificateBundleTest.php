<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\CertificateBundle;
use OCP\ICertificateManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CertificateBundleTest extends TestCase {
	private ICertificateManager&MockObject $certificateManager;

	protected function setUp(): void {
		parent::setUp();
		$this->certificateManager = $this->createMock(ICertificateManager::class);
		$this->certificateManager->method('getDefaultCertificatesBundlePath')->willReturn('/default.crt');
		$this->certificateManager->method('getAbsoluteBundlePath')->willReturn('/absolute.crt');
		$this->overwriteService(ICertificateManager::class, $this->certificateManager);
	}

	protected function tearDown(): void {
		$this->restoreService(ICertificateManager::class);
		parent::tearDown();
	}

	public function testSystemBundle(): void {
		$this->certificateManager->expects($this->never())->method('getAbsoluteBundlePath');
		$this->assertNull(CertificateBundle::getPath(false, true));
		$this->assertNull(CertificateBundle::getPath(false, false));
	}

	public function testPrimaryStorageUsesDefaultBundle(): void {
		$this->certificateManager->expects($this->never())->method('getAbsoluteBundlePath');
		$this->assertSame('/default.crt', CertificateBundle::getPath(true, true));
	}

	public function testOtherStorageUsesAbsoluteBundle(): void {
		$this->assertSame('/absolute.crt', CertificateBundle::getPath(true, false));
	}
}
