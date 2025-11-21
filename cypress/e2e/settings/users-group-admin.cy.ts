/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { randomString } from '../../support/utils/randomString.ts'
import { getUserListRow, handlePasswordConfirmation } from './usersUtils.ts'

const admin = new User('admin', 'admin')
const john = new User('john', '123456')

/**
 * Make a user subadmin of a group.
 *
 * @param user - The user to make subadmin
 * @param group - The group the user should be subadmin of
 */
function makeSubAdmin(user: User, group: string): void {
	cy.request({
		url: `${Cypress.config('baseUrl')!.replace('/index.php', '')}/ocs/v2.php/cloud/users/${user.userId}/subadmins`,
		method: 'POST',
		auth: {
			user: admin.userId,
			password: admin.userId,
		},
		headers: {
			'OCS-ApiRequest': 'true',
		},
		body: {
			groupid: group,
		},
	})
}

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

	// Skiping as this crash the webengine in the CI
	it.skip('Can create a new user when member of multiple groups', () => {
		const group2 = randomString(7)
		cy.runOccCommand(`group:add '${group2}'`)
		cy.runOccCommand(`group:adduser '${group2}' '${subadmin.userId}'`)
		makeSubAdmin(subadmin, group2)

		cy.login(subadmin)
		// open the User settings
		cy.visit('/settings/users')

		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// see that no group is pre-selected
			cy.get('[data-test="groups"] .vs__selected').should('not.exist')
			// see both groups are available
			cy.findByRole('combobox', { name: /member of the following groups/i })
				.should('be.visible')
				.click()
			// can select both groups
			cy.document().its('body')
				.findByRole('listbox', { name: 'Options' })
				.should('be.visible')
				.as('options')
				.findAllByRole('option')
				.should('have.length', 2)
				.get('@options')
				.findByRole('option', { name: group })
				.should('be.visible')
				.get('@options')
				.findByRole('option', { name: group2 })
				.should('be.visible')
				.click()
			// see group is selected
			cy.contains('[data-test="groups"] .vs__selected', group2).should('be.visible')

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

	it.skip('Only sees groups they are subadmin of', () => {
		const group2 = randomString(7)
		cy.runOccCommand(`group:add '${group2}'`)
		cy.runOccCommand(`group:adduser '${group2}' '${subadmin.userId}'`)
		// not a subadmin!

		cy.login(subadmin)
		// open the User settings
		cy.visit('/settings/users')

		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// see that the subadmin group is pre-selected
			cy.contains('[data-test="groups"] .vs__selected', group).should('be.visible')
			// see only the subadmin group is available
			cy.findByRole('combobox', { name: /member of the following groups/i })
				.should('be.visible')
				.click()
			// can select both groups
			cy.document().its('body')
				.findByRole('listbox', { name: 'Options' })
				.should('be.visible')
				.as('options')
				.findAllByRole('option')
				.should('have.length', 1)
		})
	})
})
