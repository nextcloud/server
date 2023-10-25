/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		cy.get('.app-navigation-entry__settings').contains('User management settings').click()
		// reset all visibility toggles
		cy.get('.modal-container #settings-section_visibility-settings input[type="checkbox"]').uncheck({ force: true })

		cy.get('.modal-container').within(() => {
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
		cy.get('.app-navigation-entry__settings').contains('User management settings').click()

		cy.get('.modal-container').within(() => {
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
		cy.get('.app-navigation-entry__settings').contains('User management settings').click()

		cy.get('.modal-container').within(() => {
			// disable the last login toggle
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').should('be.checked')
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').uncheck({ force: true })
			cy.get('[data-test="showLastLogin"] input[type="checkbox"]').should('not.be.checked')
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.get('.modal-container').should(el => assertNotExistOrNotVisible(el)))

		// see that the last login column is not in the header
		cy.get('[data-cy-user-list-header-last-login]').should('not.exist')

		// see that the last login column is not in all user rows
		getUserList().find('tbody tr').each(($row) => {
			cy.wrap($row).get('[data-cy-user-list-cell-last-login]').should('not.exist')
		})
	})
})
