/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder, triggerActionForFile } from './FilesUtils'
import { deleteDownloadsFolderBeforeEach } from 'cypress-delete-downloads-folder'

describe('files: Download files using file actions', { testIsolation: true }, () => {
	let user: User

	deleteDownloadsFolderBeforeEach()

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
		})
	})

	it('can download file', () => {
		cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'download')

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	it('can download file with hash name', () => {
		cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/#file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		triggerActionForFile('#file.txt', 'download')
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/#file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	it('can download file from folder with hash name', () => {
		cy.mkdir(user, '/#folder')
			.uploadContent(user, new Blob(['<content>']), 'text/plain', '/#folder/file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		navigateToFolder('#folder')
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'download')
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})
})

describe('files: Download files using default action', { testIsolation: true }, () => {
	let user: User

	deleteDownloadsFolderBeforeEach()

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
		})
	})

	it('can download file', () => {
		cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		getRowForFile('file.txt')
			.should('be.visible')
			.findByRole('button', { name: 'Download' })
			.click()

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	it('can download file with hash name', () => {
		cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/#file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		getRowForFile('#file.txt')
			.should('be.visible')
			.findByRole('button', { name: 'Download' })
			.click()

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/#file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	it('can download file from folder with hash name', () => {
		cy.mkdir(user, '/#folder')
			.uploadContent(user, new Blob(['<content>']), 'text/plain', '/#folder/file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		navigateToFolder('#folder')
		// All are visible by default
		getRowForFile('file.txt')
			.should('be.visible')
			.findByRole('button', { name: 'Download' })
			.click()

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})
})
