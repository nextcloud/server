/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { createShare } from './FilesSharingUtils.ts'
import {
	getRowForFile,
	copyFile,
	navigateToFolder,
	triggerActionForFile,
} from '../files/FilesUtils.ts'

export const copyFileForbidden = (fileName: string, dirPath: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('COPY', /\/(remote|public)\.php\/dav\/files\//).as('copyFile')

		const directories = dirPath.split('/')
		directories.forEach((directory) => {
			// select the folder
			cy.get(`[data-filename="${CSS.escape(directory)}"]`).should('be.visible').click()
		})

		// check copy button
		cy.contains('button', `Copy to ${directories.at(-1)}`).should('be.disabled')
	})
}

export const moveFileForbidden = (fileName: string, dirPath: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/(remote|public)\.php\/dav\/files\//).as('moveFile')

		// select home folder
		cy.get('button[title="Home"]').should('be.visible').click()

		const directories = dirPath.split('/')
		directories.forEach((directory) => {
			// select the folder
			cy.get(`[data-filename="${directory}"]`).should('be.visible').click()
		})

		// click move
		cy.contains('button', `Move to ${directories.at(-1)}`).should('not.exist')
	})
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
