/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'
import type { DatabaseConnection } from '../../support/sections/SetupPage.ts'

import { runExec } from '@nextcloud/e2e-test-server/docker'
import { test as base, expect } from '@playwright/test'
import { SetupPage } from '../../support/sections/SetupPage.ts'

/**
 * Installation-wizard tests. They repeatedly UN-INSTALL the shared server (so it
 * can be set up again from scratch) and the non-SQLite cases need reachable
 * database containers, so they run only in the dedicated setup job —
 * isolated in the `setup` Playwright project.
 * They are tagged `@setup` for selective runs.
 */
const test = base.extend<{ setupPage: SetupPage }>({
	setupPage: async ({ page }, use) => {
		await use(new SetupPage(page))
	},
})

/** How to handle the recommended-apps screen at the end of the wizard. */
type RecommendedAppsMode = 'skip' | 'install-success' | 'install-failure'

// The recommended-apps view fetches the listing from the appstore OCS API, so
// the mock must be OCS-shaped. Keep the app count in sync with the two entries.
const APPSTORE_APPS = {
	ocs: {
		meta: { status: 'ok', statuscode: 200, message: 'OK' },
		data: [
			{ id: 'calendar', name: 'Calendar', isCompatible: true, active: false, installed: false, internal: false },
			{ id: 'contacts', name: 'Contacts', isCompatible: true, active: false, installed: false, internal: false },
		],
	},
}
const RECOMMENDED_APP_COUNT = APPSTORE_APPS.ocs.data.length

const ENABLE_SUCCESS = { ocs: { meta: { status: 'ok', statuscode: 200, message: 'OK' }, data: { update_required: false } } }
const ENABLE_FAILURE = { ocs: { meta: { status: 'failure', statuscode: 500, message: 'Forced failure' }, data: [] } }

const MYSQL: DatabaseConnection = { user: 'root', password: 'rootpassword', name: 'nextcloud', host: 'mysql:3306' }
const MARIADB: DatabaseConnection = { user: 'root', password: 'rootpassword', name: 'nextcloud', host: 'mariadb:3306' }
const POSTGRES: DatabaseConnection = { user: 'root', password: 'rootpassword', name: 'nextcloud', host: 'postgres:5432' }
const ORACLE: DatabaseConnection = { user: 'system', password: 'oracle', name: 'FREE', host: 'oracle:1521' }

/** A unique administration account name (also used as the password). */
function randomAdmin(): string {
	return `admin-${crypto.randomUUID().slice(0, 10)}`
}

/**
 * Stub the appstore listing (always) and, for the install modes, the per-app
 * enable request — so the flow is exercised without hitting the real app store.
 * Registered before the wizard submits, so the routes are live once the
 * recommended-apps view mounts after the post-install redirect.
 */
async function mockAppstore(page: Page, mode: RecommendedAppsMode): Promise<void> {
	await page.route(/\/apps\/appstore\/api\/v1\/apps(\?.*)?$/, (route) => route.fulfill({ json: APPSTORE_APPS }))

	if (mode !== 'skip') {
		await page.route(/\/apps\/appstore\/api\/v1\/apps\/enable/, (route) => route.fulfill(mode === 'install-success'
			? { status: 200, json: ENABLE_SUCCESS }
			: { status: 500, json: ENABLE_FAILURE }))
	}
}

/**
 * Drive the admin creation + submit, assert the recommended-apps screen, then
 * either skip to the files app or install the recommended apps and assert the
 * inline per-app result.
 */
async function completeSetup(page: Page, setupPage: SetupPage, mode: RecommendedAppsMode): Promise<void> {
	const admin = randomAdmin()
	await setupPage.install(admin, admin)

	await expect(setupPage.recommendedAppsHeading()).toBeVisible()
	await expect(setupPage.skipButton()).toBeVisible()
	await expect(setupPage.installRecommendedButton()).toBeVisible()

	if (mode === 'skip') {
		await setupPage.skipButton().click()
		await page.goto('apps/files/')
		await expect(page.locator('[data-cy-files-content]')).toBeVisible()
		return
	}

	await setupPage.installRecommendedApps(admin, RECOMMENDED_APP_COUNT)

	// The frontend stays on the recommended-apps page and reflects each app's
	// result inline (no redirect after installing).
	await expect(page).toHaveURL(/\/core\/apps\/recommended/)
	await expect(setupPage.recommendedApps()).toContainText(mode === 'install-success' ? 'App already installed' : 'App download or installation failed')
}

test.describe('Nextcloud installation wizard', { tag: '@setup' }, () => {
	test.beforeEach(async () => {
		// Reset the instance to an uninstalled state so the wizard is served again
		await runExec(['rm', '-f', 'config/config.php'], { failOnError: false })
		await runExec(['rm', '-f', 'data/owncloud.db'], { failOnError: false })
	})

	test.describe('SQLite', { tag: '@db_sqlite' }, () => {
		test('installs with SQLite', async ({ page, setupPage }) => {
			test.slow()
			await mockAppstore(page, 'skip')
			await setupPage.open()

			await expect(setupPage.adminLoginField()).toBeVisible()
			await expect(setupPage.adminPasswordField()).toBeVisible()
			await expect(setupPage.dataFolderField()).toHaveValue('/var/www/html/data')

			await setupPage.selectDatabase('SQLite')
			await completeSetup(page, setupPage, 'skip')
		})

		test('installs with SQLite and installs recommended apps (success)', async ({ page, setupPage }) => {
			await mockAppstore(page, 'install-success')
			await setupPage.open()

			await setupPage.selectDatabase('SQLite')
			await completeSetup(page, setupPage, 'install-success')
		})

		test('installs with SQLite and reports failed recommended apps', async ({ page, setupPage }) => {
			await mockAppstore(page, 'install-failure')
			await setupPage.open()

			await setupPage.selectDatabase('SQLite')
			await completeSetup(page, setupPage, 'install-failure')
		})
	})

	test('installs with MySQL', { tag: '@db_mysql' }, async ({ page, setupPage }) => {
		test.slow()
		await mockAppstore(page, 'skip')
		await setupPage.open()

		await setupPage.selectDatabase('MySQL/MariaDB')
		await setupPage.fillDatabaseConnection(MYSQL)
		await completeSetup(page, setupPage, 'skip')
	})

	test('installs with MariaDB', { tag: '@db_mariadb' }, async ({ page, setupPage }) => {
		test.slow()
		await mockAppstore(page, 'skip')
		await setupPage.open()

		await setupPage.selectDatabase('MySQL/MariaDB')
		await setupPage.fillDatabaseConnection(MARIADB)
		await completeSetup(page, setupPage, 'skip')
	})

	test('installs with PostgreSQL', { tag: '@db_postgres' }, async ({ page, setupPage }) => {
		test.slow()
		await mockAppstore(page, 'skip')
		await setupPage.open()

		await setupPage.selectDatabase('PostgreSQL')
		await setupPage.fillDatabaseConnection(POSTGRES)
		await completeSetup(page, setupPage, 'skip')
	})

	test('installs with Oracle', { tag: '@db_oracle' }, async ({ page, setupPage }) => {
		test.slow()
		test.setTimeout(200_000) // Oracle is slow to start up, so give it more time
		// Oracle is only offered when the server allows all databases
		await runExec(['cp', 'tests/databases-all-config.php', 'config/config.php'])

		await mockAppstore(page, 'skip')
		await setupPage.open()

		await setupPage.selectDatabase('Oracle')
		await setupPage.fillDatabaseConnection(ORACLE)
		await completeSetup(page, setupPage, 'skip')
	})
})
