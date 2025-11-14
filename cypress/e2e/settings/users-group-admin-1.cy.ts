/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { randomString } from '../../support/utils/randomString.ts'
import { admin, makeSubAdmin } from '../../support/utils/settings.ts'
import { getUserListRow, handlePasswordConfirmation } from './usersUtils.ts'

const john = new User('john', '123456')

// This test suite is split as otherwise Cypress crashes
describe('Settings: Create accounts as a group admin', function() {
	let subadmin: User
	let group: string

	beforeEach(() => {
		group = randomString(7)
		cy.deleteUser(john)
		cy.createRandomUser().then((user) => {
			subadmin = user
			cy.runOccCommand(`group:add '${group}'`)
			cy.runOccCommand(`group:adduser '${group}' '${subadmin.userId}'`)
			makeSubAdmin(subadmin, group)
		})
	})

	it('Can create a user with prefilled single group', () => {
		cy.login(subadmin)
		// open the User settings
		cy.visit('/settings/users')

		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// see that the correct group is preselected
			cy.contains('[data-test="groups"] .vs__selected', group).should('be.visible')
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
})
