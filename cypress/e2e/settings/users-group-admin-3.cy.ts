/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { randomString } from '../../support/utils/randomString.ts'
import { makeSubAdmin } from '../../support/utils/settings.ts'

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

	it('Only sees groups they are subadmin of', () => {
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
