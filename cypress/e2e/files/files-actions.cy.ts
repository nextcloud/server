/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { FileAction } from '@nextcloud/files'

import { getActionButtonForFileId, getActionEntryForFileId, getRowForFile, getSelectionActionButton, getSelectionActionEntry, selectRowForFile, triggerActionForFile, triggerActionForFileId } from './FilesUtils'
import { ACTION_COPY_MOVE } from '../../../apps/files/src/actions/moveOrCopyAction'
import { ACTION_DELETE } from '../../../apps/files/src/actions/deleteAction'
import { ACTION_DETAILS } from '../../../apps/files/src/actions/sidebarAction'
import { ACTION_SHARING_STATUS } from '../../../apps/files_sharing/src/files_actions/sharingStatusAction'

declare global {
    interface Window {
		_nc_fileactions: FileAction[]
	}
}

// Those two arrays doesn't represent the full list of actions
// the goal is to test a few, we're not trying to match the full feature set
const expectedDefaultActionsIDs = [
	ACTION_COPY_MOVE,
	ACTION_DELETE,
	ACTION_DETAILS,
	ACTION_SHARING_STATUS,
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
		})
	})

	it('Show some nested actions', () => {
		const parent = new FileAction({
			id: 'nested-action',
			displayName: () => 'Nested Action',
			exec: cy.spy(), 
			iconSvgInline: () => '<svg></svg>',
		})

		const child1 = new FileAction({
			id: 'nested-child-1',
			displayName: () => 'Nested Child 1',
			exec: cy.spy(),
			iconSvgInline: () => '<svg></svg>',
			parent: 'nested-action',
		})

		const child2 = new FileAction({
			id: 'nested-child-2',
			displayName: () => 'Nested Child 2',
			exec: cy.spy(),
			iconSvgInline: () => '<svg></svg>',
			parent: 'nested-action',
		})

		cy.visit('/apps/files', {
			// Cannot use registerFileAction here
			onBeforeLoad: (win) => {
				if (!win._nc_fileactions) win._nc_fileactions = []
				// Cannot use registerFileAction here
				win._nc_fileactions.push(parent)
				win._nc_fileactions.push(child1)
				win._nc_fileactions.push(child2)
			}
		})

		// Open the menu
		getActionButtonForFileId(fileId).click({ force: true })

		// Check we have the parent action but not the children
		getActionEntryForFileId(fileId, 'nested-action').should('be.visible')
		getActionEntryForFileId(fileId, 'menu-back').should('not.exist')
		getActionEntryForFileId(fileId, 'nested-child-1').should('not.exist')
		getActionEntryForFileId(fileId, 'nested-child-2').should('not.exist')

		// Click on the parent action
		getActionEntryForFileId(fileId, 'nested-action')
			.find('button').last()
			.should('exist').click({ force: true })

		// Check we have the children and the back button but not the parent
		getActionEntryForFileId(fileId, 'nested-action').should('not.exist')
		getActionEntryForFileId(fileId, 'menu-back').should('be.visible')
		getActionEntryForFileId(fileId, 'nested-child-1').should('be.visible')
		getActionEntryForFileId(fileId, 'nested-child-2').should('be.visible')

		// Click on the back button
		getActionEntryForFileId(fileId, 'menu-back')
			.find('button').last()
			.should('exist').click({ force: true })

		// Check we have the parent action but not the children
		getActionEntryForFileId(fileId, 'nested-action').should('be.visible')
		getActionEntryForFileId(fileId, 'menu-back').should('not.exist')
		getActionEntryForFileId(fileId, 'nested-child-1').should('not.exist')
		getActionEntryForFileId(fileId, 'nested-child-2').should('not.exist')
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

	it('Show some nested actions for a selection', () => {
		const parent = new FileAction({
			id: 'nested-action',
			displayName: () => 'Nested Action',
			exec: cy.spy(),
			iconSvgInline: () => '<svg></svg>',
		})

		const child1 = new FileAction({
			id: 'nested-child-1',
			displayName: () => 'Nested Child 1',
			exec: cy.spy(),
			execBatch: cy.spy(),
			iconSvgInline: () => '<svg></svg>',
			parent: 'nested-action',
		})

		const child2 = new FileAction({
			id: 'nested-child-2',
			displayName: () => 'Nested Child 2',
			exec: cy.spy(),
			execBatch: cy.spy(),
			iconSvgInline: () => '<svg></svg>',
			parent: 'nested-action',
		})

		cy.visit('/apps/files', {
			// Cannot use registerFileAction here
			onBeforeLoad: (win) => {
				if (!win._nc_fileactions) win._nc_fileactions = []
				// Cannot use registerFileAction here
				win._nc_fileactions.push(parent)
				win._nc_fileactions.push(child1)
				win._nc_fileactions.push(child2)
			}
		})

		selectRowForFile('image.jpg')

		// Open the menu
		getSelectionActionButton().click({ force: true })

		// Check we have the parent action but not the children
		getSelectionActionEntry('nested-action').should('be.visible')
		getSelectionActionEntry('menu-back').should('not.exist')
		getSelectionActionEntry('nested-child-1').should('not.exist')
		getSelectionActionEntry('nested-child-2').should('not.exist')

		// Click on the parent action
		getSelectionActionEntry('nested-action')
			.find('button').last()
			.should('exist').click({ force: true })

		// Check we have the children and the back button but not the parent
		getSelectionActionEntry('nested-action').should('not.exist')
		getSelectionActionEntry('menu-back').should('be.visible')
		getSelectionActionEntry('nested-child-1').should('be.visible')
		getSelectionActionEntry('nested-child-2').should('be.visible')

		// Click on the back button
		getSelectionActionEntry('menu-back')
			.find('button').last()
			.should('exist').click({ force: true })

		// Check we have the parent action but not the children
		getSelectionActionEntry('nested-action').should('be.visible')
		getSelectionActionEntry('menu-back').should('not.exist')
		getSelectionActionEntry('nested-child-1').should('not.exist')
		getSelectionActionEntry('nested-child-2').should('not.exist')
	})
})
