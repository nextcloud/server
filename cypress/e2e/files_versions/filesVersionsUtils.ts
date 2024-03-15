/* eslint-disable jsdoc/require-jsdoc */
/**
 * @copyright Copyright (c) 2022 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
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

import type { User } from '@nextcloud/cypress'
import path from 'path'
import { createShare, type ShareSetting } from '../files_sharing/filesSharingUtils'

export const uploadThreeVersions = (user: User, fileName: string) => {
	// A new version will not be created if the changes occur
	// within less than one second of each other.
	// eslint-disable-next-line cypress/no-unnecessary-waiting
	cy.uploadContent(user, new Blob(['v1'], { type: 'text/plain' }), 'text/plain', `/${fileName}`)
		.wait(1100)
		.uploadContent(user, new Blob(['v2'], { type: 'text/plain' }), 'text/plain', `/${fileName}`)
		.wait(1100)
		.uploadContent(user, new Blob(['v3'], { type: 'text/plain' }), 'text/plain', `/${fileName}`)
	cy.login(user)
}

export function openVersionsPanel(fileName: string) {
	// Detect the versions list fetch
	cy.intercept('PROPFIND', '**/dav/versions/*/versions/**').as('getVersions')

	// Open the versions tab
	cy.window().then(win => {
		win.OCA.Files.Sidebar.setActiveTab('version_vue')
		win.OCA.Files.Sidebar.open(`/${fileName}`)
	})

	// Wait for the versions list to be fetched
	cy.wait('@getVersions')
	cy.get('#tab-version_vue').should('be.visible', { timeout: 10000 })
}

export function toggleVersionMenu(index: number) {
	cy.get('#tab-version_vue [data-files-versions-version]')
		.eq(index)
		.find('button')
		.click()
}

export function triggerVersionAction(index: number, actionName: string) {
	toggleVersionMenu(index)
	cy.get(`[data-cy-files-versions-version-action="${actionName}"]`).filter(':visible').click()
}

export function nameVersion(index: number, name: string) {
	cy.intercept('PROPPATCH', '**/dav/versions/*/versions/**').as('labelVersion')
	triggerVersionAction(index, 'label')
	cy.get(':focused').type(`${name}{enter}`)
	cy.wait('@labelVersion')
}

export function restoreVersion(index: number) {
	cy.intercept('MOVE', '**/dav/versions/*/versions/**').as('restoreVersion')
	triggerVersionAction(index, 'restore')
	cy.wait('@restoreVersion')
}

export function deleteVersion(index: number) {
	cy.intercept('DELETE', '**/dav/versions/*/versions/**').as('deleteVersion')
	triggerVersionAction(index, 'delete')
	cy.wait('@deleteVersion')
}

export function doesNotHaveAction(index: number, actionName: string) {
	toggleVersionMenu(index)
	cy.get(`[data-cy-files-versions-version-action="${actionName}"]`).should('not.exist')
	toggleVersionMenu(index)
}

export function assertVersionContent(filename: string, index: number, expectedContent: string) {
	const downloadsFolder = Cypress.config('downloadsFolder')

	triggerVersionAction(index, 'download')

	return cy.readFile(path.join(downloadsFolder, filename))
		.then((versionContent) => expect(versionContent).to.equal(expectedContent))
		.then(() => cy.exec(`rm ${downloadsFolder}/${filename}`))
}

export function setupTestSharedFileFromUser(owner: User, randomFileName: string, shareOptions: Partial<ShareSetting>) {
	return cy.createRandomUser()
		.then((recipient) => {
			cy.login(owner)
			cy.visit('/apps/files')
			createShare(randomFileName, recipient.userId, shareOptions)
			cy.login(recipient)
			cy.visit('/apps/files')
			return cy.wrap(recipient)
		})
}
