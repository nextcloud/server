/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount, shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import ContactsMenu from '../../views/ContactsMenu.vue'

const axios = vi.hoisted(() => ({
	post: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user', isAdmin: false, displayName: 'User' }),
}))

describe('ContactsMenu', function() {
	it('is closed by default', () => {
		const view = shallowMount(ContactsMenu)

		expect(view.vm.contacts).toEqual([])
		expect(view.vm.loadingText).toBe(undefined)
	})

	it('shows a loading text', async () => {
		const view = shallowMount(ContactsMenu)
		axios.post.mockResolvedValue({
			data: {
				contacts: [],
				contactsAppEnabled: false,
			},
		})

		const opening = view.vm.handleOpen()

		expect(view.vm.contacts).toEqual([])
		expect(view.vm.loadingText).toBe('Loading your contacts …')
		await opening
	})

	it('shows error view when contacts can not be loaded', async () => {
		const view = mount(ContactsMenu)
		axios.post.mockResolvedValue({})
		vi.spyOn(console, 'error').mockImplementation(() => {})

		try {
			await view.vm.handleOpen()

			throw new Error('should not be reached')
		} catch (error) {
			expect(console.error).toHaveBeenCalled()
			console.error.mockRestore()
			expect(view.vm.error).toBe(true)
			expect(view.vm.contacts).toEqual([])
			expect(view.text()).toContain('Could not load your contacts')
		}
	})

	it('shows text when there are no contacts', async () => {
		const view = mount(ContactsMenu)
		axios.post.mockResolvedValueOnce({
			data: {
				contacts: [],
				contactsAppEnabled: false,
			},
		})

		await view.vm.handleOpen()

		expect(view.vm.error).toBe(false)
		expect(view.vm.contacts).toEqual([])
		expect(view.vm.loadingText).toBe(undefined)
		expect(view.text()).toContain('No contacts found')
	})

	it('shows contacts', async () => {
		const view = mount(ContactsMenu)
		axios.post.mockResolvedValue({
			data: {
				contacts: [
					{
						id: null,
						fullName: 'Acosta Lancaster',
						topAction: {
							title: 'Mail',
							icon: 'icon-mail',
							hyperlink: 'mailto:deboraoliver%40centrexin.com',
						},
						actions: [
							{
								title: 'Mail',
								icon: 'icon-mail',
								hyperlink: 'mailto:mathisholland%40virxo.com',
							},
							{
								title: 'Details',
								icon: 'icon-info',
								hyperlink: 'https://localhost/index.php/apps/contacts',
							},
						],
						lastMessage: '',
						emailAddresses: [],
					},
					{
						id: null,
						fullName: 'Adeline Snider',
						topAction: {
							title: 'Mail',
							icon: 'icon-mail',
							hyperlink: 'mailto:ceciliasoto%40essensia.com',
						},
						actions: [
							{
								title: 'Mail',
								icon: 'icon-mail',
								hyperlink: 'mailto:pearliesellers%40inventure.com',
							},
							{
								title: 'Details',
								icon: 'icon-info',
								hyperlink: 'https://localhost/index.php/apps/contacts',
							},
						],
						lastMessage: 'cu',
						emailAddresses: [],
					},
				],
				contactsAppEnabled: true,
			},
		})

		await view.vm.handleOpen()

		expect(view.vm.error).toBe(false)
		expect(view.vm.contacts.length).toBe(2)
		expect(view.text()).toContain('Acosta Lancaster')
		expect(view.text()).toContain('Adeline Snider')
		expect(view.text()).toContain('Show all contacts')
	})
})
