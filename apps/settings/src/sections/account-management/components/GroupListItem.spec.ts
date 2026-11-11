/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import GroupListItem from './GroupListItem.vue'
import { useUsersStore } from '../store/users.ts'

/**
 * Mount the item of a group the admin may edit
 */
function mountItem() {
	return mount(GroupListItem, {
		props: { active: false, id: 'team', name: 'Team' },
		global: {
			plugins: [createTestingPinia({
				createSpy: vi.fn,
				initialState: { 'settings-users': { serverData: { isAdmin: true } } },
			})],
			stubs: {
				// The real item needs a router and only renders its actions while the menu is open
				NcAppNavigationItem: {
					props: ['menuOpen', 'loading'],
					render() {
						return h('li', this.$slots.actions?.())
					},
				},
			},
		},
	})
}

/**
 * @param wrapper The mounted item
 * @param name The name to enter
 */
async function rename(wrapper: ReturnType<typeof mountItem>, name: string) {
	const input = wrapper.findComponent(NcActionInput)
	await input.vm.$emit('update:modelValue', name)
	await input.vm.$emit('submit')
	await flushPromises()
}

describe('GroupListItem', () => {
	it('renames the group to the entered name', async () => {
		const wrapper = mountItem()
		const store = useUsersStore()
		vi.mocked(store.renameGroup).mockResolvedValue(undefined)

		await rename(wrapper, ' Team Blue ')

		expect(store.renameGroup).toHaveBeenCalledWith({ groupid: 'team', displayName: 'Team Blue' })
		expect(wrapper.findComponent(NcAppNavigationItem).props('menuOpen')).toBe(false)
		expect(wrapper.findComponent(NcAppNavigationItem).props('loading')).toBe(false)
	})

	it('keeps the menu open with the entered name when renaming fails', async () => {
		const wrapper = mountItem()
		const store = useUsersStore()
		vi.mocked(store.renameGroup).mockRejectedValue(new Error('500'))

		await rename(wrapper, 'Team Blue')

		expect(wrapper.findComponent(NcAppNavigationItem).props('menuOpen')).toBe(true)
		expect(wrapper.findComponent(NcActionInput).props('modelValue')).toBe('Team Blue')
		expect(wrapper.findComponent(NcAppNavigationItem).props('loading')).toBe(false)
	})

	it('starts renaming from the current name', async () => {
		const wrapper = mountItem()
		const item = wrapper.findComponent(NcAppNavigationItem)
		await wrapper.findComponent(NcActionInput).vm.$emit('update:modelValue', 'Team Blue')
		await item.vm.$emit('update:menuOpen', false)
		await wrapper.setProps({ name: 'Team Red' })

		await item.vm.$emit('update:menuOpen', true)

		expect(wrapper.findComponent(NcActionInput).props('modelValue')).toBe('Team Red')
	})

	it('does not rename to an empty name', async () => {
		const wrapper = mountItem()
		const store = useUsersStore()

		await rename(wrapper, '  ')

		expect(store.renameGroup).not.toHaveBeenCalled()
	})

	it('lets the menu close again', async () => {
		const wrapper = mountItem()
		const item = wrapper.findComponent(NcAppNavigationItem)

		await item.vm.$emit('update:menuOpen', true)
		expect(item.props('menuOpen')).toBe(true)
		await item.vm.$emit('update:menuOpen', false)

		expect(item.props('menuOpen')).toBe(false)
	})

	it('drops an entered name that was not submitted', async () => {
		const wrapper = mountItem()
		const item = wrapper.findComponent(NcAppNavigationItem)
		await item.vm.$emit('update:menuOpen', true)
		await wrapper.findComponent(NcActionInput).vm.$emit('update:modelValue', 'Draft')

		await item.vm.$emit('update:menuOpen', false)

		expect(wrapper.findComponent(NcActionInput).props('modelValue')).toBe('Team')
	})
})
