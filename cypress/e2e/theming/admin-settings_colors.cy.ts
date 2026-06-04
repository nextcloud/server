/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import {
	defaultBackground,
	defaultPrimary,
	pickColor,
	validateBodyThemingCss,
} from './themingUtils.ts'

const admin = new User('admin', 'admin')

describe('Change the primary color and reset it', function() {
	let selectedColor = ''

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.should('exist')
			.scrollIntoView()
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickColor(cy.findByRole('button', { name: /Primary color/ }))
			.then((color) => {
				selectedColor = color
			})

		cy.wait('@setColor')
		cy.waitUntil(() => validateBodyThemingCss(
			selectedColor,
			defaultBackground,
			defaultPrimary,
		))
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(
			selectedColor,
			defaultBackground,
			defaultPrimary,
		))
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.waitUntil(validateBodyThemingCss)
		cy.screenshot()
	})
})
