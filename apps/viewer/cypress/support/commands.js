/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

import { addMatchImageSnapshotCommand } from 'cypress-image-snapshot/command'
import axios from '@nextcloud/axios'

addMatchImageSnapshotCommand()

const url = Cypress.config('baseUrl').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

Cypress.Commands.add('login', (user, password, route = '/apps/files') => {
	cy.clearCookies()
	Cypress.Cookies.defaults({
		preserve: /^(oc|nc)/,
	})
	cy.visit(route)
	cy.get('input[name=user]').type(user)
	cy.get('input[name=password]').type(password)
	cy.get('#submit-wrapper input[type=submit]').click()
	cy.url().should('include', route)
})

Cypress.Commands.add('logout', () => {
	cy.get('#expanddiv li[data-id="logout"] a').then(logout => {
		if (logout) {
			cy.visit(logout[0].href)
		}
	})
})

Cypress.Commands.add('nextcloudCreateUser', (user, password) => {
	cy.clearCookies()
	cy.request({
		method: 'POST',
		url: `${Cypress.env('baseUrl')}/ocs/v1.php/cloud/users?format=json`,
		form: true,
		body: {
			userid: user,
			password: password
		},
		auth: { user: 'admin', pass: 'admin' },
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
			Authorization: 'Basic YWRtaW46YWRtaW4='
		}
	}).then(response => {
		cy.log(`Created user ${user}`, response.status)
	})
})

Cypress.Commands.add('uploadFile', (fileName, mimeType, path = '') => {
	// get fixture
	return cy.fixture(fileName, 'base64').then(file => {
		// convert the logo base64 string to a blob
		const blob = Cypress.Blob.base64StringToBlob(file, mimeType)
		try {
			const file = new File([blob], fileName, { type: mimeType })
			return cy.window().then(async window => {
				await axios.put(`${Cypress.env('baseUrl')}/remote.php/webdav${path}/${fileName}`, file, {
					headers: {
						requesttoken: window.OC.requestToken,
						'Content-Type': mimeType
					}
				}).then(response => {
					cy.log(`Uploaded ${fileName}`, response)
				})
			})
		} catch (error) {
			cy.log(error)
			throw new Error(`Unable to process file ${fileName}`)
		}
	})

})

Cypress.Commands.add('createFolder', dirName => {
	cy.get('#controls .actions > .button.new').click()
	cy.get('#controls .actions .newFileMenu a[data-action="folder"]').click()
	cy.get('#controls .actions .newFileMenu a[data-action="folder"] input[type="text"]').type(dirName)
	cy.get('#controls .actions .newFileMenu a[data-action="folder"] input.icon-confirm').click()
	cy.log('Created folder', dirName)
})

Cypress.Commands.add('openFile', fileName => {
	cy.get(`#fileList tr[data-file="${fileName}"] a.name`).click()
	cy.wait(250)
})

Cypress.Commands.add('getFileId', fileName => {
	return cy.get(`#fileList tr[data-file="${fileName}"]`)
		.should('have.attr', 'data-id')
})

Cypress.Commands.add('deleteFile', fileName => {
	cy.get(`#fileList tr[data-file="${fileName}"] a.name .action-menu`).click()
	cy.get(`#fileList tr[data-file="${fileName}"] a.name + .popovermenu .action-delete`).click()
})

/**
 * Create a share link and return the share url
 *
 * @param {string} path the file/folder path
 * @returns {string} the share link url
 */
Cypress.Commands.add('createLinkShare', path => {
	return cy.window().then(async window => {
		try {
			const request = await axios.post(`${Cypress.env('baseUrl')}/ocs/v2.php/apps/files_sharing/api/v1/shares`, {
				path,
				shareType: window.OC.Share.SHARE_TYPE_LINK,
			}, {
				headers: {
					requesttoken: window.OC.requestToken,
				}
			})
			if (!('ocs' in request.data) || !('token' in request.data.ocs.data && request.data.ocs.data.token.length > 0)) {
				throw request
			}
			cy.log('Share link created', request.data.ocs.data.token)
			return cy.wrap(request.data.ocs.data.token)
		} catch(error) {
			console.error(error)
		}
	}).should('have.length', 15)
})

Cypress.Commands.overwrite('matchImageSnapshot', (originalFn, subject, name, options) => {
	// hide avatar because random colour break the visual regression tests
	cy.window().then(window => {
		const avatarDiv = window.document.querySelector('.avatardiv')
		if (avatarDiv) {
			avatarDiv.remove()
		}
	})
	return originalFn(subject, name, options)
})
