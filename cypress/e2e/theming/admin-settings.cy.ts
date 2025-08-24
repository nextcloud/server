/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable n/no-unpublished-import */
import { User } from '@nextcloud/cypress'

import {
	defaultPrimary,
	defaultBackground,
	pickRandomColor,
	validateBodyThemingCss,
	validateUserThemingDefaultCss,
	expectBackgroundColor,
} from './themingUtils'
import { NavigationHeader } from '../../pages/NavigationHeader'

const admin = new User('admin', 'admin')

describe('Admin theming settings visibility check', function() {
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('See the default settings', function() {
		cy.get('[data-admin-theming-setting-color-picker]').should('exist')
		cy.get('[data-admin-theming-setting-file-reset]').should('not.exist')
		cy.get('[data-admin-theming-setting-file-remove]').should('exist')

		cy.get(
			'[data-admin-theming-setting-primary-color] [data-admin-theming-setting-color]',
		).then(($el) => expectBackgroundColor($el, defaultPrimary))

		cy.get(
			'[data-admin-theming-setting-background-color] [data-admin-theming-setting-color]',
		).then(($el) => expectBackgroundColor($el, defaultPrimary))
	})
})

describe('Change the primary color and reset it', function() {
	let selectedColor = ''

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-primary-color]').then(
			(color) => {
				selectedColor = color
			},
		)

		cy.wait('@setColor')
		cy.waitUntil(() =>
			validateBodyThemingCss(
				selectedColor,
				defaultBackground,
				defaultPrimary,
			),
		)
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() =>
			validateBodyThemingCss(
				selectedColor,
				defaultBackground,
				defaultPrimary,
			),
		)
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.waitUntil(validateBodyThemingCss)
		cy.screenshot()
	})
})

describe('Remove the default background and restore it', function() {
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as(
			'removeBackground',
		)

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null))
		cy.waitUntil(() =>
			cy.window().then((win) => {
				const backgroundPlain = getComputedStyle(
					win.document.body,
				).getPropertyValue('--image-background')
				return backgroundPlain !== ''
			}),
		)
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null))
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.waitUntil(validateBodyThemingCss)
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
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-background-color]').then(
			(color) => {
				selectedColor = color
			},
		)

		cy.wait('@setColor')
		cy.waitUntil(() =>
			validateBodyThemingCss(
				defaultPrimary,
				defaultBackground,
				selectedColor,
			),
		)
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as(
			'removeBackground',
		)

		cy.get('[data-admin-theming-setting-file-remove]').scrollIntoView()
		cy.get('[data-admin-theming-setting-file-remove]').click({
			force: true,
		})

		cy.wait('@removeBackground')
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() =>
			validateBodyThemingCss(defaultPrimary, null, selectedColor),
		)
		cy.screenshot()
	})

	it('Undo theming settings and validate login page again', function() {
		cy.resetAdminTheming()
		cy.visit('/')

		cy.waitUntil(validateBodyThemingCss)
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
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as(
			'removeBackground',
		)

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
	})

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		// Pick one of the bright color preset
		pickRandomColor(
			'[data-admin-theming-setting-background-color]',
			4,
		).then((color) => {
			selectedColor = color
		})

		cy.wait('@setColor')
		cy.waitUntil(() =>
			validateBodyThemingCss(defaultPrimary, null, selectedColor),
		)
	})

	it('See the header being inverted', function() {
		cy.waitUntil(() =>
			navigationHeader
				.getNavigationEntries()
				.find('img')
				.then((el) => {
					let ret = true
					el.each(function() {
						ret = ret && window.getComputedStyle(this).filter === 'invert(1)'
					})
					return ret
				})
		)
	})
})

