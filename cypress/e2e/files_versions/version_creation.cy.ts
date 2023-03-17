/**
 * @copyright Copyright (c) 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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

import { openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils'

describe('Versions creation', () => {
	before(() => {
		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel('test.txt')
			})
	})

	it('Opens the versions panel and sees the versions', () => {
		openVersionsPanel('test.txt')

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version')
		})
	})
})
