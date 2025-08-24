/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import RemoteShareDialog from './RemoteShareDialog.vue'

describe('RemoteShareDialog', () => {
	it('can be mounted', () => {
		cy.mount(RemoteShareDialog, {
			propsData: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		cy.findByRole('dialog')
			.should('be.visible')
			.and('contain.text', 'user123@nextcloud.local')
			.and('contain.text', 'my-photos')
		cy.findByRole('button', { name: 'Cancel' })
			.should('be.visible')
		cy.findByRole('button', { name: /add remote share/i })
			.should('be.visible')
	})

	it('does not show password input if not enabled', () => {
		cy.mount(RemoteShareDialog, {
			propsData: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		cy.findByRole('dialog')
			.should('be.visible')
			.find('input[type="password"]')
			.should('not.exist')
	})

	it('emits true when accepted', () => {
		const onClose = cy.spy().as('onClose')

		cy.mount(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			propsData: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		cy.findByRole('button', { name: 'Cancel' }).click()
		cy.get('@onClose')
			.should('have.been.calledWith', false)
	})

	it('show password input if needed', () => {
		cy.mount(RemoteShareDialog, {
			propsData: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		cy.findByRole('dialog')
			.should('be.visible')
			.find('input[type="password"]')
			.should('be.visible')
	})

	it('emits the submitted password', () => {
		const onClose = cy.spy().as('onClose')

		cy.mount(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			propsData: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		cy.get('input[type="password"]')
			.type('my password{enter}')
		cy.get('@onClose')
			.should('have.been.calledWith', true, 'my password')
	})

	it('emits no password if cancelled', () => {
		const onClose = cy.spy().as('onClose')

		cy.mount(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			propsData: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		cy.get('input[type="password"]')
			.type('my password')
		cy.findByRole('button', { name: 'Cancel' }).click()
		cy.get('@onClose')
			.should('have.been.calledWith', false)
	})
})
