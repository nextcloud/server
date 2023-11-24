/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import { installTestApp, uninstallTestApp } from '../../support/commonUtils'

const admin = new User('admin', 'admin')

describe('Admin theming set default apps', () => {
	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the current default app is the dashboard', () => {
		cy.visit('/')
		cy.url().should('match', /apps\/dashboard/)
		cy.get('#nextcloud').click()
		cy.url().should('match', /apps\/dashboard/)
	})

	it('See the default app settings', () => {
		cy.visit('/settings/admin/theming')

		cy.get('.settings-section').contains('Navigation bar settings').should('exist')
		cy.get('[data-cy-switch-default-app]').should('exist')
		cy.get('[data-cy-switch-default-app]').scrollIntoView()
	})

	it('Toggle the "use custom default app" switch', () => {
		cy.get('[data-cy-switch-default-app] input').should('not.be.checked')
		cy.get('[data-cy-switch-default-app] label').click()
		cy.get('[data-cy-switch-default-app] input').should('be.checked')
	})

	it('See the default app order selector', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
		})
	})

	it('Change the default app', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"]').scrollIntoView()

		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')

	})

	it('See the default app is changed', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
		})

		cy.get('#nextcloud').click()
		cy.url().should('match', /apps\/files/)
	})

	it('Toggle the "use custom default app" switch back to reset the default apps', () => {
		cy.visit('/settings/admin/theming')
		cy.get('[data-cy-switch-default-app]').scrollIntoView()

		cy.get('[data-cy-switch-default-app] input').should('be.checked')
		cy.get('[data-cy-switch-default-app] label').click()
		cy.get('[data-cy-switch-default-app] input').should('be.not.checked')
	})

	it('See the default app is changed back to default', () => {
		cy.reload()
		cy.get('#nextcloud').click()
		cy.url().should('match', /apps\/dashboard/)
	})
})

describe('User theming set app order', () => {
	let user: User

	before(() => {
		cy.resetAdminTheming()
		// Create random user for this test
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.login($user)
		})
	})

	after(() => cy.deleteUser(user))

	it('See the app order settings', () => {
		cy.visit('/settings/user/theming')

		cy.get('.settings-section').contains('Navigation bar settings').should('exist')
		cy.get('[data-cy-app-order]').scrollIntoView()
	})

	it('See that the dashboard app is the first one', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
		})

		cy.get('.app-menu-main .app-menu-entry').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-app-id', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-app-id', 'files')
		})
	})

	it('Change the app order', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')

		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
		})
	})

	it('See the app menu order is changed', () => {
		cy.reload()
		cy.get('.app-menu-main .app-menu-entry').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-app-id', 'files')
			else cy.wrap($el).should('have.attr', 'data-app-id', 'dashboard')
		})
	})
})

describe('User theming set app order with default app', () => {
	let user: User

	before(() => {
		cy.resetAdminTheming()
		// install a third app
		installTestApp()
		// set files as default app
		cy.runOccCommand('config:system:set --value "files" defaultapp')

		// Create random user for this test
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.login($user)
		})
	})

	after(() => {
		cy.deleteUser(user)
		uninstallTestApp()
	})

	it('See files is the default app', () => {
		cy.visit('/')
		cy.url().should('match', /apps\/files/)

		cy.get('.app-menu-main .app-menu-entry').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-app-id', 'files')
		})
	})

	it('See the app order settings: files is the first one', () => {
		cy.visit('/settings/user/theming')
		cy.get('[data-cy-app-order]').scrollIntoView()
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').should('have.length', 4).each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
			else if (idx === 1) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
		})
	})

	it('Can not change the default app', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="down"]').should('not.be.visible')

		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('not.be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('be.visible')

		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="down"]').should('not.be.visible')
	})

	it('Change the order of the other apps', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').should('not.be.visible')

		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
			else if (idx === 1) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'testapp')
			else if (idx === 2) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
		})
	})

	it('See the app menu order is changed', () => {
		cy.reload()
		cy.get('.app-menu-main .app-menu-entry').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-app-id', 'files')
			else if (idx === 1) cy.wrap($el).should('have.attr', 'data-app-id', 'testapp')
			else if (idx === 2) cy.wrap($el).should('have.attr', 'data-app-id', 'dashboard')
		})
	})
})

describe('User theming app order list accessibility', () => {
	let user: User

	before(() => {
		cy.resetAdminTheming()
		// Create random user for this test
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.login($user)
		})
	})

	after(() => {
		cy.deleteUser(user)
	})

	it('See the app order settings', () => {
		cy.visit('/settings/user/theming')
		cy.get('[data-cy-app-order]').scrollIntoView()
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').should('have.length', 2)
	})

	it('click the first button', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('be.visible').focus()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').click()
	})

	it('see the same app kept the focus', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('have.focus')
	})

	it('click the last button', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('be.visible').focus()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').click()
	})

	it('see the same app kept the focus', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('have.focus')
	})
})

describe('User theming reset app order', () => {
	let user: User

	before(() => {
		cy.resetAdminTheming()
		// Create random user for this test
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.login($user)
		})
	})

	after(() => cy.deleteUser(user))

	it('See the app order settings', () => {
		cy.visit('/settings/user/theming')

		cy.get('.settings-section').contains('Navigation bar settings').should('exist')
		cy.get('[data-cy-app-order]').scrollIntoView()
	})

	it('See that the dashboard app is the first one', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
		})

		cy.get('.app-menu-main .app-menu-entry').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-app-id', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-app-id', 'files')
		})
	})

	it('See the reset button is disabled', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').scrollIntoView()
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('have.attr', 'disabled')
	})

	it('Change the app order', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')

		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
		})
	})

	it('See the reset button is no longer disabled', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').scrollIntoView()
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('not.have.attr', 'disabled')
	})

	it('Reset the app order', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').click({ force: true })
	})

	it('See the app order is restored', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').each(($el, idx) => {
			if (idx === 0) cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'dashboard')
			else cy.wrap($el).should('have.attr', 'data-cy-app-order-element', 'files')
		})
	})

	it('See the reset button is disabled again', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('have.attr', 'disabled')
	})
})
