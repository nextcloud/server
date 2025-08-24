/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { deselectAllFiles, selectAllFiles, selectRowForFile } from './FilesUtils'

const files = {
	'image.jpg': 'image/jpeg',
	'document.pdf': 'application/pdf',
	'archive.zip': 'application/zip',
	'audio.mp3': 'audio/mpeg',
	'video.mp4': 'video/mp4',
	'readme.md': 'text/markdown',
	'welcome.txt': 'text/plain',
}
const filesCount = Object.keys(files).length

describe('files: Select all files', { testIsolation: true }, () => {
	let user: User

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			Object.keys(files).forEach((file) => {
				cy.uploadContent(user, new Blob(), files[file], '/' + file)
			})
		})
	})

	beforeEach(() => {
		cy.login(user)
		cy.visit('/apps/files')
	})

	it('Can select and unselect all files', () => {
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', filesCount)
		cy.get('[data-cy-files-list-row-checkbox]').should('have.length', filesCount)

		selectAllFiles()

		cy.get('.files-list__selected').should('contain.text', '7 selected')
		cy.get('[data-cy-files-list-row-checkbox]').findByRole('checkbox').should('be.checked')

		deselectAllFiles()

		cy.get('.files-list__selected').should('not.exist')
		cy.get('[data-cy-files-list-row-checkbox]').findByRole('checkbox').should('not.be.checked')
	})

	it('Can select some files randomly', () => {
		const randomFiles = Object.keys(files).reduce((acc, file) => {
			if (Math.random() > 0.1) {
				acc.push(file)
			}
			return acc
		}, [] as string[])

		randomFiles.forEach(name => selectRowForFile(name))

		cy.get('.files-list__selected').should('contain.text', `${randomFiles.length} selected`)
		cy.get('[data-cy-files-list-row-checkbox] input[type="checkbox"]:checked').should('have.length', randomFiles.length)
	})

	it('Can select range of files with shift key', () => {
		cy.get('[data-cy-files-list-row-checkbox]').should('have.length', filesCount)
		selectRowForFile('audio.mp3')
		cy.window().trigger('keydown', { key: 'ShiftLeft', shiftKey: true })
		selectRowForFile('readme.md')
		cy.window().trigger('keyup', { key: 'ShiftLeft', shiftKey: true })

		cy.get('.files-list__selected').should('contain.text', '4 selected')
		cy.get('[data-cy-files-list-row-checkbox] input[type="checkbox"]:checked').should('have.length', 4)

	})
})
