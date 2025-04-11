<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Mail\Provider;

use OCP\Mail\Provider\Address;
use Test\TestCase;

class AddressTest extends TestCase {

	/** @var Address&MockObject */
	private Address $address;

	protected function setUp(): void {
		parent::setUp();

		$this->address = new Address('user1@testing.com', 'User One');

	}

	public function testAddress(): void {
		
		// test set by constructor
		$this->assertEquals('user1@testing.com', $this->address->getAddress());
		// test set by setter
		$this->address->setAddress('user2@testing.com');
		$this->assertEquals('user2@testing.com', $this->address->getAddress());

	}

	public function testLabel(): void {
		
		// test set by constructor
		$this->assertEquals('User One', $this->address->getLabel());
		// test set by setter
		$this->address->setLabel('User Two');
		$this->assertEquals('User Two', $this->address->getLabel());

	}

}
