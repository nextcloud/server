/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFileId } from './FilesUtils.ts'

describe('Files hotkey handling', () => {
	before(() => {
		cy.createRandomUser().then((user) => {
			cy.mkdir(user, '/abcd')
			cy.mkdir(user, '/zyx')
			cy.rm(user, '/welcome.txt')
			cy.login(user)
		})
	})

	beforeEach(() => cy.visit('/apps/files'))

	it('Pressing "arrow down" should go to first file', () => {
		cy.get('[data-cy-files-list]')
			.press(Cypress.Keyboard.Keys.DOWN)

		cy.url()
			.should('match', /\/apps\/files\/files\/\d+/)
			.then((url) => new URL(url).pathname.split('/').at(-1))
			.then((fileId) => getRowForFileId(fileId)
				.should('exist')
				.and('have.attr', 'data-cy-files-list-row-name', 'abcd'))
	})

	it('Pressing "arrow up" should go to first file', () => {
		cy.get('[data-cy-files-list]')
			.press(Cypress.Keyboard.Keys.UP)

		cy.url()
			.should('match', /\/apps\/files\/files\/\d+/)
			.then((url) => new URL(url).pathname.split('/').at(-1))
			.then((fileId) => getRowForFileId(fileId)
				.should('exist')
				.and('have.attr', 'data-cy-files-list-row-name', 'zyx'))
	})

	it('Pressing D should open the sidebar once', () => {
		activateFirstRow()
		cy.get('[data-cy-files-list]')
			.press('d')

		cy.get('[data-cy-sidebar]')
			.should('exist')
			.and('be.visible')
	})

	it('Pressing F2 should rename the file', () => {
		activateFirstRow()
		cy.get('[data-cy-files-list]')
			.should('exist')
			.then(($el) => {
				const el = $el.get(0)
				// manually dispatch as Cypress refuses to press F-keys for "security reasons"
				cy.log('Dispatching F2 keydown/keyup events')
				el.dispatchEvent(new KeyboardEvent('keydown', { key: 'F2', code: 'F2', bubbles: true }))
				el.dispatchEvent(new KeyboardEvent('keyup', { key: 'F2', code: 'F2', bubbles: true }))
				el.dispatchEvent(new KeyboardEvent('keypress', { key: 'F2', code: 'F2', bubbles: true }))
			})

		cy.get('[data-cy-files-list-row-name]')
			.first()
			.findByRole('textbox', { name: /Folder name/ })
			.should('exist')
	})

	it('Pressing S should toggle favorite', () => {
		activateFirstRow()
		cy.get('[data-cy-files-list]')
			.press('s')

		cy.get('[data-cy-files-list-row-name]')
			.first()
			.as('firstRow')
			.findByRole('img', { name: /Favorite/ })
			.should('exist')

		cy.get('[data-cy-files-list]')
			.press('s')

		cy.get('@firstRow')
			.findByRole('img', { name: /Favorite/ })
			.should('not.exist')
	})

	it('Pressing DELETE should delete the folder', () => {
		activateFirstRow()
		cy.get('td[data-cy-files-list-row-name]')
			.should('have.length', 2)

		cy.get('[data-cy-files-list]')
			.press(Cypress.Keyboard.Keys.DELETE)

		cy.get('td[data-cy-files-list-row-name]')
			.should('have.length', 1)
	})
})

/**
 * Activates the first row in the files list by simulating a press of the down arrow key.
 */
function activateFirstRow() {
	cy.get('[data-cy-files-list]')
		.press(Cypress.Keyboard.Keys.DOWN)
	cy.url()
		.should('match', /\/apps\/files\/files\/\d+/)
}
