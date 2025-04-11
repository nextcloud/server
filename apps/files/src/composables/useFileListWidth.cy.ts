/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineComponent } from 'vue'
import { useFileListWidth } from './useFileListWidth.ts'

const ComponentMock = defineComponent({
	template: '<div id="test-component" style="width: 100%;background: white;">{{ fileListWidth }}</div>',
	setup() {
		return {
			fileListWidth: useFileListWidth(),
		}
	},
})
const FileListMock = defineComponent({
	template: '<main id="app-content-vue" style="width: 100%;"><component-mock /></main>',
	components: {
		ComponentMock,
	},
})

describe('composable: fileListWidth', () => {

	it('Has initial value', () => {
		cy.viewport(600, 400)

		cy.mount(FileListMock, {})
		cy.get('#app-content-vue')
			.should('be.visible')
			.and('contain.text', '600')
	})

	it('Is reactive to size change', () => {
		cy.viewport(600, 400)
		cy.mount(FileListMock)
		cy.get('#app-content-vue').should('contain.text', '600')

		cy.viewport(800, 400)
		cy.screenshot()
		cy.get('#app-content-vue').should('contain.text', '800')
	})

	it('Is reactive to style changes', () => {
		cy.viewport(600, 400)
		cy.mount(FileListMock)
		cy.get('#app-content-vue')
			.should('be.visible')
			.and('contain.text', '600')
			.invoke('attr', 'style', 'width: 100px')

		cy.get('#app-content-vue')
			.should('contain.text', '100')
	})
})
