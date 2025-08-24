/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { haveValidity, zipFileContains } from '../../../support/utils/assertions.ts'
import { getShareUrl, setupPublicShare } from './PublicShareUtils.ts'

/**
 * This tests ensures that on public shares the header actions menu correctly works
 */
describe('files_sharing: Public share - header actions menu', { testIsolation: true }, () => {

	before(() => setupPublicShare())
	beforeEach(() => {
		cy.logout()
		cy.visit(getShareUrl())
	})

	it('Can download all files', () => {
		cy.get('header')
			.findByRole('button', { name: 'Download' })
			.should('be.visible')
		cy.get('header')
			.findByRole('button', { name: 'Download' })
			.click()

		// check a file is downloaded
		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/shared.zip`, null, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 30)
			// Check all files are included
			.and(zipFileContains([
				'shared/',
				'shared/foo.txt',
				'shared/subfolder/',
				'shared/subfolder/bar.txt',
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
		cy.findByRole('menuitem', { name: 'Direct link' })
			.should('be.visible')
			.and('have.attr', 'href')
			.then((attribute) => expect(attribute).to.match(new RegExp(`^${Cypress.env('baseUrl')}/public.php/dav/files/.+/?accept=zip$`)))
		// see menu closes on click
		cy.findByRole('menuitem', { name: 'Direct link' })
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
		// see correct button
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
		// see correct button
		cy.findByRole('menuitem', { name: /Add to your/i })
			.should('be.visible')
			.click()
		// see the dialog
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i }).should('be.visible').within(() => {
			cy.findByRole('textbox')
				.type('user@nextcloud.local')
			// intercept request, the request is continued when the promise is resolved
			const { promise, resolve } = Promise.withResolvers()
			cy.intercept('POST', '**/apps/federatedfilesharing/createFederatedShare', (request) => {
				// we need to wait in the onResponse handler as the intercept handler times out otherwise
				request.on('response', async (response) => { await promise; response.statusCode = 503 })
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
		// see correct button
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
		cy.get('header').within(() => {
			cy.findByRole('button', { name: 'Direct link' })
				.should('not.exist')
			cy.findByRole('button', { name: 'Download' })
				.should('not.exist')
			cy.findByRole('button', { name: /Add to your/i })
				.should('not.exist')
			// Open the menu
			cy.findByRole('button', { name: /More actions/i })
				.should('be.visible')
				.click()
		})

		// See correct number of menu item
		cy.findByRole('menu', { name: 'More actions' })
			.findAllByRole('menuitem')
			.should('have.length', 3)
		cy.findByRole('menu', { name: 'More actions' })
			.within(() => {
				// See that download, federated share and direct link are moved to the menu
				cy.findByRole('menuitem', { name: /^Download/ })
					.should('be.visible')
				cy.findByRole('menuitem', { name: /Add to your/i })
					.should('be.visible')
				cy.findByRole('menuitem', { name: 'Direct link' })
					.should('be.visible')

				// See that direct link works
				cy.findByRole('menuitem', { name: 'Direct link' })
					.should('be.visible')
					.and('have.attr', 'href')
					.then((attribute) => expect(attribute).to.match(new RegExp(`^${Cypress.env('baseUrl')}/public.php/dav/files/.+/?accept=zip$`)))
				// See remote share works
				cy.findByRole('menuitem', { name: /Add to your/i })
					.should('be.visible')
					.click()
			})
		cy.findByRole('dialog', { name: /Add to your Nextcloud/i }).should('be.visible')
	})
})
