/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robbert Gurdeep Singh <git@beardhatcode.be>
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

/**
 * Make a name aimed to break the viewer in case of escaping errors
 *
 * @param {string} realName the file original name
 * @return {string} a name for the file to be uploaded as
 */
function naughtyFileName(realName) {
	const ext = realName.split('.').pop()
	return (
		'~⛰️ shot of a $[big} mountain`, '
		+ "realy #1's "
		+ '" #_+="%2520%27%22%60%25%21%23 was this called '
		+ realName
		+ 'in the'
		+ '☁️'
		+ '👩‍💻'
		+ '? :* .'
		+ ext.toUpperCase()
	)
}

let failsLeft = 5
Cypress.on('fail', (error) => {
	failsLeft--
	throw error // throw error to have test still fail
})

/**
 *
 * @param file
 * @param type
 * @param sidebar
 */
export default function(file, type, sidebar = false) {
	const placedName = naughtyFileName(file)

	const folderName
		= 'Nextcloud "%27%22%60%25%21%23" >`⛰️<' + file + "><` e*'rocks!#?#%~"

	describe(`Open ${file} in viewer with a naughty name ${sidebar ? 'with sidebar' : ''}`, function() {
		before(function() {
			// fail fast
			if (failsLeft < 0) {
				throw new Error('Too many previous tests failed')
			}

			// Init user
			cy.createRandomUser().then(user => {
				// Upload test files
				cy.createFolder(user, `/${folderName}`)
				cy.uploadFile(user, file, type, `/${folderName}/${placedName}`)

				// Visit nextcloud
				cy.login(user)
				cy.visit('/apps/files')
			})

			// wait a bit for things to be settled
			cy.openFile(folderName)
		})

		/**
		 *
		 */
		function noLoadingAnimation() {
			cy.get('body > .viewer', { timeout: 10000 })
				.should('be.visible')
				.and('have.class', 'modal-mask')
				.and('not.have.class', 'icon-loading')
		}

		/**
		 *
		 */
		function menuOk() {
			cy.get('body > .viewer .icon-error').should('not.exist')
			cy.get('body > .viewer .modal-header__name').should('contain', placedName)
			cy.get('body > .viewer .modal-header button.header-close').should(
				'be.visible',
			)
		}

		/**
		 *
		 */
		function arrowsOK() {
			cy.get('body > .viewer button.prev').should('not.be.visible')
			cy.get('body > .viewer button.next').should('not.be.visible')
		}

		it(`See ${file} as ${placedName} in the list`, function() {
			// cy.getFile will escape all the characters in the name to match it with css
			cy.getFile(placedName, { timeout: 10000 })
				.should('contain', placedName.replace(/(.*)\./, '$1 .'))
		})

		it('Open the viewer on file click', function() {
			cy.openFile(placedName)
			cy.get('body > .viewer').should('be.visible')
		})

		it('Does not see a loading animation', noLoadingAnimation)
		it('See the menu icon and title on the viewer header', menuOk)
		it('Does not see navigation arrows', arrowsOK)

		if (sidebar) {
			it('Open the sidebar', function() {
				// open the menu
				cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
				// open the sidebar
				cy.get('.action-button__icon.icon-menu-sidebar').click()
				cy.get('aside.app-sidebar').should('be.visible')
				// we hide the sidebar button if opened
				cy.get('.action-button__icon.icon-menu-sidebar').should('not.exist')
				// check the sidebar is opened for the correct file
				cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', placedName)
				// check we do not have a preview
				cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--with-figure')
				cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--compact')
				cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__figure').should('have.attr', 'style').should('contain', 'core/filetypes')
			})
		}

		it('Share the folder with a share link and access the share link', function() {
			cy.createLinkShare(folderName).then((token) => {
				cy.logout()
				cy.visit(`/s/${token}`)
			})
		})

		it('Open the viewer on file click (public)', function() {
			cy.openFile(placedName)
			cy.get('body > .viewer').should('be.visible')
		})

		it('Does not see a loading animation (public)', noLoadingAnimation)
		it('See the menu icon and title on the viewer header (public)', menuOk)
		it('Does not see navigation arrows (public)', arrowsOK)
	})
}
