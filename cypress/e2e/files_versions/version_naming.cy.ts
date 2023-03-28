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

import { nameVersion, openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils'

describe('Versions naming', () => {
	let randomFileName = ''

	before(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'

		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Names the initial version as v1', () => {
		nameVersion(2, 'v1')
		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').eq(2).contains('v1')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})
	})

	it('Names the second version as v2', () => {
		nameVersion(1, 'v2')
		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').eq(1).contains('v2')
		})
	})

	it('Names the current version as v3', () => {
		nameVersion(0, 'v3')
		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').eq(0).contains('v3 (Current version)')
		})
	})
})
