/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { SetupConfig, SetupLinks } from '../install.ts'

import { cleanup, findByRole, fireEvent, getAllByRole, getByRole, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it } from 'vitest'
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
	beforeEach(cleanup)
	beforeEach(() => {
		removeInitialState()
		mockInitialState('core', 'links', links)
	})

	it('Renders default config', async () => {
		mockInitialState('core', 'config', defaultConfig)
		const component = render(SetupView)

		// Single note is the footer help
		expect(component.getAllByRole('note')).toHaveLength(1)
		expect(component.getByRole('note').textContent).toContain('See the documentation')

		// DB radio selectors
		const dbTypes = component.getByRole('group', { name: 'Database type' })
		expect(getAllByRole(dbTypes, 'radio')).toHaveLength(3)
		await expect(findByRole(dbTypes, 'radio', { checked: true })).resolves.not.toThrow()
		await expect(findByRole(dbTypes, 'radio', { name: /MySQL/ })).resolves.not.toThrow()
		await expect(findByRole(dbTypes, 'radio', { name: /PostgreSQL/ })).resolves.not.toThrow()
		await expect(findByRole(dbTypes, 'radio', { name: /SQLite/ })).resolves.not.toThrow()

		// Sqlite warning
		await expect(component.findByText(/SQLite should only be used for minimal and development instances/)).resolves.not.toThrow()

		// admin login, password, data directory
		await expect(component.findByRole('textbox', { name: 'Administration account name' })).resolves.not.toThrow()
		await expect(component.findByLabelText('Administration account password')).resolves.not.toThrow()
		await expect(component.findByRole('textbox', { name: 'Data folder' })).resolves.not.toThrow()
	})

	it('Renders single DB sqlite', async () => {
		mockInitialState('core', 'config', {
			...defaultConfig,
			databases: {
				sqlite: 'SQLite',
			},
		})
		const component = render(SetupView)

		const dbTypes = component.getByRole('group', { name: 'Database type' })
		expect(getAllByRole(dbTypes, 'radio', { hidden: true })).toHaveLength(1)
		await expect(findByRole(dbTypes, 'radio', { name: /SQLite/, hidden: true })).resolves.not.toThrow()

		// Two warnings: sqlite and single db support
		await expect(component.findByText(/Only SQLite is available./)).resolves.not.toThrow()
		await expect(component.findByText(/SQLite should only be used for minimal and development instances/)).resolves.not.toThrow()
	})

	it('Renders single DB mysql', async () => {
		mockInitialState('core', 'config', {
			...defaultConfig,
			databases: {
				mysql: 'MySQL/MariaDB',
			},
		})
		const component = render(SetupView)

		const dbTypes = component.getByRole('group', { name: 'Database type' })
		expect(getAllByRole(dbTypes, 'radio', { hidden: true })).toHaveLength(1)
		await expect(findByRole(dbTypes, 'radio', { name: /MySQL/, hidden: true })).resolves.not.toThrow()

		// Single db support warning
		await expect(component.findByText(/Only MySQL.* is available./)).resolves.not.toThrow()

		// No SQLite warning
		await expect(component.findByText(/SQLite should only be used for minimal and development instances/)).rejects.toThrow()

		// database config
		await expect(component.findByRole('textbox', { name: /Database user/ })).resolves.not.toThrow()
		await expect(component.findByRole('textbox', { name: /Database name/ })).resolves.not.toThrow()
		await expect(component.findByRole('textbox', { name: /Database host/ })).resolves.not.toThrow()
		await expect(component.findByLabelText(/Database password/)).resolves.not.toThrow()
	})

	it('Changes fields from sqlite to mysql then oci', async () => {
		mockInitialState('core', 'config', {
			...defaultConfig,
			databases: {
				sqlite: 'SQLite',
				mysql: 'MySQL/MariaDB',
				pgsql: 'PostgreSQL',
				oci: 'Oracle',
			},
		})
		const component = render(SetupView)

		// SQLite selected
		await expect(component.findByRole('radio', { name: /SQLite/, checked: true })).resolves.not.toThrow()

		// 4 db toggles
		const dbTypes = component.getByRole('group', { name: 'Database type' })
		expect(getAllByRole(dbTypes, 'radio')).toHaveLength(4)

		// but no database config fields
		await expect(findByRole(dbTypes, 'group', { name: /Database connection/ })).rejects.toThrow()

		// Change to MySQL
		await fireEvent.click(getByRole(dbTypes, 'radio', { name: /MySQL/, checked: false }))
		expect((getByRole(dbTypes, 'radio', { name: /SQLite/, checked: false }) as HTMLInputElement).checked).toBe(false)
		expect((getByRole(dbTypes, 'radio', { name: /MySQL/, checked: true }) as HTMLInputElement).checked).toBe(true)

		// now the database config fields are visible
		await expect(component.findByRole('group', { name: /Database connection/ })).resolves.not.toThrow()
		// but not the Database tablespace
		await expect(component.findByRole('textbox', { name: /Database tablespace/ })).rejects.toThrow()

		// Change to Oracle
		await fireEvent.click(getByRole(dbTypes, 'radio', { name: /Oracle/, checked: false }))

		// see database config fields are visible and tablespace
		await expect(component.findByRole('textbox', { name: /Database tablespace/ })).resolves.not.toThrow()
		await expect(component.findByRole('group', { name: /Database connection/ })).resolves.not.toThrow()
	})
})

