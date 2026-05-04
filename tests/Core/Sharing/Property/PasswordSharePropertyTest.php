<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\Property;

use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\IHasher;
use OCP\Server;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareOwner;
use OCP\Sharing\ShareState;
use Test\TestCase;

final class PasswordSharePropertyTest extends TestCase {
	private IUser $user;

	private PasswordSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		$this->propertyType = new PasswordSharePropertyType();
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->user->delete();
	}

	private function createDummyShare(?ShareProperty $property): Share {
		$properties = [];
		if ($property instanceof ShareProperty) {
			$properties[] = $property;
		}

		return new Share(
			'123',
			new ShareOwner($this->user->getUID()),
			0,
			ShareState::Active,
			[],
			[],
			$properties,
			[],
		);
	}

	public function testIsFiltered(): void {
		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(arguments: [$this->propertyType::class => '123']), $this->createDummyShare(new ShareProperty($this->propertyType::class, Server::get(IHasher::class)->hash('123')))));
		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(arguments: [$this->propertyType::class => '123']), $this->createDummyShare(new ShareProperty($this->propertyType::class, null))));
		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(arguments: [$this->propertyType::class => '123']), $this->createDummyShare(null)));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(arguments: [$this->propertyType::class => '456']), $this->createDummyShare(new ShareProperty($this->propertyType::class, Server::get(IHasher::class)->hash('123')))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(arguments: [$this->propertyType::class => null]), $this->createDummyShare(new ShareProperty($this->propertyType::class, Server::get(IHasher::class)->hash('123')))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, Server::get(IHasher::class)->hash('123')))));
	}
}
