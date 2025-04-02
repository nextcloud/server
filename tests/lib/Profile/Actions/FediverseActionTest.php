<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Remote;

use OC\Profile\Actions\FediverseAction;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FediverseActionTest extends TestCase {

	private FediverseAction $action;

	private IAccountManager&MockObject $accountManager;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$factory = $this->createMock(IFactory::class);
		$factory->method('get')
			->with('lib')
			->willReturn($this->l10n);

		$this->action = new FediverseAction(
			$this->accountManager,
			$factory,
			$this->urlGenerator,
		);
	}

	public function testGetActionId(): void {
		self::assertEquals(
			IAccountManager::PROPERTY_FEDIVERSE,
			$this->action->getId(),
		);
	}

	public function testGetAppId(): void {
		self::assertEquals(
			'core',
			$this->action->getAppId(),
		);
	}

	public function testGetDisplayId(): void {
		$this->l10n->expects(self::once())
			->method('t')
			->with('Fediverse')
			->willReturn('Translated fediverse');

		self::assertEquals(
			'Translated fediverse',
			$this->action->getDisplayId(),
		);
	}

	public function testGetPriority(): void {
		self::assertEquals(
			50,
			$this->action->getPriority(),
		);
	}

	public function testGetIcon(): void {
		$this->urlGenerator->expects(self::once())
			->method('getAbsoluteURL')
			->with('the-image-path')
			->willReturn('resolved-image-path');

		$this->urlGenerator->expects(self::once())
			->method('imagePath')
			->with('core', 'actions/mastodon.svg')
			->willReturn('the-image-path');

		self::assertEquals(
			'resolved-image-path',
			$this->action->getIcon(),
		);
	}

	public static function dataGetTitle(): array {
		return [
			['the-user@example.com'],
			['@the-user@example.com'],
		];
	}

	/** @dataProvider dataGetTitle */
	public function testGetTitle(string $value): void {
		$property = $this->createMock(IAccountProperty::class);
		$property->method('getValue')
			->willReturn($value);

		$user = $this->createMock(IUser::class);

		$account = $this->createMock(IAccount::class);
		$account->method('getProperty')
			->with(IAccountManager::PROPERTY_FEDIVERSE)
			->willReturn($property);

		$this->accountManager->method('getAccount')
			->with($user)
			->willReturn($account);

		$this->l10n->expects(self::once())
			->method('t')
			->with(self::anything(), ['@the-user@example.com'])
			->willReturn('Translated title');

		// Preload and test
		$this->action->preload($user);
		self::assertEquals(
			'Translated title',
			$this->action->getTitle(),
		);
	}

	public static function dataGetTarget(): array {
		return [
			['', null],
			[null, null],
			['user@example.com', 'https://example.com/@user'],
			['@user@example.com', 'https://example.com/@user'],
			['@user@social.example.com', 'https://social.example.com/@user'],
			// invalid stuff
			['@user', null],
			['@example.com', null],
			['@@example.com', null],
			// evil stuff
			['user@example.com/evil.exe', null],
			['@user@example.com/evil.exe', null],
			['user/evil.exe@example.com', null],
			['@user/evil.exe@example.com', null],
		];
	}

	/** @dataProvider dataGetTarget */
	public function testGetTarget(?string $value, ?string $expected): void {
		$user = $this->createMock(IUser::class);

		$account = $this->createMock(IAccount::class);
		$this->accountManager->method('getAccount')
			->with($user)
			->willReturn($account);

		if ($value === null) {
			// Property does not exist so throw
			$account->method('getProperty')
				->with(IAccountManager::PROPERTY_FEDIVERSE)
				->willThrowException(new PropertyDoesNotExistException(IAccountManager::PROPERTY_FEDIVERSE));
		} else {
			$property = $this->createMock(IAccountProperty::class);
			$property->method('getValue')
				->willReturn($value);
			$account->method('getProperty')
				->willReturn($property);
		}

		// Preload and test
		$this->action->preload($user);
		self::assertEquals(
			$expected,
			$this->action->getTarget(),
		);
	}
}
