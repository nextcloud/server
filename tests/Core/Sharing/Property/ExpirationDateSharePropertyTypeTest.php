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
use OC\Core\AppInfo\Application;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class ExpirationDateSharePropertyTypeTest extends TestCase {
	private IUser $user;

	private ExpirationDateSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		$this->propertyType = Server::get(ExpirationDateSharePropertyType::class);
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->user->delete();
	}

	private function createDummyShare(ShareProperty $property): Share {
		return new Share(
			'123',
			new ShareUser($this->user->getUID(), null),
			0,
			ShareState::Active,
			[],
			[],
			[$property->class => $property],
			[],
		);
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	public function testGetRequired(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue(Application::APP_ID, 'shareapi_default_expire_date', 'yes');
		$config->setAppValue(Application::APP_ID, 'shareapi_default_remote_expire_date', 'yes');
		$config->setAppValue(Application::APP_ID, 'shareapi_default_internal_expire_date', 'yes');

		$keys = ['shareapi_enforce_expire_date', 'shareapi_enforce_remote_expire_date', 'shareapi_enforce_internal_expire_date'];
		foreach ($keys as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertFalse($this->propertyType->isRequired());

		foreach ($keys as $key) {
			$config->setAppValue(Application::APP_ID, $key, 'yes');
			$this->assertTrue($this->propertyType->isRequired(), $key);
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertFalse($this->propertyType->isRequired());

		$config->deleteAppValue(Application::APP_ID, 'shareapi_default_expire_date');
		$config->deleteAppValue(Application::APP_ID, 'shareapi_default_remote_expire_date');
		$config->deleteAppValue(Application::APP_ID, 'shareapi_default_internal_expire_date');
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	public function testGetDefaultValue(): void {
		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');

		$config = Server::get(IConfig::class);

		$keys = ['shareapi_default_expire_date', 'shareapi_default_remote_expire_date', 'shareapi_default_internal_expire_date'];
		foreach ($keys as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertNull($this->propertyType->getDefaultValue());

		foreach ($keys as $key) {
			$config->setAppValue(Application::APP_ID, $key, 'yes');
			$this->assertEquals($now->add(new DateInterval('P7D'))->format(DateTimeInterface::ATOM), $this->propertyType->getDefaultValue());
			$config->deleteAppValue(Application::APP_ID, $key);
		}
	}

	/**
	 * @return list<array{string, string, string}>
	 */
	public static function dataGetMinMaxDate(): array {
		return [
			['shareapi_default_expire_date', 'shareapi_enforce_expire_date', 'shareapi_expire_after_n_days'],
			['shareapi_default_remote_expire_date', 'shareapi_enforce_remote_expire_date', 'shareapi_remote_expire_after_n_days'],
			['shareapi_default_internal_expire_date', 'shareapi_enforce_internal_expire_date', 'shareapi_internal_expire_after_n_days'],
		];
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	#[DataProvider('dataGetMinMaxDate')]
	public function testGetMinMaxDate(string $defaultEnabledKey, string $defaultEnforcedKey, string $defaultValueKey): void {
		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');

		$config = Server::get(IConfig::class);

		foreach (array_merge(...self::dataGetMinMaxDate()) as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate());
		$this->assertNull($this->propertyType->getMaxDate());

		$config->setAppValue(Application::APP_ID, $defaultEnabledKey, 'yes');

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate());
		$this->assertNull($this->propertyType->getMaxDate());

		$config->setAppValue(Application::APP_ID, $defaultEnforcedKey, 'yes');
		$config->setAppValue(Application::APP_ID, $defaultValueKey, '123');

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate());
		$this->assertEquals($now->add(new DateInterval('P123DT5M')), $this->propertyType->getMaxDate());

		$config->deleteAppValue(Application::APP_ID, $defaultEnabledKey);
		$config->deleteAppValue(Application::APP_ID, $defaultEnforcedKey);
		$config->deleteAppValue(Application::APP_ID, $defaultValueKey);
	}

	public function testIsFiltered(): void {
		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');
		$future = $now->add(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);
		$past = $now->sub(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);

		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $future))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $now->format(DateTimeInterface::ATOM)))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(), $this->createDummyShare(new ShareProperty($this->propertyType::class, $past))));
	}
}
