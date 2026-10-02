/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { enableAutoUnmount, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { defineComponent, h } from 'vue'
import PublicPageMenuExternalEntry from '../../components/PublicPageMenu/PublicPageMenuExternalEntry.vue'

const PublicPageMenuEntry = defineComponent({
	name: 'PublicPageMenuEntry',
	props: ['id', 'icon', 'href', 'label', 'details'],
	emits: ['click'],
	setup(props, { emit }) {
		return () => h('li', { 'data-details': props.details }, [h('button', { onClick: () => emit('click') }, props.label)])
	},
})

const PublicPageMenuExternalDialog = defineComponent({
	name: 'PublicPageMenuExternalDialog',
	props: ['label'],
	emits: ['close'],
	setup(_, { emit }) {
		return () => h('dialog', [h('button', { class: 'close', onClick: () => emit('close') })])
	},
})

/**
 * @param attrs - Further entry attributes passed by the menu
 */
function factory(attrs = {}) {
	return mount(PublicPageMenuExternalEntry, {
		props: { id: 'save', label: 'Add to your Nextcloud', icon: 'icon-external' },
		attrs,
		global: { stubs: { PublicPageMenuEntry, PublicPageMenuExternalDialog } },
	})
}

enableAutoUnmount(afterEach)

describe('PublicPageMenuExternalEntry', () => {
	it('opens the dialog again after it was closed', async () => {
		const wrapper = factory()
		expect(wrapper.find('dialog').exists()).toBe(false)

		await wrapper.get('li button').trigger('click')
		expect(wrapper.find('dialog').exists()).toBe(true)
		expect(wrapper.emitted('click')).toHaveLength(1)

		await wrapper.get('dialog .close').trigger('click')
		expect(wrapper.find('dialog').exists()).toBe(false)

		await wrapper.get('li button').trigger('click')
		expect(wrapper.find('dialog').exists()).toBe(true)
	})

	it('passes the other attributes of the menu on to the entry', () => {
		const wrapper = factory({ details: 'Nextcloud' })

		expect(wrapper.get('li').attributes('data-details')).toBe('Nextcloud')
	})
})
