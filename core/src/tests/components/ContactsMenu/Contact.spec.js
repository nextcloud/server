/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { shallowMount } from '@vue/test-utils'

import Contact from '../../../components/ContactsMenu/Contact.vue'

describe('Contact', function() {
	it('passes the avatar url to non-user contacts', () => {
		const view = shallowMount(ContactMenuEntry, {
			propsData: {
				contact: {
					id: '11111111-2222-3333-4444-555555555555',
					uid: '11111111-2222-3333-4444-555555555555',
					fullName: 'Jane Doe',
					avatar: 'https://localhost/remote.php/dav/addressbooks/users/admin/contacts/jane.vcf?photo',
					isUser: false,
					emailAddresses: [],
					actions: [],
				},
			},
		})

		const avatar = view.findComponent(NcAvatar)
		expect(avatar.props('user')).toBeUndefined()
		expect(avatar.props('url')).toBe('https://localhost/remote.php/dav/addressbooks/users/admin/contacts/jane.vcf?photo')
		expect(avatar.props('displayName')).toBe('Jane Doe')
	})

	it('lets user contacts resolve the avatar via their user id', () => {
		const view = shallowMount(ContactMenuEntry, {
			propsData: {
				contact: {
					id: 'jane',
					uid: 'jane',
					fullName: 'Jane Doe',
					avatar: 'https://localhost/remote.php/dav/addressbooks/system/system/system/jane.vcf?photo',
					isUser: true,
					emailAddresses: [],
					actions: [],
				},
			},
		})

		const avatar = view.findComponent(NcAvatar)
		expect(avatar.props('user')).toBe('jane')
		expect(avatar.props('url')).toBeUndefined()
	})

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
