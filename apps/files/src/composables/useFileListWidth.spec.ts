/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { configMocks, mockResizeObserver } from 'jsdom-testing-mocks'
import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it } from 'vitest'
import { defineComponent } from 'vue'
import { nextTick } from 'vue'

let resizeObserver: ReturnType<typeof mockResizeObserver>

describe('composable: fileListWidth', () => {
	configMocks({ beforeAll, afterAll, beforeEach, afterEach })

	beforeAll(() => {
		resizeObserver = mockResizeObserver()
	})

	beforeEach(cleanup)

	it('Has initial value', async () => {
		const { component } = await getFileList()
		expect(component.textContent).toBe('600')
	})

	it('observes the file list element', async () => {
		const { fileList } = await getFileList()
		expect(resizeObserver.getObservedElements()).toContain(fileList)
	})

	it('Is reactive to size change', async () => {
		const { component, fileList } = await getFileList()
		expect(component.textContent).toBe('600')
		expect(resizeObserver.getObservedElements()).toHaveLength(1)

		resizeObserver.mockElementSize(fileList, { contentBoxSize: { inlineSize: 800, blockSize: 300 } })
		resizeObserver.resize(fileList)

		// await rending
		await nextTick()
		expect(component.textContent).toBe('800')
	})
})

async function getFileList() {
	const { useFileListWidth } = await import('./useFileListWidth.ts')

	const ComponentMock = defineComponent({
		template: '<div data-testid="component" style="width: 100%;background: white;">{{ fileListWidth }}</div>',
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

	const root = render(FileListMock)
	const fileList = root.baseElement.querySelector('#app-content-vue') as HTMLElement

	// mock initial size
	resizeObserver.mockElementSize(fileList, { contentBoxSize: { inlineSize: 600, blockSize: 200 } })
	resizeObserver.resize()
	// await rending
	await nextTick()

	return {
		root,
		component: root.getByTestId('component'),
		fileList,
	}
}
