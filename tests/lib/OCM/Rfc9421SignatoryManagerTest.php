<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use Firebase\JWT\Key;
use OC\OCM\OCMSignatoryManager;
use OC\OCM\Rfc9421SignatoryManager;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\Model\Signatory;
use Test\TestCase;

class Rfc9421SignatoryManagerTest extends TestCase {
	private Rfc9421SignatoryManager $wrapper;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->wrapper = $this->createInstanceWithMocks(Rfc9421SignatoryManager::class);
	}

	public function testGetOptionsForcesRfc9421Format(): void {
		$this->mocks[OCMSignatoryManager::class]->method('getOptions')->willReturn([
			'algorithm' => 'rsa-sha512',
			'rfc9421.format' => false,
		]);

		$options = $this->wrapper->getOptions();
		$this->assertTrue($options['rfc9421.format']);
		$this->assertSame('rsa-sha512', $options['algorithm']);
	}

	public function testGetLocalSignatoryReturnsJwksKey(): void {
		$signatory = $this->createMock(Signatory::class);
		$this->mocks[OCMSignatoryManager::class]->method('getLocalJwksSignatory')->willReturn($signatory);

		$this->assertSame($signatory, $this->wrapper->getLocalSignatory());
	}

	public function testGetLocalSignatoryThrowsWhenJwksKeyUnavailable(): void {
		$this->mocks[OCMSignatoryManager::class]->method('getLocalJwksSignatory')->willReturn(null);

		$this->expectException(IdentityNotFoundException::class);
		$this->wrapper->getLocalSignatory();
	}

	public function testProviderIdDelegated(): void {
		$this->mocks[OCMSignatoryManager::class]->method('getProviderId')->willReturn('ocm');
		$this->assertSame('ocm', $this->wrapper->getProviderId());
	}

	public function testRemoteSignatoryDelegated(): void {
		$signatory = $this->createMock(Signatory::class);
		$this->mocks[OCMSignatoryManager::class]->expects($this->once())
			->method('getRemoteSignatory')
			->with('sender.example.org')
			->willReturn($signatory);
		$this->assertSame($signatory, $this->wrapper->getRemoteSignatory('sender.example.org'));
	}

	public function testRemoteKeyDelegated(): void {
		$key = $this->createMock(Key::class);
		$this->mocks[OCMSignatoryManager::class]->expects($this->once())
			->method('getRemoteKey')
			->with('sender.example.org', 'kid-1')
			->willReturn($key);
		$this->assertSame($key, $this->wrapper->getRemoteKey('sender.example.org', 'kid-1'));
	}
}
