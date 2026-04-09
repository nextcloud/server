/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, findAllByRole, render } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import ContactsMenu from '../../views/ContactsMenu.vue'

const axios = vi.hoisted(() => ({
	post: vi.fn(),
	get: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user', isAdmin: false, displayName: 'User' }),
}))

afterEach(cleanup)

describe('ContactsMenu', function() {
	it('shows a loading text', async () => {
		const { promise, resolve } = Promise.withResolvers<void>()
		axios.post.mockImplementationOnce(async () => (await promise, {
			data: {
				contacts: [],
				contactsAppEnabled: false,
			},
		}))
		axios.get.mockResolvedValue({
			data: [],
		})

		const view = render(ContactsMenu)
		await view.findByRole('button')
			.then((button) => button.click())

		await expect(view.findByText(/Loading your contacts\s…/)).resolves.toBeTruthy()
		resolve()
		await expect(view.findByText('No contacts found')).resolves.toBeTruthy()
	})

	it('shows error view when contacts can not be loaded', async () => {
		axios.post.mockResolvedValue({})
		axios.get.mockResolvedValue({
			data: [],
		})
		vi.spyOn(console, 'error').mockImplementation(() => {})

		const view = render(ContactsMenu)
		await view.findByRole('button')
			.then((button) => button.click())
		await expect(view.findByText(/Could not load your contacts/)).resolves.toBeTruthy()
	})

	it('shows contacts', async () => {
		axios.get.mockResolvedValue({
			data: [],
		})
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

		const view = render(ContactsMenu)
		await view.findByRole('button')
			.then((button) => button.click())

		await expect(view.findByRole('list', { name: 'Contacts list' })).resolves.toBeTruthy()
		const list = view.getByRole('list', { name: 'Contacts list' })
		await expect(findAllByRole(list, 'listitem')).resolves.toHaveLength(2)

		const items = await findAllByRole(list, 'listitem')
		expect(items[0]!.textContent).toContain('Acosta Lancaster')
		expect(items[1]!.textContent).toContain('Adeline Snider')
	})
})
