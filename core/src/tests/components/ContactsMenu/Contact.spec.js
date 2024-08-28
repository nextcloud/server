/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
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
						hyperlink: 'mailto:deboraoliver%40centrexin.com',
					},
					emailAddresses: [],
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
				},
			},
		})

		expect(view.find('li a').exists()).toBe(true)
		expect(view.find('li a').attributes('href')).toBe('mailto:deboraoliver%40centrexin.com')
	})
})
