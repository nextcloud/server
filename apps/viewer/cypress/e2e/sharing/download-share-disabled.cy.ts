/**
 * @copyright Copyright (c) 2020 Florent Fayolle <florent@zeteo.me>
 *
 * @author Florent Fayolle <florent@zeteo.me>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

const fileName = 'image1.jpg'

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.createFolder(user, '/Photos')
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg', '/Photos/image1.jpg')
			cy.uploadFile(user, 'image2.jpg', 'image/jpeg', '/Photos/image2.jpg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	it('See the default files list', function() {
		cy.getFile('welcome.txt').should('contain', 'welcome .txt')
		cy.getFile('Photos').should('contain', 'Photos')
	})

	it('See shared files in the list', function() {
		cy.openFile('Photos')
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
		cy.getFile('image2.jpg', { timeout: 10000 })
			.should('contain', 'image2 .jpg')
	})

	it('Share the Photos folder with a share link, disable download and access the share link', function() {
		cy.on('uncaught:exception', (err) => {
			// This can happen because of blink engine handling animation, its not a bug just engine related.
			if (err.message.includes('ResizeObserver loop limit exceeded')) {
			  return false
			}
		})

		cy.createLinkShare('/Photos').then((token: string) => {
			cy.intercept('GET', '**/apps/files_sharing/api/v1/shares*').as('sharingAPI')

			// Open the sidebar from the breadcrumbs
			cy.get('[data-cy-files-content-breadcrumbs] .files-list__header-share-button').click()
			cy.get('aside.app-sidebar').should('be.visible')

			// Wait for the sidebar to be done loading
			cy.wait('@sharingAPI', { timeout: 10000 })

			// Open the share menu
			cy.get('.sharing-link-list > .sharing-entry button[aria-label*="Actions for "]').click()
			cy.get('.action-button:contains(\'Customize link\')').click()
			cy.get('.checkbox-radio-switch-checkbox').contains('Hide download').as('hideDownloadBtn')
			// click the label
			cy.get('@hideDownloadBtn').get('span').contains('Hide download').click()
			cy.get('@hideDownloadBtn').get('input[type=checkbox]').should('be.checked')

			cy.intercept('PUT', '/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('updateShare')
			cy.contains('button', 'Update share').click()
			cy.wait('@updateShare')

			// Log out and access link share
			cy.logout()
			cy.visit(`/s/${token}`)
		})
	})

	it('See only view action', () => {
		for (const file of ['image1.jpg', 'image2.jpg']) {
			cy.get(`[data-cy-files-list-row-name="${CSS.escape(file)}"]`)
				.find('[data-cy-files-list-row-actions]')
				.find('button')
				.click()
			// Only view action
			cy.get('[role="menu"]:visible')
				.find('button')
				.should('have.length', 1)
				.first()
				.should('contain.text', 'View')
			cy.get(`[data-cy-files-list-row-name="${CSS.escape(file)}"]`)
				.find('[data-cy-files-list-row-actions]')
				.find('button')
				.click()
		}
	})

	it('Open the viewer on file click', function() {
		cy.openFile('image1.jpg')
		cy.get('body > .viewer').should('be.visible')
	})

	// TODO: FIX DOWNLOAD DISABLED SHARES
	it.skip('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	// TODO: FIX DOWNLOAD DISABLED SHARES
	it.skip('See the title on the viewer header but not the Download nor the menu button', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')
		cy.get('body a[download="image1.jpg"]').should('not.exist')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('not.exist')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

})
