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

const admin = new User('admin', 'admin')

const defaultPrimary = '#0082c9'
const defaultBackground = 'kamil-porembinski-clouds.jpg'

describe('Admin theming settings', function() {
	before(function() {
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('See the default settings', function() {
		cy.get('[data-admin-theming-setting-primary-color-picker]').should('contain.text', defaultPrimary)
		cy.get('[data-admin-theming-setting-primary-color-reset]').should('not.exist')
		cy.get('[data-admin-theming-setting-background-reset]').should('not.exist')
		cy.get('[data-admin-theming-setting-background-remove]').should('be.visible')
	})
})

describe('Change the primary colour', function() {
	before(function() {
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Change the primary colour', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('setColor')

		cy.get('[data-admin-theming-setting-primary-color-picker]').click()
		cy.get('.color-picker__simple-color-circle:eq(3)').click()

		cy.wait('@setColor')
		cy.waitUntil(() => cy.window().then((win) => {
			const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary-default')
			return primary !== defaultPrimary
		}))
	})

	it('Screenshot the login page', function() {
		cy.logout()
		cy.visit('/')
		cy.screenshot()
	})

	it('Login again and go to the admin theming section', function() {
		cy.login(admin)
		cy.visit('/settings/admin/theming')
	})

	it('Reset the primary colour', function() {
		cy.intercept('*/apps/theming/ajax/undoChanges').as('undoChanges')

		cy.get('[data-admin-theming-setting-primary-color-reset]').click()

		cy.wait('@undoChanges')
		cy.waitUntil(() => cy.window().then((win) => {
			const primary = getComputedStyle(win.document.body).getPropertyValue('--color-primary-default')
			return primary === defaultPrimary
		}))
	})

	it('Screenshot the login page', function() {
		cy.logout()
		cy.visit('/')
		cy.screenshot()
	})
})

describe('Remove the default background', function() {
	before(function() {
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Remove the default background', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('removeBackground')

		cy.get('[data-admin-theming-setting-background-remove]').click()

		cy.wait('@removeBackground')
		cy.waitUntil(() => cy.window().then((win) => {
			const backgroundDefault = getComputedStyle(win.document.body).getPropertyValue('--image-background-default')
			const backgroundPlain = getComputedStyle(win.document.body).getPropertyValue('--image-background-plain')
			return !backgroundDefault.includes(defaultBackground)
				&& backgroundPlain !== ''
		}))
	})

	it('Screenshot the login page', function() {
		cy.logout()
		cy.visit('/')
		cy.screenshot()
	})

	it('Login again and go to the admin theming section', function() {
		cy.login(admin)
		cy.visit('/settings/admin/theming')
	})

	it('Restore the default background', function() {
		cy.intercept('*/apps/theming/ajax/undoChanges').as('undoChanges')

		cy.get('[data-admin-theming-setting-background-reset]').click()

		cy.wait('@undoChanges')
		cy.waitUntil(() => cy.window().then((win) => {
			const backgroundDefault = getComputedStyle(win.document.body).getPropertyValue('--image-background-default')
			const backgroundPlain = getComputedStyle(win.document.body).getPropertyValue('--image-background-plain')
			return backgroundDefault.includes(defaultBackground)
				&& backgroundPlain === ''
		}))
	})

	it('Screenshot the login page', function() {
		cy.logout()
		cy.visit('/')
		cy.screenshot()
	})
})

describe('Change the login fields', function() {
	const name = 'ABCdef123'
	const url = 'https://example.com'
	const slogan = 'Testing is fun'

	before(function() {
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

	it('Check login screen changes', function() {
		cy.logout()
		cy.visit('/')

		cy.get('[data-login-form-headline]').should('contain.text', name)
		cy.get('footer p a').should('have.text', name)
		cy.get('footer p a').should('have.attr', 'href', url)
		cy.get('footer p').should('contain.text', `– ${slogan}`)
	})

	it('Login again and go to the admin theming section', function() {
		cy.login(admin)
		cy.visit('/settings/admin/theming')
	})

	it('Undo changes', function() {
		cy.intercept('*/apps/theming/ajax/undoChanges').as('undoChanges')

		cy.get('[data-admin-theming-setting-field="name"] .input-field__clear-button')
			.scrollIntoView().click()
		cy.wait('@undoChanges')

		cy.get('[data-admin-theming-setting-field="url"] .input-field__clear-button')
			.scrollIntoView().click()
		cy.wait('@undoChanges')

		cy.get('[data-admin-theming-setting-field="slogan"] .input-field__clear-button')
			.scrollIntoView().click()
		cy.wait('@undoChanges')
	})

	it('Check login screen changes', function() {
		cy.logout()
		cy.visit('/')

		cy.get('[data-login-form-headline]').should('not.contain.text', name)
		cy.get('footer p a').should('not.have.text', name)
		cy.get('footer p a').should('not.have.attr', 'href', url)
		cy.get('footer p').should('not.contain.text', `– ${slogan}`)
	})
})

describe('Disable user theming', function() {
	before(function() {
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Disable user theming', function() {
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

	it('See the user disabled background settings', function() {
		cy.visit('/settings/user/theming')
		cy.get('[data-user-theming-background-disabled]').scrollIntoView().should('be.visible')
	})

	it('Login back as admin', function() {
		cy.logout()
		cy.login(admin)
	})

	it('See the admin theming section', function() {
		cy.visit('/settings/admin/theming')
		cy.get('[data-admin-theming-settings]').scrollIntoView().should('be.visible')
	})

	it('Enable back user theming', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('enableUserTheming')

		cy.get('[data-admin-theming-setting-disable-user-theming]')
			.scrollIntoView().should('be.visible')
		cy.get('[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]').uncheck({ force: true })
		cy.get('[data-admin-theming-setting-disable-user-theming] input[type="checkbox"]').should('not.be.checked')

		cy.wait('@enableUserTheming')
	})
})
