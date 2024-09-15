/**
 * SPDX-License: AGPL-3.0-or-later
 * SPDX-: Nextcloud GmbH and Nextcloud contributors
 */

import type { User } from '@nextcloud/cypress'
import { ShareType } from '@nextcloud/sharing'

describe('Disable download button if forbidden', { testIsolation: true }, () => {
	let sharee: User

	before(() => {
		cy.createRandomUser().then((user) => { sharee = user })
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.createFolder(user, '/Photos')
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg', '/Photos/image1.jpg')

			cy.login(user)
			cy.createShare('/Photos',
				{ shareWith: sharee.userId, shareType: ShareType.User, attributes: [{ scope: 'permissions', key: 'download', value: false }] },
			)
			cy.logout()
		})
	})

	beforeEach(() => {
		cy.login(sharee)
		cy.visit('/apps/files')
		cy.openFile('Photos')
	})

	it('See the shared folder and images in files list', () => {
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
	})

	// TODO: Fix no-download files on server
	it.skip('See the image can be shown', () => {
		cy.getFile('image1.jpg').should('be.visible')
		cy.openFile('image1.jpg')
		cy.get('body > .viewer').should('be.visible')

		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('See the title on the viewer header but not the Download nor the menu button', () => {
		cy.getFile('image1.jpg').should('be.visible')
		cy.openFile('image1.jpg')
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')

		cy.get('[role="dialog"]')
			.should('be.visible')
			.find('button[aria-label="Actions"]')
			.click()

		cy.get('[role="menu"]:visible')
			.find('button')
			.should('have.length', 2)
			.each(($el) => {
				expect($el.text()).to.match(/(Full screen|Open sidebar)/i)
			})
	})
})
