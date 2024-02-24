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

export const openVersionsPanel = (fileName: string) =>{
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

export const openVersionMenu = (index: number) => {
	cy.get('#tab-version_vue').within(() => {
		cy.get('[data-files-versions-version]')
			.eq(index).within(() => {
				cy.get('.action-item__menutoggle').filter(':visible')
					.click()
			})
	})
}

export const clickPopperAction = (actionName: string) => {
	cy.get('.v-popper__popper').filter(':visible')
		.contains(actionName)
		.click()
}

export const nameVersion = (index: number, name: string) => {
	openVersionMenu(index)
	clickPopperAction('Name this version')
	cy.get(':focused').type(`${name}{enter}`)
}

export const assertVersionContent = (filename: string, index: number, expectedContent: string) => {
	const downloadsFolder = Cypress.config('downloadsFolder')

	openVersionMenu(index)
	clickPopperAction('Download version')

	return cy.readFile(path.join(downloadsFolder, filename))
		.then((versionContent) => expect(versionContent).to.equal(expectedContent))
		.then(() => cy.exec(`rm ${downloadsFolder}/${filename}`))
}
