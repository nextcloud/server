<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use OC\Core\AppInfo\Application;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\IHasher;
use OCP\Server;
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
			new DateTimeImmutable(),
			ShareState::Active,
			null,
			[],
			[],
			$properties,
			[],
		);
	}

	public function testGetDefaultValue(): void {
		$share = new Share(
			'123',
			new ShareUser('user', null),
			new DateTimeImmutable(),
			ShareState::Active,
			null,
			[],
			[],
			[],
			[],
		);

		$appConfig = Server::get(IAppConfig::class);
		$appConfig->deleteKey(Application::APP_ID, ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED);

		$this->assertNull($this->propertyType->getDefaultValue($share));

		$appConfig->setValueBool(Application::APP_ID, ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED, true);

		$value = $this->propertyType->getDefaultValue($share);
		$this->assertNotNull($value);
		/** @psalm-suppress RedundantCastGivenDocblockType psalm:strict and rector:strict fight over the cast -_- */
		$this->assertGreaterThan(1, strlen((string)$value));
		$this->assertTrue($this->propertyType->validateValue(Server::get(IFactory::class), $share, $value));

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

	public function testIsExpiredEmailPasswordFiltered(): void {
		/** @var DateTimeImmutable $now */
		$now = self::invokePrivate($this->propertyType, 'now');

		$password = '123';
		$hashedPassword = Server::get(IHasher::class)->hash($password);

		$secret = 'abc';

		$createShare = fn (DateTimeImmutable $lastUpdated): Share => new Share(
			'456',
			new ShareUser('user', null),
			$lastUpdated,
			ShareState::Active,
			null,
			[],
			[
				new ShareRecipient(EmailShareRecipientType::class, 'test@example.com', null, $secret),
			],
			[
				$this->propertyType::class => new ShareProperty($this->propertyType::class, $hashedPassword),
			],
			[],
		);

		$expirationIntervalSeconds = 10;
		$config = Server::get(IConfig::class);
		$config->setSystemValue('sharing.enable_mail_link_password_expiration', true);
		$config->setSystemValue('sharing.mail_link_password_expiration_interval', $expirationIntervalSeconds);

		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(secret: $secret, arguments: [$this->propertyType::class => $password]), $createShare($now)));
		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(secret: $secret, arguments: [$this->propertyType::class => $password]), $createShare($now->sub(new DateInterval('PT' . ($expirationIntervalSeconds - 1) . 'S')))));
		$this->assertFalse($this->propertyType->isFiltered(new ShareAccessContext(secret: $secret, arguments: [$this->propertyType::class => $password]), $createShare($now->sub(new DateInterval('PT' . ($expirationIntervalSeconds) . 'S')))));
		$this->assertTrue($this->propertyType->isFiltered(new ShareAccessContext(secret: $secret, arguments: [$this->propertyType::class => $password]), $createShare($now->sub(new DateInterval('PT' . ($expirationIntervalSeconds + 1) . 'S')))));

		$config->deleteSystemValue('sharing.enable_mail_link_password_expiration');
		$config->deleteSystemValue('sharing.mail_link_password_expiration_interval');
	}
}
