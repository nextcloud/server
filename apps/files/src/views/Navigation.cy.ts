import FolderSvg from '@mdi/svg/svg/folder.svg'
import ShareSvg from '@mdi/svg/svg/share-variant.svg'
import { createTestingPinia } from '@pinia/testing'

import { NavigationService } from '../services/Navigation'
import NavigationView from './Navigation.vue'
import router from '../router/router'
import { useViewConfigStore } from '../store/viewConfig'

describe('Navigation renders', () => {
	const Navigation = new NavigationService() as NavigationService

	before(() => {
		cy.mockInitialState('files', 'storageStats', {
			used: 1000 * 1000 * 1000,
			quota: -1,
		})
	})

	after(() => cy.unmockInitialState())

	it('renders', () => {
		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
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
	const Navigation = new NavigationService() as NavigationService

	it('Check API entries rendering', () => {
		Navigation.register({
			id: 'files',
			name: 'Files',
			getContents: () => Promise.resolve(),
			icon: FolderSvg,
			order: 1,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
			router,
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 1)
		cy.get('[data-cy-files-navigation-item="files"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="files"]').should('contain.text', 'Files')
	})

	it('Adds a new entry and render', () => {
		Navigation.register({
			id: 'sharing',
			name: 'Sharing',
			getContents: () => Promise.resolve(),
			icon: ShareSvg,
			order: 2,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
			router,
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 2)
		cy.get('[data-cy-files-navigation-item="sharing"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="sharing"]').should('contain.text', 'Sharing')
	})

	it('Adds a new children, render and open menu', () => {
		Navigation.register({
			id: 'sharingin',
			name: 'Shared with me',
			getContents: () => Promise.resolve(),
			parent: 'sharing',
			icon: ShareSvg,
			order: 1,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
			router,
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
		expect(() => {
			Navigation.register({
				id: 'files',
				name: 'Files',
				getContents: () => Promise.resolve(),
				icon: FolderSvg,
				order: 1,
			})
		}).to.throw('Navigation id files is already registered')
	})
})

describe('Quota rendering', () => {
	const Navigation = new NavigationService()

	afterEach(() => cy.unmockInitialState())

	it('Unknown quota', () => {
		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
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
			used: 1000 * 1000 * 1000,
			quota: -1,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
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
			used: 1000 * 1000 * 1000,
			quota: 5 * 1000 * 1000 * 1000,
			relative: 20, // percent
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('contain.text', '1 GB of 5 GB used')
		cy.get('[data-cy-files-navigation-settings-quota] progress').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota] progress').should('have.attr', 'value', '20')
	})

	it('Reached quota', () => {
		cy.mockInitialState('files', 'storageStats', {
			used: 5 * 1000 * 1000 * 1000,
			quota: 1000 * 1000 * 1000,
			relative: 500, // percent
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: cy.spy,
				})],
			},
		})

		cy.get('[data-cy-files-navigation-settings-quota]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota]').should('contain.text', '5 GB of 1 GB used')
		cy.get('[data-cy-files-navigation-settings-quota] progress').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-quota] progress').should('have.attr', 'value', '100') // progress max is 100
	})
})
