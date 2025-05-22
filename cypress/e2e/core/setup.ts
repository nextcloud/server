/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
 */
function sharedSetup() {
	const randAdmin = 'admin-' + Math.random().toString(36).substring(2, 15)

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

	// Skip the setup apps
	cy.get('[data-cy-setup-recommended-apps-skip]').click()

	// Go to files
	cy.visit('/apps/files/')
	cy.get('[data-cy-files-content]').should('be.visible')
}
