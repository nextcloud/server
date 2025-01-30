/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { createShare } from './FilesSharingUtils.ts'
import { closeSidebar, getRowForFile } from '../files/FilesUtils.ts'

describe('files_sharing: Files inline status action', { testIsolation: true }, () => {
	/**
	 * Regression test of https://github.com/nextcloud/server/issues/45723
	 */
	it('No "shared" tag when user ID is purely numerical', () => {
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

	describe('Sharing inline status action handling', () => {
		let user: User
		let sharee: User

		beforeEach(() => {
			cy.createRandomUser().then(($user) => {
				user = $user
			})
			cy.createRandomUser().then(($user) => {
				sharee = $user
			})
		})

		it('Render quick option for sharing', () => {
			cy.mkdir(user, '/folder')
			cy.login(user)

			cy.visit('/apps/files')
			getRowForFile('folder')
				.should('be.visible')

			getRowForFile('folder')
				.should('be.visible')
				.find('[data-cy-files-list-row-actions]')
				.findByRole('button', { name: /Show sharing options/ })
				.should('be.visible')
				.click()

			// check the click opened the sidebar
			cy.get('[data-cy-sidebar]')
				.should('be.visible')
				// and ensure the sharing tab is selected
				.findByRole('tab', { name: 'Sharing', selected: true })
				.should('exist')
		})

		it('Render inline status action for sharer', () => {
			cy.mkdir(user, '/folder')
			cy.login(user)

			cy.visit('/apps/files')
			getRowForFile('folder')
				.should('be.visible')
			createShare('folder', sharee.userId)
			closeSidebar()

			getRowForFile('folder')
				.should('be.visible')
				.find('[data-cy-files-list-row-actions]')
				.findByRole('button', { name: /^Shared with/i })
				.should('be.visible')
		})

		it('Render inline status action for sharee', () => {
			cy.mkdir(user, '/folder')
			cy.login(user)

			cy.visit('/apps/files')
			getRowForFile('folder')
				.should('be.visible')
			createShare('folder', sharee.userId)
			closeSidebar()

			cy.login(sharee)
			cy.visit('/apps/files')

			getRowForFile('folder')
				.should('be.visible')
				.find('[data-cy-files-list-row-actions]')
				.findByRole('button', { name: `Shared by ${user.userId}` })
				.should('be.visible')
		})
	})
})
