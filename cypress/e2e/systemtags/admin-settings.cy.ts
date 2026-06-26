/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'

const admin = new User('admin', 'admin')

const tagName = 'foo'
const updatedTagName = 'bar'

function openTagSelector() {
	cy.findByRole('combobox', { name: 'Search for a tag to edit' }).click()
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
		cy.visit('/settings/admin')
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
		openTagSelector()
		cy.findByRole('option', { name: tagName }).should('be.visible')
	})
})

describe('Update system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin')
	})

	it('select the tag', () => {
		openTagSelector()
		cy.findByRole('option', { name: tagName }).click()
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', tagName)
		// see that the tag level matches the selected tag
		cy.get('.system-tag-form__group:has(#system-tag-level) .vs__selected')
			.should('contain.text', 'Public')
	})

	it('update the tag name and level', () => {
		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*').as('updateTag')
		cy.get('input#system-tag-name').clear()
		cy.get('input#system-tag-name').type(updatedTagName)
		cy.get('input#system-tag-name').should('have.value', updatedTagName)
		// select the new tag level
		cy.get('#system-tag-level').click()
		cy.findByRole('option', { name: 'Invisible' }).click()
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')
		// wait for the tag to be updated
		cy.wait('@updateTag').its('response.statusCode').should('eq', 207)
	})

	it('see the tag was successfully updated', () => {
		openTagSelector()
		// NcEllipsisedOption splits long names across spans, breaking the accessible name,
		// so match on the rendered text content instead of the option's accessible name.
		cy.contains('[role="option"]', `${updatedTagName} (invisible)`).should('be.visible')
	})
})

describe('Delete system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin')
	})

	it('select the tag', () => {
		// select the tag to edit
		openTagSelector()
		cy.contains('[role="option"]', `${updatedTagName} (invisible)`).click()
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', updatedTagName)
		// see that the tag level matches the selected tag
		cy.get('.system-tag-form__group:has(#system-tag-level) .vs__selected')
			.should('contain.text', 'Invisible')
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
		openTagSelector()
		cy.findByRole('option', { name: updatedTagName }).should('not.exist')
	})
})
