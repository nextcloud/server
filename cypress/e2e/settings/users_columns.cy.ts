/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { assertNotExistOrNotVisible, getUserList } from './usersUtils.js'

const admin = new User('admin', 'admin')

describe('Settings: Show and hide columns', function() {
	before(function() {
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	beforeEach(function() {
		// open the settings dialog
		cy.contains('button', 'Account management settings').click()
		// reset all visibility toggles
		cy.get('.modal-container #settings-section_visibility-settings input[type="checkbox"]').uncheck({ force: true })

		cy.contains('.modal-container', 'Account management settings').within(() => {
			// enable the last login toggle
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').check({ force: true })
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.get('.modal-container').should(el => assertNotExistOrNotVisible(el)))
	})

	it('Can show a column', function() {
		// see that the language column is not in the header
		cy.get('[data-cy-user-list-header-languages]').should('not.exist')

		// see that the language column is not in all user rows
		cy.get('tbody.user-list__body tr').each(($row) => {
			cy.wrap($row).get('[data-test="language"]').should('not.exist')
		})

		// open the settings dialog
		cy.contains('button', 'Account management settings').click()

		cy.contains('.modal-container', 'Account management settings').within(() => {
			// enable the language toggle
			cy.get('[data-test="showLanguages"] input[type="checkbox"]').should('not.be.checked')
			cy.get('[data-test="showLanguages"] input[type="checkbox"]').check({ force: true })
			cy.get('[data-test="showLanguages"] input[type="checkbox"]').should('be.checked')
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.get('.modal-container').should(el => assertNotExistOrNotVisible(el)))

		// see that the language column is in the header
		cy.get('[data-cy-user-list-header-languages]').should('exist')

		// see that the language column is in all user rows
		getUserList().find('tbody tr').each(($row) => {
			cy.wrap($row).get('[data-cy-user-list-cell-language]').should('exist')
		})
	})

	it('Can hide a column', function() {
		// see that the last login column is in the header
		cy.get('[data-cy-user-list-header-last-login]').should('exist')

		// see that the last login column is in all user rows
		getUserList().find('tbody tr').each(($row) => {
			cy.wrap($row).get('[data-cy-user-list-cell-last-login]').should('exist')
		})

		// open the settings dialog
		cy.contains('button', 'Account management settings').click()

		cy.contains('.modal-container', 'Account management settings').within(() => {
			// disable the last login toggle
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').should('be.checked')
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').uncheck({ force: true })
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').should('not.be.checked')
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.contains('.modal-container', 'Account management settings').should(el => assertNotExistOrNotVisible(el)))

		// see that the last login column is not in the header
		cy.get('[data-cy-user-list-header-last-login]').should('not.exist')

		// see that the last login column is not in all user rows
		getUserList().find('tbody tr').each(($row) => {
			cy.wrap($row).get('[data-cy-user-list-cell-last-login]').should('not.exist')
		})
	})
})
