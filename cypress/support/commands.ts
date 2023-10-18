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
/* eslint-disable n/no-unpublished-import */
import axios from '@nextcloud/axios'
import { addCommands, User } from '@nextcloud/cypress'
import { basename } from 'path'

// Add custom commands
import 'cypress-if'
import 'cypress-wait-until'
addCommands()

// Register this file's custom commands types
declare global {
	// eslint-disable-next-line @typescript-eslint/no-namespace
	namespace Cypress {
		interface Chainable<Subject = any> {
			/**
			 * Enable or disable a given user
			 */
			enableUser(user: User, enable?: boolean): Cypress.Chainable<Cypress.Response<any>>,

			/**
			 * Upload a file from the fixtures folder to a given user storage.
			 * **Warning**: Using this function will reset the previous session
			 */
			uploadFile(user: User, fixture?: string, mimeType?: string, target?: string): Cypress.Chainable<void>,

			/**
			 * Upload a raw content to a given user storage.
			 * **Warning**: Using this function will reset the previous session
			 */
			uploadContent(user: User, content: Blob, mimeType: string, target: string): Cypress.Chainable<void>,

			/**
			 * Reset the admin theming entirely.
			 * **Warning**: Using this function will reset the previous session
			 */
			resetAdminTheming(): Cypress.Chainable<void>,

			/**
			 * Reset the user theming settings.
			 * If provided, will clear session and login as the given user.
			 * **Warning**:  Providing a user will reset the previous session.
			 */
			resetUserTheming(user?: User): Cypress.Chainable<void>,

			/**
			 * Run an occ command in the docker container.
			 */
			runOccCommand(command: string): Cypress.Chainable<void>,
		}
	}
}

const url = (Cypress.config('baseUrl') || '').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

/**
 * Enable or disable a user
 * TODO: standardise in @nextcloud/cypress
 *
 * @param {User} user the user to dis- / enable
 * @param {boolean} enable True if the user should be enable, false to disable
 */
Cypress.Commands.add('enableUser', (user: User, enable = true) => {
	const url = `${Cypress.config('baseUrl')}/ocs/v2.php/cloud/users/${user.userId}/${enable ? 'enable' : 'disable'}`.replace('index.php/', '')
	return cy.request({
		method: 'PUT',
		url,
		form: true,
		auth: {
			user: 'admin',
			password: 'admin',
		},
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
		},
	}).then((response) => {
		cy.log(`Enabled user ${user}`, response.status)
		return cy.wrap(response)
	})
})

/**
 * cy.uploadedFile - uploads a file from the fixtures folder
 * TODO: standardise in @nextcloud/cypress
 *
 * @param {User} user the owner of the file, e.g. admin
 * @param {string} fixture the fixture file name, e.g. image1.jpg
 * @param {string} mimeType e.g. image/png
 * @param {string} [target] the target of the file relative to the user root
 */
Cypress.Commands.add('uploadFile', (user, fixture = 'image.jpg', mimeType = 'image/jpeg', target = `/${fixture}`) => {
	// get fixture
	return cy.fixture(fixture, 'base64').then(async file => {
		// convert the base64 string to a blob
		const blob = Cypress.Blob.base64StringToBlob(file, mimeType)

		cy.uploadContent(user, blob, mimeType, target)
	})
})

/**
 * cy.uploadedContent - uploads a raw content
 * TODO: standardise in @nextcloud/cypress
 *
 * @param {User} user the owner of the file, e.g. admin
 * @param {Blob} blob the content to upload
 * @param {string} mimeType e.g. image/png
 * @param {string} target the target of the file relative to the user root
 */
Cypress.Commands.add('uploadContent', (user, blob, mimeType, target) => {
	cy.clearCookies()
		.then(async () => {
			const fileName = basename(target)

			// Process paths
			const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user.userId)}`
			const filePath = target.split('/').map(encodeURIComponent).join('/')
			try {
				const file = new File([blob], fileName, { type: mimeType })
				const response = await axios({
					url: `${rootPath}${filePath}`,
					method: 'PUT',
					data: file,
					headers: {
						'Content-Type': mimeType,
					},
					auth: {
						username: user.userId,
						password: user.password,
					},
				})
				cy.log(`Uploaded content as ${fileName}`, response)
			} catch (error) {
				cy.log('error', error)
				throw new Error('Unable to process fixture')
			}
		})
})

/**
 * Reset the admin theming entirely
 */
Cypress.Commands.add('resetAdminTheming', () => {
	const admin = new User('admin', 'admin')

	cy.clearCookies()
	cy.login(admin)

	// Clear all settings
	cy.request('/csrftoken').then(({ body }) => {
		const requestToken = body.token

		axios({
			method: 'POST',
			url: '/index.php/apps/theming/ajax/undoAllChanges',
			headers: {
				requesttoken: requestToken,
			},
		})
	})

	// Clear admin session
	cy.clearCookies()
})

/**
 * Reset the current or provided user theming settings
 * It does not reset the theme config as it is enforced in the
 * server config for cypress testing.
 */
Cypress.Commands.add('resetUserTheming', (user?: User) => {
	if (user) {
		cy.clearCookies()
		cy.login(user)
	}

	// Reset background config
	cy.request('/csrftoken').then(({ body }) => {
		const requestToken = body.token

		cy.request({
			method: 'POST',
			url: '/apps/theming/background/default',
			headers: {
				requesttoken: requestToken,
			},
		})
	})

	if (user) {
		// Clear current session
		cy.clearCookies()
	}
})

Cypress.Commands.add('runOccCommand', (command: string) => {
	cy.exec(`docker exec --user www-data nextcloud-cypress-tests-server php ./occ ${command}`)
})
