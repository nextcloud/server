/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import UserManagement from './UserManagement.vue'

const route = vi.hoisted(() => ({ params: {} as Record<string, string> }))

vi.mock('vue-router', () => ({ useRoute: () => route }))
vi.mock('../components/UserList.vue', () => ({
	default: { name: 'UserList', props: ['selectedGroup', 'externalActions'], render: () => h('div') },
}))
vi.mock('./UserManagementNavigation.vue', () => ({ default: { render: () => h('nav') } }))

/**
 * Mount the page with the content containers stubbed
 */
function mountPage() {
	return mount(UserManagement, {
		global: { stubs: { NcContent: true, NcAppContent: true }, renderStubDefaultSlot: true },
	})
}

describe('UserManagement', () => {
	beforeEach(() => {
		route.params = {}
	})

	it('lets other apps register account actions', () => {
		const wrapper = mountPage()
		const action = vi.fn()

		window.OCA.Settings.UserList.registerAction('<svg/>', 'Convert', action)

		expect(wrapper.findComponent({ name: 'UserList' }).props('externalActions')).toEqual([
			{ icon: '<svg/>', text: 'Convert', action, enabled: undefined },
		])
	})

	it('shows a group typed into the URL by hand', () => {
		route.params = { selectedGroup: '50%' }
		const wrapper = mountPage()

		expect(wrapper.findComponent({ name: 'UserList' }).props('selectedGroup')).toBe('50%')
	})

	it('names the shown group in the heading', () => {
		route.params = { selectedGroup: 'Team%20%26%20Co%2050%25' }
		const wrapper = mountPage()

		expect(wrapper.findComponent({ name: 'UserList' }).props('selectedGroup')).toBe('Team & Co 50%')
		expect(wrapper.findComponent({ name: 'NcAppContent' }).props('pageHeading')).toBe('Account group: Team & Co 50%')
	})
})
