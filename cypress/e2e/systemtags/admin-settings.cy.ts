/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'

const admin = new User('admin', 'admin')

// Unique per run so left-overs of an earlier run cannot satisfy - or collide
// with - the assertions below.
const tagName = `tag-${randomString(8)}`
const updatedTagName = `tag-${randomString(8)}`

/**
 * Remove every system tag, so the dropdown only ever contains what a test made.
 */
function deleteAllTags() {
	cy.runOccCommand('tag:list --output=json').then((output) => {
		Object.keys(JSON.parse(output.stdout)).forEach((id) => {
			cy.runOccCommand(`tag:delete ${id}`)
		})
	})
}

/**
 * Open the admin settings with the tag list already fetched.
 *
 * The section loads its tags asynchronously after mount, so opening the tag
 * dropdown before that response arrives yields an empty list.
 */
function visitTagSettings() {
	cy.intercept('PROPFIND', '**/dav/systemtags').as('fetchTags')
	cy.visit('/settings/admin')
	cy.wait('@fetchTags')
}

/**
 * Open one of the form's dropdowns and yield its list box.
 *
 * The list box is only rendered while the dropdown is open, and the dropdown
 * opens on click - focussing alone leaves it closed.
 *
 * @param inputId id of the dropdown's input element
 * @return the open list box
 */
function openDropdown(inputId: string) {
	cy.get(`input#${inputId}`).click()
	return cy.get(`input#${inputId}`)
		.invoke('attr', 'aria-controls')
		.then((id) => cy.get(`ul#${id}`).should('be.visible'))
}

describe('Create system tags', () => {
	before(() => {
		cy.login(admin)
	})

	// Reset both browser and server state for every attempt: the suite runs
	// with `testIsolation: false`, so a retry would otherwise inherit the
	// half-filled form and the already created tag of the attempt that just
	// failed - and fail with 409 on creating it again.
	beforeEach(() => {
		deleteAllTags()
		visitTagSettings()
	})

	it('Can create a tag', () => {
		cy.get('input#system-tag-name').should('exist').and('have.value', '')
		cy.get('input#system-tag-name').type(tagName)
		cy.get('input#system-tag-name').should('have.value', tagName)
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')

		// see that the created tag is in the list
		cy.get('input#system-tags-input').focus()
		cy.get('input#system-tags-input').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', tagName).should('exist')
				// ensure only one tag exists
				cy.get('li').should('have.length', 1)
			})
		})
	})
})

describe('Update system tags', { testIsolation: false }, () => {
	// Create the tag this block operates on instead of inheriting it from the
	// previous block, so a failure there cannot cascade into these tests.
	before(() => {
		deleteAllTags()
		cy.runOccCommand(`tag:add '${tagName}' public`)
		cy.login(admin)
		visitTagSettings()
	})

	it('select the tag', () => {
		// select the tag to edit
		cy.get('input#system-tags-input').focus()
		cy.get('input#system-tags-input').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', tagName).should('exist').click()
			})
		})
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', tagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').click()
		cy.get('input#system-tag-level').siblings('.vs__selected').contains('Public').should('exist')
	})

	it('update the tag name and level', () => {
		cy.get('input#system-tag-name').clear()
		cy.get('input#system-tag-name').type(updatedTagName)
		cy.get('input#system-tag-name').should('have.value', updatedTagName)
		// select the new tag level
		cy.get('input#system-tag-level').focus()
		cy.get('input#system-tag-level').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', 'Invisible').should('exist').click()
			})
		})
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')
	})

	it('see the tag was successfully updated', () => {
		cy.get('input#system-tags-input').focus()
		cy.get('input#system-tags-input').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', `${updatedTagName} (invisible)`).should('exist')
				// ensure only one tag exists
				cy.get('li').should('have.length', 1)
			})
		})
	})
})

describe('Delete system tags', { testIsolation: false }, () => {
	// Same here: bring the updated tag into existence directly rather than
	// depending on the previous block having produced it.
	before(() => {
		deleteAllTags()
		cy.runOccCommand(`tag:add '${updatedTagName}' invisible`)
		cy.login(admin)
		visitTagSettings()
	})

	it('select the tag', () => {
		// select the tag to edit
		cy.get('input#system-tags-input').focus()
		cy.get('input#system-tags-input').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', `${updatedTagName} (invisible)`).should('exist').click()
			})
		})
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', updatedTagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').focus()
		cy.get('input#system-tag-level').siblings('.vs__selected').contains('Invisible').should('exist')
	})

	it('can delete the tag', () => {
		cy.get('.system-tag-form__row').within(() => {
			cy.contains('button', 'Delete').should('be.enabled').click()
		})
	})

	it('see that the deleted tag is not present', () => {
		cy.get('input#system-tags-input').focus()
		cy.get('input#system-tags-input').invoke('attr', 'aria-controls').then(id => {
			cy.get(`ul#${id}`).within(() => {
				cy.contains('li', updatedTagName).should('not.exist')
			})
		})
	})
})
