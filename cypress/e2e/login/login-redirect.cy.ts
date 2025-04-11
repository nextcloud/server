/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Test that when a session expires / the user logged out in another tab,
 * the user gets redirected to the login on the next request.
 */
describe('Logout redirect ', { testIsolation: true }, () => {

	let user

	before(() => {
		cy.createRandomUser()
			.then(($user) => {
				user = $user
			})
	})

	it('Redirects to login if session timed out', () => {
		// Login and see settings
		cy.login(user)
		cy.visit('/settings/user#profile')
		cy.findByRole('checkbox', { name: /Enable profile/i })
			.should('exist')

		// clear session
		cy.clearAllCookies()

		// trigger an request
		cy.findByRole('checkbox', { name: /Enable profile/i })
			.click({ force: true })

		// See that we are redirected
		cy.url()
			.should('match', /\/login/i)
			.and('include', `?redirect_url=${encodeURIComponent('/index.php/settings/user#profile')}`)

		cy.get('form[name="login"]').should('be.visible')
	})

	it('Redirect from login works', () => {
		cy.logout()
		// visit the login
		cy.visit(`/login?redirect_url=${encodeURIComponent('/index.php/settings/user#profile')}`)

		// see login
		cy.get('form[name="login"]').should('be.visible')
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(user.userId)
			cy.get('input[name="password"]').type(user.password)
			cy.contains('button[data-login-form-submit]', 'Log in').click()
		})

		// see that we are correctly redirected
		cy.url().should('include', '/index.php/settings/user#profile')
		cy.findByRole('checkbox', { name: /Enable profile/i })
			.should('exist')
	})

})
