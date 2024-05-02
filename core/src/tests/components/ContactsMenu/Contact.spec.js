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

import { shallowMount } from '@vue/test-utils'

import Contact from '../../../components/ContactsMenu/Contact.vue'

describe('Contact', function() {
	it('links to the top action', () => {
		const view = shallowMount(Contact, {
			propsData: {
				contact: {
					id: null,
					fullName: 'Acosta Lancaster',
					topAction: {
						title: 'Mail',
						icon: 'icon-mail',
						hyperlink: 'mailto:deboraoliver%40centrexin.com'
					},
					emailAddresses: [],
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
						},
					],
					lastMessage: '',
				},
			},
		})

		expect(view.find('li a').exists()).toBe(true)
		expect(view.find('li a').attributes('href')).toBe('mailto:deboraoliver%40centrexin.com')
	})
})
