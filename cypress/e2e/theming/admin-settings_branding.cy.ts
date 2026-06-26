/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'

const admin = new User('admin', 'admin')

describe('Admin theming: Setting custom project URLs', function() {
	this.beforeEach(() => {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
		cy.visit('/settings/admin/theming')
		cy.intercept('POST', '**/apps/theming/ajax/updateStylesheet').as('updateTheming')
	})

	it('Setting the web link', () => {
		cy.findByRole('textbox', { name: /web link/i })
			.and('have.attr', 'type', 'url')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.should('be.visible')
			.type('{selectAll}http://example.com/path?query#fragment{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', 'Nextcloud')
			.should('be.visible')
			.and('have.attr', 'href', 'http://example.com/path?query#fragment')
	})

	it('Setting the legal notice link', () => {
		cy.findByRole('textbox', { name: /legal notice link/i })
			.should('exist')
			.and('have.attr', 'type', 'url')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.type('http://example.com/path?query#fragment{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', /legal notice/i)
			.should('be.visible')
			.and('have.attr', 'href', 'http://example.com/path?query#fragment')
	})

	it('Setting the privacy policy link', () => {
		cy.findByRole('textbox', { name: /privacy policy link/i })
			.should('exist')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.should('have.attr', 'type', 'url')
			.type('http://privacy.local/path?query#fragment{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', /privacy policy/i)
			.should('be.visible')
			.and('have.attr', 'href', 'http://privacy.local/path?query#fragment')
	})
})

describe('Admin theming: Web link corner cases', function() {
	this.beforeEach(() => {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
		cy.visit('/settings/admin/theming')
		cy.intercept('POST', '**/apps/theming/ajax/updateStylesheet').as('updateTheming')
	})

	it('Already URL encoded', () => {
		cy.findByRole('textbox', { name: /web link/i })
			.and('have.attr', 'type', 'url')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.should('be.visible')
			.type('{selectAll}http://example.com/%22path%20with%20space%22{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', 'Nextcloud')
			.should('be.visible')
			.and('have.attr', 'href', 'http://example.com/%22path%20with%20space%22')
	})

	it('URL with double quotes', () => {
		cy.findByRole('textbox', { name: /web link/i })
			.and('have.attr', 'type', 'url')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.should('be.visible')
			.type('{selectAll}http://example.com/"path"{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', 'Nextcloud')
			.should('be.visible')
			.and('have.attr', 'href', 'http://example.com/%22path%22')
	})

	it('URL with double quotes and already encoded', () => {
		cy.findByRole('textbox', { name: /web link/i })
			.and('have.attr', 'type', 'url')
			.as('input')
			.scrollIntoView()
		cy.get('@input')
			.should('be.visible')
			.type('{selectAll}http://example.com/"the%20path"{enter}')

		cy.wait('@updateTheming')

		cy.logout()

		cy.visit('/')
		cy.contains('a', 'Nextcloud')
			.should('be.visible')
			.and('have.attr', 'href', 'http://example.com/%22the%20path%22')
	})
})

describe('Admin theming: Change the login fields then reset them', function() {
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
		cy.findByRole('heading', { name: /^Theming/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Change the name field', function() {
		cy.intercept('*/apps/theming/ajax/updateStylesheet').as('updateFields')

		// Name
		cy.findByRole('textbox', { name: 'Name' })
			.should('be.visible')
			.type(`{selectall}${name}{enter}`)
		cy.wait('@updateFields')

		// Url
		cy.findByRole('textbox', { name: 'Web link' })
			.should('be.visible')
			.type(`{selectall}${url}{enter}`)
		cy.wait('@updateFields')

		// Slogan
		cy.findByRole('textbox', { name: 'Slogan' })
			.should('be.visible')
			.type(`{selectall}${slogan}{enter}`)
		cy.wait('@updateFields')
	})

	it('Ensure undo button presence', function() {
		cy.findAllByRole('button', { name: /undo changes/i })
			.should('have.length', 3)
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
		cy.login(admin)
		cy.visit('/settings/admin/theming')
		cy.findAllByRole('button', { name: /undo changes/i })
			.each((button) => {
				cy.intercept('*/apps/theming/ajax/undoChanges').as('undoField')
				cy.wrap(button).click()
				cy.wait('@undoField')
			})
		cy.logout()
	})

	it('Validate login screen changes again', function() {
		cy.visit('/')

		cy.get('[data-login-form-headline]').should('not.contain.text', name)
		cy.get('footer p a').should('not.have.text', name)
		cy.get('footer p a').should('not.have.attr', 'href', url)
		cy.get('footer p').should('not.contain.text', `– ${slogan}`)
	})
})
