/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { haveValidity, zipFileContains } from '../../support/utils/assertions.ts'
import { openSharingPanel } from './FilesSharingUtils.ts'
// @ts-expect-error The package is currently broken - but works...
import { deleteDownloadsFolderBeforeEach } from 'cypress-delete-downloads-folder'

describe('files_sharing: Public share - header actions menu', { testIsolation: true }, () => {

	let shareUrl: string
	const shareName = 'to be shared'

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
		})
	})

	deleteDownloadsFolderBeforeEach()

	beforeEach(() => {
		cy.logout()
		cy.visit(shareUrl)
	})

	it('Can download all files', () => {
		// Check the button
		cy.get('header')
			.findByRole('button', { name: 'Download all files' })
			.should('be.visible')
		cy.get('header')
			.findByRole('button', { name: 'Download all files' })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/${shareName}.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				`${shareName}/`,
				`${shareName}/foo.txt`,
				`${shareName}/subfolder/`,
				`${shareName}/subfolder/bar.txt`,
			]))
	})

	it('Can copy direct link', () => {
		// Check the button
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.should('be.visible')
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.click()
		// See the menu
		cy.findByRole('menu', { name: /More action/i })
			.should('be.visible')
		// see correct link in item
		cy.findByRole('menuitem', { name: /Direct link/i })
			.should('be.visible')
			.and('have.attr', 'href')
			.then((attribute) => expect(attribute).to.match(/^http:\/\/.+\/download$/))
		// see menu closes on click
		cy.findByRole('menuitem', { name: /Direct link/i })
			.click()
		cy.findByRole('menu', { name: /More actions/i })
			.should('not.exist')
	})

	it('Can create federated share', () => {
		// Check the button
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.should('be.visible')
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.click()
		// See the menu
		cy.findByRole('menu', { name: /More action/i })
			.should('be.visible')
		// see correct item
		cy.findByRole('menuitem', { name: /Add to your/i })
			.should('be.visible')
			.click()
		// see the dialog
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i })
			.should('be.visible')
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i }).within(() => {
			cy.findByRole('textbox')
				.type('user@nextcloud.local')
			// create share
			cy.intercept('POST', '**/apps/federatedfilesharing/createFederatedShare')
				.as('createFederatedShare')
			cy.findByRole('button', { name: 'Create share' })
				.click()
			cy.wait('@createFederatedShare')
		})
	})

	it('Has user feedback while creating federated share', () => {
		// Check the button
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.should('be.visible')
			.click()
		cy.findByRole('menuitem', { name: /Add to your/i })
			.should('be.visible')
			.click()
		// see the dialog
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i }).should('be.visible').within(() => {
			cy.findByRole('textbox')
				.type('user@nextcloud.local')
			// intercept request, the request is continued when the promise is resolved
			const { promise, resolve } = Promise.withResolvers()
			cy.intercept('POST', '**/apps/federatedfilesharing/createFederatedShare', async (req) => {
				await promise
				req.reply({ statusCode: 503 })
			}).as('createFederatedShare')
			// create the share
			cy.findByRole('button', { name: 'Create share' })
				.click()
			// see that while the share is created the button is disabled
			cy.findByRole('button', { name: 'Create share' })
				.should('be.disabled')
				.then(() => {
					// continue the request
					resolve(null)
				})
			cy.wait('@createFederatedShare')
			// see that the button is no longer disabled
			cy.findByRole('button', { name: 'Create share' })
				.should('not.be.disabled')
		})
	})

	it('Has input validation for federated share', () => {
		// Check the button
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.should('be.visible')
			.click()
		// see correct item
		cy.findByRole('menuitem', { name: /Add to your/i })
			.should('be.visible')
			.click()
		// see the dialog
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i }).should('be.visible').within(() => {
			// Check domain only
			cy.findByRole('textbox')
				.type('nextcloud.local')
			cy.findByRole('textbox')
				.should(haveValidity(/user/i))
			// Check no valid domain
			cy.findByRole('textbox')
				.type('{selectAll}user@invalid')
			cy.findByRole('textbox')
				.should(haveValidity(/invalid.+url/i))
		})
	})

	it('See primary action is moved to menu on small screens', () => {
		cy.viewport(490, 490)
		// Check the button does not exist
		cy.get('header')
			.should('be.visible')
			.findByRole('button', { name: 'Download all files' })
			.should('not.exist')
		// Open the menu
		cy.get('header')
			.findByRole('button', { name: /More actions/i })
			.should('be.visible')
			.click()
		// See that the button is located in the menu
		cy.findByRole('menuitem', { name: /Download all files/i })
			.should('be.visible')
		// See all other items are also available
		cy.findByRole('menu', { name: 'More actions' })
			.findAllByRole('menuitem')
			.should('have.length', 3)
		// Click the button to test the download
		cy.findByRole('menuitem', { name: /Download all files/i })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/${shareName}.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				`${shareName}/`,
				`${shareName}/foo.txt`,
				`${shareName}/subfolder/`,
				`${shareName}/subfolder/bar.txt`,
			]))
	})
})
