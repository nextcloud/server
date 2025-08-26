/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createTestingPinia } from '@pinia/testing'
import DialogConfirmFileExtension from './DialogConfirmFileExtension.vue'
import { useUserConfigStore } from '../store/userconfig'

describe('DialogConfirmFileExtension', () => {
	it('renders with both extensions', () => {
		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('heading')
			.should('contain.text', 'Change file extension')
		cy.get('@dialog')
			.findByRole('checkbox', { name: /Do not show this dialog again/i })
			.should('exist')
			.and('not.be.checked')
		cy.get('@dialog')
			.findByRole('button', { name: 'Keep .old' })
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Use .new' })
			.should('be.visible')
	})

	it('renders without old extension', () => {
		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Keep without extension' })
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Use .new' })
			.should('be.visible')
	})

	it('renders without new extension', () => {
		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				oldExtension: '.old',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Keep .old' })
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Remove extension' })
			.should('be.visible')
	})

	it('emits correct value on keep old', () => {
		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		}).as('component')

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Keep .old' })
			.click()
		cy.get('@component')
			.its('wrapper')
			.should((wrapper) => expect(wrapper.emitted('close')).to.eql([[false]]))
	})

	it('emits correct value on use new', () => {
		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		}).as('component')

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('button', { name: 'Use .new' })
			.click()
		cy.get('@component')
			.its('wrapper')
			.should((wrapper) => expect(wrapper.emitted('close')).to.eql([[true]]))
	})

	it('updates user config when checking the checkbox', () => {
		const pinia = createTestingPinia({
			createSpy: cy.spy,
		})

		cy.mount(DialogConfirmFileExtension, {
			propsData: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [pinia],
			},
		}).as('component')

		cy.findByRole('dialog')
			.as('dialog')
			.should('be.visible')
		cy.get('@dialog')
			.findByRole('checkbox', { name: /Do not show this dialog again/i })
			.check({ force: true })

		cy.wrap(useUserConfigStore())
			.its('update')
			.should('have.been.calledWith', 'show_dialog_file_extension', false)
	})
})
