/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'

const admin = new User('admin', 'admin')

const tagName = 'foo'
const updatedTagName = 'bar'

/**
 * Open the system-tags NcSelect dropdown and return its (visible) menu.
 * Since the Vue 3 migration the dropdown opens on click, not focus, and the
 * options are listed in `.vs__dropdown-menu` rather than an aria-controls list.
 */
function openTagDropdown(): Cypress.Chainable<JQuery<HTMLElement>> {
	cy.get('input#system-tags-input').click({ force: true })
	return cy.get('.vs__dropdown-menu').should('be.visible')
}

describe('Create system tags', () => {
	before(() => {
		// delete any existing tags
		cy.runOccCommand('tag:list --output=json').then((output) => {
			Object.keys(JSON.parse(output.stdout)).forEach((id) => {
				cy.runOccCommand(`tag:delete ${id}`)
			})
		})

		// login as admin and go to admin settings
		cy.login(admin)
		cy.visit('/settings/admin/server')
	})

	it('Can create a tag', () => {
		cy.intercept('POST', '/remote.php/dav/systemtags').as('createTag')
		cy.get('input#system-tag-name').should('exist').and('have.value', '')
		cy.get('input#system-tag-name').type(tagName)
		cy.get('input#system-tag-name').should('have.value', tagName)
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')

		// wait for the tag to be created
		cy.wait('@createTag').its('response.statusCode').should('eq', 201)

		// see that the created tag is in the list
		openTagDropdown().contains('li', tagName).should('be.visible')
	})
})

describe('Update system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin/server')
	})

	it('select the tag', () => {
		openTagDropdown().contains('li', tagName).click({ force: true })
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', tagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').siblings('.vs__selected').should('contain', 'Public')
	})

	it('update the tag name and level', () => {
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*').as('updateTag')
		cy.get('input#system-tag-name').clear()
		cy.get('input#system-tag-name').type(updatedTagName)
		cy.get('input#system-tag-name').should('have.value', updatedTagName)
		// select the new tag level
		cy.get('input#system-tag-level').click({ force: true })
		cy.get('.vs__dropdown-menu').should('be.visible').contains('li', 'Invisible').click({ force: true })
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')
		// wait for the tag to be updated
		cy.wait('@updateTag').its('response.statusCode').should('eq', 207)
	})

	it('see the tag was successfully updated', () => {
		openTagDropdown().contains('li', `${updatedTagName} (invisible)`).should('be.visible')
	})
})

describe('Delete system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin/server')
	})

	it('select the tag', () => {
		// select the tag to edit
		openTagDropdown().contains('li', `${updatedTagName} (invisible)`).click({ force: true })
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', updatedTagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').siblings('.vs__selected').should('contain', 'Invisible')
	})

	it('can delete the tag', () => {
		cy.intercept('DELETE', '/remote.php/dav/systemtags/*').as('deleteTag')
		cy.get('.system-tag-form__row').within(() => {
			cy.contains('button', 'Delete').should('be.enabled').click()
		})
		// wait for the tag to be deleted
		cy.wait('@deleteTag').its('response.statusCode').should('eq', 204)
	})

	it('see that the deleted tag is not present', () => {
		cy.get('input#system-tags-input').click({ force: true })
		cy.get('.vs__dropdown-menu').should('be.visible').should('not.contain', updatedTagName)
	})
})
