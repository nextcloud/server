/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/e2e-test-server/cypress'

import { closeSidebar, enableGridMode, getActionButtonForFile, getInlineActionEntryForFile, getRowForFile } from '../files/FilesUtils.ts'
import { createShare } from './FilesSharingUtils.ts'

describe('files_sharing: Sharing status action', { testIsolation: true }, () => {
	/**
	 * Regression test of https://github.com/nextcloud/server/issues/45723
	 */
	it('No "shared" tag when user ID is purely numerical but there are no shares', () => {
		const user = {
			language: 'en',
			password: 'test1234',
			userId: String(Math.floor(Math.random() * 1000)),
		} as User
		cy.createUser(user)
		cy.mkdir(user, '/folder')
		cy.login(user)

		cy.visit('/apps/files')

		getRowForFile('folder')
			.should('be.visible')
			.find('[data-cy-files-list-row-actions]')
			.findByRole('button', { name: 'Shared' })
			.should('not.exist')
	})

	it('Render quick option for sharing', () => {
		cy.createRandomUser().then((user) => {
			cy.mkdir(user, '/folder')
			cy.login(user)

			cy.visit('/apps/files')
		})

		getRowForFile('folder')
			.should('be.visible')
			.find('[data-cy-files-list-row-actions]')
			.findByRole('button', { name: /Sharing options/ })
			.should('be.visible')
			.click()

		// check the click opened the sidebar
		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			// and ensure the sharing tab is selected
			.findByRole('tab', { name: 'Sharing', selected: true })
			.should('exist')
	})

	describe('Sharing inline status action handling', () => {
		let user: User
		let sharee: User

		before(() => {
			cy.createRandomUser().then(($user) => {
				sharee = $user
			})
			cy.createRandomUser().then(($user) => {
				user = $user
				cy.mkdir(user, '/folder')
				cy.login(user)

				cy.visit('/apps/files')
				getRowForFile('folder').should('be.visible')

				createShare('folder', sharee.userId)
				closeSidebar()
			})
			cy.logout()
		})

		it('Render inline status action for sharer', () => {
			cy.login(user)
			cy.visit('/apps/files')

			getInlineActionEntryForFile('folder', 'sharing-status')
				.should('have.attr', 'aria-label', `Shared with ${sharee.userId}`)
				.should('have.attr', 'title', `Shared with ${sharee.userId}`)
				.should('be.visible')
		})

		it('Render status action in gridview for sharer', () => {
			cy.login(user)
			cy.visit('/apps/files')
			enableGridMode()

			getRowForFile('folder')
				.should('be.visible')
			getActionButtonForFile('folder')
				.click()
			cy.findByRole('menu')
				.findByRole('menuitem', { name: /shared with/i })
				.should('be.visible')
		})

		it('Render inline status action for sharee', () => {
			cy.login(sharee)
			cy.visit('/apps/files')

			getInlineActionEntryForFile('folder', 'sharing-status')
				.should('have.attr', 'aria-label', `Shared by ${user.userId}`)
				.should('be.visible')
		})

		it('Render status action in grid view for sharee', () => {
			cy.login(sharee)
			cy.visit('/apps/files')

			enableGridMode()

			getRowForFile('folder')
				.should('be.visible')
			getActionButtonForFile('folder')
				.click()
			cy.findByRole('menu')
				.findByRole('menuitem', { name: `Shared by ${user.userId}` })
				.should('be.visible')
		})
	})
})
