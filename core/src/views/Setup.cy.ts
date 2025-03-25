/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { SetupConfig, SetupLinks } from '../install'
import SetupView from './Setup.vue'

import '../../css/guest.css'

const defaultConfig = Object.freeze({
	adminlogin: '',
	adminpass: '',
	dbuser: '',
	dbpass: '',
	dbname: '',
	dbtablespace: '',
	dbhost: '',
	dbtype: '',
	databases: {
		sqlite: 'SQLite',
		mysql: 'MySQL/MariaDB',
		pgsql: 'PostgreSQL',
	},
	directory: '',
	hasAutoconfig: false,
	htaccessWorking: true,
	serverRoot: '/var/www/html',
	errors: [],
}) as SetupConfig

const links = {
	adminInstall: 'https://docs.nextcloud.com/server/32/go.php?to=admin-install',
	adminSourceInstall: 'https://docs.nextcloud.com/server/32/go.php?to=admin-source_install',
	adminDBConfiguration: 'https://docs.nextcloud.com/server/32/go.php?to=admin-db-configuration',
} as SetupLinks

describe('Default setup page', () => {
	beforeEach(() => {
		cy.mockInitialState('core', 'links', links)
	})

	afterEach(() => cy.unmockInitialState())

	it('Renders default config', () => {
		cy.mockInitialState('core', 'config', defaultConfig)
		cy.mount(SetupView)

		cy.get('[data-cy-setup-form]').scrollIntoView()
		cy.get('[data-cy-setup-form]').should('be.visible')

		// Single note is the footer help
		cy.get('[data-cy-setup-form-note]')
			.should('have.length', 1)
			.should('be.visible')
		cy.get('[data-cy-setup-form-note]').should('contain', 'See the documentation')

		// DB radio selectors
		cy.get('[data-cy-setup-form-field^="dbtype"]')
			.should('exist')
			.find('input')
			.should('be.checked')

		cy.get('[data-cy-setup-form-field="dbtype-mysql"]').should('exist')
		cy.get('[data-cy-setup-form-field="dbtype-pgsql"]').should('exist')
		cy.get('[data-cy-setup-form-field="dbtype-oci"]').should('not.exist')

		// Sqlite warning
		cy.get('[data-cy-setup-form-db-note="sqlite"]')
			.should('be.visible')

		// admin login, password, data directory and 3 DB radio selectors
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 6)
	})

	it('Renders single DB sqlite', () => {
		const config = {
			...defaultConfig,
			databases: {
				sqlite: 'SQLite',
			},
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// No DB radio selectors if only sqlite
		cy.get('[data-cy-setup-form-field^="dbtype"]')
			.should('not.exist')

		// Two warnings: sqlite and single db support
		cy.get('[data-cy-setup-form-db-note="sqlite"]')
			.should('be.visible')
		cy.get('[data-cy-setup-form-db-note="single-db"]')
			.should('be.visible')

		// Admin login, password and data directory
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 3)
	})

	it('Renders single DB mysql', () => {
		const config = {
			...defaultConfig,
			databases: {
				mysql: 'MySQL/MariaDB',
			},
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// No DB radio selectors if only mysql
		cy.get('[data-cy-setup-form-field^="dbtype"]')
			.should('not.exist')

		// Single db support warning
		cy.get('[data-cy-setup-form-db-note="single-db"]')
			.should('be.visible')
			.invoke('html')
			.should('contains', links.adminSourceInstall)

		// No SQLite warning
		cy.get('[data-cy-setup-form-db-note="sqlite"]')
			.should('not.exist')

		// Admin login, password, data directory, db user,
		// db password, db name and db host
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 7)
	})

	it('Changes fields from sqlite to mysql then oci', () => {
		const config = {
			...defaultConfig,
			databases: {
				sqlite: 'SQLite',
				mysql: 'MySQL/MariaDB',
				pgsql: 'PostgreSQL',
				oci: 'Oracle',
			},
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// SQLite selected
		cy.get('[data-cy-setup-form-field="dbtype-sqlite"]')
			.should('be.visible')
			.find('input')
			.should('be.checked')

		// Admin login, password, data directory and 4 DB radio selectors
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 7)

		// Change to MySQL
		cy.get('[data-cy-setup-form-field="dbtype-mysql"]').click()
		cy.get('[data-cy-setup-form-field="dbtype-mysql"] input').should('be.checked')

		// Admin login, password, data directory, db user, db password,
		// db name, db host and 4 DB radio selectors
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 11)

		// Change to Oracle
		cy.get('[data-cy-setup-form-field="dbtype-oci"]').click()
		cy.get('[data-cy-setup-form-field="dbtype-oci"] input').should('be.checked')

		// Admin login, password, data directory, db user, db password,
		// db name, db table space, db host and 4 DB radio selectors
		cy.get('[data-cy-setup-form-field]')
			.should('be.visible')
			.should('have.length', 12)
		cy.get('[data-cy-setup-form-field="dbtablespace"]')
			.should('be.visible')
	})
})

