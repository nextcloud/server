/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createFolder, getRowForFile, haveValidity, navigateToFolder } from './FilesUtils'

describe('"New"-menu', { testIsolation: true }, () => {

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/apps/files')
		})
	})

	it('Create new folder', () => {
		// Click the "new" button
		cy.get('[data-cy-upload-picker]')
			.findByRole('button', { name: 'New' })
			.should('be.visible')
			.click()
		// Click the "new folder" menu entry
		cy.findByRole('menuitem', { name: 'New folder' })
			.should('be.visible')
			.click()
		// Create a folder
		cy.intercept('MKCOL', '**/remote.php/dav/files/**').as('mkdir')
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.type('A new folder{enter}')
		cy.wait('@mkdir')
		// See the folder is visible
		getRowForFile('A new folder')
			.should('be.visible')
	})

	it('Does not allow creating forbidden folder names', () => {
		// Click the "new" button
		cy.get('[data-cy-upload-picker]')
			.findByRole('button', { name: 'New' })
			.should('be.visible')
			.click()
		// Click the "new folder" menu entry
		cy.findByRole('menuitem', { name: 'New folder' })
			.should('be.visible')
			.click()
		// enter folder name
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.type('.htaccess')
		// See that input has invalid state set
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.should(haveValidity(/reserved name/i))
		// See that it can not create
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('button', { name: 'Create' })
			.should('be.disabled')
	})

	it('Does not allow creating folders with already existing names', () => {
		createFolder('already exists')
		// Click the "new" button
		cy.get('[data-cy-upload-picker]')
			.findByRole('button', { name: 'New' })
			.should('be.visible')
			.click()
		// Click the "new folder" menu entry
		cy.findByRole('menuitem', { name: 'New folder' })
			.should('be.visible')
			.click()
		// enter folder name
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.type('already exists')
		// See that input has invalid state set
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.should(haveValidity(/already in use/i))
		// See that it can not create
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('button', { name: 'Create' })
			.should('be.disabled')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/47530
	 */
	it('Create same folder in child folder', () => {
		// setup other folders
		createFolder('folder')
		createFolder('other folder')
		navigateToFolder('folder')

		// Click the "new" button
		cy.get('[data-cy-upload-picker]')
			.findByRole('button', { name: 'New' })
			.should('be.visible')
			.click()
		// Click the "new folder" menu entry
		cy.findByRole('menuitem', { name: 'New folder' })
			.should('be.visible')
			.click()
		// enter folder name
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.type('other folder')
		// See that creating is allowed
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('textbox', { name: 'Folder name' })
			.should(haveValidity(''))
		// can create
		cy.intercept('MKCOL', '**/remote.php/dav/files/**').as('mkdir')
		cy.findByRole('dialog', { name: /create new folder/i })
			.findByRole('button', { name: 'Create' })
			.click()
		cy.wait('@mkdir')
		// see it is created
		getRowForFile('other folder')
			.should('be.visible')
	})
})
