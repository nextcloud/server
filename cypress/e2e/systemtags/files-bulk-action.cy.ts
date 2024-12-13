/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { randomBytes } from 'crypto'
import { getRowForFile, selectAllFiles, selectRowForFile, triggerSelectionAction } from '../files/FilesUtils'
import { createShare } from '../files_sharing/FilesSharingUtils'

let tags = {} as Record<string, number>
const files = [
	'file1.txt',
	'file2.txt',
	'file3.txt',
	'file4.txt',
	'file5.txt',
]

function resetTags() {
	tags = {}
	for (const tag in [0, 1, 2, 3, 4]) {
		tags[randomBytes(8).toString('base64').slice(0, 6)] = 0
	}

	// delete any existing tags
	cy.runOccCommand('tag:list --output=json').then((output) => {
		Object.keys(JSON.parse(output.stdout)).forEach((id) => {
			cy.runOccCommand(`tag:delete ${id}`)
		})
	})

	// create tags
	Object.keys(tags).forEach((tag) => {
		cy.runOccCommand(`tag:add ${tag} public --output=json`).then((output) => {
			tags[tag] = JSON.parse(output.stdout).id as number
		})
	})
	cy.log('Using tags', tags)
}

function expectInlineTagForFile(file: string, tags: string[]) {
	getRowForFile(file)
		.find('[data-systemtags-fileid]')
		.findAllByRole('listitem')
		.should('have.length', tags.length)
		.each(tag => {
			expect(tag.text()).to.be.oneOf(tags)
		})
}

function triggerTagManagementDialogAction() {
	cy.intercept('PROPFIND', '/remote.php/dav/systemtags/').as('getTagsList')
	triggerSelectionAction('systemtags:bulk')
	cy.wait('@getTagsList')
	cy.get('[data-cy-systemtags-picker]').should('be.visible')
}

