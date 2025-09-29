/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getRowForFile } from '../../files/FilesUtils.ts'
import { openSharingPanel } from '../FilesSharingUtils.ts'

describe('files_sharing: Public share - File drop', { testIsolation: true }, () => {

	let shareUrl: string
	let user: string
	const shareName = 'shared'

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user.userId
			cy.mkdir($user, `/${shareName}`)
			cy.uploadContent($user, new Blob(['content']), 'text/plain', `/${shareName}/foo.txt`)
			cy.login($user)
			// open the files app
			cy.visit('/apps/files')
			// open the sidebar
			openSharingPanel(shareName)
			// create the share
			cy.intercept('POST', '**/ocs/v2.php/apps/files_sharing/api/v1/shares').as('createShare')
			cy.findByRole('button', { name: 'Create a new share link' })
				.click()
			// extract the link
			cy.wait('@createShare').should(({ response }) => {
				const { ocs } = response?.body ?? {}
				shareUrl = ocs?.data.url
				expect(shareUrl).to.match(/^http:\/\//)
			})

			// Update the share to be a file drop
			cy.findByRole('list', { name: 'Link shares' })
				.findAllByRole('listitem')
				.first()
				.findByRole('button', { name: /Actions/i })
				.click()
			cy.findByRole('menuitem', { name: /Customize link/i })
				.should('be.visible')
				.click()
			cy.get('[data-cy-files-sharing-share-permissions-bundle]')
				.should('be.visible')
			cy.get('[data-cy-files-sharing-share-permissions-bundle="file-drop"]')
				.click()

			// save the update
			cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('updateShare')
			cy.findByRole('button', { name: 'Update share' })
				.click()
			cy.wait('@updateShare')
		})
	})

	beforeEach(() => {
		cy.logout()
		cy.visit(shareUrl)
	})

	it('Cannot see share content', () => {
		cy.contains(`Upload files to ${shareName}`)
			.should('be.visible')

		// foo exists
		cy.userFileExists(user, `${shareName}/foo.txt`).should('be.gt', 0)
		// but is not visible
		getRowForFile('foo.txt')
			.should('not.exist')
	})

	it('Can only see upload files and upload folders menu entries', () => {
		cy.contains(`Upload files to ${shareName}`)
			.should('be.visible')

		cy.findByRole('button', { name: 'New' })
			.should('be.visible')
			.click()
		// See upload actions
		cy.findByRole('menuitem', { name: 'Upload files' })
			.should('be.visible')
		cy.findByRole('menuitem', { name: 'Upload folders' })
			.should('be.visible')
		// But no other
		cy.findByRole('menu')
			.findAllByRole('menuitem')
			.should('have.length', 2)
	})

	it('Can only see dedicated upload button', () => {
		cy.contains(`Upload files to ${shareName}`)
			.should('be.visible')

		cy.findByRole('button', { name: 'Upload' })
			.should('be.visible')
			.click()
		// See upload actions
		cy.findByRole('menuitem', { name: 'Upload files' })
			.should('be.visible')
		cy.findByRole('menuitem', { name: 'Upload folders' })
			.should('be.visible')
		// But no other
		cy.findByRole('menu')
			.findAllByRole('menuitem')
			.should('have.length', 2)
	})

	it('Can upload files', () => {
		cy.contains(`Upload files to ${shareName}`)
			.should('be.visible')

		const { promise, resolve } = Promise.withResolvers()
		cy.intercept('PUT', '**/public.php/dav/files/**', (request) => {
			if (request.url.includes('first.txt')) {
				// just continue the first one
				request.continue()
			} else {
				// We delay the second one until we checked that the progress bar is visible
				request.on('response', async () => { await promise })
			}
		}).as('uploadFile')

		cy.get('[data-cy-files-sharing-file-drop] input[type="file"]')
			.should('exist')
			.selectFile([
				{ fileName: 'first.txt', contents: Buffer.from('8 bytes!') },
				{ fileName: 'second.md', contents: Buffer.from('x'.repeat(128)) },
			], { force: true })

		cy.wait('@uploadFile')

		cy.findByRole('progressbar')
			.should('be.visible')
			.and((el) => { expect(Number.parseInt(el.attr('value') ?? '0')).be.gte(50) })
			// continue second request
			.then(() => resolve(null))

		cy.wait('@uploadFile')

		// Check files uploaded
		cy.userFileExists(user, `${shareName}/first.txt`).should('eql', 8)
		cy.userFileExists(user, `${shareName}/second.md`).should('eql', 128)
	})

	describe('Terms of service', { testIsolation: true }, () => {
		before(() => cy.runOccCommand('config:app:set --value \'TEST: Some disclaimer text\' --type string core shareapi_public_link_disclaimertext'))
		beforeEach(() => cy.visit(shareUrl))
		after(() => cy.runOccCommand('config:app:delete core shareapi_public_link_disclaimertext'))

		it('shows ToS on file-drop view', () => {
			cy.get('[data-cy-files-sharing-file-drop]')
				.contains(`Upload files to ${shareName}`)
				.should('be.visible')
			cy.get('[data-cy-files-sharing-file-drop]')
				.contains('agree to the terms of service')
				.should('be.visible')
			cy.findByRole('button', { name: /Terms of service/i })
				.should('be.visible')
				.click()

			cy.findByRole('dialog', { name: 'Terms of service' })
				.should('contain.text', 'TEST: Some disclaimer text')
				// close
				.findByRole('button', { name: 'Close' })
				.click()

			cy.findByRole('dialog', { name: 'Terms of service' })
				.should('not.exist')
		})
	})
})
