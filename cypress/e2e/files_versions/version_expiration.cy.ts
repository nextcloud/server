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

import { assertVersionContent, nameVersion, openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils'

describe('Versions expiration', () => {
	beforeEach(() => {
		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel('test.txt')
			})
	})

	it('Expire all versions', () => {
		cy.runOccCommand('versions:expire')
		cy.visit('/apps/files')
		openVersionsPanel('test.txt')

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 1)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
		})

		assertVersionContent(0, 'v3')
	})

	it('Expire versions v2', () => {
		nameVersion(2, 'v1')

		cy.runOccCommand('versions:expire')
		cy.visit('/apps/files')
		openVersionsPanel('test.txt')

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 2)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(1).contains('v1')
		})

		assertVersionContent(0, 'v3')
		assertVersionContent(1, 'v1')
	})
})
