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
/* eslint-disable n/no-unpublished-import */
import { User } from '@nextcloud/cypress'
import { colord } from 'colord'

import { defaultPrimary, defaultBackground, pickRandomColor, validateBodyThemingCss, validateUserThemingDefaultCss } from './themingUtils'

const admin = new User('admin', 'admin')

describe('Admin theming settings visibility check', function() {
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('See the default settings', function() {
		cy.get('[data-admin-theming-setting-primary-color-picker]').should('contain.text', defaultPrimary)
		cy.get('[data-admin-theming-setting-primary-color-reset]').should('not.exist')
		cy.get('[data-admin-theming-setting-file-reset]').should('not.exist')
		cy.get('[data-admin-theming-setting-file-remove]').should('be.visible')
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-primary-color-picker]')
			.then(color => { selectedColor = color })

		cy.wait('@setColor')
		cy.waitUntil(() => validateBodyThemingCss(selectedColor, defaultBackground))
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(selectedColor, defaultBackground))
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null))
		cy.waitUntil(() => cy.window().then((win) => {
			const backgroundPlain = getComputedStyle(win.document.body).getPropertyValue('--image-background-plain')
			return backgroundPlain !== ''
		}))
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

describe('Remove the default background with a custom primary color', function() {
	let selectedColor = ''

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-primary-color-picker]')
			.then(color => selectedColor = color)

		cy.wait('@setColor')
		cy.waitUntil(() => validateBodyThemingCss(selectedColor, defaultBackground))
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
	})

	it('Screenshot the login page and validate login page', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(selectedColor, null))
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
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.resetUserTheming(admin)
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

		cy.get('[data-admin-theming-setting-file-remove]').click()

		cy.wait('@removeBackground')
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		// Pick one of the bright color preset
		cy.get('[data-admin-theming-setting-primary-color-picker]').click()
		cy.get('.color-picker__simple-color-circle:eq(4)').click()

		cy.wait('@setColor')
		cy.waitUntil(() => validateBodyThemingCss('#ddcb55', null))
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Change the name field', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('updateFields')

		// Name
		cy.get('[data-admin-theming-setting-field="name"] input[type="text"]')
			.scrollIntoView()
			.type('{selectall}')
			.type(name)
			.type('{enter}')
		cy.wait('@updateFields')

		// Url
		cy.get('[data-admin-theming-setting-field="url"] input[type="url"]')
			.scrollIntoView()
			.type('{selectall}')
			.type(url)
			.type('{enter}')
		cy.wait('@updateFields')

		// Slogan
		cy.get('[data-admin-theming-setting-field="slogan"] input[type="text"]')
			.scrollIntoView()
			.type('{selectall}')
			.type(slogan)
			.type('{enter}')
		cy.wait('@updateFields')
	})

	it('Ensure undo button presence', function() {
		cy.get('[data-admin-theming-setting-field="name"] .input-field__clear-button')
			.scrollIntoView().should('be.visible')
		cy.get('[data-admin-theming-setting-field="url"] .input-field__clear-button')
			.scrollIntoView().should('be.visible')
		cy.get('[data-admin-theming-setting-field="slogan"] .input-field__clear-button')
			.scrollIntoView().should('be.visible')
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Disable user background theming', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('disableUserTheming')

		cy.get('[data-admin-theming-setting-disable-user-theming]')
			.scrollIntoView().should('be.visible')
		cy.get('[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]').check({ force: true })
		cy.get('[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]').should('be.checked')

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
		cy.get('[data-user-theming-background-disabled]').scrollIntoView().should('be.visible')
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Change the primary color', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		pickRandomColor('[data-admin-theming-setting-primary-color-picker]')
			.then(color => { selectedColor = color })

		cy.wait('@setColor')
		cy.waitUntil(() => cy.window().then((win) => {
			const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary-default')
			return colord(primary).isEqual(selectedColor)
		}))
	})

	it('Change the default background', function() {
		cy.intercept('*/apps/theming/ajax/uploadImage').as('setBackground')

		cy.fixture('image.jpg', null).as('background')
		cy.get('[data-admin-theming-setting-file="background"] input[type="file"]').selectFile('@background', { force: true })

		cy.wait('@setBackground')
		cy.waitUntil(() => cy.window().then((win) => {
			const currentBackgroundDefault = getComputedStyle(win.document.body).getPropertyValue('--image-background-default')
			return currentBackgroundDefault.includes('/apps/theming/image/background?v=')
		}))
	})

	it('Login page should match admin theming settings', function() {
		cy.logout()
		cy.visit('/')

		cy.waitUntil(() => validateBodyThemingCss(selectedColor, '/apps/theming/image/background?v='))
	})

	it('Login as user', function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.get('[data-user-theming-background-default]').should('be.visible')
		cy.get('[data-user-theming-background-default]').should('have.class', 'background--active')

		cy.waitUntil(() => validateUserThemingDefaultCss(selectedColor, '/apps/theming/image/background?v='))
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
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

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
		cy.get('[data-user-theming-background-settings]').scrollIntoView().should('be.visible')
	})

	it('Default user background settings should match admin theming settings', function() {
		cy.get('[data-user-theming-background-default]').should('be.visible')
		cy.get('[data-user-theming-background-default]').should('have.class', 'background--active')

		cy.waitUntil(() => validateUserThemingDefaultCss(defaultPrimary, null))
	})
})
