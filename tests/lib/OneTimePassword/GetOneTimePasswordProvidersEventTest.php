<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\OneTimePassword;

use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class OneTimePasswordTest
 *
 * @package Test\OneTimePassword
 */
#[Group(name: 'OTP')]
class GetOneTimePasswordProvidersEventTest extends TestCase {

	public static function dataTestAddProvider() {
		return [
			[['mail'], 'mail', ['mail']],
			[['mail'], 'other', []],
			[['mail', 'other'], 'mail', ['mail']],
			[['mail', 'mail', 'other'], 'mail', ['mail', 'mail']]

		];
	}

	#[DataProvider('dataTestAddProvider')]
	public function testAddProvider(array $providerIds, ?string $filterId, array $expectedIds) {
		$event = new GetOneTimePasswordProvidersEvent($filterId);

		foreach ($providerIds as $pId) {
			$provider = $this->createMock(IOneTimePasswordProvider::class);
			$provider->method('getProviderId')->willReturn($pId);
			$event->addProvider($provider);
		}

		$result = $event->getProviders();
		$actualIds = [];
		foreach ($result as $provider) {
			$actualIds[] = $provider->getProviderId();
		}

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($expectedIds, $actualIds, []);
	}

}
