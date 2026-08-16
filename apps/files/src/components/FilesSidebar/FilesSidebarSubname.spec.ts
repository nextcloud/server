/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Permission } from '@nextcloud/files'
import { enableAutoDestroy, shallowMount } from '@vue/test-utils'
import { afterEach, describe, expect, test } from 'vitest'
import FilesSidebarSubname from './FilesSidebarSubname.vue'

enableAutoDestroy(afterEach)

describe('FilesSidebarSubname', () => {
	test('renders full path for nested file', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Documents/Projects/report.pdf',
			owner: 'admin',
			mime: 'application/pdf',
			permissions: Permission.READ,
			root: '/files/admin',
			size: 1024,
		})

		const wrapper = shallowMount(FilesSidebarSubname, {
			propsData: { node: file },
		})

		expect(wrapper.text()).toContain('/Documents/Projects/report.pdf')
	})

	test('renders full path for root-level file', () => {
		const file = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/report.pdf',
			owner: 'admin',
			mime: 'application/pdf',
			permissions: Permission.READ,
			root: '/files/admin',
			size: 1024,
		})

		const wrapper = shallowMount(FilesSidebarSubname, {
			propsData: { node: file },
		})

		expect(wrapper.text()).toContain('/report.pdf')
	})

	test('updates path when selected node changes', async () => {
		const firstFile = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/First.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		const secondFile = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Documents/Second.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const wrapper = shallowMount(FilesSidebarSubname, {
			propsData: { node: firstFile },
		})
		expect(wrapper.text()).toContain('/First.txt')

		await wrapper.setProps({ node: secondFile })

		expect(wrapper.text()).not.toContain('/First.txt')
		expect(wrapper.text()).toContain('/Documents/Second.txt')
	})
})
