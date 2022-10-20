/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import type { User } from '@nextcloud/cypress'

const defaultPrimary = '#006aa3'
const defaultBackground = 'kamil-porembinski-clouds.jpg'

const validateThemingCss = function(expectedPrimary = '#0082c9', expectedBackground = 'kamil-porembinski-clouds.jpg', bright = false) {
	return cy.window().then((win) => {
		const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary')
		const background = getComputedStyle(win.document.body).getPropertyValue('--image-background')
		const invertIfBright = getComputedStyle(win.document.body).getPropertyValue('--background-image-invert-if-bright')

		// Returning boolean for cy.waitUntil usage
		return primary === expectedPrimary
			&& background.includes(expectedBackground)
			&& invertIfBright === (bright ? 'invert(100%)' : 'no')
	})
}

describe('User default background settings', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	// Default cloud background is not rendered if admin theming background remains unchanged
	it('Default cloud background is not rendered', function() {
		cy.get(`[data-user-theming-background-shipped="${defaultBackground}"]`).should('not.exist')
	})

	it('Default is selected on new users', function() {
		cy.get('[data-user-theming-background-default]').should('be.visible')
		cy.get('[data-user-theming-background-default]').should('have.class', 'background--active')
	})
})

describe('User select shipped backgrounds', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Select a shipped background', function() {
		const background = 'anatoly-mikhaltsov-butterfly-wing-scale.jpg'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.get(`[data-user-theming-background-shipped="${background}"]`).click()

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateThemingCss('#a53c17', background))
	})

	it('Select a bright shipped background', function() {
		const background = 'bernie-cetonia-aurata-take-off-composition.jpg'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.get(`[data-user-theming-background-shipped="${background}"]`).click()

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateThemingCss('#56633d', background, true))
	})

	it('Remove background', function() {
		cy.intercept('*/apps/theming/background/custom').as('clearBackground')

		// Clear background
		cy.get('[data-user-theming-background-clear]').click()

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateThemingCss('#56633d', ''))
	})
})

describe('User select a custom color', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Select a custom color', function() {
		cy.intercept('*/apps/theming/background/color').as('setColor')

		cy.get('[data-user-theming-background-color]').click()
		cy.get('.color-picker__simple-color-circle:eq(3)').click()

		// Validate clear background
		cy.wait('@setColor')
		cy.waitUntil(() => cy.window().then((win) => {
			const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary')
			return primary !== defaultPrimary
		}))
	})
})

describe('User select a custom background', function() {
	const image = 'image.jpg'
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.uploadFile(user, image, 'image/jpeg')
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Select a custom background', function() {
		cy.intercept('*/apps/theming/background/custom').as('setBackground')

		// Pick background
		cy.get('[data-user-theming-background-custom]').click()
		cy.get(`#picker-filestable tr[data-entryname="${image}"]`).click()
		cy.get('#oc-dialog-filepicker-content ~ .oc-dialog-buttonrow button.primary').click()

		// Wait for background to be set
		cy.wait('@setBackground')
		cy.waitUntil(() => validateThemingCss('#4c0c04', 'apps/theming/background?v='))
	})
})
