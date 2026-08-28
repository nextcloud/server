/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { configureNextcloud, docker, getContainer, runExec, runOcc, startNextcloud, stopNextcloud, waitOnNextcloud } from '@nextcloud/e2e-test-server/docker'
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

	if (process.env.PLAYWRIGHT_SETUP) {
		// The installer (setup) tests need to reach the database service containers
		// (mysql, mariadb, …) that CI exposes on the GitHub Actions network. Join it
		// when present; a no-op locally and in the normal test job where it is absent.
		await connectToActionsNetwork()
	}

	await waitOnNextcloud(ip)
	await configureNextcloud(process.env.PLAYWRIGHT_SETUP ? [] : ['viewer'])

	if (process.env.PLAYWRIGHT_SETUP) {
		// When the apps folder is mounted, configureNextcloud writes an
		// apps.config.php declaring a writable apps path at
		// `/var/www/html/apps_writable`, but it only creates that directory as a
		// side effect of installing an app into it. The setup job installs no
		// apps (empty list above), so the directory is never created. The setup
		// tests remove config.php in beforeEach — leaving apps.config.php — and
		// the wizard then fails to boot with `App directory
		// "/var/www/html/apps_writable" not found`. Create it up front.
		await runExec(['mkdir', '-p', '/var/www/html/apps_writable'], { user: 'root' })
		await runExec(['chown', 'www-data:www-data', '/var/www/html/apps_writable'], { user: 'root' })
		process.stdout.write('├─ Created writable apps folder for the setup tests\n')
	}

	process.stdout.write('\nApply custom configuration for Playwright tests\n')
	await runExec(['php', '-r', '$db = new SQLite3("data/owncloud.db");$db->busyTimeout(5000);$db->exec("PRAGMA journal_mode = wal;");'])
	process.stdout.write('├─ Enabled SQLite WAL mode for better performance\n')

	await runOcc(['config:system:set', 'cache_app_config', '--value', 'false', '--type', 'boolean'])
	process.stdout.write('├─ Disabled caching AppConfig\n') // otherwise test setup using OCC will need to wait 3s so that web cache TTL expires

	await runOcc(['config:system:set', 'appstoreenabled', '--value', 'false', '--type', 'boolean'])
	process.stdout.write('├─ Disabled app store\n')

	// createRandomUser() generates short passwords that the policy would reject
	await runOcc(['app:disable', 'password_policy'])
	process.stdout.write('├─ Disabled password policy for random test users\n')

	process.stdout.write('├─ Initialize cron job...\n')
	await runExec(['php', 'cron.php'])
	process.stdout.write('│  └─ OK !\n')
	process.stdout.write('└─ Nextcloud container ready to run Playwright tests\n')
}

/**
 * Connect the Nextcloud container to the GitHub Actions bridge network (named
 * `github_network*`) if it exists, so it can resolve the database service
 * containers by hostname. Does nothing when the network is absent.
 */
async function connectToActionsNetwork() {
	const networks = await docker.listNetworks()
	const network = networks.find((n) => n.Name.startsWith('github_network'))
	if (!network) {
		return
	}

	await docker.getNetwork(network.Id).connect({ Container: getContainer().id })
	process.stdout.write('├─ Connected to the GitHub Actions network for the setup tests\n')
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
