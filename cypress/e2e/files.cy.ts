/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
describe('Login with a new user and open the files app', function() {
	before(function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	after(function() {
		cy.logout()
	})

	it('See the default file welcome.txt in the files list', function() {
		cy.visit('/apps/files')
		cy.get('[data-cy-files-list] [data-cy-files-list-row-name="welcome.txt"]').should('be.visible')
	})
})
