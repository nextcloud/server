<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Test\Unit\Activity;

use OCA\TwoFactorBackupCodes\Activity\Provider;
use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProviderTest extends TestCase {
	private IFactory&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private IManager&MockObject $activityManager;
	private Provider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->activityManager = $this->createMock(IManager::class);

		$this->provider = new Provider($this->l10n, $this->urlGenerator, $this->activityManager);
	}

	public function testParseUnrelated(): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->expectException(UnknownActivityException::class);

		$this->provider->parse($lang, $event);
	}

	public static function subjectData(): array {
		return [
			['codes_generated'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'subjectData')]
	public function testParse(string $subject): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$l = $this->createMock(IL10N::class);

		$event->expects($this->once())
			->method('getApp')
			->willReturn('twofactor_backupcodes');
		$this->l10n->expects($this->once())
			->method('get')
			->with('twofactor_backupcodes', $lang)
			->willReturn($l);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/password.svg')
			->willReturn('path/to/image');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('path/to/image')
			->willReturn('absolute/path/to/image');
		$event->expects($this->once())
			->method('setIcon')
			->with('absolute/path/to/image');
		$event->expects($this->once())
			->method('getSubject')
			->willReturn($subject);
		$event->expects($this->once())
			->method('setParsedSubject');

		$this->provider->parse($lang, $event);
	}

	public function testParseInvalidSubject(): void {
		$lang = 'ru';
		$l = $this->createMock(IL10N::class);
		$event = $this->createMock(IEvent::class);

		$event->expects($this->once())
			->method('getApp')
			->willReturn('twofactor_backupcodes');
		$this->l10n->expects($this->once())
			->method('get')
			->with('twofactor_backupcodes', $lang)
			->willReturn($l);
		$event->expects($this->once())
			->method('getSubject')
			->willReturn('unrelated');

		$this->expectException(UnknownActivityException::class);
		$this->provider->parse($lang, $event);
	}
}
