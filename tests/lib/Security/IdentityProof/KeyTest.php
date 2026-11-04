<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\IdentityProof;

use OC\Security\IdentityProof\Key;
use OCP\Security\IdentityProof\Algorithm;
use Test\TestCase;

class KeyTest extends TestCase {
	/** @var Key */
	private $key;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->key = new Key('public', 'private', Algorithm::Es384);
	}

	public function testGetPrivate(): void {
		$this->assertSame('private', $this->key->getPrivate());
	}

	public function testGetPublic(): void {
		$this->assertSame('public', $this->key->getPublic());
	}

	public function testGetAlgorithm(): void {
		$this->assertSame(Algorithm::Es384, $this->key->getAlgorithm());
	}
}
