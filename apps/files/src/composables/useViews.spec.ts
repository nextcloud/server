/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation, View } from '@nextcloud/files'
import { enableAutoDestroy, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { defineComponent } from 'vue'
import { useViews, useVisibleViews } from './useViews.ts'

// Just a wrapper so we can test the composable
const TestComponent = defineComponent({
	template: '<div />',
	setup() {
		return {
			views: useViews(),
			visibleViews: useVisibleViews(),
		}
	},
})

enableAutoDestroy(afterEach)

describe('Composables: useViews', () => {
	const navigation = getNavigation()

	beforeEach(() => {
		const views = [...navigation.views]
		for (const view of views) {
			navigation.remove(view.id)
		}
	})

	it('should return empty array without registered views', () => {
		const wrapper = mount(TestComponent)
		expect(getViewsInWrapper(wrapper)).toStrictEqual([])
	})

	it('should return already registered views', () => {
		const view = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect(getViewsInWrapper(wrapper)).toStrictEqual([view.id])
	})

	it('should be reactive on registering new views', () => {
		const view = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'My View 2', order: 1 })

		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect(getViewsInWrapper(wrapper)).toStrictEqual([view.id])

		// now register view 2 and check it is reactively added
		navigation.register(view2)
		expect(getViewsInWrapper(wrapper)).toStrictEqual([view.id, view2.id])
	})
})

describe('Composables: useVisibleViews', () => {
	const navigation = getNavigation()

	beforeEach(() => {
		const views = [...navigation.views]
		for (const view of views) {
			navigation.remove(view.id)
		}
	})

	it('should return empty array without registered views', () => {
		const wrapper = mount(TestComponent)
		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([])
	})

	it('should return already registered views', () => {
		const view = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([view.id])
	})

	it('should ignore hidden views', () => {
		const view = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0, hidden: true })
		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([])
	})

	it('should ignore hidden views', () => {
		const hiddenView = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'hidden', name: 'My hidden view', order: 0, hidden: true })
		navigation.register(hiddenView)

		const wrapper = mount(TestComponent)
		const visibleView = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		navigation.register(visibleView)

		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([visibleView.id])
	})

	it('should be reactive on registering new views', () => {
		const view = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		const view2 = new View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'My View 2', order: 1 })

		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([view.id])

		// now register view 2 and check it is reactively added
		navigation.register(view2)
		expect(getVisibleViewsInWrapper(wrapper)).toStrictEqual([view.id, view2.id])
	})
})

/**
 * Get the view ids from the wrapper's component instance.
 *
 * @param wrapper - The wrapper
 */
function getViewsInWrapper(wrapper: ReturnType<typeof mount>) {
	const vm = wrapper.vm as unknown as InstanceType<typeof TestComponent>
	return vm.views.map((view) => view.id)
}

/**
 * Get the visible (non-hidden) view ids from the wrapper's component instance.
 *
 * @param wrapper - The wrapper
 */
function getVisibleViewsInWrapper(wrapper: ReturnType<typeof mount>) {
	const vm = wrapper.vm as unknown as InstanceType<typeof TestComponent>
	return vm.visibleViews.map((view) => view.id)
}
