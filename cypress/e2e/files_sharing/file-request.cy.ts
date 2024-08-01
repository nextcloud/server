/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { createFolder, getRowForFile, navigateToFolder } from '../files/FilesUtils'
import { createFileRequest, enterGuestName } from './FilesSharingUtils'

describe('Files', { testIsolation: true }, () => {
	let user: User
	let url = ''
	let folderName = 'test-folder'

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
		cy.get('#public-upload .emptycontent').should('be.visible')
		cy.get('#public-upload h2').contains(`Upload files to ${folderName}`)
		cy.get('#public-upload input[type="file"]').as('fileInput').should('exist')

		cy.intercept('PUT', '/public.php/dav/files/*/*').as('uploadFile')

		// Upload a file
		cy.get('@fileInput').selectFile({
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
