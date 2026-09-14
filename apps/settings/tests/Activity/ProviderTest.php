<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests;

use OCA\Settings\Activity\Provider;
use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderTest extends TestCase {
	private IFactory&MockObject $l10nFactory;
	private IURLGenerator&MockObject $urlGenerator;
	private IUserManager&MockObject $userManager;
	private IManager&MockObject $activityManager;
	private IL10N&MockObject $l;
	private Provider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);

		$this->l10nFactory->method('get')
			->with('settings', 'en')
			->willReturn($this->l);

		$this->provider = new Provider(
			$this->l10nFactory,
			$this->urlGenerator,
			$this->userManager,
			$this->activityManager,
		);
	}

	public function testParseUnrelatedApp(): void {
		$event = $this->createMock(IEvent::class);
		$event->method('getApp')->willReturn('comments');

		$this->expectException(UnknownActivityException::class);
		$this->provider->parse('en', $event);
	}

	public function testParseUnknownSubject(): void {
		$event = $this->createMock(IEvent::class);
		$event->method('getApp')->willReturn('settings');
		$event->method('getSubject')->willReturn('something_else');

		$this->expectException(UnknownActivityException::class);
		$this->provider->parse('en', $event);
	}

	public static function dataRevokedAllCount(): array {
		return [
			'single token' => [1],
			'several tokens' => [7],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataRevokedAllCount')]
	public function testParseRevokedAllUsesPluralForm(int $count): void {
		$event = $this->createMock(IEvent::class);
		$event->method('getApp')->willReturn('settings');
		$event->method('getSubject')->willReturn(Provider::APP_TOKEN_DELETED_ALL);
		$event->method('getSubjectParameters')->willReturn(['count' => $count]);

		$this->l->expects($this->once())
			->method('n')
			->with(
				'You revoked %n other session',
				'You revoked %n other sessions',
				$count,
			)
			->willReturn('parsed subject');

		// Aggregate event, so there are no rich parameters to substitute.
		$event->expects($this->once())
			->method('setRichSubject')
			->with('parsed subject', []);

		$this->provider->parse('en', $event);
	}

	public function testParseRevokedAllWithoutCountParameter(): void {
		$event = $this->createMock(IEvent::class);
		$event->method('getApp')->willReturn('settings');
		$event->method('getSubject')->willReturn(Provider::APP_TOKEN_DELETED_ALL);
		$event->method('getSubjectParameters')->willReturn([]);

		$this->l->expects($this->once())
			->method('n')
			->with($this->anything(), $this->anything(), 0)
			->willReturn('parsed subject');

		$this->provider->parse('en', $event);
	}
}
