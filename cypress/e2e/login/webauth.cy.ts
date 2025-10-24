/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

interface IChromeVirtualAuthenticator {
	authenticatorId: string
}

/**
 * Create a virtual authenticator using chrome debug protocol
 */
async function createAuthenticator(): Promise<IChromeVirtualAuthenticator> {
	await Cypress.automation('remote:debugger:protocol', {
		command: 'WebAuthn.enable',
	})
	const authenticator = await Cypress.automation('remote:debugger:protocol', {
		command: 'WebAuthn.addVirtualAuthenticator',
		params: {
			options: {
				protocol: 'ctap2',
				ctap2Version: 'ctap2_1',
				hasUserVerification: true,
				transport: 'usb',
				automaticPresenceSimulation: true,
				isUserVerified: true,
			},
		},
	})
	return authenticator
}

/**
 * Delete a virtual authenticator using chrome devbug protocol
 *
 * @param authenticator the authenticator object
 */
async function deleteAuthenticator(authenticator: IChromeVirtualAuthenticator) {
	await Cypress.automation('remote:debugger:protocol', {
		command: 'WebAuthn.removeVirtualAuthenticator',
		params: {
			...authenticator,
		},
	})
}

describe('Login using WebAuthn', () => {
	let authenticator: IChromeVirtualAuthenticator
	let user: User

	afterEach(() => {
		cy.deleteUser(user)
			.then(() => deleteAuthenticator(authenticator))
	})

	beforeEach(() => {
		cy.createRandomUser()
			.then(($user) => {
				user = $user
				cy.login(user)
			})
			.then(() => createAuthenticator())
			.then(($authenticator) => {
				authenticator = $authenticator
				cy.log('Created virtual authenticator')
			})
	})

	it('add and delete WebAuthn', () => {
		cy.intercept('**/settings/api/personal/webauthn/registration').as('webauthn')
		cy.visit('/settings/user/security')

		cy.contains('[role="note"]', /No devices configured/i).should('be.visible')

		cy.findByRole('button', { name: /Add WebAuthn device/i })
			.should('be.visible')
			.click()

		cy.wait('@webauthn')

		cy.findByRole('textbox', { name: /Device name/i })
			.should('be.visible')
			.type('test device{enter}')

		cy.wait('@webauthn')

		cy.contains('[role="note"]', /No devices configured/i).should('not.exist')

		cy.findByRole('list', { name: /following devices are configured for your account/i })
			.should('be.visible')
			.contains('li', 'test device')
			.should('be.visible')
			.findByRole('button', { name: /Actions/i })
			.click()

		cy.findByRole('menuitem', { name: /Delete/i })
			.should('be.visible')
			.click()

		cy.contains('[role="note"]', /No devices configured/i).should('be.visible')
		cy.findByRole('list', { name: /following devices are configured for your account/i })
			.should('not.exist')

		cy.reload()
		cy.contains('[role="note"]', /No devices configured/i).should('be.visible')
	})

	it('add WebAuthn and login', () => {
		cy.intercept('GET', '**/settings/api/personal/webauthn/registration').as('webauthnSetupInit')
		cy.intercept('POST', '**/settings/api/personal/webauthn/registration').as('webauthnSetupDone')
		cy.intercept('POST', '**/login/webauthn/start').as('webauthnLogin')

		cy.visit('/settings/user/security')

		cy.findByRole('button', { name: /Add WebAuthn device/i })
			.should('be.visible')
			.click()
		cy.wait('@webauthnSetupInit')

		cy.findByRole('textbox', { name: /Device name/i })
			.should('be.visible')
			.type('test device{enter}')
		cy.wait('@webauthnSetupDone')

		cy.findByRole('list', { name: /following devices are configured for your account/i })
			.should('be.visible')
			.findByText('test device')
			.should('be.visible')

		cy.logout()
		cy.visit('/login')

		cy.findByRole('button', { name: /Log in with a device/i })
			.should('be.visible')
			.click()

		cy.findByRole('form', { name: /Log in with a device/i })
			.should('be.visible')
			.findByRole('textbox', { name: /Login or email/i })
			.should('be.visible')
			.type(`{selectAll}${user.userId}`)

		cy.findByRole('button', { name: /Log in/i })
			.click()
		cy.wait('@webauthnLogin')

		// Then I see that the current page is the Files app
		cy.url().should('match', /apps\/dashboard(\/|$)/)
	})
})
