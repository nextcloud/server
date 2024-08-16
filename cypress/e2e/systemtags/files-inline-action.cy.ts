/* eslint-disable no-unused-expressions */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { randomBytes } from 'crypto'
import { closeSidebar, getRowForFile, triggerActionForFile } from '../files/FilesUtils.ts'

describe('Systemtags: Files integration', { testIsolation: true }, () => {
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

		getRowForFile('file.txt').should('be.visible')
		triggerActionForFile('file.txt', 'details')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('button', { name: 'Actions' })
			.should('be.visible')
			.click()

		cy.findByRole('menuitem', { name: 'Tags' })
			.should('be.visible')
			.click()

		cy.intercept('PUT', '**/remote.php/dav/systemtags-relations/files/**').as('assignTag')
		cy.get('[data-cy-sidebar]')
			.findByRole('combobox', { name: /collaborative tags/i })
			.should('be.visible')
			.type(`${tag}{enter}`)
		cy.wait('@assignTag')
		closeSidebar()

		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.findByRole('listitem')
			.should('be.visible')
			.and('contain.text', tag)
	})

	it('See two assigned tags are also shown in the file list', () => {
		const tag1 = randomBytes(5).toString('base64')
		const tag2 = randomBytes(5).toString('base64')

		getRowForFile('file.txt').should('be.visible')
		triggerActionForFile('file.txt', 'details')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('button', { name: 'Actions' })
			.should('be.visible')
			.click()

		cy.findByRole('menuitem', { name: 'Tags' })
			.should('be.visible')
			.click()

		cy.intercept('PUT', '**/remote.php/dav/systemtags-relations/files/**').as('assignTag')
		cy.get('[data-cy-sidebar]').within(() => {
			cy.findByRole('combobox', { name: /collaborative tags/i })
				.should('be.visible')
				.type(`${tag1}{enter}`)
			cy.wait('@assignTag')
			cy.findByRole('combobox', { name: /collaborative tags/i })
				.should('be.visible')
				.type(`${tag2}{enter}`)
			cy.wait('@assignTag')
		})

		closeSidebar()
		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.children()
			.should('have.length', 2)
			.should('contain.text', tag1)
			.should('contain.text', tag2)
	})

	it.only('See three assigned tags result in overflow entry', () => {
		const tag1 = randomBytes(4).toString('base64')
		const tag2 = randomBytes(4).toString('base64')
		const tag3 = randomBytes(4).toString('base64')

		getRowForFile('file.txt').should('be.visible')

		cy.intercept('PROPFIND', '**/remote.php/dav/**').as('sidebarLoaded')
		triggerActionForFile('file.txt', 'details')
		cy.wait('@sidebarLoaded')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('button', { name: 'Actions' })
			.should('be.visible')
			.click()

		cy.findByRole('menuitem', { name: 'Tags' })
			.should('be.visible')
			.click()

		cy.intercept('PUT', '**/remote.php/dav/systemtags-relations/files/**').as('assignTag')
		cy.get('[data-cy-sidebar]').within(() => {
			cy.findByRole('combobox', { name: /collaborative tags/i })
				.should('be.visible')
				.type(`${tag1}{enter}`)
			cy.wait('@assignTag')

			cy.findByRole('combobox', { name: /collaborative tags/i })
				.should('be.visible')
				.type(`${tag2}{enter}`)
			cy.wait('@assignTag')

			cy.findByRole('combobox', { name: /collaborative tags/i })
				.should('be.visible')
				.type(`${tag3}{enter}`)
			cy.wait('@assignTag')
		})

		closeSidebar()
		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.children()
			.then(($children) => {
				expect($children.length).to.eq(4)
				expect($children.get(0)).be.visible
				expect($children.get(1)).be.visible
				// not visible - just for accessibility
				expect($children.get(2)).not.be.visible
				expect($children.get(3)).not.be.visible
				// Text content
				expect($children.get(1)).contain.text('+2')
				// Remove the '+x' element
				const elements = [$children.get(0), ...$children.get().slice(2)]
					.map((el) => el.innerText.trim())
				expect(elements).to.have.members([tag1, tag2, tag3])
			})
	})
})
