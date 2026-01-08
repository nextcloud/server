/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomBytes } from 'crypto'
import { getRowForFile } from '../files/FilesUtils.ts'
import { addTagToFile } from './utils.ts'

describe('Systemtags: Files integration', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('See first assigned tag in the file list', () => {
		const tag = randomBytes(8).toString('base64')
		addTagToFile('file.txt', tag)
		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.findByRole('listitem')
			.should('be.visible')
			.and('contain.text', tag)
	})

	it('See two assigned tags are also shown in the file list', () => {
		const tag1 = randomBytes(5).toString('base64')
		const tag2 = randomBytes(5).toString('base64')
		addTagToFile('file.txt', tag1)
		addTagToFile('file.txt', tag2)
		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.children()
			.should('have.length', 2)
			.should('contain.text', tag1)
			.should('contain.text', tag2)
	})

	it('See three assigned tags result in overflow entry', () => {
		const tag1 = randomBytes(4).toString('base64')
		const tag2 = randomBytes(4).toString('base64')
		const tag3 = randomBytes(4).toString('base64')
		addTagToFile('file.txt', tag1)
		addTagToFile('file.txt', tag2)
		addTagToFile('file.txt', tag3)
		cy.reload()

		getRowForFile('file.txt')
			.findByRole('list', { name: /collaborative tags/i })
			.children()
			.then(($children) => {
				expect($children.length).to.eq(4)
				expect($children.get(0)).be.visible
				expect($children.get(1)).be.visible
				// not visible - just for accessibility
				expect($children.get(2)).not.be.visible
				expect($children.get(3)).not.be.visible
				// Text content
				expect($children.get(1)).contain.text('+2')
				// Remove the '+x' element
				const elements = [$children.get(0), ...$children.get().slice(2)]
					.map((el) => el.innerText.trim())
				expect(elements).to.have.members([tag1, tag2, tag3])
			})
	})
})
