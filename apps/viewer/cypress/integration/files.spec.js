/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

describe('Files default view', function() {
	before(function() {
		cy.login('admin', 'admin')
	})
	after(function() {
		cy.logout()
	})

	it('See the default files list', function() {
		cy.get('#fileList tr').should('contain', 'welcome.txt')
	})

	it('Take screenshot', function() {
		cy.screenshot()
	})

	it('Capture the app viewer version', function() {
		cy.visit('/settings/apps/enabled/viewer')
		cy.get('#app-sidebar > div > h2').should('contain', 'Viewer')
		cy.screenshot()
	})
})
