/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { randomString } from '../../support/utils/randomString.ts'
import { handlePasswordConfirmation } from '../core-utils.ts'

type RecommendedAppsMode = 'skip' | 'install-success' | 'install-failure'

/**
 * DO NOT RENAME THIS FILE to .cy.ts ⚠️
 * This is not following the pattern of the other files in this folder
 * because it is manually added to the tests by the cypress config.
 */
describe('Can install Nextcloud', { testIsolation: true, retries: 0 }, () => {
	beforeEach(() => {
		// Move the config file and data folder
		cy.runCommand('rm /var/www/html/config/config.php', { failOnNonZeroExit: false })
		cy.runCommand('rm /var/www/html/data/owncloud.db', { failOnNonZeroExit: false })
	})

	it('Sqlite', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminlogin"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminpass"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="directory"]').should('have.value', '/var/www/html/data')

		// Select the SQLite database
		cy.get('[data-cy-setup-form-field="dbtype-sqlite"] input').check({ force: true })

		sharedSetup()
	})

	it('Sqlite - Install recommended apps (success)', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="dbtype-sqlite"] input').check({ force: true })

		sharedSetup('install-success')
	})

	it('Sqlite - Install recommended apps (failure)', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="dbtype-sqlite"] input').check({ force: true })

		sharedSetup('install-failure')
	})

	it('MySQL', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminlogin"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminpass"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="directory"]').should('have.value', '/var/www/html/data')

		// Select the SQLite database
		cy.get('[data-cy-setup-form-field="dbtype-mysql"] input').check({ force: true })

		// Fill in the DB form
		cy.get('[data-cy-setup-form-field="dbuser"]').type('{selectAll}oc_autotest')
		cy.get('[data-cy-setup-form-field="dbpass"]').type('{selectAll}nextcloud')
		cy.get('[data-cy-setup-form-field="dbname"]').type('{selectAll}oc_autotest')
		cy.get('[data-cy-setup-form-field="dbhost"]').type('{selectAll}mysql:3306')

		sharedSetup()
	})

	it('MariaDB', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminlogin"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminpass"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="directory"]').should('have.value', '/var/www/html/data')

		// Select the SQLite database
		cy.get('[data-cy-setup-form-field="dbtype-mysql"] input').check({ force: true })

		// Fill in the DB form
		cy.get('[data-cy-setup-form-field="dbuser"]').type('{selectAll}oc_autotest')
		cy.get('[data-cy-setup-form-field="dbpass"]').type('{selectAll}nextcloud')
		cy.get('[data-cy-setup-form-field="dbname"]').type('{selectAll}oc_autotest')
		cy.get('[data-cy-setup-form-field="dbhost"]').type('{selectAll}mariadb:3306')

		sharedSetup()
	})

	it('PostgreSQL', () => {
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminlogin"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminpass"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="directory"]').should('have.value', '/var/www/html/data')

		// Select the SQLite database
		cy.get('[data-cy-setup-form-field="dbtype-pgsql"] input').check({ force: true })

		// Fill in the DB form
		cy.get('[data-cy-setup-form-field="dbuser"]').type('{selectAll}root')
		cy.get('[data-cy-setup-form-field="dbpass"]').type('{selectAll}rootpassword')
		cy.get('[data-cy-setup-form-field="dbname"]').type('{selectAll}nextcloud')
		cy.get('[data-cy-setup-form-field="dbhost"]').type('{selectAll}postgres:5432')

		sharedSetup()
	})

	it('Oracle', () => {
		Cypress.config('pageLoadTimeout', 200000)
		cy.runCommand('cp /var/www/html/tests/databases-all-config.php /var/www/html/config/config.php')
		cy.visit('/')
		cy.get('[data-cy-setup-form]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminlogin"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="adminpass"]').should('be.visible')
		cy.get('[data-cy-setup-form-field="directory"]').should('have.value', '/var/www/html/data')

		// Select the SQLite database
		cy.get('[data-cy-setup-form-field="dbtype-oci"] input').check({ force: true })

		// Fill in the DB form
		cy.get('[data-cy-setup-form-field="dbuser"]').type('{selectAll}system')
		cy.get('[data-cy-setup-form-field="dbpass"]').type('{selectAll}oracle')
		cy.get('[data-cy-setup-form-field="dbname"]').type('{selectAll}FREE')
		cy.get('[data-cy-setup-form-field="dbhost"]').type('{selectAll}oracle:1521')

		sharedSetup()
	})
})

/**
 * Shared admin setup function for the Nextcloud setup
 *
 * @param mode How to handle the recommended apps screen at the end of the
 *             install assistant: skip it, exercise the install button with a
 *             stubbed success response, or stub a failure response.
 */
function sharedSetup(mode: RecommendedAppsMode = 'skip') {
	const randAdmin = 'admin-' + randomString(10)

	// mock appstore
	cy.intercept('**/settings/apps/list', { fixture: 'appstore/apps.json' })

	// Fill in the form
	cy.get('[data-cy-setup-form-field="adminlogin"]').type(randAdmin)
	cy.get('[data-cy-setup-form-field="adminpass"]').type(randAdmin)

	// Nothing more to do on sqlite, let's continue
	cy.get('[data-cy-setup-form-submit]').click()

	// Wait for the setup to finish
	cy.location('pathname', { timeout: 10000 })
		.should('include', '/core/apps/recommended')

	// See the apps setup
	cy.get('[data-cy-setup-recommended-apps]')
		.should('be.visible')
		.within(() => {
			cy.findByRole('heading', { name: 'Recommended apps' })
				.should('be.visible')
			cy.findByRole('button', { name: 'Skip' })
				.should('be.visible')
			cy.findByRole('button', { name: 'Install recommended apps' })
				.should('be.visible')
		})

	if (mode === 'skip') {
		// Skip the setup apps
		cy.get('[data-cy-setup-recommended-apps-skip]').click()

		// Go to files
		cy.visit('/apps/files/')
		cy.get('[data-cy-files-content]').should('be.visible')
		return
	}

	// Stub the bulk enable endpoint so we exercise the frontend flow without
	// hitting the real app store.
	cy.intercept('POST', '**/settings/apps/enable', mode === 'install-success'
		? { statusCode: 200, body: { data: { update_required: false } } }
		: { statusCode: 500, body: { data: { message: 'Forced failure' } } }).as('enableApps')

	cy.get('[data-cy-setup-recommended-apps-install]').click()

	// The strict password-confirmation dialog must appear and must result in a
	// Basic auth header on the enable request.
	cy.findByRole('dialog', { name: 'Authentication required' })
		.should('be.visible')
	handlePasswordConfirmation(randAdmin)
	cy.wait('@enableApps')
		.its('request.headers.authorization')
		.should('match', /^Basic /)

	if (mode === 'install-success') {
		// Frontend redirects via window.location to the default page.
		cy.location('pathname', { timeout: 10000 })
			.should('not.include', '/core/apps/recommended')
	} else {
		// Stay on the recommended-apps page and surface the per-app error state.
		cy.location('pathname').should('include', '/core/apps/recommended')
		cy.get('[data-cy-setup-recommended-apps]')
			.should('contain.text', 'App download or installation failed')
	}
}
