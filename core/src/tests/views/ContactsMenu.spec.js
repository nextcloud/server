/**
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import axios from '@nextcloud/axios'
import { mount, shallowMount } from '@vue/test-utils'

import ContactsMenu from '../../views/ContactsMenu.vue'

jest.mock('@nextcloud/axios', () => ({
	post: jest.fn(),
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
		jest.spyOn(console, 'error').mockImplementation(() => {})

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
		axios.post.mockResolvedValue({
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
							hyperlink: 'mailto:deboraoliver%40centrexin.com'
						},
						actions: [
							{
								title: 'Mail',
								icon: 'icon-mail',
								hyperlink: 'mailto:mathisholland%40virxo.com'
							},
							{
								title: 'Details',
								icon: 'icon-info',
								hyperlink: 'https://localhost/index.php/apps/contacts'
							}
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
							hyperlink: 'mailto:ceciliasoto%40essensia.com'
						},
						actions: [
							{
								title: 'Mail',
								icon: 'icon-mail',
								hyperlink: 'mailto:pearliesellers%40inventure.com'
							},
							{
								title: 'Details',
								icon: 'icon-info',
								hyperlink: 'https://localhost/index.php/apps/contacts'
							}
						],
						lastMessage: 'cu',
						emailAddresses: [],
					}
				],
				contactsAppEnabled: false,
			},
		})

		await view.vm.handleOpen()

		expect(view.vm.error).toBe(false)
		expect(view.vm.contacts.length).toBe(2)
		expect(view.text()).toContain('Acosta Lancaster')
		expect(view.text()).toContain('Adeline Snider')
	})

	it('shows link ot Contacts', async () => {
		const view = shallowMount(ContactsMenu)
		axios.post.mockResolvedValue({
			data: {
				contacts: [
					{
						id: 1,
					},
					{
						id: 2,
					},
				],
				contactsAppEnabled: true,
			},
		})

		await view.vm.handleOpen()

		expect(view.text()).toContain('Show all contacts …')
	})
})
