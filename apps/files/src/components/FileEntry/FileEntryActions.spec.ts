/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Permission } from '@nextcloud/files'
import { createTestingPinia } from '@pinia/testing'
import { enableAutoUnmount, shallowMount } from '@vue/test-utils'
import { afterEach, describe, expect, test, vi } from 'vitest'
import { nextTick } from 'vue'
import NcActions from '@nextcloud/vue/components/NcActions'
import FileEntryActions from './FileEntryActions.vue'

vi.mock('@nextcloud/auth')

enableAutoUnmount(afterEach)

const source = new File({
	id: 42,
	source: 'http://nextcloud.local/remote.php/dav/files/test/report.pdf',
	root: '/files/test',
	owner: 'test',
	mime: 'application/pdf',
	permissions: Permission.READ,
})

/**
 * Mount the actions of a file entry, in grid mode to skip measuring the inline actions
 *
 * @param opened - Whether the actions menu of the entry is open
 * @param props - Further props
 */
function mountActions(opened: boolean, props: Record<string, unknown> = {}) {
	return shallowMount(FileEntryActions, {
		props: { source, opened, gridMode: true, ...props },
		global: {
			plugins: [createTestingPinia({ createSpy: vi.fn })],
		},
	})
}

describe('FileEntryActions.vue', () => {
	test('passes the opened state to the actions menu', () => {
		const wrapper = mountActions(true)

		expect(wrapper.getComponent(NcActions).props('open')).toBe(true)
	})

	test.each([
		[false, true],
		[true, false],
	])('changes from opened=%s to opened=%s as soon as the actions menu does', (opened, open) => {
		const wrapper = mountActions(opened)

		wrapper.getComponent(NcActions).vm.$emit('update:open', open)

		expect(wrapper.emitted('update:opened')).toEqual([[open]])
	})

	test('leaves an open submenu when the actions menu closes', async () => {
		const wrapper = mountActions(true, {
			'onUpdate:opened': (opened: boolean) => wrapper.setProps({ opened }),
		})
		await wrapper.setData({ openedSubmenu: { id: 'submenu' } })

		wrapper.getComponent(NcActions).vm.$emit('update:open', false)
		await nextTick()

		expect(wrapper.vm.openedSubmenu).toBeNull()
	})

	// `closed` follows the closing transition: it can come after the menu was opened again, or never without animations
	test('ignores the end of a closing transition', () => {
		const wrapper = mountActions(true)

		wrapper.getComponent(NcActions).vm.$emit('closed')

		expect(wrapper.emitted('update:opened')).toBeUndefined()
	})
})
