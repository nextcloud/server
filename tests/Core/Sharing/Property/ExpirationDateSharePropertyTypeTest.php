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
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use OC\Core\AppInfo\Application;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class ExpirationDateSharePropertyTypeTest extends TestCase {
	private IUser $user;

	private ExpirationDateSharePropertyType $propertyType;

	private ShareRecipient $tokenRecipient;

	private ShareRecipient $emailRecipient;

	private ShareRecipient $remoteRecipient;

	private ShareRecipient $localNonTokenAndEmailRecipient;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		$this->propertyType = Server::get(ExpirationDateSharePropertyType::class);

		$this->tokenRecipient = new ShareRecipient(TokenShareRecipientType::class, 'token', null);
		$this->emailRecipient = new ShareRecipient(EmailShareRecipientType::class, 'example@example.com', null);
		$this->remoteRecipient = new ShareRecipient(UserShareRecipientType::class, 'user', 'https://example.com');
		$this->localNonTokenAndEmailRecipient = new ShareRecipient(UserShareRecipientType::class, 'user', null);
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

	/**
	 * @return list<array{string, string, string}>
	 */
	public static function dataConfigKeys(): array {
		return [
			['shareapi_default_expire_date', 'shareapi_enforce_expire_date', 'shareapi_expire_after_n_days'],
			['shareapi_default_remote_expire_date', 'shareapi_enforce_remote_expire_date', 'shareapi_remote_expire_after_n_days'],
			['shareapi_default_internal_expire_date', 'shareapi_enforce_internal_expire_date', 'shareapi_internal_expire_after_n_days'],
		];
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	#[DataProvider('dataConfigKeys')]
	public function testGetRequired(string $defaultEnabledKey, string $defaultEnforcedKey, string $defaultValueKey): void {
		$share = new Share(
			'123',
			new ShareUser('user', null),
			0,
			ShareState::Active,
			[],
			[
				$this->tokenRecipient,
				$this->emailRecipient,
				$this->remoteRecipient,
				$this->localNonTokenAndEmailRecipient,
			],
			[],
			[],
		);

		$config = Server::get(IConfig::class);
		foreach (array_merge(...self::dataConfigKeys()) as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$config->setAppValue(Application::APP_ID, $defaultEnabledKey, 'yes');

		$this->assertFalse($this->propertyType->isRequired($share));

		$config->setAppValue(Application::APP_ID, $defaultEnforcedKey, 'yes');
		$this->assertTrue($this->propertyType->isRequired($share));

		$config->deleteAppValue(Application::APP_ID, $defaultEnabledKey);
		$config->deleteAppValue(Application::APP_ID, $defaultEnforcedKey);
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	#[DataProvider('dataConfigKeys')]
	public function testGetDefaultValue(string $defaultEnabledKey, string $defaultEnforcedKey, string $defaultValueKey): void {
		$share = new Share(
			'123',
			new ShareUser('user', null),
			0,
			ShareState::Active,
			[],
			[
				$this->tokenRecipient,
				$this->emailRecipient,
				$this->remoteRecipient,
				$this->localNonTokenAndEmailRecipient,
			],
			[],
			[],
		);

		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');

		$config = Server::get(IConfig::class);
		foreach (array_merge(...self::dataConfigKeys()) as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertNull($this->propertyType->getDefaultValue($share));

		$config->setAppValue(Application::APP_ID, $defaultEnabledKey, 'yes');
		$this->assertEquals($now->add(new DateInterval('P7D'))->format(DateTimeInterface::ATOM), $this->propertyType->getDefaultValue($share));
		$config->deleteAppValue(Application::APP_ID, $defaultEnabledKey);
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	#[DataProvider('dataConfigKeys')]
	public function testGetMinMaxDate(string $defaultEnabledKey, string $defaultEnforcedKey, string $defaultValueKey): void {
		$share = new Share(
			'123',
			new ShareUser('user', null),
			0,
			ShareState::Active,
			[],
			[
				$this->tokenRecipient,
				$this->emailRecipient,
				$this->remoteRecipient,
				$this->localNonTokenAndEmailRecipient,
			],
			[],
			[],
		);

		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');

		$config = Server::get(IConfig::class);
		foreach (array_merge(...self::dataConfigKeys()) as $key) {
			$config->deleteAppValue(Application::APP_ID, $key);
		}

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate($share));
		$this->assertNull($this->propertyType->getMaxDate($share));

		$config->setAppValue(Application::APP_ID, $defaultEnabledKey, 'yes');

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate($share));
		$this->assertNull($this->propertyType->getMaxDate($share));

		$config->setAppValue(Application::APP_ID, $defaultEnforcedKey, 'yes');
		$config->setAppValue(Application::APP_ID, $defaultValueKey, '123');

		$this->assertEquals($now->add(new DateInterval('PT5M')), $this->propertyType->getMinDate($share));
		$this->assertEquals($now->add(new DateInterval('P123DT5M')), $this->propertyType->getMaxDate($share));

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
