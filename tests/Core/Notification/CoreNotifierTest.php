<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Notification;

use OC\Core\Notification\CoreNotifier;
use OC\Notification\Notification;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use Test\TestCase;

class CoreNotifierTest extends TestCase {
	public function testPrepareCodeIntegrityChanged(): void {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$l10n->method('n')->willReturnCallback(
			fn (string $singular, string $plural, int $count, array $parameters = []): string
				=> vsprintf(str_replace('%n', (string)$count, $count === 1 ? $singular : $plural), $parameters)
		);
		$factory = $this->createMock(IFactory::class);
		$factory->method('get')->with('core', 'de')->willReturn($l10n);

		$url = $this->createMock(IURLGenerator::class);
		$url->method('linkToRouteAbsolute')
			->with('settings.AdminSettings.index', ['section' => 'overview'])
			->willReturn('https://cloud.example.com/settings/admin/overview');
		$url->method('getAbsoluteURL')->willReturn('https://cloud.example.com/core/img/actions/error.svg');

		$notification = new Notification($this->createMock(IValidator::class), $this->createMock(IRichTextFormatter::class));
		$notification->setApp('core')
			->setSubject('code_integrity_changed', ['files' => 1, 'unverified' => ['core']]);

		$notifier = new CoreNotifier($this->createMock(IConfig::class), $factory, $url);
		$notifier->prepare($notification, 'de');

		$this->assertSame('The code integrity check result has changed', $notification->getParsedSubject());
		$this->assertSame(
			"1 file does not match the signed release. It may have been modified or added without authorization.\n"
			. "The signature of core is missing or invalid, so it could not be verified.\n"
			. 'Review the results in the administration overview.',
			$notification->getParsedMessage(),
		);
		$this->assertSame('https://cloud.example.com/settings/admin/overview', $notification->getLink());
	}
}
