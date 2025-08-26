/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ShareContext } from './PublicShareUtils.ts'
import { createLinkShare, setupData } from './PublicShareUtils.ts'

/**
 * This tests ensures that on public shares the header avatar menu correctly works
 */
describe('files_sharing: Public share - header avatar menu', { testIsolation: true }, () => {
	let context: ShareContext
	let firstPublicShareUrl = ''
	let secondPublicShareUrl = ''

	before(() => {
		cy.createRandomUser()
			.then((user) => {
				context = {
					user,
					url: undefined,
				}
				setupData(context.user, 'public1')
				setupData(context.user, 'public2')
				createLinkShare(context, 'public1').then((shareUrl) => {
					firstPublicShareUrl = shareUrl
					cy.log(`Created first share with URL: ${shareUrl}`)
				})
				createLinkShare(context, 'public2').then((shareUrl) => {
					secondPublicShareUrl = shareUrl
					cy.log(`Created second share with URL: ${shareUrl}`)
				})
			})
	})

	beforeEach(() => {
		cy.logout()
		cy.visit(firstPublicShareUrl)
	})

	it('See the undefined avatar menu', () => {
		cy.get('header')
			.findByRole('navigation', { name: /User menu/i })
			.should('be.visible')
			.findByRole('button', { name: /User menu/i })
			.should('be.visible')
			.click()
		cy.get('#header-menu-public-page-user-menu')
			.as('headerMenu')

		// Note that current guest user is not identified
		cy.get('@headerMenu')
			.should('be.visible')
			.findByRole('note')
			.should('be.visible')
			.should('contain', 'not identified')

		// Button to set guest name
		cy.get('@headerMenu')
			.findByRole('link', { name: /Set public name/i })
			.should('be.visible')
	})

	it('Can set public name', () => {
		cy.get('header')
			.findByRole('navigation', { name: /User menu/i })
			.should('be.visible')
			.findByRole('button', { name: /User menu/i })
			.should('be.visible')
			.as('userMenuButton')

		// Open the user menu
		cy.get('@userMenuButton').click()
		cy.get('#header-menu-public-page-user-menu')
			.as('headerMenu')

		cy.get('@headerMenu')
			.findByRole('link', { name: /Set public name/i })
			.should('be.visible')
			.click()

		// Check the dialog is visible
		cy.findByRole('dialog', { name: /Guest identification/i })
			.should('be.visible')
			.as('guestIdentificationDialog')

		// Check the note is visible
		cy.get('@guestIdentificationDialog')
			.findByRole('note')
			.should('contain', 'not identified')

		// Check the input is visible
		cy.get('@guestIdentificationDialog')
			.findByRole('textbox', { name: /Name/i })
			.should('be.visible')
			.type('{selectAll}John Doe{enter}')

		// Check that the dialog is closed
		cy.get('@guestIdentificationDialog')
			.should('not.exist')

		// Check that the avatar changed
		cy.get('@userMenuButton')
			.find('img')
			.invoke('attr', 'src')
			.should('include', 'avatar/guest/John%20Doe')
	})

	it('Guest name us persistent and can be changed', () => {
		cy.get('header')
			.findByRole('navigation', { name: /User menu/i })
			.should('be.visible')
			.findByRole('button', { name: /User menu/i })
			.should('be.visible')
			.as('userMenuButton')

		// Open the user menu
		cy.get('@userMenuButton').click()
		cy.get('#header-menu-public-page-user-menu')
			.as('headerMenu')

		cy.get('@headerMenu')
			.findByRole('link', { name: /Set public name/i })
			.should('be.visible')
			.click()

		// Check the dialog is visible
		cy.findByRole('dialog', { name: /Guest identification/i })
			.should('be.visible')
			.as('guestIdentificationDialog')

		// Set the name
		cy.get('@guestIdentificationDialog')
			.findByRole('textbox', { name: /Name/i })
			.should('be.visible')
			.type('{selectAll}Jane Doe{enter}')

		// Check that the dialog is closed
		cy.get('@guestIdentificationDialog')
			.should('not.exist')

		// Create another share
		cy.visit(secondPublicShareUrl)

		cy.get('header')
			.findByRole('navigation', { name: /User menu/i })
			.should('be.visible')
			.findByRole('button', { name: /User menu/i })
			.should('be.visible')
			.as('userMenuButton')

		// Open the user menu
		cy.get('@userMenuButton').click()
		cy.get('#header-menu-public-page-user-menu')
			.as('headerMenu')

		// See the note with the current name
		cy.get('@headerMenu')
			.findByRole('note')
			.should('contain', 'You will be identified as Jane Doe')

		cy.get('@headerMenu')
			.findByRole('link', { name: /Change public name/i })
			.should('be.visible')
			.click()

		// Check the dialog is visible
		cy.findByRole('dialog', { name: /Guest identification/i })
			.should('be.visible')
			.as('guestIdentificationDialog')

		// Check that the note states the current name
		// cy.get('@guestIdentificationDialog')
		// 	.findByRole('note')
		// 	.should('contain', 'are currently identified as Jane Doe')

		// Change the name
		cy.get('@guestIdentificationDialog')
			.findByRole('textbox', { name: /Name/i })
			.should('be.visible')
			.type('{selectAll}Foo Bar{enter}')

		// Check that the dialog is closed
		cy.get('@guestIdentificationDialog')
			.should('not.exist')

		// Check that the avatar changed with the second name
		cy.get('@userMenuButton')
			.find('img')
			.invoke('attr', 'src')
			.should('include', 'avatar/guest/Foo%20Bar')
	})
})
