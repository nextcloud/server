/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState } from '../../support/commonUtils.ts'
import { getUserListRow, handlePasswordConfirmation, toggleEditButton, waitLoading } from './usersUtils.ts'

const admin = new User('admin', 'admin')

describe('Settings: User Manager Management', function() {
	let user: User
	let manager: User

	beforeEach(function() {
		clearState()
		cy.createRandomUser().then(($user) => {
			manager = $user
			return cy.createRandomUser()
		}).then(($user) => {
			user = $user
			cy.login(admin)
			cy.intercept('PUT', `/ocs/v2.php/cloud/users/${user.userId}*`).as('updateUser')
		})
	})

	it('Can assign and remove a manager through the UI', function() {
		cy.visit('/settings/users')

		toggleEditButton(user, true)

		// Scroll to manager cell and wait for it to be visible
		getUserListRow(user.userId)
			.find('[data-cy-user-list-cell-manager]')
			.scrollIntoView()
			.should('be.visible')

		// Assign a manager
		getUserListRow(user.userId).find('[data-cy-user-list-cell-manager]').within(() => {
			// Verify no manager is set initially
			cy.get('.vs__selected').should('not.exist')

			// Open the dropdown menu
			cy.get('[role="combobox"]').click({ force: true })

			// Wait for the dropdown to be visible and initialized
			waitLoading('[data-cy-user-list-input-manager]')

			// Type the manager's username to search
			cy.get('input[type="search"]').type(manager.userId, { force: true })

			// Wait for the search results to load
			waitLoading('[data-cy-user-list-input-manager]')
		})

		// Now select the manager from the filtered results
		// Since the dropdown is floating, we need to search globally
		cy.get('.vs__dropdown-menu').find('li').contains('span', manager.userId).should('be.visible').click({ force: true })

		// Handle password confirmation if needed
		handlePasswordConfirmation(admin.password)

		// Verify the manager is selected in the UI
		cy.get('.vs__selected').should('exist').and('contain.text', manager.userId)

		// Verify the PUT request was made to set the manager
		cy.wait('@updateUser').then((interception) => {
			// Verify the request URL and body
			expect(interception.request.url).to.match(/\/cloud\/users\/.+/)
			expect(interception.request.body).to.deep.equal({
				key: 'manager',
				value: manager.userId,
			})
			expect(interception.response?.statusCode).to.equal(200)
		})

		// Wait for the save to complete
		waitLoading('[data-cy-user-list-input-manager]')

		// Verify the manager is set in the backend
		cy.getUserData(user).then(($result) => {
			expect($result.body).to.contain(`<manager>${manager.userId}</manager>`)
		})

		// Now remove the manager
		getUserListRow(user.userId).find('[data-cy-user-list-cell-manager]').within(() => {
			// Clear the manager selection
			cy.get('.vs__clear').click({ force: true })

			// Verify the manager is cleared in the UI
			cy.get('.vs__selected').should('not.exist')

			// Handle password confirmation if needed
			handlePasswordConfirmation(admin.password)
		})

		// Verify the PUT request was made to clear the manager
		cy.wait('@updateUser').then((interception) => {
			// Verify the request URL and body
			expect(interception.request.url).to.match(/\/cloud\/users\/.+/)
			expect(interception.request.body).to.deep.equal({
				key: 'manager',
				value: '',
			})
			expect(interception.response?.statusCode).to.equal(200)
		})

		// Wait for the save to complete
		waitLoading('[data-cy-user-list-input-manager]')

		// Verify the manager is cleared in the backend
		cy.getUserData(user).then(($result) => {
			expect($result.body).to.not.contain(`<manager>${manager.userId}</manager>`)
			expect($result.body).to.contain('<manager></manager>')
		})

		// Finish editing the user
		toggleEditButton(user, false)
	})
})
