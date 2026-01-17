/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { handlePasswordConfirmation } from '../settings/usersUtils.ts'

describe('files_external settings', () => {
	before(() => {
		cy.runOccCommand('app:enable files_external')
		cy.login({ language: 'en', password: 'admin', userId: 'admin' })
	})

	beforeEach(() => {
		cy.runOccCommand('files_external:list --output json')
			.then((exec) => {
				const list = JSON.parse(exec.stdout)
				for (const { mount_id: mountId } of list) {
					cy.runOccCommand('files_external:delete ' + mountId + ' --yes')
				}
			})
		cy.visit('/settings/admin/externalstorages')
	})

	it('can see the settings section', () => {
		cy.findByRole('heading', { name: /External storage/, level: 2 })
			.should('be.visible')
		cy.findByRole('table', { name: 'External storages' })
			.should('be.visible')
	})

	it('can see the dialog', () => {
		openDialog()

		cy.findByRole('dialog', { name: 'Add storage' })
			.within(() => {
				cy.findByRole('textbox', { name: 'Folder name' })
					.should('be.visible')

				getComboBox(/External storage/)
					.should('be.visible')
				getComboBox(/Authentication/)
					.should('be.visible')
				getComboBox(/Restrict to/)
					.should('be.visible')
				cy.findByRole('button', { name: 'Create' })
					.should('be.visible')
					.and('have.attr', 'type', 'submit')
			})
	})

	it('can create storage using the dialog', () => {
		openDialog()

		cy.findByRole('dialog', { name: 'Add storage' })
			.within(() => {
				cy.findByRole('textbox', { name: 'Folder name' })
					.should('be.visible')
					.type('My Storage')

				getComboBox(/External storage/)
					.should('be.visible')
					.click()
				cy.root().closest('body')
					.findByRole('option', { name: 'WebDAV' })
					.should('be.visible')
					.click()

				getComboBox(/Authentication/)
					.should('be.visible')
					.as('authComboBox')
					.click()
				cy.root().closest('body')
					.findByRole('option', { name: /Login and password/ })
					.should('be.visible')
					.click()

				cy.findByRole('textbox', { name: 'Login' })
					.should('be.visible')
					.type('admin')

				cy.get('input[type="password"]')
					.should('be.visible')
					.type('admin')

				cy.findByRole('button', { name: 'Create' })
					.should('be.visible')
					.click()

				cy.findByRole('textbox', { name: 'URL' })
					.should('be.visible')
					.and((el) => el.is(':invalid'))
					.type('http://localhost/remote.php/dav/files/admin')

				cy.findByRole('checkbox', { name: /Secure/ })
					.uncheck({ force: true })

				cy.findByRole('button', { name: 'Create' })
					.should('be.visible')
					.click()
			})
		handlePasswordConfirmation('admin')

		cy.findAllByRole('dialog').should('not.exist')

		getTable()
			.findAllByRole('row')
			.should('have.length', 1)
		getTable()
			.findByRole('row')
			.as('storageRow')
			.findByRole('cell', { name: /My Storage/ })
			.should('be.visible')

		cy.get('@storageRow')
			.findByRole('cell', { name: /WebDAV/ })
			.should('be.visible')
		cy.get('@storageRow')
			.findByRole('cell', { name: /Login and password/ })
			.should('be.visible')
		cy.get('@storageRow')
			.findByRole('button', { name: /Edit/ })
			.should('be.visible')
		cy.get('@storageRow')
			.findByRole('button', { name: /Delete/ })
			.should('be.visible')
			.as('deleteButton')

		cy.get('@deleteButton')
			.click()
		handlePasswordConfirmation('admin')

		getTable()
			.findByRole('row')
			.should('not.exist')
	})
})

/**
 * Get the external storages table
 */
function getTable() {
	return cy.findByRole('table', { name: 'External storages' })
		.find('tbody')
}

function openDialog() {
	cy.findByRole('button', { name: 'Add external storage' }).click()
	cy.findByRole('dialog', { name: 'Add storage' }).should('be.visible')
}

function getComboBox(match: RegExp) {
	return cy.contains('label', match)
		.should('be.visible')
		.then((el) => Cypress.$(`#${el.attr('for')}`))
}
