<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\IdentityProof;

use OC\Security\IdentityProof\Key;
use Test\TestCase;

class KeyTest extends TestCase {
	/** @var Key */
	private $key;

	protected function setUp(): void {
		parent::setUp();

		$this->key = new Key('public', 'private');
	}

	public function testGetPrivate(): void {
		$this->assertSame('private', $this->key->getPrivate());
	}

	public function testGetPublic(): void {
		$this->assertSame('public', $this->key->getPublic());
	}
}
