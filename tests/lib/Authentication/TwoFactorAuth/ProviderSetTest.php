<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\ProviderSet;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\Authentication\TwoFactorAuth\IProvider;
use Test\TestCase;

class ProviderSetTest extends TestCase {
	/** @var ProviderSet */
	private $providerSet;

	public function testIndexesProviders() {
		$p1 = $this->createMock(IProvider::class);
		$p1->method('getId')->willReturn('p1');
		$p2 = $this->createMock(IProvider::class);
		$p2->method('getId')->willReturn('p2');
		$expected = [
			'p1' => $p1,
			'p2' => $p2,
		];

		$set = new ProviderSet([$p2, $p1], false);

		$this->assertEquals($expected, $set->getProviders());
	}

	public function testGet3rdPartyProviders() {
		$p1 = $this->createMock(IProvider::class);
		$p1->method('getId')->willReturn('p1');
		$p2 = $this->createMock(IProvider::class);
		$p2->method('getId')->willReturn('p2');
		$p3 = $this->createMock(BackupCodesProvider::class);
		$p3->method('getId')->willReturn('p3');
		$expected = [
			'p1' => $p1,
			'p2' => $p2,
		];

		$set = new ProviderSet([$p2, $p1], false);

		$this->assertEquals($expected, $set->getPrimaryProviders());
	}

	public function testGetProvider() {
		$p1 = $this->createMock(IProvider::class);
		$p1->method('getId')->willReturn('p1');

		$set = new ProviderSet([$p1], false);
		$provider = $set->getProvider('p1');

		$this->assertEquals($p1, $provider);
	}

	public function testGetProviderNotFound() {
		$set = new ProviderSet([], false);
		$provider = $set->getProvider('p1');

		$this->assertNull($provider);
	}

	public function testIsProviderMissing() {
		$set = new ProviderSet([], true);

		$this->assertTrue($set->isProviderMissing());
	}
}
