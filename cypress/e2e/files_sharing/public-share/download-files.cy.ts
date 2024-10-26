/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// @ts-expect-error The package is currently broken - but works...
import { deleteDownloadsFolderBeforeEach } from 'cypress-delete-downloads-folder'

import { zipFileContains } from '../../../support/utils/assertions.ts'
import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getShareUrl, setupPublicShare } from './setup-public-share.ts'

describe('files_sharing: Public share - downloading files', { testIsolation: true }, () => {

	before(() => setupPublicShare())

	deleteDownloadsFolderBeforeEach()

	beforeEach(() => {
		cy.logout()
		cy.visit(getShareUrl())
	})

	it('Can download all files', () => {
		getRowForFile('foo.txt').should('be.visible')

		cy.get('[data-cy-files-list]').within(() => {
			cy.findByRole('checkbox', { name: /Toggle selection for all files/i })
				.should('exist')
				.check({ force: true })

			// see that two files are selected
			cy.contains('2 selected').should('be.visible')

			// click download
			cy.findByRole('button', { name: 'Download (selected)' })
				.should('be.visible')
				.click()

			// check a file is downloaded
			const downloadsFolder = Cypress.config('downloadsFolder')
			cy.readFile(`${downloadsFolder}/download.zip`, null, { timeout: 15000 })
				.should('exist')
				.and('have.length.gt', 30)
				// Check all files are included
				.and(zipFileContains([
					'foo.txt',
					'subfolder/',
					'subfolder/bar.txt',
				]))
		})
	})

	it('Can download selected files', () => {
		getRowForFile('subfolder')
			.should('be.visible')

		cy.get('[data-cy-files-list]').within(() => {
			getRowForFile('subfolder')
				.findByRole('checkbox')
				.check({ force: true })

			// see that two files are selected
			cy.contains('1 selected').should('be.visible')

			// click download
			cy.findByRole('button', { name: 'Download (selected)' })
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
					'subfolder/bar.txt',
				]))
		})
	})

	it('Can download folder by action', () => {
		getRowForFile('subfolder')
			.should('be.visible')

		cy.get('[data-cy-files-list]').within(() => {
			triggerActionForFile('subfolder', 'download')

			// check a file is downloaded
			const downloadsFolder = Cypress.config('downloadsFolder')
			cy.readFile(`${downloadsFolder}/subfolder.zip`, null, { timeout: 15000 })
				.should('exist')
				.and('have.length.gt', 30)
				// Check all files are included
				.and(zipFileContains([
					'subfolder/',
					'subfolder/bar.txt',
				]))
		})
	})

	it('Can download file by action', () => {
		getRowForFile('foo.txt')
			.should('be.visible')

		cy.get('[data-cy-files-list]').within(() => {
			triggerActionForFile('foo.txt', 'download')

			// check a file is downloaded
			const downloadsFolder = Cypress.config('downloadsFolder')
			cy.readFile(`${downloadsFolder}/foo.txt`, 'utf-8', { timeout: 15000 })
				.should('exist')
				.and('have.length.gt', 5)
				.and('contain', '<content>foo</content>')
		})
	})

	it('Can download file by selection', () => {
		getRowForFile('foo.txt')
			.should('be.visible')

		cy.get('[data-cy-files-list]').within(() => {
			getRowForFile('foo.txt')
				.findByRole('checkbox')
				.check({ force: true })

			cy.findByRole('button', { name: 'Download (selected)' })
				.click()

			// check a file is downloaded
			const downloadsFolder = Cypress.config('downloadsFolder')
			cy.readFile(`${downloadsFolder}/foo.txt`, 'utf-8', { timeout: 15000 })
				.should('exist')
				.and('have.length.gt', 5)
				.and('contain', '<content>foo</content>')
		})
	})
})