describe('Change the login fields then reset them', function() {
	const name = 'ABCdef123'
	const url = 'https://example.com'
	const slogan = 'Testing is fun'

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Change the name field', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('updateFields')

		// Name
		cy.get(
			'[data-admin-theming-setting-field="name"] input[type="text"]',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="name"] input[type="text"]',
		).type(`{selectall}${name}{enter}`)
		cy.wait('@updateFields')

		// Url
		cy.get(
			'[data-admin-theming-setting-field="url"] input[type="url"]',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="url"] input[type="url"]',
		).type(`{selectall}${url}{enter}`)
		cy.wait('@updateFields')

		// Slogan
		cy.get(
			'[data-admin-theming-setting-field="slogan"] input[type="text"]',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="slogan"] input[type="text"]',
		).type(`{selectall}${slogan}{enter}`)
		cy.wait('@updateFields')
	})

	it('Ensure undo button presence', function() {
		cy.get(
			'[data-admin-theming-setting-field="name"] .input-field__trailing-button',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="name"] .input-field__trailing-button',
		).should('be.visible')

		cy.get(
			'[data-admin-theming-setting-field="url"] .input-field__trailing-button',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="url"] .input-field__trailing-button',
		).should('be.visible')

		cy.get(
			'[data-admin-theming-setting-field="slogan"] .input-field__trailing-button',
		).scrollIntoView()
		cy.get(
			'[data-admin-theming-setting-field="slogan"] .input-field__trailing-button',
		).should('be.visible')
	})

	it('Validate login screen changes', function() {
		cy.logout()
		cy.visit('/')

		cy.get('[data-login-form-headline]').should('contain.text', name)
		cy.get('footer p a').should('have.text', name)
		cy.get('footer p a').should('have.attr', 'href', url)
		cy.get('footer p').should('contain.text', `– ${slogan}`)
	})

	it('Undo theming settings', function() {
		cy.resetAdminTheming()
	})

	it('Validate login screen changes again', function() {
		cy.visit('/')

		cy.get('[data-login-form-headline]').should('not.contain.text', name)
		cy.get('footer p a').should('not.have.text', name)
		cy.get('footer p a').should('not.have.attr', 'href', url)
		cy.get('footer p').should('not.contain.text', `– ${slogan}`)
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
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Disable user background theming', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as(
			'disableUserTheming',
		)

		cy.get(
			'[data-admin-theming-setting-disable-user-theming]',
		).scrollIntoView()
		cy.get('[data-admin-theming-setting-disable-user-theming]').should(
			'be.visible',
		)
		cy.get(
			'[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]',
		).check({ force: true })
		cy.get(
			'[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]',
		).should('be.checked')

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
		cy.contains(
			'Customization has been disabled by your administrator',
		).should('exist')
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
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Change the default background', function() {
		cy.intercept('*/apps/theming/ajax/uploadImage').as('setBackground')

		cy.fixture('image.jpg', null).as('background')
		cy.get(
			'[data-admin-theming-setting-file="background"] input[type="file"]',
		).selectFile('@background', { force: true })

		cy.wait('@setBackground')
		cy.waitUntil(() =>
			validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				null,
			),
		)
	})

	it('Change the background color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-background-color]').then(
			(color) => {
				selectedColor = color
			},
		)

		cy.wait('@setColor')
		cy.waitUntil(() =>
			validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				selectedColor,
			),
		)
	})

	it('Login page should match admin theming settings', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() =>
			validateBodyThemingCss(
				defaultPrimary,
				'/apps/theming/image/background?v=',
				selectedColor,
			),
		)
	})

	it('Login as user', function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView()
		cy.get('[data-user-theming-background-settings]').should('be.visible')
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.get('[data-user-theming-background-default]').should('be.visible')
		cy.get('[data-user-theming-background-default]').should(
			'have.class',
			'background--active',
		)

		cy.waitUntil(() =>
			validateUserThemingDefaultCss(
				selectedColor,
				'/apps/theming/image/background?v=',
			),
		)
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
		cy.get('[data-admin-theming-settings]')
			.should('exist')
			.scrollIntoView()
		cy.get('[data-admin-theming-settings]').should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as(
			'removeBackground',
		)

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null))
	})

	it('Login page should match admin theming settings', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null))
	})

	it('Login as user', function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView()
		cy.get('[data-user-theming-background-settings]').should('be.visible')
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.get('[data-user-theming-background-default]').should('be.visible')
		cy.get('[data-user-theming-background-default]').should(
			'have.class',
			'background--active',
		)

		cy.waitUntil(() => validateUserThemingDefaultCss(defaultPrimary, null))
	})
})
