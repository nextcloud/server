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

	// Mock the app store listing. The recommended-apps view fetches the apps via
	// the appstore OCS API (`GET …/apps/appstore/api/v1/apps`), so the fixture
	// must be OCS-shaped (`ocs.data`). Keep this in sync with the fixture, which
	// currently exposes two recommended apps (calendar, contacts).
	cy.intercept('GET', '**/apps/appstore/api/v1/apps', { fixture: 'appstore/apps.json' })

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

	// The recommended apps are installed one after another, each via a single
	// OCS enable request (`POST …/apps/appstore/api/v1/apps/enable`). Stub it so
	// we exercise the frontend flow without hitting the real app store.
	cy.intercept('POST', '**/apps/appstore/api/v1/apps/enable', mode === 'install-success'
		? { statusCode: 200, body: { ocs: { meta: { status: 'ok', statuscode: 200, message: 'OK' }, data: { update_required: false } } } }
		: { statusCode: 500, body: { ocs: { meta: { status: 'failure', statuscode: 500, message: 'Forced failure' }, data: [] } } }).as('enableApp')

	cy.get('[data-cy-setup-recommended-apps-install]').click()

	// Each app is enabled with a strict password confirmation, so one dialog is
	// shown per app (there is no longer a single bulk request). Confirm every
	// dialog and assert each enable request carries a Basic auth header.
	// Keep RECOMMENDED_APP_COUNT in sync with the appstore/apps.json fixture.
	const RECOMMENDED_APP_COUNT = 2
	for (let i = 0; i < RECOMMENDED_APP_COUNT; i++) {
		cy.findByRole('dialog', { name: 'Authentication required' })
			.should('be.visible')
		handlePasswordConfirmation(randAdmin)
		cy.wait('@enableApp')
			.its('request.headers.authorization')
			.should('match', /^Basic /)
	}

	// The frontend no longer redirects after installing; it stays on the
	// recommended-apps page and reflects the per-app result inline.
	cy.location('pathname').should('include', '/core/apps/recommended')
	if (mode === 'install-success') {
		cy.get('[data-cy-setup-recommended-apps]')
			.should('contain.text', 'App already installed')
	} else {
		cy.get('[data-cy-setup-recommended-apps]')
			.should('contain.text', 'App download or installation failed')
	}
}
