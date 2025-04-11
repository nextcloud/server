/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { createFolder, getRowForFile, navigateToFolder } from '../files/FilesUtils'
import { createFileRequest } from './FilesSharingUtils'

const enterGuestName = (name: string) => {
	cy.findByRole('dialog', { name: /Upload files to/ })
		.should('be.visible')
		.within(() => {
			cy.findByRole('textbox', { name: 'Nickname' })
				.should('be.visible')

			cy.findByRole('textbox', { name: 'Nickname' })
				.type(`{selectall}${name}`)

			cy.findByRole('button', { name: 'Submit name' })
				.should('be.visible')
				.click()
		})

	cy.findByRole('dialog', { name: /Upload files to/ })
		.should('not.exist')
}

describe('Files', { testIsolation: true }, () => {
	const folderName = 'test-folder'
	let user: User
	let url = ''

	it('Login with a user and create a file request', () => {
		cy.createRandomUser().then((_user) => {
			user = _user
			cy.login(user)
		})

		cy.visit('/apps/files')
		createFolder(folderName)

		createFileRequest(`/${folderName}`)
		cy.get('@fileRequestUrl').should('contain', '/s/').then((_url: string) => {
			cy.logout()
			url = _url
		})
	})

	it('Open the file request as a guest', () => {
		cy.visit(url)
		enterGuestName('Guest')

		// Check various elements on the page
		cy.contains(`Upload files to ${folderName}`)
			.should('be.visible')
		cy.findByRole('button', { name: 'Upload' })
			.should('be.visible')

		cy.intercept('PUT', '/public.php/dav/files/*/*').as('uploadFile')

		// Upload a file
		cy.get('[data-cy-files-sharing-file-drop] input[type="file"]')
			.should('exist')
			.selectFile({
				contents: Cypress.Buffer.from('abcdef'),
				fileName: 'file.txt',
				mimeType: 'text/plain',
				lastModified: Date.now(),
			}, { force: true })

		cy.wait('@uploadFile').its('response.statusCode').should('eq', 201)
	})

	it('Check the uploaded file', () => {
		cy.login(user)
		cy.visit(`/apps/files/files?dir=/${folderName}`)
		getRowForFile('Guest')
			.should('be.visible')
		navigateToFolder('Guest')
		getRowForFile('file.txt').should('be.visible')
	})
})
