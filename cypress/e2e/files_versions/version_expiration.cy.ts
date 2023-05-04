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
	let randomFileName = ''

	beforeEach(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'

		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Expire all versions', () => {
		cy.runOccCommand('config:system:set versions_retention_obligation --value "0, 0"')
		cy.runOccCommand('versions:expire')
		cy.runOccCommand('config:system:set versions_retention_obligation --value auto')
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 1)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
		})

		assertVersionContent(randomFileName, 0, 'v3')
	})

	it('Expire versions v2', () => {
		nameVersion(2, 'v1')

		cy.runOccCommand('config:system:set versions_retention_obligation --value "0, 0"')
		cy.runOccCommand('versions:expire')
		cy.runOccCommand('config:system:set versions_retention_obligation --value auto')
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 2)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(1).contains('v1')
		})

		assertVersionContent(randomFileName, 0, 'v3')
		assertVersionContent(randomFileName, 1, 'v1')
	})
})
