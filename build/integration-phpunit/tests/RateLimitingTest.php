<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration\Tests;

use NextcloudIntegration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Rate limiting of app framework routes.
 *
 * Exercises the two routes of OCA\Testing\Controller\RateLimitTestController:
 * one carrying only an anonymous limit, one carrying an anonymous and a user
 * limit. Limits are keyed by client IP respectively user, and the counters are
 * only released by time, so tests sharing a route have to wait out its period.
 */
#[Group('RateLimiting')]
final class RateLimitingTest extends ApiTestCase {
	/** #[AnonRateLimit(limit: 1, period: 10)] */
	private const ANON_PROTECTED = '/index.php/apps/testing/anonProtected';
	private const ANON_PROTECTED_PERIOD = 10;

	/** #[UserRateLimit(limit: 5, period: 100)] and #[AnonRateLimit(limit: 1, period: 100)] */
	private const USER_AND_ANON_PROTECTED = '/index.php/apps/testing/userAndAnonProtected';
	private const USER_AND_ANON_USER_LIMIT = 5;

	private static bool $testingAppWasEnabled = false;

	public static function setUpBeforeClass(): void {
		self::$testingAppWasEnabled = self::occ()->isAppEnabled('testing');
		if (!self::$testingAppWasEnabled) {
			self::occ()->enableApp('testing');
		}

		// Rate limiting is disabled by default and by build/integration/run.sh.
		self::occ()->setSystemConfig('ratelimit.protection.enabled', 'true', 'bool');

		self::users()->ensureExists('user0');
	}

	public static function tearDownAfterClass(): void {
		self::occ()->setSystemConfig('ratelimit.protection.enabled', 'false', 'bool');

		if (!self::$testingAppWasEnabled) {
			self::occ()->disableApp('testing');
		}
	}

	public function testAnonymousLimitAlsoAppliesToAuthenticatedRequests(): void {
		$this->waitOutAnonProtectedPeriod();
		$user0 = self::user('user0');

		self::assertStatus(200, $user0->request('GET', self::ANON_PROTECTED));
		self::assertStatus(429, $user0->request('GET', self::ANON_PROTECTED),
			'The route carries no user limit, so an authenticated client is counted against the anonymous limit.');

		$this->waitOutAnonProtectedPeriod();
		self::assertStatus(200, $user0->request('GET', self::ANON_PROTECTED));
	}

	public function testAnonymousAndAuthenticatedRequestsShareTheSameLimit(): void {
		$this->waitOutAnonProtectedPeriod();

		self::assertStatus(200, self::guest()->request('GET', self::ANON_PROTECTED));
		self::assertStatus(429, self::user('user0')->request('GET', self::ANON_PROTECTED),
			'Both clients come from the same address and the route has no user limit, so they share one counter.');

		$this->waitOutAnonProtectedPeriod();
		self::assertStatus(200, self::user('user0')->request('GET', self::ANON_PROTECTED));
	}

	public function testUserLimitIsCountedSeparatelyFromAnonymousLimit(): void {
		$guest = self::guest();
		$user0 = self::user('user0');

		self::assertStatus(200, $guest->request('GET', self::USER_AND_ANON_PROTECTED));
		self::assertStatus(429, $guest->request('GET', self::USER_AND_ANON_PROTECTED),
			'The anonymous limit of 1 request is exhausted.');

		// The user counter is untouched by the two guest requests above.
		for ($request = 1; $request <= self::USER_AND_ANON_USER_LIMIT; $request++) {
			self::assertStatus(200, $user0->request('GET', self::USER_AND_ANON_PROTECTED),
				sprintf('Request %d of the user limit of %d.', $request, self::USER_AND_ANON_USER_LIMIT));
		}

		self::assertStatus(429, $user0->request('GET', self::USER_AND_ANON_PROTECTED),
			sprintf('Request %d exceeds the user limit of %d.', self::USER_AND_ANON_USER_LIMIT + 1, self::USER_AND_ANON_USER_LIMIT));

		self::assertStatus(429, $guest->request('GET', self::USER_AND_ANON_PROTECTED),
			'The anonymous counter is still exhausted and was not reset by the authenticated requests.');
	}

	/**
	 * Waits until the anonymous counter of {@see self::ANON_PROTECTED} is
	 * released again. The counter is shared by every test using that route, so
	 * each of them has to wait rather than relying on the order they run in.
	 */
	private function waitOutAnonProtectedPeriod(): void {
		sleep(self::ANON_PROTECTED_PERIOD + 1);
	}
}
