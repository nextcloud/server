<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files;

use OCA\DAV\Files\LazySearchBackend;
use PHPUnit\Framework\MockObject\MockObject;
use SearchDAV\Backend\ISearchBackend;
use Test\TestCase;

class LazySearchBackendTest extends TestCase {
	private ISearchBackend&MockObject $backend;
	private LazySearchBackend $lazyBackend;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(ISearchBackend::class);
		$this->lazyBackend = new LazySearchBackend();
	}

	public function testIsValidScopeWithoutBackend(): void {
		$this->assertFalse($this->lazyBackend->isValidScope('/files/alice', 1, '/alice'));
	}

	public function testIsValidScopeDelegatesToBackend(): void {
		$this->backend->method('getArbiterPath')
			->willReturn('');
		$this->backend->expects($this->exactly(2))
			->method('isValidScope')
			->with('/files/alice/does-not-exist', 1, '/alice/does-not-exist')
			->willReturnOnConsecutiveCalls(false, true);
		$this->lazyBackend->setBackend($this->backend);

		$this->assertFalse($this->lazyBackend->isValidScope('/files/alice/does-not-exist', 1, '/alice/does-not-exist'));
		$this->assertTrue($this->lazyBackend->isValidScope('/files/alice/does-not-exist', 1, '/alice/does-not-exist'));
	}
}
