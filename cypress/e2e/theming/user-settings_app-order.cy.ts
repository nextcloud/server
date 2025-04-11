/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { installTestApp, uninstallTestApp } from '../../support/commonUtils'
import { NavigationHeader } from '../../pages/NavigationHeader'

/**
 * Intercept setting the app order as `updateAppOrder`
 */
function interceptAppOrder() {
	cy.intercept('POST', '/ocs/v2.php/apps/provisioning_api/api/v1/config/users/core/apporder').as('updateAppOrder')
}

before(() => uninstallTestApp())

describe('User theming set app order', () => {
	const navigationHeader = new NavigationHeader()
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
		const appOrder = ['Dashboard', 'Files']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]))
	})

	it('Change the app order', () => {
		interceptAppOrder()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')
		cy.wait('@updateAppOrder')

		const appOrder = ['Files', 'Dashboard']
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))
	})

	it('See the app menu order is changed', () => {
		cy.reload()
		const appOrder = ['Files', 'Dashboard']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]))
	})
})

describe('User theming set app order with default app', () => {
	const navigationHeader = new NavigationHeader()
	let user: User

	before(() => {
		cy.resetAdminTheming()
		// install a third app
		installTestApp()
		// set files as default app
		cy.runOccCommand('config:system:set --value \'files\' defaultapp')

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
		// Check the redirect to the default app works
		cy.request({ url: '/', followRedirect: false }).then((response) => {
			expect(response.status).to.eq(302)
			expect(response).to.have.property('headers')
			expect(response.headers.location).to.contain('/apps/files')
		})
	})

	it('See the app order settings: files is the first one', () => {
		cy.visit('/settings/user/theming')
		cy.get('[data-cy-app-order]').scrollIntoView()

		const appOrder = ['Files', 'Dashboard', 'Test App 2', 'Test App']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))
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
		interceptAppOrder()

		// Move the testapp up twice, it should be the first one after files
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').click()
		cy.wait('@updateAppOrder')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').click()
		cy.wait('@updateAppOrder')

		// Can't get up anymore, files is enforced as default app
		cy.get('[data-cy-app-order] [data-cy-app-order-element="testapp"] [data-cy-app-order-button="up"]').should('not.be.visible')

		// Check the final list order
		const appOrder = ['Files', 'Test App', 'Dashboard', 'Test App 2']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))
	})

	it('See the app menu order is changed', () => {
		cy.reload()

		const appOrder = ['Files', 'Test App', 'Dashboard', 'Test App 2']
		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]))
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
		interceptAppOrder()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('be.visible').focus()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').click()
		cy.wait('@updateAppOrder')
	})

	it('see the same app kept the focus', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('have.focus')
	})

	it('click the last button', () => {
		interceptAppOrder()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('be.visible').focus()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').click()
		cy.wait('@updateAppOrder')
	})

	it('see the same app kept the focus', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="down"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="up"]').should('not.have.focus')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="dashboard"] [data-cy-app-order-button="down"]').should('have.focus')
	})
})

describe('User theming reset app order', () => {
	const navigationHeader = new NavigationHeader()
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
		const appOrder = ['Dashboard', 'Files']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]))
	})

	it('See the reset button is disabled', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').scrollIntoView()
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('have.attr', 'disabled')
	})

	it('Change the app order', () => {
		interceptAppOrder()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')
		cy.wait('@updateAppOrder')

		// Check the app order settings UI
		const appOrder = ['Files', 'Dashboard']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))
	})

	it('See the reset button is no longer disabled', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').scrollIntoView()
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('not.have.attr', 'disabled')
	})

	it('Reset the app order', () => {
		cy.intercept('GET', '/ocs/v2.php/core/navigation/apps').as('loadApps')
		interceptAppOrder()
		cy.get('[data-test-id="btn-apporder-reset"]').click({ force: true })

		cy.wait('@updateAppOrder')
			.its('request.body')
			.should('have.property', 'configValue', '[]')
		cy.wait('@loadApps')
	})

	it('See the app order is restored', () => {
		const appOrder = ['Dashboard', 'Files']
		// Check the app order settings UI
		cy.get('[data-cy-app-order] [data-cy-app-order-element]')
			.each((element, index) => expect(element).to.contain.text(appOrder[index]))

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]))
	})

	it('See the reset button is disabled again', () => {
		cy.get('[data-test-id="btn-apporder-reset"]').should('be.visible').and('have.attr', 'disabled')
	})
})
