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
/* eslint-disable node/no-unpublished-import */
import axios from '@nextcloud/axios'
import { addCommands, User } from '@nextcloud/cypress'
import { basename } from 'path'

// Add custom commands
import 'cypress-wait-until'
addCommands()

// Register this file's custom commands types
declare global {
	// eslint-disable-next-line @typescript-eslint/no-namespace
	namespace Cypress {
		interface Chainable<Subject = any> {
			uploadFile(user: User, fixture?: string, mimeType?: string, target ?: string): Cypress.Chainable<void>,
			resetTheming(): Cypress.Chainable<void>,
		}
	}
}

const url = (Cypress.config('baseUrl') || '').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

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
	cy.clearCookies()
	const fileName = basename(target)

	// get fixture
	return cy.fixture(fixture, 'base64').then(async file => {
		// convert the base64 string to a blob
		const blob = Cypress.Blob.base64StringToBlob(file, mimeType)

		// Process paths
		const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user.userId)}`
		const filePath = target.split('/').map(encodeURIComponent).join('/')
		try {
			const file = new File([blob], fileName, { type: mimeType })
			await axios({
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
			}).then(response => {
				cy.log(`Uploaded ${fixture} as ${fileName}`, response)
			})
		} catch (error) {
			cy.log('error', error)
			throw new Error(`Unable to process fixture ${fixture}`)
		}
	})
})

/**
 * Reset the admin theming entirely
 */
 Cypress.Commands.add('resetTheming', () => {
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
				'requesttoken': requestToken,
			},
		})
	})

	// Clear admin session
	cy.clearCookies()
})
