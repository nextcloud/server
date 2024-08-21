/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable no-console */
/* eslint-disable n/no-unpublished-import */
/* eslint-disable n/no-extraneous-import */

import Docker from 'dockerode'
import waitOn from 'wait-on'
import { c as createTar } from 'tar'
import path from 'path'
import { execSync } from 'child_process'
import { existsSync } from 'fs'

export const docker = new Docker()

const CONTAINER_NAME = 'nextcloud-cypress-tests-server'
const SERVER_IMAGE = 'ghcr.io/nextcloud/continuous-integration-shallow-server'

/**
 * Start the testing container
 *
 * @param {string} branch the branch of your current work
 */
export const startNextcloud = async function(branch: string = getCurrentGitBranch()): Promise<any> {

	try {
		try {
			// Pulling images
			console.log('\nPulling images... ⏳')
			await new Promise((resolve, reject): any => docker.pull(SERVER_IMAGE, (err, stream) => {
				if (err) {
					reject(err)
				}
				if (stream === null) {
					reject(new Error('Could not connect to docker, ensure docker is running.'))
					return
				}

				// https://github.com/apocas/dockerode/issues/357
				docker.modem.followProgress(stream, onFinished)

				/**
				 *
				 * @param err
				 */
				function onFinished(err) {
					if (!err) {
						resolve(true)
						return
					}
					reject(err)
				}
			}))
			console.log('└─ Done')
		} catch (e) {
			console.log('└─ Failed to pull images')
			throw e
		}

		// Remove old container if exists
		console.log('\nChecking running containers... 🔍')
		try {
			const oldContainer = docker.getContainer(CONTAINER_NAME)
			const oldContainerData = await oldContainer.inspect()
			if (oldContainerData) {
				console.log('├─ Existing running container found')
				console.log('├─ Removing... ⏳')
				// Forcing any remnants to be removed just in case
				await oldContainer.remove({ force: true })
				console.log('└─ Done')
			}
		} catch (error) {
			console.log('└─ None found!')
		}

		// Starting container
		console.log('\nStarting Nextcloud container... 🚀')
		console.log(`├─ Using branch '${branch}'`)
		const container = await docker.createContainer({
			Image: SERVER_IMAGE,
			name: CONTAINER_NAME,
			HostConfig: {
				Binds: [],
			},
			Env: [
				`BRANCH=${branch}`,
			],
		})
		await container.start()

		// Get container's IP
		const ip = await getContainerIP(container)

		console.log(`├─ Nextcloud container's IP is ${ip} 🌏`)
		return ip
	} catch (err) {
		console.log('└─ Unable to start the container 🛑')
		console.log('\n', err, '\n')
		stopNextcloud()
		throw new Error('Unable to start the container')
	}
}

/**
 * Configure Nextcloud
 */
export const configureNextcloud = async function() {
	console.log('\nConfiguring nextcloud...')
	const container = docker.getContainer(CONTAINER_NAME)
	await runExec(container, ['php', 'occ', '--version'], true)

	// Be consistent for screenshots
	await runExec(container, ['php', 'occ', 'config:system:set', 'default_language', '--value', 'en'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'force_language', '--value', 'en'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'default_locale', '--value', 'en_US'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'force_locale', '--value', 'en_US'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'enforce_theme', '--value', 'light'], true)

	// Speed up test and make them less flaky. If a cron execution is needed, it can be triggered manually.
	await runExec(container, ['php', 'occ', 'background:cron'], true)

	// Setup redis
	await runExec(container, ['service', 'redis-server', 'start'], true, 'root')
	await runExec(container, ['php', 'occ', 'config:system:set', 'memcache.distributed', '--value', '\\OC\\Memcache\\Redis'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'memcache.locking', '--value', '\\OC\\Memcache\\Redis'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'redis', 'host', '--value', 'localhost'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'redis', 'port', '--value', '6379', '--type', 'integer'], true)

	// Saving DB state
	console.log('├─ Creating init DB snapshot...')
	await runExec(container, ['cp', '/var/www/html/data/owncloud.db', '/var/www/html/data/owncloud.db-init'], true)

	console.log('└─ Nextcloud is now ready to use 🎉')
}

export const createSnapshot = async function(): Promise<string> {
	console.log('\nSaving Nextcloud DB...')
	const randomString = Math.random().toString(36).substring(7)
	const container = docker.getContainer(CONTAINER_NAME)
	await runExec(container, ['cp', '/var/www/html/data/owncloud.db', '/var/www/html/data/owncloud.db-' + randomString], true)
	console.log('└─ Done')
	return randomString
}

