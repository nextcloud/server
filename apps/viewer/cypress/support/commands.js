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

import { addCommands, User } from '@nextcloud/cypress'
import { basename } from 'path'
import axios from '@nextcloud/axios'
import compareSnapshotCommand from 'cypress-visual-regression/dist/command.js'

addCommands()
compareSnapshotCommand()

const url = Cypress.config('baseUrl').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

/**
 * cy.uploadedFile - uploads a file from the fixtures folder
 *
 * @param {User} user the owner of the file, e.g. admin
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
			cy.log(error)
			throw new Error(`Unable to process fixture ${fixture}`)
		}
	})

})

Cypress.Commands.add('createFolder', (user, target) => {
	cy.clearCookies()

	const dirName = basename(target)
	const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user.userId)}`
	const dirPath = target.split('/').map(encodeURIComponent).join('/')

	 cy.request({
		url: `${rootPath}${dirPath}`,
		method: 'MKCOL',
		auth: {
			username: user.userId,
			password: user.password,
		},
	}).then(response => {
		cy.log(`Created folder ${dirName}`, response)
	})
})

Cypress.Commands.add('openFile', fileName => {
	cy.get(`.files-fileList tr[data-file="${CSS.escape(fileName)}"] a.name`).click()
	// eslint-disable-next-line
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
 * @return {string} the share link url
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
