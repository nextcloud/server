/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { randomString } from '../../support/utils/randomString.ts'
import { openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions creation', () => {
	let randomFileName = ''

	before(() => {
		randomFileName = randomString(10) + '.txt'

		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Opens the versions panel and sees the versions', () => {
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version')
		})
	})

	it('See yourself as version author', () => {
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.findByRole('tabpanel', { name: 'Versions' })
			.findByRole('list', { name: 'File versions' })
			.findAllByRole('listitem')
			.should('have.length', 3)
			.first()
			.find('[data-cy-files-version-author-name]')
			.should('exist')
			.and('contain.text', 'You')
	})
})
