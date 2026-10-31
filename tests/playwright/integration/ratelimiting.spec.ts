/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expectStatus, test } from './fixtures/api.ts'

/** Route carrying `#[AnonRateLimit(limit: 1, period: 10)]`. */
const ANON_PROTECTED = 'apps/testing/anonProtected'
const ANON_PROTECTED_PERIOD = 10

/** Route carrying `#[UserRateLimit(limit: 5, period: 100)]` and `#[AnonRateLimit(limit: 1, period: 100)]`. */
const USER_AND_ANON_PROTECTED = 'apps/testing/userAndAnonProtected'
const USER_AND_ANON_USER_LIMIT = 5

/**
 * Rate limiting of app framework routes.
 *
 * Exercises the two routes of OCA\Testing\Controller\RateLimitTestController.
 * Limits are keyed by client IP respectively user and are only released by
 * time, so tests sharing a route have to wait out its period.
 */
test.describe('Rate limiting', () => {
	let testingAppWasEnabled = false

	test.beforeAll(async () => {
		const { stdout } = await runOcc(['app:list', '--output=json'])
		testingAppWasEnabled = 'testing' in JSON.parse(stdout).enabled
		if (!testingAppWasEnabled) {
			await runOcc(['app:enable', '--force', 'testing'])
		}

		// Rate limiting is disabled by default and by the test server setup.
		// Enabling it affects the whole instance, which is why this project runs
		// on its own with a single worker.
		await runOcc(['config:system:set', 'ratelimit.protection.enabled', '--value', 'true', '--type', 'bool'])
	})

	test.afterAll(async () => {
		await runOcc(['config:system:set', 'ratelimit.protection.enabled', '--value', 'false', '--type', 'bool'])

		if (!testingAppWasEnabled) {
			await runOcc(['app:disable', 'testing'])
		}
	})

	test('the anonymous limit also applies to authenticated requests', async ({ userRequest }) => {
		await waitOutAnonProtectedPeriod()

		await expectStatus(await userRequest.get(ANON_PROTECTED), 200)
		await expectStatus(
			await userRequest.get(ANON_PROTECTED),
			429,
			'The route carries no user limit, so an authenticated client is counted against the anonymous limit.',
		)

		await waitOutAnonProtectedPeriod()
		await expectStatus(await userRequest.get(ANON_PROTECTED), 200)
	})

	test('anonymous and authenticated requests share the same limit', async ({ guestRequest, userRequest }) => {
		await waitOutAnonProtectedPeriod()

		await expectStatus(await guestRequest.get(ANON_PROTECTED), 200)
		await expectStatus(
			await userRequest.get(ANON_PROTECTED),
			429,
			'Both clients come from the same address and the route has no user limit, so they share one counter.',
		)

		await waitOutAnonProtectedPeriod()
		await expectStatus(await userRequest.get(ANON_PROTECTED), 200)
	})

	test('the user limit is counted separately from the anonymous limit', async ({ guestRequest, userRequest }) => {
		await expectStatus(await guestRequest.get(USER_AND_ANON_PROTECTED), 200)
		await expectStatus(
			await guestRequest.get(USER_AND_ANON_PROTECTED),
			429,
			'The anonymous limit of 1 request is exhausted.',
		)

		// The user counter is untouched by the two guest requests above.
		for (let request = 1; request <= USER_AND_ANON_USER_LIMIT; request++) {
			await expectStatus(
				await userRequest.get(USER_AND_ANON_PROTECTED),
				200,
				`Request ${request} of the user limit of ${USER_AND_ANON_USER_LIMIT}.`,
			)
		}

		await expectStatus(
			await userRequest.get(USER_AND_ANON_PROTECTED),
			429,
			`Request ${USER_AND_ANON_USER_LIMIT + 1} exceeds the user limit of ${USER_AND_ANON_USER_LIMIT}.`,
		)

		await expectStatus(
			await guestRequest.get(USER_AND_ANON_PROTECTED),
			429,
			'The anonymous counter is still exhausted and was not reset by the authenticated requests.',
		)
	})
})

/**
 * Wait until the anonymous counter of `ANON_PROTECTED` is released again. The
 * counter is shared by every test using that route, so each of them waits
 * rather than relying on the order they run in.
 *
 * The wait cannot be replaced by polling the route until it answers `200`,
 * because the successful poll would itself consume the single request the
 * period allows.
 */
function waitOutAnonProtectedPeriod(): Promise<void> {
	return new Promise((resolve) => setTimeout(resolve, (ANON_PROTECTED_PERIOD + 1) * 1000))
}
