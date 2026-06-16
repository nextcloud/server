/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { assertVersionContent, doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions download', () => {
	let randomFileName = ''
	let user: User

	before(() => cy.runOccCommand('config:app:set --value no core shareapi_allow_view_without_download'))
	after(() => {
		cy.runOccCommand('config:app:delete core shareapi_allow_view_without_download')
	})

	beforeEach(() => {
		randomFileName = randomString(10) + '.txt'

		cy.createRandomUser()
			.then((_user) => {
				user = _user
				uploadThreeVersions(user, randomFileName)
			})
	})

	it('Download versions and assert their content', () => {
		cy.login(user)
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		assertVersionContent(0, 'v3')
		assertVersionContent(1, 'v2')
		assertVersionContent(2, 'v1')
	})

	it('Download versions of shared file with download permission', () => {
		setupTestSharedFileFromUser(user, randomFileName, { download: true })
		openVersionsPanel(randomFileName)

		assertVersionContent(0, 'v3')
		assertVersionContent(1, 'v2')
		assertVersionContent(2, 'v1')
	})

	it('Does not show action without download permission', () => {
		setupTestSharedFileFromUser(user, randomFileName, { download: false })
		openVersionsPanel(randomFileName)

		cy.get('[data-files-versions-version]').eq(0).find('.action-item__menutoggle').should('not.exist')
		cy.get('[data-files-versions-version]').eq(0).get('[data-cy-version-action="download"]').should('not.exist')

		doesNotHaveAction(1, 'download')
		doesNotHaveAction(2, 'download')
	})
})
