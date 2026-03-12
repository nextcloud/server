/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getActionButtonForFileId, getActionEntryForFileId, getRowForFile, getSelectionActionButton, getSelectionActionEntry, selectRowForFile } from './FilesUtils.ts'

const ACTION_DELETE = 'delete'
const ACTION_COPY_MOVE = 'move-copy'
const ACTION_DETAILS = 'details'

// Those two arrays doesn't represent the full list of actions
// the goal is to test a few, we're not trying to match the full feature set
const expectedDefaultActionsIDs = [
	ACTION_COPY_MOVE,
	ACTION_DELETE,
	ACTION_DETAILS,
]
const expectedDefaultSelectionActionsIDs = [
	ACTION_COPY_MOVE,
	ACTION_DELETE,
]

describe('Files: Actions', { testIsolation: true }, () => {
	let user: User
	let fileId: number = 0

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.uploadContent(user, new Blob([]), 'image/jpeg', '/image.jpg').then((response) => {
			fileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
		})
		cy.login(user)
	}))

	it('Show some standard actions', () => {
		cy.visit('/apps/files')
		getRowForFile('image.jpg').should('be.visible')

		expectedDefaultActionsIDs.forEach((actionId) => {
			// Open the menu
			getActionButtonForFileId(fileId).click({ force: true })
			// Check the action is visible
			getActionEntryForFileId(fileId, actionId).should('be.visible')
			// Close the menu
			cy.get('body').click({ force: true })
		})
	})

	it('Show some actions for a selection', () => {
		cy.visit('/apps/files')
		getRowForFile('image.jpg').should('be.visible')

		selectRowForFile('image.jpg')

		cy.get('[data-cy-files-list-selection-actions]').should('be.visible')
		getSelectionActionButton().should('be.visible')

		// Open the menu
		getSelectionActionButton().click({ force: true })

		// Check the action is visible
		expectedDefaultSelectionActionsIDs.forEach((actionId) => {
			getSelectionActionEntry(actionId).should('be.visible')
		})
	})
})
