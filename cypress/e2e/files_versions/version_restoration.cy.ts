/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { navigateToFolder } from '../files/FilesUtils.ts'
import { assertVersionContent, doesNotHaveAction, openVersionsPanel, restoreVersion, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions restoration', () => {
	let randomFileName = ''
	let user: User

	beforeEach(() => {
		randomFileName = randomString(10) + '.txt'

		cy.createRandomUser()
			.then((_user) => {
				user = _user
				cy.mkdir(_user, '/share')
				uploadThreeVersions(user, `share/${randomFileName}`)
				cy.login(user)
				cy.visit('/apps/files')
			})
	})

	it('Restores initial version', () => {
		navigateToFolder('share')
		openVersionsPanel(randomFileName)
		// Current version does not have restore action
		doesNotHaveAction(0, 'restore')
		restoreVersion(2)

		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})

		// Downloads versions and assert there content
		assertVersionContent(0, 'v1')
		assertVersionContent(1, 'v3')
		assertVersionContent(2, 'v2')
	})

	it('Restore versions of shared file with update permission', () => {
		setupTestSharedFileFromUser(user, 'share', { update: true })
		navigateToFolder('share')
		openVersionsPanel(randomFileName)

		restoreVersion(2)
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})
		assertVersionContent(0, 'v1')
		assertVersionContent(1, 'v3')
		assertVersionContent(2, 'v2')
	})

	it('Does not show action without delete permission', () => {
		setupTestSharedFileFromUser(user, 'share', { update: false })
		navigateToFolder('share')
		openVersionsPanel(randomFileName)

		cy.get('[data-files-versions-version]').eq(0).find('.action-item__menutoggle').should('not.exist')
		cy.get('[data-files-versions-version]').eq(0).get('[data-cy-version-action="restore"]').should('not.exist')

		doesNotHaveAction(1, 'restore')
		doesNotHaveAction(2, 'restore')
	})
})
