/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Navigation, View } from '@nextcloud/files'

import * as nextcloudFiles from '@nextcloud/files'
import { enableAutoDestroy, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent } from 'vue'
import { useViews } from './useViews.ts'

// Just a wrapper so we can test the composable
const TestComponent = defineComponent({
	template: '<div></div>',
	setup() {
		return {
			views: useViews(),
		}
	},
})

enableAutoDestroy(afterEach)

describe('Composables: useViews', () => {
	const spy = vi.spyOn(nextcloudFiles, 'getNavigation')
	let navigation: Navigation

	beforeEach(() => {
		navigation = new nextcloudFiles.Navigation()
		spy.mockImplementation(() => navigation)
	})

	it('should return empty array without registered views', () => {
		const wrapper = mount(TestComponent)
		expect((wrapper.vm as unknown as { views: View[] }).views).toStrictEqual([])
	})

	it('should return already registered views', () => {
		const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect((wrapper.vm as unknown as { views: View[] }).views).toStrictEqual([view])
	})

	it('should be reactive on registering new views', () => {
		const view = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-1', name: 'My View 1', order: 0 })
		const view2 = new nextcloudFiles.View({ getContents: () => Promise.reject(new Error()), icon: '<svg></svg>', id: 'view-2', name: 'My View 2', order: 1 })

		// register before mount
		navigation.register(view)
		// now mount and check that the view is listed
		const wrapper = mount(TestComponent)
		expect((wrapper.vm as unknown as { views: View[] }).views).toStrictEqual([view])

		// now register view 2 and check it is reactively added
		navigation.register(view2)
		expect((wrapper.vm as unknown as { views: View[] }).views).toStrictEqual([view, view2])
	})
})
