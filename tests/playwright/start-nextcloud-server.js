/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { configureNextcloud, runExec, runOcc, startNextcloud, stopNextcloud, waitOnNextcloud } from '@nextcloud/e2e-test-server/docker'
import { existsSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const rootDir = resolve(dirname(fileURLToPath(import.meta.url)), '../..')

function getMounts() {
	const mounts = {
		'3rdparty': resolve(rootDir, '3rdparty'),
		apps: resolve(rootDir, 'apps'),
		core: resolve(rootDir, 'core'),
		dist: resolve(rootDir, 'dist'),
		lib: resolve(rootDir, 'lib'),
		ocs: resolve(rootDir, 'ocs'),
		'ocs-provider': resolve(rootDir, 'ocs-provider'),
		resources: resolve(rootDir, 'resources'),
		tests: resolve(rootDir, 'tests'),
		'console.php': resolve(rootDir, 'console.php'),
		'cron.php': resolve(rootDir, 'cron.php'),
		'index.php': resolve(rootDir, 'index.php'),
		occ: resolve(rootDir, 'occ'),
		'public.php': resolve(rootDir, 'public.php'),
		'remote.php': resolve(rootDir, 'remote.php'),
		'status.php': resolve(rootDir, 'status.php'),
		'version.php': resolve(rootDir, 'version.php'),
	}

	return Object.fromEntries(Object.entries(mounts).filter(([, path]) => existsSync(path)))
}

async function start() {
	const port = Number.parseInt(process.env.NEXTCLOUD_PORT ?? '8042', 10)
	const ip = await startNextcloud(process.env.BRANCH, false, {
		mounts: getMounts(),
		exposePort: port,
		forceRecreate: true,
	})

	await runExec(['mkdir', '-p', 'apps-cypress'])
	await runExec(['cp', 'cypress/fixtures/app.config.php', 'config'])

	await waitOnNextcloud(ip)
	await configureNextcloud()

	process.stdout.write('\nApply custom configuration for Playwright tests\n')
	await runOcc(['config:system:set', 'appstoreenabled', '--value', 'false', '--type', 'boolean'])
	process.stdout.write('├─ Disabled app store\n')
	await runExec(['php', '-r', '$db = new SQLite3("data/owncloud.db");$db->busyTimeout(5000);$db->exec("PRAGMA journal_mode = wal;");'])
	process.stdout.write('├─ Enabled SQLite WAL mode for better performance\n')
	process.stdout.write('├─ Initialize cron job...\n')
	await runExec(['php', 'cron.php'])
	process.stdout.write('│  └─ OK !\n')
	process.stdout.write('└─ Nextcloud container ready to run Playwright tests\n')
}

async function stop() {
	process.stderr.write('Stopping Nextcloud server…\n')
	await stopNextcloud()
	process.exit(0)
}

process.on('SIGTERM', stop)
process.on('SIGINT', stop)

await start()

while (true) {
	await new Promise((resolvePromise) => setTimeout(resolvePromise, 5000))
}
