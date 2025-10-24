/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { zipFileContains } from '../../support/utils/assertions.ts'
import { deleteDownloadsFolderBeforeEach } from '../../support/utils/deleteDownloadsFolder.ts'
import { randomString } from '../../support/utils/randomString.ts'
import { getRowForFile, navigateToFolder, triggerActionForFile } from './FilesUtils.ts'

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

	it('can download folder', () => {
		cy.mkdir(user, '/subfolder')
		cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/subfolder/file.txt')

		cy.login(user)
		cy.visit('/apps/files')
		getRowForFile('subfolder')
			.should('be.visible')

		triggerActionForFile('subfolder', 'download')

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/subfolder.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'subfolder/',
				'subfolder/file.txt',
			]))
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

describe('files: Download files using selection', () => {
	deleteDownloadsFolderBeforeEach()

	it('can download selected files', () => {
		cy.createRandomUser().then((user) => {
			cy.mkdir(user, '/subfolder')
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/subfolder/file.txt')
			cy.login(user)
			cy.visit('/apps/files')
		})

		getRowForFile('subfolder')
			.should('be.visible')

		getRowForFile('subfolder')
			.findByRole('checkbox')
			.check({ force: true })

		// see that two files are selected
		cy.get('[data-cy-files-list]').within(() => {
			cy.contains('1 selected').should('be.visible')
		})

		// click download
		cy.get('[data-cy-files-list-selection-actions]')
			.findByRole('button', { name: 'Actions' })
			.click()
		cy.findByRole('menuitem', { name: 'Download (selected)' })
			.should('be.visible')
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/subfolder.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'subfolder/',
				'subfolder/file.txt',
			]))
	})

	it('can download multiple selected files', () => {
		cy.createRandomUser().then((user) => {
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/other file.txt')
			cy.login(user)
			cy.visit('/apps/files')
		})

		getRowForFile('file.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		getRowForFile('other file.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		cy.get('[data-cy-files-list]').within(() => {
			// see that two files are selected
			cy.contains('2 selected').should('be.visible')
		})

		// click download
		cy.get('[data-cy-files-list-selection-actions]')
			.findByRole('button', { name: 'Actions' })
			.click()
		cy.findByRole('menuitem', { name: 'Download (selected)' })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/download.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'file.txt',
				'other file.txt',
			]))
	})

	/**
	 * Regression test of https://help.nextcloud.com/t/unable-to-download-files-on-nextcloud-when-multiple-files-selected/221327/5
	 */
	it('can download selected files with special characters', () => {
		cy.createRandomUser().then((user) => {
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/1+1.txt')
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/some@other.txt')
			cy.login(user)
			cy.visit('/apps/files')
		})

		getRowForFile('some@other.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		getRowForFile('1+1.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		cy.get('[data-cy-files-list]').within(() => {
			// see that two files are selected
			cy.contains('2 selected').should('be.visible')
		})

		// click download
		cy.get('[data-cy-files-list-selection-actions]')
			.findByRole('button', { name: 'Actions' })
			.click()
		cy.findByRole('menuitem', { name: 'Download (selected)' })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/download.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'1+1.txt',
				'some@other.txt',
			]))
	})

	/**
	 * Regression test of https://help.nextcloud.com/t/unable-to-download-files-on-nextcloud-when-multiple-files-selected/221327/5
	 */
	it('can download selected files with email uid', () => {
		const name = `${randomString(5)}@${randomString(3)}`
		const user: User = { userId: name, password: name, language: 'en' }

		cy.createUser(user).then(() => {
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/other file.txt')
			cy.login(user)
			cy.visit('/apps/files')
		})

		getRowForFile('file.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		getRowForFile('other file.txt')
			.should('be.visible')
			.findByRole('checkbox')
			.check({ force: true })

		cy.get('[data-cy-files-list]').within(() => {
			// see that two files are selected
			cy.contains('2 selected').should('be.visible')
		})

		// click download
		cy.get('[data-cy-files-list-selection-actions]')
			.findByRole('button', { name: 'Actions' })
			.click()
		cy.findByRole('menuitem', { name: 'Download (selected)' })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/download.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'file.txt',
				'other file.txt',
			]))
	})
})
