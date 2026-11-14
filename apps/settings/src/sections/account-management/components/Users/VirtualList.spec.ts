/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h, nextTick, ref } from 'vue'
import VirtualList from './VirtualList.vue'

const footerVisible = ref(false)

vi.mock('@vueuse/core', async (importActual) => ({
	...(await importActual<object>()),
	useElementVisibility: () => footerVisible,
}))

const Row = {
	props: ['user'],
	render() {
		return h('tr', { 'data-row': this.user.id })
	},
}

/**
 * @param count Number of items
 */
function mountList(count: number) {
	return mount(VirtualList, {
		props: {
			dataComponent: Row,
			dataKey: 'id',
			dataSources: Array.from({ length: count }, (_, i) => ({ id: `user-${i}` })),
			itemHeight: 50,
		},
	})
}

describe('VirtualList', () => {
	beforeEach(() => {
		footerVisible.value = false
	})

	it('renders only the first items and pads for the rest', () => {
		const wrapper = mountList(100)

		// No measured height yet, so only the buffer rows are rendered
		expect(wrapper.findAll('[data-row]')).toHaveLength(6)
		expect(wrapper.find('tbody').attributes('style')).toContain('padding-bottom: 4700px')
	})

	it('reports when the end of the list becomes visible', async () => {
		const wrapper = mountList(3)

		footerVisible.value = true
		await nextTick()

		expect(wrapper.emitted('scrollEnd')).toHaveLength(1)
	})

	it('renders the items after scrolling', async () => {
		const wrapper = mountList(100)
		const table = wrapper.find('table')

		table.element.scrollTop = 2000
		await table.trigger('scroll')

		// Items 37 to 42: 3 buffer rows before index 40, 3 after it
		expect(wrapper.findAll('[data-row]').map((row) => row.attributes('data-row'))).toEqual(['user-37', 'user-38', 'user-39', 'user-40', 'user-41', 'user-42'])
	})
})
