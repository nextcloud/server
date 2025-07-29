/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('files_external settings', () => {
	before(() => {
		cy.runOccCommand('app:enable files_external')
		cy.login({ language: 'en', password: 'admin', userId: 'admin' })
	})

	beforeEach(() => {
		cy.runOccCommand('files_external:list --output json')
			.then((exec) => {
				const list = JSON.parse(exec.stdout)
				for (const entry of list) {
					cy.runOccCommand('files_external:delete ' + entry)
				}
			})
		cy.visit('/settings/admin/externalstorages')
	})

	it('can see the settings section', () => {
		cy.findByRole('heading', { name: 'External storage' })
			.should('be.visible')
		cy.get('table#externalStorage')
			.should('be.visible')
	})

	it('populates the row and creates a new empty one', () => {
		selectBackend('local')

		// See cell now contains the backend
		getTable()
			.findAllByRole('row')
			.first()
			.find('.backend')
			.should('contain.text', 'Local')

		// and the backend select is available but clear
		getBackendSelect()
			.should('have.value', null)

		// the suggested mount point name is set to the backend
		getTable()
			.findAllByRole('row')
			.first()
			.find('input[name="mountPoint"]')
			.should('have.value', 'Local')
	})

	it('does not save the storage with missing configuration', function() {
		selectBackend('local')

		getTable()
			.findAllByRole('row').first()
			.should('be.visible')
			.within(() => {
				cy.findByRole('checkbox', { name: 'All people' })
					.check()
				cy.get('button[title="Save"]')
					.click()
			})

		cy.findByRole('dialog', { name: 'Confirm your password' })
			.should('not.exist')
	})

	it('does not save the storage with applicable configuration', function() {
		selectBackend('local')

		getTable()
			.findAllByRole('row').first()
			.should('be.visible')
			.within(() => {
				cy.get('input[placeholder="Location"]')
					.type('/tmp')
				cy.get('button[title="Save"]')
					.click()
			})

		cy.findByRole('dialog', { name: 'Confirm your password' })
			.should('not.exist')
	})

	it('does save the storage with needed configuration', function() {
		selectBackend('local')

		getTable()
			.findAllByRole('row').first()
			.should('be.visible')
			.within(() => {
				cy.findByRole('checkbox', { name: 'All people' })
					.check()
				cy.get('input[placeholder="Location"]')
					.type('/tmp')
				cy.get('button[title="Save"]')
					.click()
			})

		cy.findByRole('dialog', { name: 'Confirm your password' })
			.should('be.visible')
	})
})

/**
 * Get the external storages table
 */
function getTable() {
	return cy.get('table#externalStorage')
		.find('tbody')
}

/**
 * Get the backend select element
 */
function getBackendSelect() {
	return getTable()
		.findAllByRole('row')
		.last()
		.findByRole('combobox')
}

/**
 * @param backend - Backend to select
 */
function selectBackend(backend: string): void {
	getBackendSelect()
		.select(backend)
}
