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
import { User } from '@nextcloud/cypress'

import { pickRandomColor, validateBodyThemingCss } from './themingUtils'

const defaultPrimary = '#006aa3'
const defaultBackground = 'kamil-porembinski-clouds.jpg'
const admin = new User('admin', 'admin')

describe('User default background settings', function() {
	before(function() {
		cy.resetAdminTheming()
		cy.resetUserTheming(admin)
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

describe('User select shipped backgrounds and remove background', function() {
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
		cy.waitUntil(() => validateBodyThemingCss('#a53c17', background))
	})

	it('Select a bright shipped background', function() {
		const background = 'bernie-cetonia-aurata-take-off-composition.jpg'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.get(`[data-user-theming-background-shipped="${background}"]`).click()

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#56633d', background, true))
	})

	it('Remove background', function() {
		cy.intercept('*/apps/theming/background/custom').as('clearBackground')

		// Clear background
		cy.get('[data-user-theming-background-clear]').click()

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateBodyThemingCss('#56633d', ''))
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

		pickRandomColor('[data-user-theming-background-color]')

		// Validate custom colour change
		cy.wait('@setColor')
		cy.waitUntil(() => cy.window().then((win) => {
			const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary')
			return primary !== defaultPrimary
		}))
	})
})

describe('User select a bright custom color and remove background', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Remove background', function() {
		cy.intercept('*/apps/theming/background/custom').as('clearBackground')

		// Clear background
		cy.get('[data-user-theming-background-clear]').click()

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateBodyThemingCss(undefined, ''))
	})

	it('Select a custom color', function() {
		cy.intercept('*/apps/theming/background/color').as('setColor')

		// Pick one of the bright color preset
		cy.get('[data-user-theming-background-color]').click()
		cy.get('.color-picker__simple-color-circle:eq(4)').click()

		// Validate custom colour change
		cy.wait('@setColor')
	})

	it('See the header being inverted', function() {
		cy.waitUntil(() => cy.window().then((win) => {
			const firstEntry = win.document.querySelector('.app-menu-main li img')
			if (!firstEntry) {
				return false
			}
			return getComputedStyle(firstEntry).filter === 'invert(1)'
		}))
	})

	it('Select a shipped background', function() {
		const background = 'anatoly-mikhaltsov-butterfly-wing-scale.jpg'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.get(`[data-user-theming-background-shipped="${background}"]`).click()

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#a53c17', background))
	})

	it('See the header NOT being inverted', function() {
		cy.waitUntil(() => cy.window().then((win) => {
			const firstEntry = win.document.querySelector('.app-menu-main li')
			if (!firstEntry) {
				return false
			}
			return getComputedStyle(firstEntry).filter === 'none'
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

		cy.on('uncaught:exception', (err) => {
			// This can happen because of blink engine & skeleton animation, its not a bug just engine related.
			if (err.message.includes('ResizeObserver loop limit exceeded')) {
			  return false
			}
		})

		// Pick background
		cy.get('[data-user-theming-background-custom]').click()
		cy.get('.file-picker__files tr').contains(image).click()
		cy.get('.dialog__actions .button-vue--vue-primary').click()

		// Wait for background to be set
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#4c0c04', 'apps/theming/background?v='))
	})
})

describe('User changes settings and reload the page', function() {
	const image = 'image.jpg'
	const primaryFromImage = '#4c0c04'

	let selectedColor = ''

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

		cy.on('uncaught:exception', (err) => {
			// This can happen because of blink engine & skeleton animation, its not a bug just engine related.
			if (err.message.includes('ResizeObserver loop limit exceeded')) {
			  return false
			}
		})

		// Pick background
		cy.get('[data-user-theming-background-custom]').click()
		cy.get('.file-picker__files tr').contains(image).click()
		cy.get('.dialog__actions .button-vue--vue-primary').click()

		// Wait for background to be set
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss(primaryFromImage, 'apps/theming/background?v='))
	})

	it('Select a custom color', function() {
		cy.intercept('*/apps/theming/background/color').as('setColor')

		cy.get('[data-user-theming-background-color]').click()
		cy.get('.color-picker__simple-color-circle:eq(5)').click()

		// Validate clear background
		cy.wait('@setColor')
		cy.waitUntil(() => cy.window().then((win) => {
			selectedColor = getComputedStyle(win.document.body).getPropertyValue('--color-primary')
			return selectedColor !== primaryFromImage
		}))
	})

	it('Reload the page and validate persistent changes', function() {
		cy.reload()
		cy.waitUntil(() => validateBodyThemingCss(selectedColor, 'apps/theming/background?v='))
	})
})
