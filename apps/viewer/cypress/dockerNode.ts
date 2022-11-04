/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Docker from 'dockerode'
import waitOn from 'wait-on'
import path from 'path'

export const docker = new Docker()
const CONTAINER_NAME = 'nextcloud-cypress-tests'

const pkg = require('../package.json');
const APP_PATH = path.resolve(__dirname, '../')
const APP_NAME = pkg.name

const SERVER_IMAGE = 'ghcr.io/nextcloud/continuous-integration-shallow-server'

/**
 * Start the testing container
 */
export const startNextcloud = async function (branch: string = 'master'): Promise<any> {
	try {
		// Pulling images
		console.log('Pulling images...')
		await docker.pull(SERVER_IMAGE)

		// Getting latest image
		console.log('\nChecking running containers... 🔍')
		const localImage = await docker.listImages({ filters: `{"reference": ["${SERVER_IMAGE}"]}` })

		// Remove old container if exists and not initialized by us
		try {
			const oldContainer = docker.getContainer(CONTAINER_NAME)
			const oldContainerData = await oldContainer.inspect()
			if (oldContainerData.State.Running) {
				console.log(`├─ Existing running container found`)
				if (localImage[0].Id !== oldContainerData.Image) {
					console.log(`└─ But running container is outdated, replacing...`)
				} else {
					// Get container's IP
					console.log(`├─ Reusing that container`)
					let ip = await getContainerIP(oldContainer)
					return ip
				}
			} else {
				console.log(`└─ None found!`)
			}
			// Forcing any remnants to be removed just in case
			await oldContainer.remove({ force: true })
		} catch (error) {
			console.log(`└─ None found!`)
		}

		// Starting container
		console.log('\nStarting Nextcloud container... 🚀')
		console.log(`├─ Using branch '${branch}'`)
		console.log(`├─ And binding app '${APP_NAME}' from '${APP_PATH}'`)
		const container = await docker.createContainer({
			Image: SERVER_IMAGE,
			name: CONTAINER_NAME,
			Env: [`BRANCH=${branch}`],
			HostConfig: {
				Binds: [`${APP_PATH}:/var/www/html/apps/${APP_NAME}`],
			},
		})
		await container.start()

		// Get container's IP
		let ip = await getContainerIP(container)

		console.log(`├─ Nextcloud container's IP is ${ip} 🌏`)
		return ip
	} catch (err) {
		console.log(`└─ Unable to start the container 🛑`)
		console.log(err)
		stopNextcloud()
		throw new Error('Unable to start the container')
	}
}

/**
 * Configure Nextcloud
 */
export const configureNextcloud = async function () {
	console.log('\nConfiguring nextcloud...')
	const container = docker.getContainer(CONTAINER_NAME)
	await runExec(container, ['php', 'occ', '--version'], true)

	// Be consistent for screenshots
	await runExec(container, ['php', 'occ', 'config:system:set', 'default_language', '--value', 'en'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'force_language', '--value', 'en'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'default_locale', '--value', 'en_US'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'force_locale', '--value', 'en_US'], true)
	await runExec(container, ['php', 'occ', 'config:system:set', 'enforce_theme', '--value', 'light'], true)

	// Enable the app and give status
	await runExec(container, ['php', 'occ', 'app:enable', '--force', 'viewer'], true)
	// await runExec(container, ['php', 'occ', 'app:list'], true)

	console.log('└─ Nextcloud is now ready to use 🎉')
}

/**
 * Force stop the testing container
 */
export const stopNextcloud = async function () {
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
 */
export const getContainerIP = async function (
	container = docker.getContainer(CONTAINER_NAME)
): Promise<string> {
	let ip = ''
	let tries = 0
	while (ip === '' && tries < 10) {
		tries++

		await container.inspect(function (err, data) {
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
export const waitOnNextcloud = async function (ip: string) {
	console.log('├─ Waiting for Nextcloud to be ready... ⏳')
	await waitOn({ resources: [`http://${ip}/index.php`] })
	console.log('└─ Done')
}

const runExec = async function (
	container: Docker.Container,
	command: string[],
	verbose: boolean = false
) {
	const exec = await container.exec({
		Cmd: command,
		AttachStdout: true,
		AttachStderr: true,
		User: 'www-data',
	})

	return new Promise((resolve, reject) => {
		exec.start({}, (err, stream) => {
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

const sleep = function (milliseconds: number) {
	return new Promise((resolve) => setTimeout(resolve, milliseconds))
}
