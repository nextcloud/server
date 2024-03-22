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
/* eslint-disable no-console */
/* eslint-disable n/no-unpublished-import */
/* eslint-disable n/no-extraneous-import */

import type { Container } from 'dockerode'

import { execSync } from 'child_process'
import { docker, startNextcloud } from '@nextcloud/cypress/docker'

import tar from 'tar'

const CONTAINER_NAME = 'nextcloud-cypress-tests'

/**
 * Start the Nextcloud docker container
 * @param branch Branch to run (leave empty to autodetect)
 */
export const startDockerServer = (branch?: string): string => startNextcloud(branch ?? getCurrentGitBranch(), false)

const getCurrentGitBranch = function() {
	const branch = execSync('git rev-parse --abbrev-ref HEAD').toString().trim() || 'master'
	const match = branch.match(/(master|main|stable\d+)/)
	if (match) {
		return match[1]
	}
	return 'master'
}

/**
 * Applying local changes to the container
 * Only triggered if we're not in CI. Otherwise the
 * continuous-integration-shallow-server image will
 * already fetch the proper branch.
 */
export const applyChangesToNextcloud = async function() {
	console.log('\nApply local changes to nextcloud...')
	const container = docker.getContainer(CONTAINER_NAME) as Container

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
	]

	folderPaths.forEach((path) => {
		console.log(`├─ Copying ${path}`)
	})

	// Tar-streaming the above folders into the container
	const serverTar = tar.c({ gzip: false }, folderPaths)
	await container.putArchive(serverTar, {
		path: htmlPath,
	})

	// Making sure we have the proper permissions
	await runExec(container, ['chown', '-R', 'www-data:www-data', htmlPath], false, 'root')

	console.log('└─ Changes applied successfully 🎉')
}

const runExec = async function(
	container: Container,
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
