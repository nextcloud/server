/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AppIcon from '../../components/AppIcon.vue'
import SearchResult from '../../components/UnifiedSearch/SearchResult.vue'

function factory(propsData = {}) {
	return mount(SearchResult, {
		props: { title: 'A document', resourceUrl: '/f/1', thumbnailUrl: '', ...propsData },
	})
}

describe('SearchResult combobox option', () => {
	// aria-activedescendant on the input references the option element's id, and the
	// option is the <li> (decision 1B: per-group listboxes). The modal supplies the
	// role from context; the id comes from the prop. Both must land on the same <li>.
	it('puts the option role and the id on the same element', () => {
		const li = factory({ elementId: 'unified-search-result-files-0', option: true }).find('li')

		expect(li.attributes('role')).toBe('option')
		expect(li.attributes('id')).toBe('unified-search-result-files-0')
	})

	it('marks the active row through NcListItem so it is visually highlighted', () => {
		const wrapper = factory({ elementId: 'row-0', active: true })

		expect(wrapper.findComponent(NcListItem).props('active')).toBe(true)
	})

	it('leaves non-active rows unmarked', () => {
		const wrapper = factory({ elementId: 'row-0', active: false })

		expect(wrapper.findComponent(NcListItem).props('active')).toBe(false)
	})
})

describe('SearchResult option opening', () => {
	afterEach(() => {
		vi.restoreAllMocks()
		vi.unstubAllGlobals()
	})

	// jsdom's location.assign is non-configurable, so replace the whole location.
	function stubAssign() {
		const assign = vi.fn()
		vi.stubGlobal('location', { assign })
		return assign
	}

	function stubOpen() {
		return vi.spyOn(window, 'open').mockImplementation(() => null)
	}

	it('renders no link inside an option', () => {
		const wrapper = factory({ option: true })

		expect(wrapper.find('a').attributes('href')).toBeUndefined()
	})

	it('keeps the link when not an option', () => {
		const wrapper = factory()

		expect(wrapper.find('a').attributes('href')).toBe('/f/1')
		expect(wrapper.find('li').attributes('role')).toBeUndefined()
	})

	it('opens the result in the same tab on click', async () => {
		const assign = stubAssign()

		await factory({ option: true }).find('a').trigger('click')

		expect(assign).toHaveBeenCalledWith('/f/1')
	})

	it.each(['ctrlKey', 'metaKey', 'shiftKey'])('opens the result in a new tab on %s click', async (modifier) => {
		const open = stubOpen()
		const assign = stubAssign()

		await factory({ option: true }).find('a').trigger('click', { [modifier]: true })

		expect(open).toHaveBeenCalledWith('/f/1', '_blank', 'noopener')
		expect(assign).not.toHaveBeenCalled()
	})

	it('opens the result in a new tab on middle click', async () => {
		const open = stubOpen()

		await factory({ option: true }).find('li').trigger('auxclick', { button: 1 })

		expect(open).toHaveBeenCalledWith('/f/1', '_blank', 'noopener')
	})

	it('ignores a right click', async () => {
		const open = stubOpen()

		await factory({ option: true }).find('li').trigger('auxclick', { button: 2 })

		expect(open).not.toHaveBeenCalled()
	})
})

describe('SearchResult icon', () => {
	it('renders a URL icon (e.g. a settings section) as an image', () => {
		const wrapper = factory({ icon: '/apps/settings/img/password.svg' })

		const img = wrapper.find('img')
		expect(img.exists()).toBe(true)
		expect(img.attributes('src')).toBe('/apps/settings/img/password.svg')
	})

	it('hands an app icon (rounded, no thumbnail) to AppIcon', () => {
		const wrapper = factory({ icon: '/apps/files/img/app.svg', rounded: true })

		expect(wrapper.findComponent(AppIcon).props('icon')).toBe('/apps/files/img/app.svg')
	})

	it('does not render a broken image for a legacy CSS-class icon', () => {
		const wrapper = factory({ icon: 'icon-confirm' })

		// A class string is not a URL, so no <img> should point at it.
		expect(wrapper.findAll('img').length).toBe(0)
	})

	it('renders the thumbnail image when one is provided', () => {
		const wrapper = factory({ thumbnailUrl: '/preview/1', icon: 'icon-confirm' })

		const img = wrapper.find('img')
		expect(img.exists()).toBe(true)
		expect(img.attributes('src')).toBe('/preview/1')
	})
})
