<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareOwner;
use OCP\Sharing\ShareState;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class ExpirationDateSharePropertyTest extends TestCase {
	private IUser $user;

	private ExpirationDateSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		$this->propertyType = new ExpirationDateSharePropertyType();
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->user->delete();
	}

	private function createDummyShare(ShareProperty $property): Share {
		return new Share(
			'123',
			new ShareOwner($this->user->getUID()),
			0,
			ShareState::Active,
			[],
			[],
			[$property],
			[],
		);
	}

	public function testIsFiltered(): void {
		$now = new DateTimeImmutable();
		$future = $now->add(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);
		$past = $now->sub(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);

		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $future))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $now->format(DateTimeInterface::ATOM)))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $past))));
	}
}
