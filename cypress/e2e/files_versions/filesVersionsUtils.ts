/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable jsdoc/require-jsdoc */
import type { User } from '@nextcloud/cypress'
import { createShare, type ShareSetting } from '../files_sharing/FilesSharingUtils'

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

export function assertVersionContent(index: number, expectedContent: string) {
	cy.intercept({ method: 'GET', times: 1, url: 'remote.php/**' }).as('downloadVersion')
	triggerVersionAction(index, 'download')
	cy.wait('@downloadVersion')
		.then(({ response }) => expect(response?.body).to.equal(expectedContent))
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
