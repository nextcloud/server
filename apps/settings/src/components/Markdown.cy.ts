/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Markdown from './Markdown.vue'

describe('Markdown component', () => {
	it('renders links', () => {
		cy.mount(Markdown, {
			propsData: {
				text: 'This is [a link](http://example.com)!',
			},
		})

		cy.contains('This is')
			.find('a')
			.should('exist')
			.and('have.attr', 'href', 'http://example.com')
			.and('contain.text', 'a link')
	})

	it('renders headings', () => {
		cy.mount(Markdown, {
			propsData: {
				text: '# level 1\nText\n## level 2\nText\n### level 3\nText\n#### level 4\nText\n##### level 5\nText\n###### level 6\nText\n',
			},
		})

		for (let level = 1; level <= 6; level++) {
			cy.contains(`h${level}`, `level ${level}`)
				.should('be.visible')
		}
	})

	it('can limit headings', () => {
		cy.mount(Markdown, {
			propsData: {
				text: '# level 1\nText\n## level 2\nText\n### level 3\nText\n#### level 4\nText\n##### level 5\nText\n###### level 6\nText\n',
				minHeading: 4,
			},
		})

		cy.get('h1').should('not.exist')
		cy.get('h2').should('not.exist')
		cy.get('h3').should('not.exist')
		cy.get('h4')
			.should('exist')
			.and('contain.text', 'level 1')
		cy.get('h5')
			.should('exist')
			.and('contain.text', 'level 2')
		cy.contains('h6', 'level 3').should('exist')
		cy.contains('h6', 'level 4').should('exist')
		cy.contains('h6', 'level 5').should('exist')
		cy.contains('h6', 'level 6').should('exist')
	})
})
