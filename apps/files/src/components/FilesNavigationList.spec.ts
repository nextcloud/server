/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation, View } from '@nextcloud/files'
import { enableAutoDestroy, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, test } from 'vitest'
import { nextTick } from 'vue'
import FilesNavigationList from './FilesNavigationList.vue'

enableAutoDestroy(afterEach)

describe('FilesNavigationList.vue', () => {
	beforeEach(() => {
		const navigation = getNavigation()
		const views = [...navigation.views]
		for (const view of views) {
			navigation.remove(view.id)
		}
	})

	test('views are added reactivly', async () => {
		const navigation = getNavigation()
		const view1 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 1 })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'My View 2', order: 9 })

		navigation.register(view1)

		const wrapper = shallowMount(FilesNavigationList)
		let items = wrapper.findAllComponents({ name: 'FilesNavigationListItem' })
		expect(items).toHaveLength(1)
		expect(items.at(0).props('view').id).toBe('view-1')

		navigation.register(view2)
		await nextTick()

		items = wrapper.findAllComponents({ name: 'FilesNavigationListItem' })
		expect(items).toHaveLength(2)
		expect(items.at(0).props('view').id).toBe('view-1')
		expect(items.at(1).props('view').id).toBe('view-2')
	})

	test('views are correctly sorted', () => {
		const navigation = getNavigation()
		const view1 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'Z - first', order: 1 })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'A - last', order: 9 })

		navigation.register(view2)
		navigation.register(view1)

		const wrapper = shallowMount(FilesNavigationList)
		const items = wrapper.findAllComponents({ name: 'FilesNavigationListItem' })
		expect(items).toHaveLength(2)
		expect(items.at(0).props('view').id).toBe('view-1')
		expect(items.at(1).props('view').id).toBe('view-2')
	})

	/**
	 * Idea here is that there are two views:
	 * - "100 second"
	 * - "2 first"
	 *
	 * When sorting by string "10" would be before "2 " (because 1 is before 2),
	 * but we want natural sorting so "2" is before "100" just like humans would expect.
	 */
	test('views without order property are correctly sorted using natural sort', () => {
		const navigation = getNavigation()
		const view1 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: '2 first' })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: '100 second' })

		navigation.register(view2)
		navigation.register(view1)

		const wrapper = shallowMount(FilesNavigationList)
		const items = wrapper.findAllComponents({ name: 'FilesNavigationListItem' })
		expect(items).toHaveLength(2)
		expect(items.at(0).props('view').id).toBe('view-1')
		expect(items.at(1).props('view').id).toBe('view-2')
	})

	test('views without order are always sorted behind views with order property', () => {
		const navigation = getNavigation()
		const view1 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: '2 first', order: 0 })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: '1 second' })

		navigation.register(view2)
		navigation.register(view1)

		const wrapper = shallowMount(FilesNavigationList)
		const items = wrapper.findAllComponents({ name: 'FilesNavigationListItem' })
		expect(items).toHaveLength(2)
		expect(items.at(0).props('view').id).toBe('view-1')
		expect(items.at(1).props('view').id).toBe('view-2')
	})
})
