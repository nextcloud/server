/* eslint-disable import/first */
import FolderSvg from '@mdi/svg/svg/folder.svg'
import ShareSvg from '@mdi/svg/svg/share-variant.svg'

import NavigationService from '../services/Navigation'
import NavigationView from './Navigation.vue'
import router from '../router/router.js'

const Navigation = new NavigationService()

console.log(FolderSvg)

describe('Navigation renders', () => {
	it('renders', () => {
		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-settings-button]').should('be.visible')
	})
})

describe('Navigation API', () => {
	it('Check API entries rendering', () => {
		Navigation.register({
			id: 'files',
			name: 'Files',
			getFiles: () => [],
			icon: FolderSvg,
			order: 1,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
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
			getFiles: () => [],
			icon: ShareSvg,
			order: 2,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
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
			getFiles: () => [],
			parent: 'sharing',
			icon: ShareSvg,
			order: 1,
		})

		cy.mount(NavigationView, {
			propsData: {
				Navigation,
			},
			router,
		})

		cy.get('[data-cy-files-navigation]').should('be.visible')
		cy.get('[data-cy-files-navigation-item]').should('have.length', 3)

		// Intercept collapse preference request
		cy.intercept('POST', '*/apps/files/api/v1/toggleShowFolder/*', {
			statusCode: 200,
		  }).as('toggleShowFolder')

		// Toggle the sharing entry children
		cy.get('[data-cy-files-navigation-item="sharing"] button.icon-collapse').should('exist')
		cy.get('[data-cy-files-navigation-item="sharing"] button.icon-collapse').click({ force: true })
		cy.wait('@toggleShowFolder')

		// Validate children
		cy.get('[data-cy-files-navigation-item="sharingin"]').should('be.visible')
		cy.get('[data-cy-files-navigation-item="sharingin"]').should('contain.text', 'Shared with me')

	})

	it('Throws when adding a duplicate entry', () => {
		expect(() => {
			Navigation.register({
				id: 'files',
				name: 'Files',
				getFiles: () => [],
				icon: FolderSvg,
				order: 1,
			})
		}).to.throw('Navigation id files is already registered')
	})
})