describe('Systemtags: Files bulk action', { testIsolation: false }, () => {
	let snapshot: string
	let user1: User
	let user2: User

	before(() => {
		cy.createRandomUser().then((_user1) => {
			user1 = _user1
			cy.createRandomUser().then((_user2) => {
				user2 = _user2
			})

			files.forEach((file) => {
				cy.uploadContent(user1, new Blob([]), 'text/plain', '/' + file)
			})
		})

		resetTags()
	})

	it('Can assign tag to selection', () => {
		cy.login(user1)
		cy.visit('/apps/files')

		files.forEach((file) => {
			getRowForFile(file).should('be.visible')
		})
		selectRowForFile('file2.txt')
		selectRowForFile('file4.txt')

		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

		const tag = Object.keys(tags)[3]
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData')
		cy.wait('@assignTagData')
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file2.txt', [tag])
		expectInlineTagForFile('file4.txt', [tag])
	})

	it('Can assign multiple tags to selection', () => {
		cy.login(user1)
		cy.visit('/apps/files')

		files.forEach((file) => {
			getRowForFile(file).should('be.visible')
		})
		selectAllFiles()

		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

		const prevTag = Object.keys(tags)[3]
		const tag1 = Object.keys(tags)[1]
		const tag2 = Object.keys(tags)[2]
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag1]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag2]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData')
		cy.wait('@assignTagData')
		cy.get('@getTagData.all').should('have.length', 2)
		cy.get('@assignTagData.all').should('have.length', 2)
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file1.txt', [tag1, tag2])
		expectInlineTagForFile('file2.txt', [prevTag, tag1, tag2])
		expectInlineTagForFile('file3.txt', [tag1, tag2])
		expectInlineTagForFile('file4.txt', [prevTag, tag1, tag2])
		expectInlineTagForFile('file5.txt', [tag1, tag2])
	})

	it('Can remove tag from selection', () => {
		cy.login(user1)
		cy.visit('/apps/files')

		files.forEach((file) => {
			getRowForFile(file).should('be.visible')
		})
		selectRowForFile('file1.txt')
		selectRowForFile('file3.txt')
		selectRowForFile('file4.txt')

		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

		const firstTag = Object.keys(tags)[3]
		const tag1 = Object.keys(tags)[1]
		const tag2 = Object.keys(tags)[2]
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag2]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData')
		cy.wait('@assignTagData')
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file1.txt', [tag1])
		expectInlineTagForFile('file2.txt', [firstTag, tag1, tag2])
		expectInlineTagForFile('file3.txt', [tag1])
		expectInlineTagForFile('file4.txt', [firstTag, tag1])
		expectInlineTagForFile('file5.txt', [tag1, tag2])

	})

	it('Can remove multiple tags from selection', () => {
		cy.login(user1)
		cy.visit('/apps/files')

		files.forEach((file) => {
			getRowForFile(file).should('be.visible')
		})
		selectAllFiles()

		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

		cy.get('[data-cy-systemtags-picker-tag] input:indeterminate').should('exist')
			.click({ force: true, multiple: true })
		// indeterminate became checked
		cy.get('[data-cy-systemtags-picker-tag] input:checked').should('exist')
			.click({ force: true, multiple: true })
		// now all are unchecked
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData')
		cy.wait('@assignTagData')
		cy.get('@getTagData.all').should('have.length', 3)
		cy.get('@assignTagData.all').should('have.length', 3)
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file1.txt', [])
		expectInlineTagForFile('file2.txt', [])
		expectInlineTagForFile('file3.txt', [])
		expectInlineTagForFile('file4.txt', [])
		expectInlineTagForFile('file5.txt', [])
	})

	it('Can assign and remove multiple tags as a secondary user', () => {
		// Create new users
		cy.createRandomUser().then((_user1) => {
			user1 = _user1
			cy.createRandomUser().then((_user2) => {
				user2 = _user2
			})

			files.forEach((file) => {
				cy.uploadContent(user1, new Blob([]), 'text/plain', '/' + file)
			})
		})

		cy.login(user1)
		cy.visit('/apps/files')

		files.forEach((file) => {
			getRowForFile(file).should('be.visible')
		})
		selectAllFiles()

		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData1')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData1')

		const tag1 = Object.keys(tags)[0]
		const tag2 = Object.keys(tags)[3]
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag1]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag2]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData1')
		cy.wait('@assignTagData1')
		cy.get('@getTagData1.all').should('have.length', 2)
		cy.get('@assignTagData1.all').should('have.length', 2)
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file1.txt', [tag1, tag2])
		expectInlineTagForFile('file2.txt', [tag1, tag2])
		expectInlineTagForFile('file3.txt', [tag1, tag2])
		expectInlineTagForFile('file4.txt', [tag1, tag2])
		expectInlineTagForFile('file5.txt', [tag1, tag2])

		createShare('file1.txt', user2.userId)
		createShare('file3.txt', user2.userId)

		cy.login(user2)
		cy.visit('/apps/files')

		getRowForFile('file1.txt').should('be.visible')
		getRowForFile('file3.txt').should('be.visible')

		expectInlineTagForFile('file1.txt', [tag1, tag2])
		expectInlineTagForFile('file3.txt', [tag1, tag2])

		selectRowForFile('file1.txt')
		selectRowForFile('file3.txt')
		triggerTagManagementDialogAction()
		cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

		cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData2')
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData2')

		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag1]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get(`[data-cy-systemtags-picker-tag=${tags[tag2]}]`).should('be.visible')
			.findByRole('checkbox').click({ force: true })
		cy.get('[data-cy-systemtags-picker-button-submit]').click()

		cy.wait('@getTagData2')
		cy.wait('@assignTagData2')
		cy.get('@getTagData2.all').should('have.length', 2)
		cy.get('@assignTagData2.all').should('have.length', 2)
		cy.get('[data-cy-systemtags-picker]').should('not.exist')

		expectInlineTagForFile('file1.txt', [])
		expectInlineTagForFile('file3.txt', [])

		cy.login(user1)
		cy.visit('/apps/files')

		expectInlineTagForFile('file1.txt', [])
		expectInlineTagForFile('file3.txt', [])
	})

	it('Can create tag and assign files to it', () => {
		cy.createRandomUser().then((user1) => {
			files.forEach((file) => {
				cy.uploadContent(user1, new Blob([]), 'text/plain', '/' + file)
			})

			cy.login(user1)
			cy.visit('/apps/files')

			files.forEach((file) => {
				getRowForFile(file).should('be.visible')
			})
			selectAllFiles()

			triggerTagManagementDialogAction()
			cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 5)

			cy.intercept('POST', '/remote.php/dav/systemtags').as('createTag')
			cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
			cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

			const newTag = randomBytes(8).toString('base64').slice(0, 6)
			cy.get('[data-cy-systemtags-picker-input]').type(newTag)

			cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 0)
			cy.get('[data-cy-systemtags-picker-button-create]').should('be.visible')
			cy.get('[data-cy-systemtags-picker-button-create]').click()

			cy.wait('@createTag')
			cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 6)
			// Verify the new tag is selected by default
			cy.get('[data-cy-systemtags-picker-tag]').contains(newTag)
				.parents('[data-cy-systemtags-picker-tag]')
				.findByRole('checkbox', { hidden: true }).should('be.checked')

			// Apply changes
			cy.get('[data-cy-systemtags-picker-button-submit]').click()

			cy.wait('@getTagData')
			cy.wait('@assignTagData')
			cy.get('@getTagData.all').should('have.length', 1)
			cy.get('@assignTagData.all').should('have.length', 1)
			cy.get('[data-cy-systemtags-picker]').should('not.exist')

			expectInlineTagForFile('file1.txt', [newTag])
			expectInlineTagForFile('file2.txt', [newTag])
			expectInlineTagForFile('file3.txt', [newTag])
			expectInlineTagForFile('file4.txt', [newTag])
			expectInlineTagForFile('file5.txt', [newTag])
		})
	})
})