describe('Setup page with errors and warning', () => {
	beforeEach(() => {
		cy.mockInitialState('core', 'links', links)
	})

	afterEach(() => cy.unmockInitialState())

	it('Renders error from backend', () => {
		const config = {
			...defaultConfig,
			errors: [
				{
					error: 'Error message',
					hint: 'Error hint',
				},
			],
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// Error message and hint
		cy.get('[data-cy-setup-form-note="error"]')
			.should('be.visible')
			.should('have.length', 1)
			.should('contain', 'Error message')
			.should('contain', 'Error hint')
	})

	it('Renders errors from backend', () => {
		const config = {
			...defaultConfig,
			errors: [
				'Error message 1',
				{
					error: 'Error message',
					hint: 'Error hint',
				},
			],
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// Error message and hint
		cy.get('[data-cy-setup-form-note="error"]')
			.should('be.visible')
			.should('have.length', 2)
		cy.get('[data-cy-setup-form-note="error"]').eq(0)
			.should('contain', 'Error message 1')
		cy.get('[data-cy-setup-form-note="error"]').eq(1)
			.should('contain', 'Error message')
			.should('contain', 'Error hint')
	})

	it('Renders all the submitted fields on error', () => {
		const config = {
			...defaultConfig,
			adminlogin: 'admin',
			adminpass: 'password',
			dbname: 'nextcloud',
			dbtype: 'mysql',
			dbuser: 'nextcloud',
			dbpass: 'password',
			dbhost: 'localhost',
			directory: '/var/www/html/nextcloud',
		} as SetupConfig
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		cy.get('input[data-cy-setup-form-field="adminlogin"]')
			.should('have.value', 'admin')
		cy.get('input[data-cy-setup-form-field="adminpass"]')
			.should('have.value', 'password')
		cy.get('[data-cy-setup-form-field="dbtype-mysql"] input')
			.should('be.checked')
		cy.get('input[data-cy-setup-form-field="dbname"]')
			.should('have.value', 'nextcloud')
		cy.get('input[data-cy-setup-form-field="dbuser"]')
			.should('have.value', 'nextcloud')
		cy.get('input[data-cy-setup-form-field="dbpass"]')
			.should('have.value', 'password')
		cy.get('input[data-cy-setup-form-field="dbhost"]')
			.should('have.value', 'localhost')
		cy.get('input[data-cy-setup-form-field="directory"]')
			.should('have.value', '/var/www/html/nextcloud')
	})

	it('Renders the htaccess warning', () => {
		const config = {
			...defaultConfig,
			htaccessWorking: false,
		}
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		cy.get('[data-cy-setup-form-note="htaccess"]')
			.should('be.visible')
			.should('contain', 'Security warning')
			.invoke('html')
			.should('contains', links.adminInstall)
	})
})

describe('Setup page with autoconfig', () => {
	beforeEach(() => {
		cy.mockInitialState('core', 'links', links)
	})

	afterEach(() => cy.unmockInitialState())

	it('Renders autoconfig', () => {
		const config = {
			...defaultConfig,
			hasAutoconfig: true,
			dbname: 'nextcloud',
			dbtype: 'mysql',
			dbuser: 'nextcloud',
			dbpass: 'password',
			dbhost: 'localhost',
			directory: '/var/www/html/nextcloud',
		} as SetupConfig
		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// Autoconfig info note
		cy.get('[data-cy-setup-form-note="autoconfig"]')
			.should('be.visible')
			.should('contain', 'Autoconfig file detected')

		// Database and storage section is hidden as already set in autoconfig
		cy.get('[data-cy-setup-form-advanced-config]').should('be.visible')
			.invoke('attr', 'open')
			.should('equal', undefined)

		// Oracle tablespace is hidden
		cy.get('[data-cy-setup-form-field="dbtablespace"]')
			.should('not.exist')
	})
})

describe('Submit a full form sends the data', () => {
	beforeEach(() => {
		cy.mockInitialState('core', 'links', links)
	})

	afterEach(() => cy.unmockInitialState())

	it('Submits a full form', () => {
		const config = {
			...defaultConfig,
			adminlogin: 'admin',
			adminpass: 'password',
			dbname: 'nextcloud',
			dbtype: 'mysql',
			dbuser: 'nextcloud',
			dbpass: 'password',
			dbhost: 'localhost',
			dbtablespace: 'tablespace',
			directory: '/var/www/html/nextcloud',
		} as SetupConfig

		cy.intercept('POST', '**', {
			delay: 2000,
		}).as('setup')

		cy.mockInitialState('core', 'config', config)
		cy.mount(SetupView)

		// Not chaining breaks the test as the POST prevents the element from being retrieved twice
		// eslint-disable-next-line cypress/unsafe-to-chain-command
		cy.get('[data-cy-setup-form-submit]')
			.click()
			.invoke('attr', 'disabled')
			.should('equal', 'disabled', { timeout: 500 })

		cy.wait('@setup')
			.its('request.body')
			.should('deep.equal', new URLSearchParams({
				adminlogin: 'admin',
				adminpass: 'password',
				directory: '/var/www/html/nextcloud',
				dbtype: 'mysql',
				dbuser: 'nextcloud',
				dbpass: 'password',
				dbname: 'nextcloud',
				dbhost: 'localhost',
			}).toString())
	})
})
