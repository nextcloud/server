<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Federation\FederatedCalendarImpl;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FederatedCalendarImplTest extends TestCase {
	private FederatedCalendarImpl $federatedCalendarImpl;

	private CalDavBackend&MockObject $calDavBackend;

	protected function setUp(): void {
		parent::setUp();

		$this->calDavBackend = $this->createMock(CalDavBackend::class);

		$this->federatedCalendarImpl = new FederatedCalendarImpl(
			[],
			$this->calDavBackend,
		);
	}

	public function testGetPermissions(): void {
		$this->assertEquals(1, $this->federatedCalendarImpl->getPermissions());
	}

	public function testIsDeleted(): void {
		$this->assertFalse($this->federatedCalendarImpl->isDeleted());
	}

	public function testIsShared(): void {
		$this->assertTrue($this->federatedCalendarImpl->isShared());
	}

	public function testIsWritable(): void {
		$this->assertFalse($this->federatedCalendarImpl->isWritable());
	}
}
