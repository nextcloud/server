/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { NavigationHeader } from '../../pages/NavigationHeader.ts'
import {
	defaultBackground,
	defaultPrimary,
	pickColor,
	validateBodyThemingCss,
	validateUserThemingDefaultCss,
} from './themingUtils.ts'

const admin = new User('admin', 'admin')

describe('Remove the default background and restore it', { testIsolation: false }, function() {
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

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')
		cy.intercept('*/apps/theming/theme/default.css?*').as('cssLoaded')

		cy.findByRole('checkbox', { name: /remove background image/i })
			.should('exist')
			.should('not.be.checked')
			.check({ force: true })

		cy.wait('@removeBackground')
		cy.wait('@cssLoaded')

		cy.window()
			.should(() => validateBodyThemingCss(defaultPrimary, null))
		cy.waitUntil(() => cy.window().then((win) => {
			const backgroundPlain = getComputedStyle(win.document.body).getPropertyValue('--image-background')
			return backgroundPlain !== ''
		}))
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss(defaultPrimary, null))
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss())
		cy.screenshot()
	})
})

describe('Remove the default background with a custom background color', function() {
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

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')
		cy.intercept('*/apps/theming/theme/default.css?*').as('cssLoaded')

		pickColor(cy.findByRole('button', { name: /Background color/ }))
			.then((color) => {
				selectedColor = color
			})

		cy.wait('@setColor')
		cy.wait('@cssLoaded')

		cy.window()
			.should(() => validateBodyThemingCss(
				defaultPrimary,
				defaultBackground,
				selectedColor,
			))
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

		cy.findByRole('checkbox', { name: /remove background image/i })
			.should('exist')
			.should('not.be.checked')
			.check({ force: true })
		cy.wait('@removeBackground')
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss(defaultPrimary, null, selectedColor))
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss())
		cy.screenshot()
	})
})

describe('Remove the default background with a bright color', function() {
	const navigationHeader = new NavigationHeader()
	let selectedColor = ''

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.resetUserTheming(admin)
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.should('exist')
			.scrollIntoView()
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')
		cy.findByRole('checkbox', { name: /remove background image/i })
			.check({ force: true })
		cy.wait('@removeBackground')
	})

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')
		cy.intercept('*/apps/theming/theme/default.css?*').as('cssLoaded')

		pickColor(cy.findByRole('button', { name: /Background color/ }), 4)
			.then((color) => {
				selectedColor = color
			})

		cy.wait('@setColor')
		cy.wait('@cssLoaded')

		cy.window()
			.should(() => validateBodyThemingCss(defaultPrimary, null, selectedColor))
	})

	it('See the header being inverted', function() {
		cy.waitUntil(() => navigationHeader
			.getNavigationEntries()
			.find('img')
			.then((el) => {
				let ret = true
				el.each(function() {
					ret = ret && window.getComputedStyle(this).filter === 'invert(1)'
				})
				return ret
			}))
	})
})

describe('Disable user theming and enable it back', function() {
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

	it('Disable user background theming', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('disableUserTheming')

		cy.findByRole('checkbox', { name: /Disable user theming/ })
			.should('exist')
			.and('not.be.checked')
			.check({ force: true })

		cy.wait('@disableUserTheming')
	})

	it('Login as user', function() {
		cy.logout()
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('User cannot not change background settings', function() {
		cy.visit('/settings/user/theming')
		cy.contains('Customization has been disabled by your administrator').should('exist')
	})
})

describe('The user default background settings reflect the admin theming settings', function() {
	let selectedColor = ''

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	after(function() {
		cy.resetAdminTheming()
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.should('exist')
			.scrollIntoView()
	})

	it('Change the default background', function() {
		cy.intercept('*/apps/theming/ajax/uploadImage').as('setBackground')
		cy.intercept('*/apps/theming/theme/default.css?*').as('cssLoaded')

		cy.fixture('image.jpg', null).as('background')
		cy.get('input[type="file"][name="background"]')
			.should('exist')
			.selectFile('@background', { force: true })

		cy.wait('@setBackground')
		cy.wait('@cssLoaded')

		cy.window()
			.should(() => validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				null,
			))
	})

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')
		cy.intercept('*/apps/theming/theme/default.css?*').as('cssLoaded')

		pickColor(cy.findByRole('button', { name: /Background color/ }))
			.then((color) => {
				selectedColor = color
			})

		cy.wait('@setColor')
		cy.wait('@cssLoaded')

		cy.window()
			.should(() => validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				selectedColor,
			))
	})

	it('Login page should match admin theming settings', function() {
		cy.logout()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				selectedColor,
			))
	})

	it('Login as user', function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.scrollIntoView()
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.findByRole('button', { name: 'Default background' })
			.should('exist')
			.and('have.attr', 'aria-pressed', 'true')

		cy.window()
			.should(() => validateUserThemingDefaultCss(
				selectedColor,
				'/apps/theming/image/background?v=',
			))
	})
})

describe('The user default background settings reflect the admin theming settings with background removed', function() {
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	after(function() {
		cy.resetAdminTheming()
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.should('exist')
			.scrollIntoView()
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')
		cy.findByRole('checkbox', { name: /remove background image/i })
			.check({ force: true })
		cy.wait('@removeBackground')
	})

	it('Login page should match admin theming settings', function() {
		cy.logout()
		cy.visit('/')

		cy.window()
			.should(() => validateBodyThemingCss(defaultPrimary, null))
	})

	it('Login as user', function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: 'Background and color' })
			.scrollIntoView()
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.findByRole('button', { name: 'Default background' })
			.should('exist')
			.and('have.attr', 'aria-pressed', 'true')

		cy.window()
			.should(() => validateUserThemingDefaultCss(defaultPrimary, null))
	})
})
