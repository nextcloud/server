/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Captures the initial state of real Nextcloud pages as benchmark fixtures.
 *
 * Usage: node capture.mjs [baseUrl] [user] [password]
 *
 * Each fixture maps "<app>-<key>" to the JSON string the server rendered,
 * i.e. exactly what InitialStateService::getInitialStates() returns.
 */

import { chromium } from 'playwright'
import { mkdirSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const [baseUrl = 'http://localhost:8089', user = 'admin', password = 'admin'] = process.argv.slice(2)
const outDir = join(dirname(fileURLToPath(import.meta.url)), 'fixtures')

const pages = {
	login: { path: '/index.php/login', auth: false },
	files: { path: '/index.php/apps/files/', auth: true },
	dashboard: { path: '/index.php/apps/dashboard/', auth: true },
	'settings-users': { path: '/index.php/settings/users', auth: true },
	'settings-admin': { path: '/index.php/settings/admin/overview', auth: true },
}

const browser = await chromium.launch()
const anonymous = await browser.newContext()
const authenticated = await browser.newContext()

const loginPage = await authenticated.newPage()
await loginPage.goto(baseUrl + '/login')
await loginPage.getByRole('textbox', { name: /login|account name/i }).fill(user)
await loginPage.getByRole('textbox', { name: /password/i }).fill(password)
await loginPage.getByRole('button', { name: 'Log in', exact: true }).click()
await loginPage.waitForURL((url) => !url.pathname.includes('/login'))
await loginPage.close()

mkdirSync(outDir, { recursive: true })
for (const [name, { path, auth }] of Object.entries(pages)) {
	const page = await (auth ? authenticated : anonymous).newPage()
	const response = await page.goto(baseUrl + path, { waitUntil: 'domcontentloaded' })
	const states = await page.evaluate(() => Object.fromEntries(
		[...document.querySelectorAll('#initial-state-container input')]
			.map((input) => [
				input.id.slice('initial-state-'.length),
				new TextDecoder().decode(Uint8Array.from(atob(input.value), (c) => c.charCodeAt(0))),
			]),
	))
	await page.close()
	const keys = Object.keys(states).length
	const bytes = Object.values(states).reduce((sum, value) => sum + value.length, 0)
	console.log(`${name}: HTTP ${response.status()}, ${keys} keys, ${bytes} bytes of JSON`)
	writeFileSync(join(outDir, `${name}.json`), JSON.stringify(states, null, '\t') + '\n')
}

await browser.close()
