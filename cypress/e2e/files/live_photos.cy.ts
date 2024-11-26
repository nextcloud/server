/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import {
	clickOnBreadcrumbs,
	copyFile,
	createFolder,
	getRowForFile,
	getRowForFileId,
	moveFile,
	navigateToFolder,
	renameFile,
	triggerActionForFile,
	triggerInlineActionForFileId,
} from './FilesUtils'
import { setShowHiddenFiles, setupLivePhotos } from './LivePhotosUtils'

describe('Files: Live photos', { testIsolation: true }, () => {
	let user: User
	let randomFileName: string
	let jpgFileId: number
	let movFileId: number

	beforeEach(() => {
		setupLivePhotos()
			.then((setupInfo) => {
				user = setupInfo.user
				randomFileName = setupInfo.fileName
				jpgFileId = setupInfo.jpgFileId
				movFileId = setupInfo.movFileId
			})
	})

	it('Only renders the .jpg file', () => {
		getRowForFileId(jpgFileId).should('have.length', 1)
		getRowForFileId(movFileId).should('have.length', 0)
	})

	context("'Show hidden files' is enabled", () => {
		beforeEach(() => {
			setShowHiddenFiles(true)
		})

		it("Shows both files when 'Show hidden files' is enabled", () => {
			getRowForFileId(jpgFileId).should('have.length', 1).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}.jpg`)
			getRowForFileId(movFileId).should('have.length', 1).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}.mov`)
		})

		it('Copies both files when copying the .jpg', () => {
			copyFile(`${randomFileName}.jpg`, '.')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).mov`).should('have.length', 1)
		})

		it('Copies both files when copying the .mov', () => {
			copyFile(`${randomFileName}.mov`, '.')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName} (copy).mov`).should('have.length', 1)
		})

		it('Keeps live photo link when copying folder', () => {
			createFolder('folder')
			moveFile(`${randomFileName}.jpg`, 'folder')
			copyFile('folder', '.')
			navigateToFolder('folder (copy)')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)

			setShowHiddenFiles(false)

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)
		})

		it('Block copying live photo in a folder containing a mov file with the same name', () => {
			createFolder('folder')
			cy.uploadContent(user, new Blob(['mov file'], { type: 'video/mov' }), 'video/mov', `/folder/${randomFileName}.mov`)
			cy.login(user)
			cy.visit('/apps/files')
			copyFile(`${randomFileName}.jpg`, 'folder')
			navigateToFolder('folder')

			cy.get('[data-cy-files-list-row-fileid]').should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName} (copy).jpg`).should('have.length', 0)
		})

		it('Moves files when moving the .jpg', () => {
			renameFile(`${randomFileName}.jpg`, `${randomFileName}_moved.jpg`)
			clickOnBreadcrumbs('All files')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.jpg`)
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.mov`)
		})

		it('Moves files when moving the .mov', () => {
			renameFile(`${randomFileName}.mov`, `${randomFileName}_moved.mov`)
			clickOnBreadcrumbs('All files')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.jpg`)
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('equal', `${randomFileName}_moved.mov`)
		})

		it('Deletes files when deleting the .jpg', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)

			cy.visit('/apps/files/trashbin')

			getRowForFileId(jpgFileId).invoke('attr', 'data-cy-files-list-row-name').should('to.match', new RegExp(`^${randomFileName}.jpg\\.d[0-9]+$`))
			getRowForFileId(movFileId).invoke('attr', 'data-cy-files-list-row-name').should('to.match', new RegExp(`^${randomFileName}.mov\\.d[0-9]+$`))
		})

		it('Block deletion when deleting the .mov', () => {
			triggerActionForFile(`${randomFileName}.mov`, 'delete')
			clickOnBreadcrumbs('All files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)

			cy.visit('/apps/files/trashbin')

			getRowForFileId(jpgFileId).should('have.length', 0)
			getRowForFileId(movFileId).should('have.length', 0)
		})

		it('Restores files when restoring the .jpg', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			cy.visit('/apps/files/trashbin')
			triggerInlineActionForFileId(jpgFileId, 'restore')
			clickOnBreadcrumbs('Deleted files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)

			cy.visit('/apps/files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 1)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 1)
		})

		it('Blocks restoration when restoring the .mov', () => {
			triggerActionForFile(`${randomFileName}.jpg`, 'delete')
			cy.visit('/apps/files/trashbin')
			triggerInlineActionForFileId(movFileId, 'restore')
			clickOnBreadcrumbs('Deleted files')

			getRowForFileId(jpgFileId).should('have.length', 1)
			getRowForFileId(movFileId).should('have.length', 1)

			cy.visit('/apps/files')

			getRowForFile(`${randomFileName}.jpg`).should('have.length', 0)
			getRowForFile(`${randomFileName}.mov`).should('have.length', 0)
		})
	})
})
