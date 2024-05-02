/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { User } from '@nextcloud/cypress'

const admin = new User('admin', 'admin')

const tagName = 'foo'
const updatedTagName = 'bar'

describe('Create system tags', () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin')
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
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin')
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
	before(() => {
		cy.login(admin)
		cy.visit('/settings/admin')
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
