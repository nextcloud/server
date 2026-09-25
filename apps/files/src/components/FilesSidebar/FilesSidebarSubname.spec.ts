/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'

import { File, Permission } from '@nextcloud/files'
import { enableAutoDestroy, shallowMount } from '@vue/test-utils'
import { afterEach, describe, expect, test } from 'vitest'
import FilesSidebarSubname from './FilesSidebarSubname.vue'

enableAutoDestroy(afterEach)

describe('FilesSidebarSubname', () => {
	const folder = { path: '/Current' } as IFolder

	test('renders path in a separate section from metadata', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/report.pdf',
			owner: 'admin',
			mime: 'application/pdf',
			permissions: Permission.READ,
			root: '/files/admin',
			size: 1024,
		})

		const wrapper = shallowMount(FilesSidebarSubname, {
			propsData: { folder, node: file },
		})
		const sections = wrapper.element.children

		expect(sections).toHaveLength(2)
		expect(sections[0].textContent).toContain('Location')
		expect(sections[0].textContent).toContain('/report.pdf')
		expect(sections[1].textContent).toContain('1 KB')
	})

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
			propsData: { folder, node: file },
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
			propsData: { folder, node: file },
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
			propsData: { folder, node: firstFile },
		})
		expect(wrapper.text()).toContain('/First.txt')

		await wrapper.setProps({ node: secondFile })

		expect(wrapper.text()).not.toContain('/First.txt')
		expect(wrapper.text()).toContain('/Documents/Second.txt')
	})

	test('does not render location for a file in the current folder', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Documents/report.pdf',
			owner: 'admin',
			mime: 'application/pdf',
			permissions: Permission.READ,
			root: '/files/admin',
			size: 1024,
		})

		const wrapper = shallowMount(FilesSidebarSubname, {
			propsData: { folder: { path: '/Documents' } as IFolder, node: file },
		})

		expect(wrapper.text()).not.toContain('Location')
		expect(wrapper.text()).not.toContain('/Documents/report.pdf')
		expect(wrapper.text()).toContain('1 KB')
	})
})
