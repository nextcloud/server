/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { getRowForFile } from '../../files/FilesUtils.ts'
import { createLinkShare, setupData } from './PublicShareUtils.ts'

describe('files_sharing: Public share - setting the default view mode', () => {

	let user: User

	beforeEach(() => {
		cy.createRandomUser()
			.then(($user) => (user = $user))
			.then(() => setupData(user, 'shared'))
	})

	it('is by default in list view', () => {
		const context = { user }
		createLinkShare(context, 'shared')
			.then((url) => {
				cy.logout()
				cy.visit(url!)

				// See file is visible
				getRowForFile('foo.txt').should('be.visible')
				// See we are in list view
				cy.findByRole('button', { name: 'Switch to grid view' })
					.should('be.visible')
					.and('not.be.disabled')
			})
	})

	it('can be toggled by user', () => {
		const context = { user }
		createLinkShare(context, 'shared')
			.then((url) => {
				cy.logout()
				cy.visit(url!)

				// See file is visible
				getRowForFile('foo.txt')
					.should('be.visible')
					// See we are in list view
					.find('.files-list__row-icon')
					.should(($el) => expect($el.outerWidth()).to.be.lessThan(99))

				// See the grid view toggle
				cy.findByRole('button', { name: 'Switch to grid view' })
					.should('be.visible')
					.and('not.be.disabled')
					// And can change to grid view
					.click()

				// See we are in grid view
				getRowForFile('foo.txt')
					.find('.files-list__row-icon')
					.should(($el) => expect($el.outerWidth()).to.be.greaterThan(99))

				// See the grid view toggle is now the list view toggle
				cy.findByRole('button', { name: 'Switch to list view' })
					.should('be.visible')
					.and('not.be.disabled')
			})
	})

	it('can be changed to default grid view', () => {
		const context = { user }
		createLinkShare(context, 'shared')
			.then((url) => {
				// Can set the "grid" view checkbox
				cy.findByRole('list', { name: 'Link shares' })
					.findAllByRole('listitem')
					.first()
					.findByRole('button', { name: /Actions/i })
					.click()
				cy.findByRole('menuitem', { name: /Customize link/i }).click()
				cy.findByRole('checkbox', { name: /Show files in grid view/i })
					.scrollIntoView()
				cy.findByRole('checkbox', { name: /Show files in grid view/i })
					.should('not.be.checked')
					.check({ force: true })

				// Wait for the share update
				cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('updateShare')
				cy.findByRole('button', { name: 'Update share' }).click()
				cy.wait('@updateShare').its('response.statusCode').should('eq', 200)

				// Logout and visit the share
				cy.logout()
				cy.visit(url!)

				// See file is visible
				getRowForFile('foo.txt').should('be.visible')
				// See we are in list view
				cy.findByRole('button', { name: 'Switch to list view' })
					.should('be.visible')
					.and('not.be.disabled')
			})
	})
})
