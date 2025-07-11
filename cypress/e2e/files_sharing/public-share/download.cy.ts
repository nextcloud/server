/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// @ts-expect-error The package is currently broken - but works...
import { deleteDownloadsFolderBeforeEach } from 'cypress-delete-downloads-folder'
import { createLinkShare, getShareUrl, openLinkShareDetails, setupPublicShare, type ShareContext } from './PublicShareUtils.ts'
import { getRowForFile, getRowForFileId, triggerActionForFile, triggerActionForFileId } from '../../files/FilesUtils.ts'
import { zipFileContains } from '../../../support/utils/assertions.ts'
import type { User } from '@nextcloud/cypress'

describe('files_sharing: Public share - downloading files', { testIsolation: true }, () => {

	// in general there is no difference except downloading
	// as file shares have the source of the share token but a different displayname
	describe('file share', () => {
		let fileId: number

		before(() => {
			cy.createRandomUser().then((user) => {
				const context: ShareContext = { user }
				cy.uploadContent(user, new Blob(['<content>foo</content>']), 'text/plain', '/file.txt')
					.then(({ headers }) => { fileId = Number.parseInt(headers['oc-fileid']) })
				cy.login(user)
				createLinkShare(context, 'file.txt')
					.then(() => cy.logout())
					.then(() => cy.visit(context.url!))
			})
		})

		it('can download the file', () => {
			getRowForFileId(fileId)
				.should('be.visible')
			getRowForFileId(fileId)
				.find('[data-cy-files-list-row-name]')
				.should((el) => expect(el.text()).to.match(/file\s*\.txt/)) // extension is sparated so there might be a space between
			triggerActionForFileId(fileId, 'download')
			// check a file is downloaded with the correct name
			const downloadsFolder = Cypress.config('downloadsFolder')
			cy.readFile(`${downloadsFolder}/file.txt`, 'utf-8', { timeout: 15000 })
				.should('exist')
				.and('have.length.gt', 5)
				.and('contain', '<content>foo</content>')
		})
	})

	describe('folder share', () => {
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

	describe('download permission - link share', () => {
		let context: ShareContext
		beforeEach(() => {
			cy.createRandomUser().then((user) => {
				cy.mkdir(user, '/test')

				context = { user }
				createLinkShare(context, 'test')
				cy.login(context.user)
				cy.visit('/apps/files')
			})
		})

		deleteDownloadsFolderBeforeEach()

		it('download permission is retained', () => {
			getRowForFile('test').should('be.visible')
			triggerActionForFile('test', 'details')

			openLinkShareDetails(0)

			cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('update')

			cy.findByRole('checkbox', { name: /hide download/i })
				.should('exist')
				.and('not.be.checked')
				.check({ force: true })
			cy.findByRole('checkbox', { name: /hide download/i })
				.should('be.checked')
			cy.findByRole('button', { name: /update share/i })
				.click()

			cy.wait('@update')

			openLinkShareDetails(0)
			cy.findByRole('checkbox', { name: /hide download/i })
				.should('be.checked')

			cy.reload()

			getRowForFile('test').should('be.visible')
			triggerActionForFile('test', 'details')
			openLinkShareDetails(0)
			cy.findByRole('checkbox', { name: /hide download/i })
				.should('be.checked')
		})
	})

	describe('download permission - mail share', () => {
		let user: User

		beforeEach(() => {
			cy.createRandomUser().then(($user) => {
				user = $user
				cy.mkdir(user, '/test')
				cy.login(user)
				cy.visit('/apps/files')
			})
		})

		it('download permission is retained', () => {
			getRowForFile('test').should('be.visible')
			triggerActionForFile('test', 'details')

			cy.findByRole('combobox', { name: /Enter external recipients/i })
				.type('test@example.com')

			cy.get('.option[sharetype="4"][user="test@example.com"]')
				.parent('li')
				.click()
			cy.findByRole('button', { name: /advanced settings/i })
				.should('be.visible')
				.click()

			cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('update')

			cy.findByRole('checkbox', { name: /hide download/i })
				.should('exist')
				.and('not.be.checked')
				.check({ force: true })
			cy.findByRole('button', { name: /save share/i })
				.click()

			cy.wait('@update')

			openLinkShareDetails(1)
			cy.findByRole('button', { name: /advanced settings/i })
				.click()
			cy.findByRole('checkbox', { name: /hide download/i })
				.should('exist')
				.and('be.checked')
		})
	})
})