export const restoreSnapshot = async function(snapshot: string) {
	console.log('\nRestoring Nextcloud DB...')
	const container = docker.getContainer(CONTAINER_NAME)
	await runExec(container, ['cp', '/var/www/html/data/owncloud.db-' + snapshot, '/var/www/html/data/owncloud.db'], true)
	console.log('└─ Done')
}

/**
 * Applying local changes to the container
 * Only triggered if we're not in CI. Otherwise the
 * continuous-integration-shallow-server image will
 * already fetch the proper branch.
 */
export const applyChangesToNextcloud = async function() {
	console.log('\nApply local changes to nextcloud...')

	const htmlPath = '/var/www/html'
	const folderPaths = [
		'./3rdparty',
		'./apps',
		'./core',
		'./dist',
		'./lib',
		'./ocs',
		'./ocs-provider',
		'./resources',
		'./console.php',
		'./cron.php',
		'./index.php',
		'./occ',
		'./public.php',
		'./remote.php',
		'./status.php',
		'./version.php',
	].filter((folderPath) => {
		const fullPath = path.resolve(__dirname, '..', folderPath)

		if (existsSync(fullPath)) {
			console.log(`├─ Copying ${folderPath}`)
			return true
		}
		return false
	})

	// Don't try to apply changes, when there are none. Otherwise we
	// still execute the 'chown' command, which is not needed.
	if (folderPaths.length === 0) {
		console.log('└─ No local changes found to apply')
		return
	}

	const container = docker.getContainer(CONTAINER_NAME)

	// Tar-streaming the above folders into the container
	const serverTar = createTar({ gzip: false }, folderPaths)
	await container.putArchive(serverTar, {
		path: htmlPath,
	})

	// Making sure we have the proper permissions
	await runExec(container, ['chown', '-R', 'www-data:www-data', htmlPath], false, 'root')

	console.log('└─ Changes applied successfully 🎉')
}

/**
 * Force stop the testing container
 */
export const stopNextcloud = async function() {
	try {
		const container = docker.getContainer(CONTAINER_NAME)
		console.log('Stopping Nextcloud container...')
		container.remove({ force: true })
		console.log('└─ Nextcloud container removed 🥀')
	} catch (err) {
		console.log(err)
	}
}

/**
 * Get the testing container's IP
 *
 * @param {Docker.Container} container the container to get the IP from
 */
export const getContainerIP = async function(
	container = docker.getContainer(CONTAINER_NAME),
): Promise<string> {
	let ip = ''
	let tries = 0
	while (ip === '' && tries < 10) {
		tries++

		await container.inspect(function(err, data) {
			if (err) {
				throw err
			}
			ip = data?.NetworkSettings?.IPAddress || ''
		})

		if (ip !== '') {
			break
		}

		await sleep(1000 * tries)
	}

	return ip
}

// Would be simpler to start the container from cypress.config.ts,
// but when checking out different branches, it can take a few seconds
// Until we can properly configure the baseUrl retry intervals,
// We need to make sure the server is already running before cypress
// https://github.com/cypress-io/cypress/issues/22676
export const waitOnNextcloud = async function(ip: string) {
	console.log('├─ Waiting for Nextcloud to be ready... ⏳')
	await waitOn({
		resources: [`http://${ip}/index.php`],
		// wait for nextcloud to  be up and return any non error status
		validateStatus: (status) => status >= 200 && status < 400,
		// timout in ms
		timeout: 5 * 60 * 1000,
		// timeout for a single HTTP request
		httpTimeout: 60 * 1000,
	})
	console.log('└─ Done')
}

const runExec = async function(
	container: Docker.Container,
	command: string[],
	verbose = false,
	user = 'www-data',
) {
	const exec = await container.exec({
		Cmd: command,
		AttachStdout: true,
		AttachStderr: true,
		User: user,
	})

	return new Promise((resolve, reject) => {
		exec.start({}, (err, stream) => {
			if (err) {
				reject(err)
			}
			if (stream) {
				stream.setEncoding('utf-8')
				stream.on('data', str => {
					if (verbose && str.trim() !== '') {
						console.log(`├─ ${str.trim().replace(/\n/gi, '\n├─ ')}`)
					}
				})
				stream.on('end', resolve)
			}
		})
	})
}

const sleep = function(milliseconds: number) {
	return new Promise((resolve) => setTimeout(resolve, milliseconds))
}

const getCurrentGitBranch = function() {
	return execSync('git rev-parse --abbrev-ref HEAD').toString().trim() || 'master'
}
