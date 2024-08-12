/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from "@nextcloud/cypress"
import { createShare } from "./FilesSharingUtils.ts"

describe('Limit to sharing to people in the same group', () => {
	let alice: User
	let bob: User
	let randomFileName1 = ''
	let randomFileName2 = ''
	let randomGroupName = ''
	let randomGroupName2 = ''
	let randomGroupName3 = ''

	before(() => {
		randomFileName1 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
		randomFileName2 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
		randomGroupName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
		randomGroupName2 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
		randomGroupName3 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)

		cy.runOccCommand('config:app:set core shareapi_only_share_with_group_members --value yes')

		cy.createRandomUser()
			.then(user => {
				alice = user
				cy.createRandomUser()
			})
			.then(user => {
				bob = user

				cy.runOccCommand(`group:add ${randomGroupName}`)
				cy.runOccCommand(`group:add ${randomGroupName2}`)
				cy.runOccCommand(`group:add ${randomGroupName3}`)
				cy.runOccCommand(`group:adduser ${randomGroupName} ${alice.userId}`)
				cy.runOccCommand(`group:adduser ${randomGroupName} ${bob.userId}`)
				cy.runOccCommand(`group:adduser ${randomGroupName2} ${alice.userId}`)
				cy.runOccCommand(`group:adduser ${randomGroupName2} ${bob.userId}`)
				cy.runOccCommand(`group:adduser ${randomGroupName3} ${bob.userId}`)

				cy.uploadContent(alice, new Blob(['share to bob'], { type: 'text/plain' }), 'text/plain', `/${randomFileName1}`)
				cy.uploadContent(bob, new Blob(['share by bob'], { type: 'text/plain' }), 'text/plain', `/${randomFileName2}`)

				cy.login(alice)
				cy.visit('/apps/files')
				createShare(randomFileName1, bob.userId)
				cy.login(bob)
				cy.visit('/apps/files')
				createShare(randomFileName2, alice.userId)
			})
	})

	after(() => {
		cy.runOccCommand('config:app:set core shareapi_only_share_with_group_members --value no')
	})

	it('Alice can see the shared file', () => {
		cy.login(alice)
		cy.visit('/apps/files')
		cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName2}"]`).should('exist')
	})

	it('Bob can see the shared file', () => {
		cy.login(alice)
		cy.visit('/apps/files')
		cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName1}"]`).should('exist')
	})

	context('Bob is removed from the first group', () => {
		before(() => {
			cy.runOccCommand(`group:removeuser ${randomGroupName} ${bob.userId}`)
		})

		it('Alice can see the shared file', () => {
			cy.login(alice)
			cy.visit('/apps/files')
			cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName2}"]`).should('exist')
		})

		it('Bob can see the shared file', () => {
			cy.login(alice)
			cy.visit('/apps/files')
			cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName1}"]`).should('exist')
		})
	})

	context('Bob is removed from the second group', () => {
		before(() => {
			cy.runOccCommand(`group:removeuser ${randomGroupName2} ${bob.userId}`)
		})

		it('Alice cannot see the shared file', () => {
			cy.login(alice)
			cy.visit('/apps/files')
		cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName2}"]`).should('not.exist')
		})

		it('Bob cannot see the shared file', () => {
			cy.login(alice)
			cy.visit('/apps/files')
		cy.get(`[data-cy-files-list] [data-cy-files-list-row-name="${randomFileName1}"]`).should('not.exist')
		})
	})
})
