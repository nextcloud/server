/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/// <reference types="cypress-if" />
import { User } from '@nextcloud/cypress'
import { getUserListRow, handlePasswordConfirmation } from './usersUtils'

const admin = new User('admin', 'admin')
const john = new User('john', '123456')

describe('Settings: Create and delete accounts', function() {
	beforeEach(function() {
		cy.listUsers().then((users) => {
			if ((users as string[]).includes(john.userId)) {
				// ensure created user is deleted
				cy.deleteUser(john)
			}
		})
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	it('Can create a user', function() {
		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// see that the username is ""
			cy.get('input[data-test="username"]').should('exist').and('have.value', '')
			// set the username to john
			cy.get('input[data-test="username"]').type(john.userId)
			// see that the username is john
			cy.get('input[data-test="username"]').should('have.value', john.userId)
			// see that the password is ""
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			// set the password to 123456
			cy.get('input[type="password"]').type(john.password)
			// see that the password is 123456
			cy.get('input[type="password"]').should('have.value', john.password)
		})

		cy.get('form[data-test="form"]').parents('[role="dialog"]').within(() => {
			// submit the new user form
			cy.get('button[type="submit"]').click({ force: true })
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created user is in the list
		getUserListRow(john.userId)
			// see that the list of users contains the user john
			.contains(john.userId).should('exist')
	})

	it('Can create a user with additional field data', function() {
		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// set the username
			cy.get('input[data-test="username"]').should('exist').and('have.value', '')
			cy.get('input[data-test="username"]').type(john.userId)
			cy.get('input[data-test="username"]').should('have.value', john.userId)
			// set the display name
			cy.get('input[data-test="displayName"]').should('exist').and('have.value', '')
			cy.get('input[data-test="displayName"]').type('John Smith')
			cy.get('input[data-test="displayName"]').should('have.value', 'John Smith')
			// set the email
			cy.get('input[data-test="email"]').should('exist').and('have.value', '')
			cy.get('input[data-test="email"]').type('john@example.org')
			cy.get('input[data-test="email"]').should('have.value', 'john@example.org')
			// set the password
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			cy.get('input[type="password"]').type(john.password)
			cy.get('input[type="password"]').should('have.value', john.password)
		})

		cy.get('form[data-test="form"]').parents('[role="dialog"]').within(() => {
			// submit the new user form
			cy.get('button[type="submit"]').click({ force: true })
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created user is in the list
		getUserListRow(john.userId)
			// see that the list of users contains the user john
			.contains(john.userId)
			.should('exist')
	})

	it('Can delete a user', function() {
		let testUser
		// create user
		cy.createRandomUser()
			.then(($user) => {
				testUser = $user
			})
		cy.login(admin)
		// ensure created user is present
		cy.reload().then(() => {
			// see that the user is in the list
			getUserListRow(testUser.userId).within(() => {
				// see that the list of users contains the user testUser
				cy.contains(testUser.userId).should('exist')
				// open the actions menu for the user
				cy.get('[data-cy-user-list-cell-actions]')
					.find('button.action-item__menutoggle')
					.click({ force: true })
			})

			// The "Delete account" action in the actions menu is shown and clicked
			cy.get('.action-item__popper .action').contains('Delete account').should('exist').click({ force: true })

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// And confirmation dialog accepted
			cy.get('.nc-generic-dialog button').contains(`Delete ${testUser.userId}`).click({ force: true })

			// deleted clicked the user is not shown anymore
			getUserListRow(testUser.userId).should('not.exist')
		})
	})
})
