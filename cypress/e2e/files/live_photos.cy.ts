/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { clickOnBreadcrumbs, closeSidebar, copyFile, getRowForFile, getRowForFileId, renameFile, triggerActionForFile, triggerInlineActionForFileId } from './FilesUtils'

/**
 *
 * @param user
 * @param fileName
 * @param domain
 * @param requesttoken
 * @param metadata
 */
function setMetadata(user: User, fileName: string, domain: string, requesttoken: string, metadata: object) {
	cy.request({
		method: 'PROPPATCH',
		url: `http://${domain}/remote.php/dav/files/${user.userId}/${fileName}`,
		auth: { user: user.userId, pass: user.password },
		headers: {
			requesttoken,
		},
		body: `<?xml version="1.0"?>
			<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
				<d:set>
					<d:prop>
						${Object.entries(metadata).map(([key, value]) => `<${key}>${value}</${key}>`).join('\n')}
					</d:prop>
				</d:set>
			</d:propertyupdate>`,
	})
}

describe('Files: Live photos', { testIsolation: true }, () => {
	let currentUser: User
	let randomFileName: string
	let jpgFileId: number
	let movFileId: number
	let hostname: string
	let requesttoken: string

	before(() => {
		cy.createRandomUser().then((user) => {
			currentUser = user
			cy.login(currentUser)
			cy.visit('/apps/files')
		})

		cy.url().then(url => { hostname = new URL(url).hostname })
	})

	beforeEach(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)

		cy.uploadContent(currentUser, new Blob(['jpg file'], { type: 'image/jpg' }), 'image/jpg', `/${randomFileName}.jpg`)
			.then(response => { jpgFileId = parseInt(response.headers['oc-fileid']) })
		cy.uploadContent(currentUser, new Blob(['mov file'], { type: 'video/mov' }), 'video/mov', `/${randomFileName}.mov`)
			.then(response => { movFileId = parseInt(response.headers['oc-fileid']) })

		cy.login(currentUser)
		cy.visit('/apps/files')

		cy.get('head').invoke('attr', 'data-requesttoken').then(_requesttoken => { requesttoken = _requesttoken as string })

		cy.then(() => {
			setMetadata(currentUser, `${randomFileName}.jpg`, hostname, requesttoken, { 'nc:metadata-files-live-photo': movFileId })
			setMetadata(currentUser, `${randomFileName}.mov`, hostname, requesttoken, { 'nc:metadata-files-live-photo': jpgFileId })
		})

		cy.then(() => {
			cy.visit(`/apps/files/files/${jpgFileId}`) // Refresh and scroll to the .jpg file.
			closeSidebar()
		})
	})

	it('Only renders the .jpg file', () => {
		getRowForFileId(jpgFileId).should('have.length', 1)
		getRowForFileId(movFileId).should('have.length', 0)
	})

	context("'Show hidden files' is enabled", () => {
		before(() => {
			cy.login(currentUser)
			cy.visit('/apps/files')
			cy.get('[data-cy-files-navigation-settings-button]').click()
			// Force:true because the checkbox is hidden by the pretty UI.
			cy.get('[data-cy-files-settings-setting="show_hidden"] input').check({ force: true })
		})

		it("Shows both files when 'Show hidden files' is enabled", () => {
			getRowForFileId(jpgFileId).should('have.length', 1).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}.jpg`)
			getRowForFileId(movFileId).should('have.length', 1).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}.mov`)
		})

		it('Copies both files when copying the .jpg', () => {
			copyFile(`${randomFileName}.jpg`, '.')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).mov`).should('have.length', 1)
		})

		it('Copies both files when copying the .mov', () => {
			copyFile(`${randomFileName}.mov`, '.')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).mov`).should('have.length', 1)
		})

		it('Moves files when moving the .jpg', () => {
			renameFile(`${randomFileName}.jpg`, `${randomFileName}_moved.jpg`)
			clickOnBreadcrumbs('All files')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.jpg`)
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.mov`)
		})

		it('Moves files when moving the .mov', () => {
			renameFile(`${randomFileName}.mov`, `${randomFileName}_moved.mov`)
			clickOnBreadcrumbs('All files')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.jpg`)
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.mov`)
		})

		it('Deletes files when deleting the .jpg', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)

			cy.visit('/apps/files/trashbin')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('to.match', new RegExp(`^${randomFileName}.jpg\\.d[0-9]+$`))
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('to.match', new RegExp(`^${randomFileName}.mov\\.d[0-9]+$`))
		})

		it('Block deletion when deleting the .mov', () => {
			triggerActionForFile(`${randomFileName}.mov`, 'delete')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)

			cy.visit('/apps/files/trashbin')

			getRowForFileId(jpgFileId).should('have.length', 0)
			getRowForFileId(movFileId).should('have.length', 0)
		})

		it('Restores files when restoring the .jpg', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			cy.visit('/apps/files/trashbin')
			triggerInlineActionForFileId(jpgFileId, 'restore')
			clickOnBreadcrumbs('Deleted files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)

			cy.visit('/apps/files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
		})

		it('Blocks restoration when restoring the .mov', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			cy.visit('/apps/files/trashbin')
			triggerInlineActionForFileId(movFileId, 'restore')
			clickOnBreadcrumbs('Deleted files')

			getRowForFileId(jpgFileId).should('have.length', 1)
			getRowForFileId(movFileId).should('have.length', 1)

			cy.visit('/apps/files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)
		})
	})
})
