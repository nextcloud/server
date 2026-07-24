<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Property;

use OC\Core\AppInfo\Application;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\IHasher;
use OCP\Server;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class PasswordSharePropertyTypeTest extends TestCase {
	private IUser $user;

	private PasswordSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		$this->propertyType = Server::get(PasswordSharePropertyType::class);
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->user->delete();
	}

	private function createDummyShare(?ShareProperty $property): Share {
		$properties = [];
		if ($property instanceof ShareProperty) {
			$properties[$property->class] = $property;
		}

		return new Share(
			'123',
			new ShareUser($this->user->getUID(), null),
			0,
			ShareState::Active,
			[],
			[],
			$properties,
			[],
		);
	}

	public function testGetDefaultValue(): void {
		$appConfig = Server::get(IAppConfig::class);
		$appConfig->deleteKey(Application::APP_ID, ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED);

		$this->assertNull($this->propertyType->getDefaultValue());

		$appConfig->setValueBool(Application::APP_ID, ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED, true);

		$value = $this->propertyType->getDefaultValue();
		$this->assertNotNull($value);
		/** @psalm-suppress RedundantCastGivenDocblockType psalm:strict and rector:strict fight over the cast -_- */
		$this->assertGreaterThan(1, strlen((string)$value));
		$this->assertTrue($this->propertyType->validateValue(Server::get(IFactory::class), $value));

		$appConfig->deleteKey(Application::APP_ID, ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED);
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
