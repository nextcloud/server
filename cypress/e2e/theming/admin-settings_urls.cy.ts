/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { User } from '@nextcloud/cypress'

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
