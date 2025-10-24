/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { clearState } from '../../support/commonUtils.ts'

describe('Calendar: Availability', { testIsolation: true }, () => {
	before(() => {
		clearState()
	})

	it('User can see the availability section in settings', () => {
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/settings/user')
		})

		// can see the section
		cy.findAllByRole('link', { name: /Availability/ })
			.should('be.visible')
			.click()

		cy.url().should('match', /settings\/user\/availability$/)
		cy.findByRole('heading', { name: /Availability/, level: 2 })
			.should('be.visible')
	})

	it('Users can set their availability status', () => {
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/settings/user/availability')
		})

		// can see the settings
		cy.findByRole('list', { name: 'Weekdays' })
			.should('be.visible')
			.within(() => {
				cy.contains('li', 'Friday')
					.should('be.visible')
					.should('contain.text', 'No working hours set')
					.as('fridayItem')
					.findByRole('button', { name: 'Add slot' })
					.click()
			})

		cy.get('@fridayItem')
			.findByLabelText(/start time/i)
			.type('09:00')

		cy.get('@fridayItem')
			.findByLabelText(/end time/i)
			.type('18:00')

		cy.intercept('PROPPATCH', '**/remote.php/dav/calendars/*/inbox').as('saveAvailability')
		cy.get('#availability')
			.findByRole('button', { name: 'Save' })
			.click()
		cy.wait('@saveAvailability')

		cy.reload()

		cy.findByRole('list', { name: 'Weekdays' })
			.should('be.visible')
			.within(() => {
				cy.contains('li', 'Friday')
					.should('be.visible')
					.should('not.contain.text', 'No working hours set')
			})
	})

	it('Users can set their absence', () => {
		cy.createUser({ language: 'en', password: 'password', userId: 'replacement-user' })
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/settings/user/availability')
		})

		cy.findByRole('heading', { name: /absence/i }).scrollIntoView()

		cy.findByLabelText(/First day/)
			.should('be.visible')
			.type('2024-12-24')

		cy.findByLabelText(/Last day/)
			.should('be.visible')
			.type('2024-12-28')

		cy.findByRole('textbox', { name: /Short absence/ })
			.should('be.visible')
			.type('Vacation')
		cy.findByRole('textbox', { name: /Long absence/ })
			.should('be.visible')
			.type('Happy holidays!')

		cy.intercept('GET', '**/ocs/v2.php/apps/files_sharing/api/v1/sharees?*search=replacement*').as('userSearch')
		cy.findByRole('searchbox')
			.should('be.visible')
			.as('userSearchBox')
			.click()
		cy.get('@userSearchBox')
			.type('replacement')
		cy.wait('@userSearch')

		cy.findByRole('option', { name: 'replacement-user' })
			.click()

		cy.intercept('POST', '**/ocs/v2.php/apps/dav/api/v1/outOfOffice/*').as('saveAbsence')
		cy.get('#absence')
			.findByRole('button', { name: 'Save' })
			.click()
		cy.wait('@saveAbsence')

		cy.reload()

		// see its saved
		cy.findByLabelText(/First day/)
			.should('have.value', '2024-12-24')
		cy.findByLabelText(/Last day/)
			.should('have.value', '2024-12-28')
		cy.findByRole('textbox', { name: /Short absence/ })
			.should('have.value', 'Vacation')
		cy.findByRole('textbox', { name: /Long absence/ })
			.should('have.value', 'Happy holidays!')
		cy.findByRole('combobox')
			.should('contain.text', 'replacement-user')
	})
})
