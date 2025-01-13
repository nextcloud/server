/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Navigation } from '@nextcloud/files'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import { createTestingPinia } from '@pinia/testing'

import NavigationView from './Navigation.vue'
import { useViewConfigStore } from '../store/viewConfig'
import { Folder, View, getNavigation } from '@nextcloud/files'

import router from '../router/router'

const resetNavigation = () => {
	const nav = getNavigation()
	;[...nav.views].forEach(({ id }) => nav.remove(id))
	nav.setActive(null)
}

const createView = (id: string, name: string, parent?: string) => new View({
	id,
	name,
	getContents: async () => ({ folder: {} as Folder, contents: [] }),
	icon: FolderSvg,
	order: 1,
	parent,
})

describe('Navigation renders', () => {
	before(() => {
		delete window._nc_navigation

		cy.mockInitialState('files', 'storageStats', {
			used: 1000 * 1000 * 1000,
			quota: -1,
		})
	})

	after(() => cy.unmockInitialState())

	it('renders', () => {
		cy.mount(NavigationView, {
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-button]').should('be.visible')
	})
})

describe('Navigation API', () => {
	let Navigation: Navigation

	before(async () => {
		delete window._nc_navigation
		Navigation = getNavigation()

		await router.replace({ name: 'filelist', params: { view: 'files' } })
	})

	beforeEach(() => resetNavigation())

	it('Check API entries rendering', () => {
		Navigation.register(createView('files', 'Files'))
		console.warn(Navigation.views)

		cy.mount(NavigationView, {
			router,
			global: {
				plugins: [
					createTestingPinia({
						createSpy: cy.spy,
					}),
				],
			},
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 1)
		cy.get('[data-cy-files-navigation-item="files"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="files"]').should('contain.text', 'Files')
	})

	it('Adds a new entry and render', () => {
		Navigation.register(createView('files', 'Files'))
		Navigation.register(createView('sharing', 'Sharing'))

		cy.mount(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 2)
		cy.get('[data-cy-files-navigation-item="sharing"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="sharing"]').should('contain.text', 'Sharing')
	})

	it('Adds a new children, render and open menu', () => {
		Navigation.register(createView('files', 'Files'))
		Navigation.register(createView('sharing', 'Sharing'))
		Navigation.register(createView('sharingin', 'Shared with me', 'sharing'))

		cy.mount(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.wrap(useViewConfigStore()).as('viewConfigStore')

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 3)

		// Toggle the sharing entry children
		cy.get('[data-cy-files-navigation-item="sharing"] button.icon-collapse').should('exist')
		cy.get('[data-cy-files-navigation-item="sharing"] button.icon-collapse').click({ force: true })

		// Expect store update to be called
		cy.get('@viewConfigStore').its('update').should('have.been.calledWith', 'sharing', 'expanded', true)

		// Validate children
		cy.get('[data-cy-files-navigation-item="sharingin"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="sharingin"]').should('contain.text', 'Shared with me')

		// Toggle the sharing entry children 🇦again
		cy.get('[data-cy-files-navigation-item="sharing"] button.icon-collapse').click({ force: true })
		cy.get('[data-cy-files-navigation-item="sharingin"]').should('not.be.visible')

		// Expect store update to be called
		cy.get('@viewConfigStore').its('update').should('have.been.calledWith', 'sharing', 'expanded', false)
	})

	it('Throws when adding a duplicate entry', () => {
		Navigation.register(createView('files', 'Files'))
		expect(() => Navigation.register(createView('files', 'Files')))
			.to.throw('View id files is already registered')
	})
})

describe('Quota rendering', () => {
	before(() => {
		delete window._nc_navigation
	})

	afterEach(() => cy.unmockInitialState())

	it('Unknown quota', () => {
		cy.mount(NavigationView, {
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('not.exist')
	})

	it('Unlimited quota', () => {
		cy.mockInitialState('files', 'storageStats', {
			used: 1024 * 1024 * 1024,
			quota: -1,
		})

		cy.mount(NavigationView, {
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('contain.text', '1 GB used')
		cy.get('[data-cy-files-navigation-settings-quota] progress').should('not.exist')
	})

	it('Non-reached quota', () => {
		cy.mockInitialState('files', 'storageStats', {
			used: 1024 * 1024 * 1024,
			quota: 5 * 1024 * 1024 * 1024,
			relative: 20, // percent
		})

		cy.mount(NavigationView, {
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('contain.text', '1 GB of 5 GB used')
		cy.get('[data-cy-files-navigation-settings-quota] progress')
			.should('exist')
			.and('have.attr', 'value', '20')
	})

	it('Reached quota', () => {
		cy.mockInitialState('files', 'storageStats', {
			used: 5 * 1024 * 1024 * 1024,
			quota: 1024 * 1024 * 1024,
			relative: 500, // percent
		})

		cy.mount(NavigationView, {
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('contain.text', '5 GB of 1 GB used')
		cy.get('[data-cy-files-navigation-settings-quota] progress')
			.should('exist')
			.and('have.attr', 'value', '100') // progress max is 100
	})
})
