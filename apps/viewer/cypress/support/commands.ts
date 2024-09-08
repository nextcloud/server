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

import { addCommands } from '@nextcloud/cypress'
import { Permission } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { addCompareSnapshotCommand } from 'cypress-visual-regression/dist/command'
import { basename } from 'path'

addCommands()
addCompareSnapshotCommand()

const url = Cypress.config('baseUrl')!.replace(/\/index.php\/?$/g, '')
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

	// get fixture
	return cy.fixture(fixture, 'binary').then(file => {
		// convert the binary to a blob
		const blob = Cypress.Blob.binaryStringToBlob(file, mimeType)

		const fileName = basename(target)
		const rootPath = `${Cypress.env('baseUrl')}/remote.php/dav/files/${encodeURIComponent(user.userId)}`
		const filePath = target.split('/').map(encodeURIComponent).join('/')

		cy.request({
			url: `${rootPath}${filePath}`,
			method: 'PUT',
			body: blob,
			headers: {
				'Content-Type': mimeType,
			},
			auth: {
				username: user.userId,
				password: user.password,
			},
		}).then(response => {
			cy.log(`Uploaded file ${fileName}`, response)
		})
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

Cypress.Commands.add('getFile', fileName => {
	return cy.get(`[data-cy-files-list] tr[data-cy-files-list-row-name="${CSS.escape(fileName)}"]`)
})

Cypress.Commands.add('openFile', fileName => {
	cy.getFile(fileName).click()
	// eslint-disable-next-line
	cy.wait(250)
})

Cypress.Commands.add('openFileInSingleShare', () => {
	cy.get('tr[data-cy-files-list-row-name]')
		.should('have.length', 1)
	// eslint-disable-next-line
	cy.wait(250)
})

Cypress.Commands.add('getFileId', fileName => {
	return cy.getFile(fileName)
		.should('have.attr', 'data-cy-files-list-row-fileid')
})

Cypress.Commands.add('deleteFile', fileName => {
	cy.getFile(fileName).clickAction('delete')
})

Cypress.Commands.add('reloadCurrentFilesList', () => {
	cy.get('[data-cy-files-content-breadcrumbs] a[title="Reload current directory"]').click()
})

Cypress.Commands.add(
	'clickAction',
	{ prevSubject: 'element' },
	(subject, action) => {
		subject.find('[data-cy-files-list-row-actions] button').click()
		cy.get(`[data-cy-files-list-row-action="${action}"]`).click()
	},
)

interface ShareOptions {
	shareType: number
	shareWith?: string
	permissions: number
	attributes?: { value: boolean, key: string, scope: string}[]
}

Cypress.Commands.add('createShare', (path: string, shareOptions?: ShareOptions) => {
	return cy.request('/csrftoken').then(({ body }) => {
		const requesttoken = body.token

		return cy.request({
			method: 'POST',
			url: '../ocs/v2.php/apps/files_sharing/api/v1/shares?format=json',
			headers: {
				requesttoken,
			},
			body: {
				path,
				permissions: Permission.READ,
				...shareOptions,
				attributes: shareOptions?.attributes && JSON.stringify(shareOptions.attributes),
			},
		}).then(({ body }) => {
			const shareToken = body.ocs?.data?.token
			if (shareToken === undefined) {
				throw new Error('Invalid OCS response')
			}
			cy.log('Share link created', shareToken)
			return cy.wrap(shareToken)
		})
	})
})

/**
 * Create a share link and return the share url
 *
 * @param {string} path the file/folder path
 * @return {string} the share link url
 */
Cypress.Commands.add('createLinkShare', path => {
	return cy.createShare(path, { shareType: ShareType.Link })
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
