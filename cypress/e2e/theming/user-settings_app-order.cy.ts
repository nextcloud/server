/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { NavigationHeader } from '../../pages/NavigationHeader.ts'
import { SettingsAppOrderList } from '../../pages/SettingsAppOrderList.ts'
import { installTestApp, uninstallTestApp } from '../../support/commonUtils.ts'

before(() => uninstallTestApp())

describe('User theming set app order', () => {
	const navigationHeader = new NavigationHeader()
	const appOrderList = new SettingsAppOrderList()
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
		visitAppOrderSettings()
	})

	it('See that the dashboard app is the first one', () => {
		const appOrder = ['Dashboard', 'Files']
		appOrderList.assertAppOrder(appOrder)

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]!))
	})

	it('Change the app order', () => {
		appOrderList.interceptAppOrder()
		appOrderList.getAppOrderList()
			.scrollIntoView()
		appOrderList.getUpButtonForApp('Files')
			.should('be.visible')
			.click()
		appOrderList.waitForAppOrderUpdate()

		appOrderList.assertAppOrder(['Files', 'Dashboard'])
	})

	it('See the app menu order is changed', () => {
		cy.reload()
		const appOrder = ['Files', 'Dashboard']
		appOrderList.getAppOrderList()
			.scrollIntoView()
		appOrderList.assertAppOrder(appOrder)

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]!))
	})
})

describe('User theming set app order with default app', () => {
	const appOrderList = new SettingsAppOrderList()
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
		visitAppOrderSettings()

		const appOrder = ['Files', 'Dashboard', 'Test App 2', 'Test App']
		appOrderList.getAppOrderList()
			.scrollIntoView()
		appOrderList.assertAppOrder(appOrder)
	})

	it('Can not change the default app', () => {
		appOrderList.getUpButtonForApp('Files').should('not.exist')
		appOrderList.getDownButtonForApp('Files').should('not.exist')
		appOrderList.getUpButtonForApp('Dashboard').should('not.exist')
		// but can move down
		appOrderList.getDownButtonForApp('Dashboard').should('be.visible')
	})

	it('Can see the correct buttons for other apps', () => {
		appOrderList.getUpButtonForApp('Test App 2').should('be.visible')
		appOrderList.getDownButtonForApp('Test App 2').should('be.visible')
		appOrderList.getUpButtonForApp('Test App').should('be.visible')
		appOrderList.getDownButtonForApp('Test App').should('not.exist')
	})

	it('Change the order of the other apps', () => {
		appOrderList.interceptAppOrder()
		appOrderList.getUpButtonForApp('Test App').click()
		appOrderList.waitForAppOrderUpdate()
		appOrderList.getUpButtonForApp('Test App').click()
		appOrderList.waitForAppOrderUpdate()

		// Can't get up anymore, files is enforced as default app
		appOrderList.getUpButtonForApp('Test App').should('not.exist')

		// Check the app order settings UI
		appOrderList.assertAppOrder(['Files', 'Test App', 'Dashboard', 'Test App 2'])
	})

	it('See the app menu order is changed', () => {
		cy.reload()

		const appOrder = ['Files', 'Test App', 'Dashboard', 'Test App 2']
		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]!))
	})
})

describe('User theming app order list accessibility', () => {
	const appOrderList = new SettingsAppOrderList()
	let user: User

	before(() => {
		cy.resetAdminTheming()
		installTestApp()
		// Create random user for this test
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.login($user)
		})
	})

	after(() => {
		uninstallTestApp()
		cy.deleteUser(user)
	})

	it('click the first button', () => {
		visitAppOrderSettings()
		appOrderList.interceptAppOrder()
		appOrderList.getDownButtonForApp('Dashboard')
			.should('be.visible')
			.scrollIntoView()
		appOrderList.getDownButtonForApp('Dashboard')
			.focus()
		appOrderList.getDownButtonForApp('Dashboard')
			.click()
		appOrderList.waitForAppOrderUpdate()
	})

	it('see the same app kept the focus', () => {
		appOrderList.getDownButtonForApp('Dashboard').should('have.focus')
	})

	it('click the last button', () => {
		appOrderList.interceptAppOrder()
		appOrderList.getUpButtonForApp('Dashboard')
			.should('be.visible')
			.focus()
		appOrderList.getUpButtonForApp('Dashboard').click()
		appOrderList.waitForAppOrderUpdate()
	})

	it('see the same app kept the focus', () => {
		appOrderList.getUpButtonForApp('Dashboard').should('not.exist')
		appOrderList.getDownButtonForApp('Dashboard').should('have.focus')
	})
})

describe('User theming reset app order', () => {
	const appOrderList = new SettingsAppOrderList()
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

	it('See that the dashboard app is the first one', () => {
		visitAppOrderSettings()

		const appOrder = ['Dashboard', 'Files']
		appOrderList.assertAppOrder(appOrder)

		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]!))
	})

	it('See the reset button is disabled', () => {
		appOrderList.getResetButton()
			.scrollIntoView()
		appOrderList.getResetButton()
			.should('be.disabled')
	})

	it('Change the app order', () => {
		appOrderList.interceptAppOrder()
		appOrderList.getUpButtonForApp('Files')
			.should('be.visible')
			.click()
		appOrderList.waitForAppOrderUpdate()

		appOrderList.assertAppOrder(['Files', 'Dashboard'])
	})

	it('See the reset button is no longer disabled', () => {
		appOrderList.getResetButton()
			.scrollIntoView()
		appOrderList.getResetButton()
			.should('be.visible')
			.and('be.enabled')
	})

	it('Reset the app order', () => {
		cy.intercept('GET', '/ocs/v2.php/core/navigation/apps').as('loadApps')
		appOrderList.interceptAppOrder()
		appOrderList.getResetButton().click({ force: true })

		cy.wait('@updateAppOrder')
			.its('request.body')
			.should('have.property', 'configValue', '[]')
		cy.wait('@loadApps')
	})

	it('See the app order is restored', () => {
		const appOrder = ['Dashboard', 'Files']
		appOrderList.assertAppOrder(appOrder)
		// Check the top app menu order
		navigationHeader.getNavigationEntries()
			.each((entry, index) => expect(entry).contain.text(appOrder[index]!))
	})

	it('See the reset button is disabled again', () => {
		appOrderList.getResetButton()
			.should('be.disabled')
	})
})

function visitAppOrderSettings() {
	cy.visit('/settings/user/theming')
	cy.findByRole('heading', { name: /Navigation bar settings/ })
		.should('exist')
		.scrollIntoView()
}
