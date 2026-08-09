/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { randomString } from '../../support/utils/randomString.ts'

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
 * Open one of the form's dropdowns and yield an entry of its list box.
 *
 * The list box is only rendered while the dropdown is open, and the dropdown
 * opens on click - focussing alone leaves it closed. Querying the entry by its
 * full selector keeps a list re-render retryable; resolving it from the list
 * box element would bind the assertion to a detached snapshot.
 *
 * @param inputId id of the dropdown's input element
 * @param title the entry's title attribute, omit to yield the list box itself
 * @return the queried entry
 */
function openDropdown(inputId: string, title?: string) {
	cy.get(`input#${inputId}`).click()
	return cy.get(`input#${inputId}`)
		.invoke('attr', 'aria-controls')
		.then((id) => cy.get(title === undefined ? `ul#${id}` : `ul#${id} li span[title="${title}"]`))
}

/**
 * Pick a tag from the "search for a tag to edit" dropdown.
 *
 * @param label the tag's entry as rendered in the list
 */
function selectTag(label: string) {
	openDropdown('system-tags-input', label).click()
}

describe('Create system tags', () => {
	before(() => {
		cy.login(admin)
	})

	// The suite runs with `testIsolation: false`, so a retry would otherwise
	// inherit the half-filled form and the tag the failed attempt created -
	// and fail with 409 on creating it again.
	beforeEach(() => {
		deleteAllTags()
		visitTagSettings()
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
		openDropdown('system-tags-input', tagName)
			.should('have.length', 1)
	})
})

describe('Update system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
	})

	// Rebuild the tag for every attempt: `before()` does not re-run on a retry,
	// so a failed attempt would leave the tag already renamed and the form
	// already holding those values - retyping them emits no PROPPATCH at all
	// and every further attempt fails.
	beforeEach(() => {
		deleteAllTags()
		cy.runOccCommand(`tag:add '${tagName}' public`)
		visitTagSettings()
	})

	it('select the tag', () => {
		selectTag(tagName)
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', tagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').click()
		cy.get('input#system-tag-level').siblings('.vs__selected').contains('Public').should('exist')
	})

	it('update the tag name and level', () => {
		selectTag(tagName)

		cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*').as('updateTag')
		cy.get('input#system-tag-name').clear()
		cy.get('input#system-tag-name').type(updatedTagName)
		cy.get('input#system-tag-name').should('have.value', updatedTagName)
		// select the new tag level
		openDropdown('system-tag-level', 'Invisible').click()
		// submit the form
		cy.get('input#system-tag-name').type('{enter}')
		// wait for the tag to be updated
		cy.wait('@updateTag').its('response.statusCode').should('eq', 207)

		// see that the updated tag is in the list
		openDropdown('system-tags-input', `${updatedTagName} (invisible)`)
			.should('have.length', 1)
	})
})

describe('Delete system tags', { testIsolation: false }, () => {
	before(() => {
		cy.login(admin)
	})

	// Same as above: the delete below removes the tag, so every attempt needs
	// its own one to operate on.
	beforeEach(() => {
		deleteAllTags()
		cy.runOccCommand(`tag:add '${updatedTagName}' invisible`)
		visitTagSettings()
	})

	it('select the tag', () => {
		selectTag(`${updatedTagName} (invisible)`)
		// see that the tag name matches the selected tag
		cy.get('input#system-tag-name').should('exist').and('have.value', updatedTagName)
		// see that the tag level matches the selected tag
		cy.get('input#system-tag-level').focus()
		cy.get('input#system-tag-level').siblings('.vs__selected').contains('Invisible').should('exist')
	})

	it('can delete the tag', () => {
		selectTag(`${updatedTagName} (invisible)`)

		cy.intercept('DELETE', '/remote.php/dav/systemtags/*').as('deleteTag')
		cy.get('.system-tag-form__row').within(() => {
			cy.contains('button', 'Delete').should('be.enabled').click()
		})
		// wait for the tag to be deleted
		cy.wait('@deleteTag').its('response.statusCode').should('eq', 204)

		// see that the deleted tag is gone from the list
		openDropdown('system-tags-input', updatedTagName)
			.should('not.exist')
	})
})
