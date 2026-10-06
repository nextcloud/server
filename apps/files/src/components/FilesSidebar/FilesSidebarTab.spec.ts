/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, ISidebarTab, IView } from '@nextcloud/files'
import type { ISidebarDataProvider } from '../../sidebar/types.ts'

import { File } from '@nextcloud/files'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import { isProxy, ref, shallowRef } from 'vue'
import FilesSidebarTab from './FilesSidebarTab.vue'
import { resetSidebarDataProvider, setSidebarDataProvider } from '../../sidebar/provider.ts'
import { useSidebarStore } from '../../store/sidebar.ts'

vi.mock('@nextcloud/dialogs')

const tagName = 'test-sidebar-tab'

class TestSidebarTab extends HTMLElement {
	node?: INode
	folder?: IFolder
}
window.customElements.define(tagName, TestSidebarTab)

const tab: ISidebarTab = {
	id: 'test',
	order: 10,
	displayName: 'Test',
	iconSvgInline: '<svg></svg>',
	tagName,
}

const node = new File({
	id: 1,
	source: 'https://cloud.example.com/remote.php/dav/files/test/file.txt',
	owner: 'test',
	mime: 'text/plain',
	root: '/files/test',
})

function buildProvider(): ISidebarDataProvider {
	// Like the files store, a deep ref hands out a reactive proxy of the node
	const current = ref<INode>()
	return {
		node: current,
		folder: shallowRef<IFolder>(),
		view: shallowRef<IView>(),
		setNode(newNode?: INode) {
			current.value = newNode
		},
	}
}

describe('FilesSidebarTab', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		setSidebarDataProvider(buildProvider())
	})

	afterEach(() => {
		resetSidebarDataProvider()
	})

	test('hands a plain clone of the current node to the tab element', async () => {
		// Browsers refuse to clone reactive proxies, which jsdom does not emulate
		const structuredClone = vi.spyOn(globalThis, 'structuredClone')
		useSidebarStore().open(node)

		const wrapper = mount(FilesSidebarTab, {
			props: { active: true, tab },
			global: {
				stubs: {
					NcAppSidebarTab: { template: '<div><slot /></div>' },
				},
			},
		})

		await vi.waitFor(() => {
			expect(wrapper.find(tagName).exists()).toBe(true)
		})

		const element = wrapper.find(tagName).element as TestSidebarTab
		expect(isProxy(useSidebarStore().currentContext!.node)).toBe(true)
		expect(structuredClone).toHaveBeenCalled()
		expect(structuredClone.mock.calls.some(([value]) => isProxy(value))).toBe(false)
		expect(isProxy(element.node)).toBe(false)
		expect(element.node).not.toBe(node)
		expect(element.node?.source).toBe(node.source)
	})
})
