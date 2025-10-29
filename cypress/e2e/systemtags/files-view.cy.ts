/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomBytes } from 'crypto'
import { closeSidebar, getRowForFile, getRowForFileId, triggerActionForFile } from '../files/FilesUtils.ts'

describe('Systemtags: Files view', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('See first assigned tag in the file list', () => {
		const tag = randomBytes(8).toString('base64')
		let tagId

		// Tag the file
		tagNode(tag, 'folder')
			.then((id) => { tagId = id })

		// open the tags view
		cy.visit('/apps/files/tags').then(() => {
			// see the tag
			getRowForFileId(tagId).should('be.visible')
			getRowForFile('folder').should('not.exist')
			getRowForFile('file.txt').should('not.exist')

			// see that the tag has its content
			getRowForFileId(tagId).find('[data-cy-files-list-row-name-link]').click()
			getRowForFile('folder').should('be.visible')
			getRowForFile('file.txt').should('not.exist')
		})
	})
})

function getCollaborativeTagsInput(): Cypress.Chainable<JQuery<HTMLElement>> {
	return cy.get('[data-cy-sidebar]')
		.findByRole('combobox', { name: /collaborative tags/i })
		.should('be.visible')
		.should('not.have.attr', 'disabled', { timeout: 5000 })
}

function tagNode(tag: string, node: string): Cypress.Chainable<number> {
	getRowForFile(node).should('be.visible')

	triggerActionForFile(node, 'details')
	cy.get('[data-cy-sidebar]')
		.should('be.visible')
		.findByRole('button', { name: 'Actions' })
		.should('be.visible')
		.click()
	cy.findByRole('menuitem', { name: 'Tags' })
		.should('be.visible')
		.click()
	cy.intercept('PUT', '**/remote.php/dav/systemtags-relations/files/**').as('assignTag')
	getCollaborativeTagsInput()
		.type(`{selectAll}${tag}{enter}`)
	cy.wait('@assignTag')
	closeSidebar()
	return cy.get('@assignTag')
		.then(({ request }) => request.body.id)
}
