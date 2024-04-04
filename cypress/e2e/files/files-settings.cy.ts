import type { User } from '@nextcloud/cypress'
import { getRowForFile } from './FilesUtils'

const showHiddenFiles = () => {
	// Open the files settings
	cy.get('[data-cy-files-navigation-settings-button] a').click({ force: true })
	// Toggle the hidden files setting
	cy.get('[data-cy-files-settings-setting="show_hidden"]').within(() => {
		cy.get('input').should('not.be.checked')
		cy.get('input').check({ force: true })
	})
	// Close the dialog
	cy.get('[data-cy-files-navigation-settings] button[aria-label="Close"]').click()
}

describe('files: Hide or show hidden files', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			cy.uploadContent(user, new Blob([]), 'text/plain', '/.file')
			cy.mkdir(user, '/.folder')
			cy.login(user)
		})
	})

	context('view: All files', { testIsolation: false }, () => {
		it('hides dot-files by default', () => {
			cy.visit('/apps/files')

			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
		})
	})

	context('view: Personal files', { testIsolation: false }, () => {
		it('hides dot-files by default', () => {
			cy.visit('/apps/files/personal')

			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
		})
	})

	context('view: Recent files', { testIsolation: false }, () => {
		it('hides dot-files by default', () => {
			// also add hidden file in hidden folder
			cy.uploadContent(user, new Blob([]), 'text/plain', '/.folder/other-file')
			cy.login(user)
			cy.visit('/apps/files/recent')

			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
			getRowForFile('other-file').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
			getRowForFile('other-file').should('be.visible')
		})
	})
})
