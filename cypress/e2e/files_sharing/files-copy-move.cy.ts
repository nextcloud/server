/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import {
	copyFile,
	getRowForFile,
	navigateToFolder,
	triggerActionForFile,
} from '../files/FilesUtils.ts'
import { createShare } from './FilesSharingUtils.ts'

const ACTION_COPY_MOVE = 'move-copy'

export function copyFileForbidden(fileName: string, dirPath: string) {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, ACTION_COPY_MOVE)

	cy.get('.file-picker').should('be.visible')

	const directories = dirPath.split('/')
	directories.forEach((directory) => {
		cy.get('.file-picker')
			.find(`[data-filename="${CSS.escape(directory)}"]`)
			.should('be.visible')
			.click()
	})

	// Re-query after possible re-render and assert eventual disabled state
	cy.get('.file-picker')
		.contains('button', `Copy to ${directories.at(-1)}`, { timeout: 10000 })
		.should('be.visible')
		.and('be.disabled')
}

export function moveFileForbidden(fileName: string, dirPath: string) {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, ACTION_COPY_MOVE)

	cy.get('.file-picker').should('be.visible')

	// Avoid stale chained subject when breadcrumb re-renders
	cy.get('.file-picker .breadcrumb')
		.findByRole('button', { name: 'All files' })
		.should('be.visible')
		.click()

	// Re-acquire file picker after navigation click
	cy.get('.file-picker').should('be.visible')

	const directories = dirPath.split('/')
	directories.forEach((directory) => {
		cy.get('.file-picker')
			.find(`[data-filename="${CSS.escape(directory)}"]`)
			.should('be.visible')
			.click()
	})

	// If move is forbidden, the move button should not be present.
	// Use should('not.contain') on the parent to avoid the cy.contains() + should('not.exist')
	// anti-pattern, where cy.contains() must first find the element before the negation can pass,
	// causing unnecessary waits or false failures.
	cy.get('.file-picker', { timeout: 10000 })
		.should('not.contain', `Move to ${directories.at(-1)}`)
}

describe('files_sharing: Move or copy files', { testIsolation: true }, () => {
	let user: User
	let sharee: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
		})
		cy.createRandomUser().then(($user) => {
			sharee = $user
		})
	})

	it('can create a file in a shared folder', () => {
		// share the folder
		cy.mkdir(user, '/folder')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('folder', sharee.userId, { read: true, download: true })
		cy.logout()

		// Now for the sharee
		cy.uploadContent(sharee, new Blob([]), 'text/plain', '/folder/file.txt')
		cy.login(sharee)
		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		navigateToFolder('folder')
		// Content of the shared folder
		getRowForFile('file.txt').should('be.visible')
	})

	it('can copy a file to a shared folder', () => {
		// share the folder
		cy.mkdir(user, '/folder')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('folder', sharee.userId, { read: true, download: true })
		cy.logout()

		// Now for the sharee
		cy.uploadContent(sharee, new Blob([]), 'text/plain', '/file.txt')
		cy.login(sharee)
		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		// copy file to a shared folder
		copyFile('file.txt', 'folder')
		// click on the folder should open it in files
		navigateToFolder('folder')
		// Content of the shared folder
		getRowForFile('file.txt').should('be.visible')
	})

	it('can not copy a file to a shared folder with no create permissions', () => {
		// share the folder
		cy.mkdir(user, '/folder')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('folder', sharee.userId, { read: true, download: true, create: false })
		cy.logout()

		// Now for the sharee
		cy.uploadContent(sharee, new Blob([]), 'text/plain', '/file.txt')
		cy.login(sharee)
		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		copyFileForbidden('file.txt', 'folder')
	})

	it('can not move a file from a shared folder with no delete permissions', () => {
		// share the folder
		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('folder', sharee.userId, { read: true, download: true, delete: false })
		cy.logout()

		// Now for the sharee
		cy.mkdir(sharee, '/folder-own')
		cy.login(sharee)
		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		navigateToFolder('folder')
		getRowForFile('file.txt').should('be.visible')
		moveFileForbidden('file.txt', 'folder-own')
	})
})
