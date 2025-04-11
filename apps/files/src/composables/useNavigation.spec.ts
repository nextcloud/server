/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Navigation, View } from '@nextcloud/files'

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent } from 'vue'

import { useNavigation } from './useNavigation'
import * as nextcloudFiles from '@nextcloud/files'

// Just a wrapper so we can test the composable
const TestComponent = defineComponent({
	template: '<div></div>',
	setup() {
		const { currentView, views } = useNavigation()
		return {
			currentView,
			views,
		}
	},
})

describe('Composables: useNavigation', () => {
	const spy = vi.spyOn(nextcloudFiles, 'getNavigation')
	let navigation: Navigation

	describe('currentView', () => {
		beforeEach(() => {
			navigation = new nextcloudFiles.Navigation()
			spy.mockImplementation(() => navigation)
		})

		it('should return null without active navigation', () => {
			const wrapper = mount(TestComponent)
			expect((wrapper.vm as unknown as { currentView: View | null}).currentView).toBe(null)
		})

		it('should return already active navigation', async () => {
			const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
			navigation.register(view)
			navigation.setActive(view)
			// Now the navigation is already set it should take the active navigation
			const wrapper = mount(TestComponent)
			expect((wrapper.vm as unknown as { currentView: View | null}).currentView).toBe(view)
		})

		it('should be reactive on updating active navigation', async () => {
			const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
			navigation.register(view)
			const wrapper = mount(TestComponent)

			// no active navigation
			expect((wrapper.vm as unknown as { currentView: View | null}).currentView).toBe(null)

			navigation.setActive(view)
			// Now the navigation is set it should take the active navigation
			expect((wrapper.vm as unknown as { currentView: View | null}).currentView).toBe(view)
		})
	})

	describe('views', () => {
		beforeEach(() => {
			navigation = new nextcloudFiles.Navigation()
			spy.mockImplementation(() => navigation)
		})

		it('should return empty array without registered views', () => {
			const wrapper = mount(TestComponent)
			expect((wrapper.vm as unknown as { views: View[]}).views).toStrictEqual([])
		})

		it('should return already registered views', () => {
			const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
			// register before mount
			navigation.register(view)
			// now mount and check that the view is listed
			const wrapper = mount(TestComponent)
			expect((wrapper.vm as unknown as { views: View[]}).views).toStrictEqual([view])
		})

		it('should be reactive on registering new views', () => {
			const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
			const view2 = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'My View 2', order: 1 })

			// register before mount
			navigation.register(view)
			// now mount and check that the view is listed
			const wrapper = mount(TestComponent)
			expect((wrapper.vm as unknown as { views: View[]}).views).toStrictEqual([view])

			// now register view 2 and check it is reactivly added
			navigation.register(view2)
			expect((wrapper.vm as unknown as { views: View[]}).views).toStrictEqual([view, view2])
		})
	})
})
