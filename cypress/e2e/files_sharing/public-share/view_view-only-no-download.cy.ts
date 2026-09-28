/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getActionButtonForFile, getRowForFile, navigateToFolder } from '../../files/FilesUtils.ts'
import { openSharingPanel } from '../FilesSharingUtils.ts'

describe('files_sharing: Public share - View only', { testIsolation: true }, () => {

	let shareUrl: string
	const shareName = 'shared'

	before(() => {
		cy.createRandomUser().then(($user) => {
			cy.mkdir($user, `/${shareName}`)
			cy.mkdir($user, `/${shareName}/subfolder`)
			cy.uploadContent($user, new Blob([]), 'text/plain', `/${shareName}/foo.txt`)
			cy.uploadContent($user, new Blob([]), 'text/plain', `/${shareName}/subfolder/bar.txt`)
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

			// Update the share to be a view-only-no-download share
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
			cy.get('[data-cy-files-sharing-share-permissions-bundle="read-only"]')
				.click()
			cy.findByRole('checkbox', { name: 'Hide download' })
				.check({ force: true })
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

	it('Can see the files list', () => {
		// foo exists
		getRowForFile('foo.txt')
			.should('be.visible')
	})

	it('But no actions available', () => {
		// foo exists
		getRowForFile('foo.txt')
			.should('be.visible')
		// but no actions
		getActionButtonForFile('foo.txt')
			.should('not.exist')

		// TODO: We really need Viewer in the server repo.
		// So we could at least test viewing images
	})

	it('Can navigate to subfolder', () => {
		getRowForFile('subfolder')
			.should('be.visible')

		navigateToFolder('subfolder')

		getRowForFile('bar.txt')
			.should('be.visible')

		// but also no actions
		getActionButtonForFile('bar.txt')
			.should('not.exist')
	})

	it('Cannot upload files', () => {
		// wait for file list to be ready
		getRowForFile('foo.txt')
			.should('be.visible')
	})
})