describe('Setup page with errors and warning', () => {
	beforeEach(cleanup)
	beforeEach(() => {
		removeInitialState()
		mockInitialState('core', 'links', links)
	})

	it('Renders error from backend', async () => {
		mockInitialState('core', 'config', {
			...defaultConfig,
			errors: [
				{
					error: 'Error message',
					hint: 'Error hint',
				},
			],
		})
		const component = render(SetupView)

		// Error message and hint
		await expect(component.findByText('Error message')).resolves.not.toThrow()
		await expect(component.findByText('Error hint')).resolves.not.toThrow()
	})

	it('Renders errors from backend', async () => {
		const config = {
			...defaultConfig,
			errors: [
				'Error message 1',
				{
					error: 'Error message 2',
					hint: 'Error hint',
				},
			],
		}
		mockInitialState('core', 'config', config)
		const component = render(SetupView)

		// Error message and hint
		await expect(component.findByText('Error message 1')).resolves.not.toThrow()
		await expect(component.findByText('Error message 2')).resolves.not.toThrow()
		await expect(component.findByText('Error hint')).resolves.not.toThrow()
	})

	it('Renders all the submitted fields on error', async () => {
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
		mockInitialState('core', 'config', config)
		const component = render(SetupView)

		await expect(component.findByRole('textbox', { name: 'Data folder' })).resolves.not.toThrow()
		expect((component.getByRole('textbox', { name: 'Data folder' }) as HTMLInputElement).value).toBe('/var/www/html/nextcloud')

		await expect(component.findByRole('textbox', { name: 'Administration account name' })).resolves.not.toThrow()
		expect((component.getByRole('textbox', { name: 'Administration account name' }) as HTMLInputElement).value).toBe('admin')

		await expect(component.findByLabelText('Administration account password')).resolves.not.toThrow()
		expect((component.getByLabelText('Administration account password') as HTMLInputElement).value).toBe('password')

		await expect(component.findByRole('radio', { name: /MySQL/, checked: true, hidden: true })).resolves.not.toThrow()
		await expect(component.findByRole('textbox', { name: 'Database name' })).resolves.not.toThrow()
		expect((component.getByRole('textbox', { name: 'Database name' }) as HTMLInputElement).value).toBe('nextcloud')
		await expect(component.findByRole('textbox', { name: 'Database user' })).resolves.not.toThrow()
		expect((component.getByRole('textbox', { name: 'Database user' }) as HTMLInputElement).value).toBe('nextcloud')
		await expect(component.findByRole('textbox', { name: 'Database host' })).resolves.not.toThrow()
		expect((component.getByRole('textbox', { name: 'Database host' }) as HTMLInputElement).value).toBe('localhost')
		await expect(component.findByLabelText('Database password')).resolves.not.toThrow()
		expect((component.getByLabelText('Database password') as HTMLInputElement).value).toBe('password')
	})

	it('Renders the htaccess warning', async () => {
		const config = {
			...defaultConfig,
			htaccessWorking: false,
		}
		mockInitialState('core', 'config', config)
		const component = render(SetupView)

		await expect(component.findByText('Security warning')).resolves.not.toThrow()
	})
})

describe('Setup page with autoconfig', () => {
	beforeEach(cleanup)
	beforeEach(() => {
		removeInitialState()
		mockInitialState('core', 'links', links)
	})

	it('Renders autoconfig', async () => {
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
		mockInitialState('core', 'config', config)
		const component = render(SetupView)

		// Autoconfig info note
		await expect(component.findByText('Autoconfig file detected')).resolves.not.toThrow()

		// Oracle tablespace is hidden
		await expect(component.findByRole('textbox', { name: 'Database tablespace' })).rejects.toThrow()

		// Database and storage section is hidden as already set in autoconfig
		await expect(component.findByText('Storage & database')).resolves.not.toThrow()
		expect(component.getByText('Storage & database').closest('details')!.getAttribute('hidden')).toBeNull()
	})
})

/**
 * Remove the mocked initial state
 */
function removeInitialState(): void {
	document.querySelectorAll('input[type="hidden"]').forEach((el) => {
		el.remove()
	})
	// clear the cache
	delete globalThis._nc_initial_state
}

/**
 * Helper to mock an initial state value
 * @param app - The app
 * @param key - The key
 * @param value - The value
 */
function mockInitialState(app: string, key: string, value: unknown): void {
	const el = document.createElement('input')
	el.value = btoa(JSON.stringify(value))
	el.id = `initial-state-${app}-${key}`
	el.type = 'hidden'

	document.head.appendChild(el)
}
