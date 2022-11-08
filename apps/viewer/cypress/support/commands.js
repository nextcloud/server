/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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

// eslint-disable-next-line node/no-unpublished-import
import compareSnapshotCommand from 'cypress-visual-regression/dist/command'
import axios from '@nextcloud/axios'
import { basename } from 'path'

compareSnapshotCommand()

const url = Cypress.config('baseUrl').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

/**
 * You should always upload files and/or create users
 * before login, so that the cookies are NOT YET defined.
 */
Cypress.Commands.add('login', (user, password = user) => {
	cy.clearCookies()

	// Keep sessions active between tests until
	// we use the new cypress session API
	Cypress.Cookies.defaults({
		preserve: /^(oc|nc)/,
	})

	cy.request('/csrftoken').then(({ body }) => {
		const requesttoken = body.token
		cy.request({
			method: 'POST',
			url: '/login',
			body: { user, password, requesttoken },
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			followRedirect: false,
		})
	})
})

Cypress.Commands.add('logout', () => {
	cy.request('/csrftoken').then(({ body }) => {
		const requestToken = body.token
		cy.visit(`/logout?requesttoken=${encodeURIComponent(requestToken)}`)
	})
	cy.clearCookies()
})

Cypress.Commands.add('nextcloudCreateUser', (user, password = user) => {
	cy.clearCookies()
	cy.request({
		method: 'POST',
		url: `${Cypress.env('baseUrl')}/ocs/v1.php/cloud/users?format=json`,
		form: true,
		body: {
			userid: user,
			password,
		},
		auth: { user: 'admin', pass: 'admin' },
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
			Authorization: `Basic ${btoa('admin:admin')}`,
		},
	}).then(response => {
		if (response.body.ocs.meta.status.toLowerCase() === 'ok') {
			cy.log(`Created user ${user}`, response.status)
		} else {
			cy.log(response)
			throw new Error(`Unable to create user ${user}`)
		}
	})
})

/**
 * cy.uploadedFile - uploads a file from the fixtures folder
 *
 * @param {string} user the owner of the file, e.g. admin
 * @param {string} fixture the fixture file name, e.g. image1.jpg
 * @param {string} mimeType e.g. image/png
 * @param {string} [target] the target of the file relative to the user root
 */
Cypress.Commands.add('uploadFile', (user, fixture, mimeType, target = `/${fixture}`) => {
	cy.clearCookies()
	const fileName = basename(target)

	// get fixture
	return cy.fixture(fixture, 'base64').then(async file => {
		// convert the base64 string to a blob
		const blob = Cypress.Blob.base64StringToBlob(file, mimeType)

		// Process paths
		const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user)}`
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
					username: user,
					password: user,
				},
			}).then(response => {
				cy.log(`Uploaded ${fixture} as ${fileName}`, response)
			})
		} catch (error) {
			cy.log(error)
			throw new Error(`Unable to process fixture ${fixture}`)
		}
	})

})

Cypress.Commands.add('createFolder', (user, target) => {
	cy.clearCookies()

	const dirName = basename(target)
	const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user)}`
	const dirPath = target.split('/').map(encodeURIComponent).join('/')

	 cy.request({
		url: `${rootPath}${dirPath}`,
		method: 'MKCOL',
		auth: {
			username: user,
			password: user,
		},
	}).then(response => {
		cy.log(`Created folder ${dirName}`, response)
	})
})

Cypress.Commands.add('openFile', fileName => {
	cy.get(`.files-fileList tr[data-file="${CSS.escape(fileName)}"] a.name`).click()
	cy.wait(250)
})

Cypress.Commands.add('getFileId', fileName => {
	return cy.get(`.files-fileList tr[data-file="${CSS.escape(fileName)}"]`)
		.should('have.attr', 'data-id')
})

Cypress.Commands.add('deleteFile', fileName => {
	cy.get(`.files-fileList tr[data-file="${CSS.escape(fileName)}"] a.name .action-menu`).click()
	cy.get(`.files-fileList tr[data-file="${CSS.escape(fileName)}"] a.name + .popovermenu .action-delete`).click()
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
				},
			})
			if (!('ocs' in request.data) || !('token' in request.data.ocs.data && request.data.ocs.data.token.length > 0)) {
				throw request
			}
			cy.log('Share link created', request.data.ocs.data.token)
			return cy.wrap(request.data.ocs.data.token)
		} catch (error) {
			console.error(error)
		}
	}).should('have.length', 15)
})

Cypress.Commands.overwrite('compareSnapshot', (originalFn, subject, name, options) => {
	// hide avatar because random colour break the visual regression tests
	cy.window().then(window => {
		const avatarDiv = window.document.querySelector('.avatardiv')
		if (avatarDiv) {
			avatarDiv.remove()
		}
	})
	return originalFn(subject, name, options)
})
