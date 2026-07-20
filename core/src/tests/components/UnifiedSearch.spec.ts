/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type * as VueUseCore from '@vueuse/core'

import { shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

const mobile = ref(false)
vi.mock('@nextcloud/vue/composables/useIsMobile', () => ({
	useIsSmallMobile: () => mobile,
	useIsMobile: () => ref(false),
}))
vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn(), subscribe: vi.fn() }))
// Controllable location so we can exercise the shortcut allowlist bail-out.
// Partial mock: @nextcloud/vue also pulls other composables from @vueuse/core.
const location = ref({ pathname: '/' })
vi.mock('@vueuse/core', async (importOriginal) => ({
	...(await importOriginal<typeof VueUseCore>()),
	useBrowserLocation: () => location,
}))

import UnifiedSearch from '../../views/UnifiedSearch.vue'

function factory() {
	return shallowMount(UnifiedSearch, {
		global: { mocks: { t: (_: string, s: string) => s, OCP: {} } },
	})
}

/**
 * Dispatch Ctrl+<key> on the window and report whether the handler claimed it.
 */
function pressCtrl(key = 'k') {
	const event = new KeyboardEvent('keydown', { key, ctrlKey: true, bubbles: true, cancelable: true })
	const prevented = vi.spyOn(event, 'preventDefault')
	window.dispatchEvent(event)
	return prevented
}

beforeEach(() => {
	mobile.value = false
	location.value = { pathname: '/' }
	window.OCP = { Accessibility: { disableKeyboardShortcuts: () => true } }
})
afterEach(() => vi.clearAllMocks())

describe('UnifiedSearch open-state model', () => {
	it('desktop: typing opens, clearing closes', async () => {
		const wrapper = factory()
		wrapper.vm.queryText = 'abc'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		wrapper.vm.queryText = ''
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
	})

	it('mobile: typing does NOT open the modal', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.queryText = 'abc'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
	})

	it('mobile: header button click opens the modal', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.findComponent({ name: 'UnifiedSearchInput' }).vm.$emit('click')
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(true)
	})

	it('mobile: clearing the query does NOT close an open modal', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.showUnifiedSearch = true
		wrapper.vm.queryText = ''
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(true)
	})
})

describe('UnifiedSearch focus shortcut (Ctrl/Cmd+K)', () => {
	function mountWithShortcuts() {
		window.OCP = { Accessibility: { disableKeyboardShortcuts: () => false } }
		return factory()
	}

	it('desktop: focuses the header input and claims the key', () => {
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl()

		expect(focusInput).toHaveBeenCalled()
		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('mobile: opens the modal instead (no header input to focus)', () => {
		mobile.value = true
		const wrapper = mountWithShortcuts()

		pressCtrl()

		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		wrapper.destroy()
	})

	it('is not bound when the user disabled keyboard shortcuts', () => {
		const wrapper = factory() // beforeEach leaves shortcuts disabled
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		pressCtrl()

		expect(focusInput).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('stays out of the way on pages that own the search shortcut', () => {
		location.value = { pathname: '/settings/users' }
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl()

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('unbinds the shortcut when the component is torn down', () => {
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		wrapper.destroy()
		pressCtrl()

		expect(focusInput).not.toHaveBeenCalled()
	})

	// Under Caps Lock / Shift, event.key is 'K'. The shortcut must still fire.
	it('fires regardless of key case', () => {
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		pressCtrl('K')

		expect(focusInput).toHaveBeenCalled()
		wrapper.destroy()
	})
})

describe('UnifiedSearch find shortcut (Ctrl+F) aligns with Ctrl+K', () => {
	function mountWithShortcuts() {
		window.OCP = { Accessibility: { disableKeyboardShortcuts: () => false } }
		return factory()
	}

	// Ctrl+F used to open the modal on an empty query; it now mirrors Ctrl+K.
	it('desktop: focuses the input instead of opening an empty modal', () => {
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f')

		expect(focusInput).toHaveBeenCalled()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('mobile: opens the modal (no header input to focus)', () => {
		mobile.value = true
		const wrapper = mountWithShortcuts()

		pressCtrl('f')

		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		wrapper.destroy()
	})

	// Deck & co. own Ctrl+F for their in-app search bar; that must survive the alignment.
	it('on local-search pages still toggles the local bar, not the global input', () => {
		location.value = { pathname: '/apps/deck' }
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		pressCtrl('f')

		expect(wrapper.vm.showLocalSearch).toBe(true)
		expect(focusInput).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('stays out of the way on pages that own the search shortcut', () => {
		location.value = { pathname: '/settings/users' }
		const wrapper = mountWithShortcuts()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})
})

describe('UnifiedSearch combobox expanded state', () => {
	// The header input is the combobox for the unified results only. On local-search
	// pages (e.g. deck) Ctrl+F opens just the local bar, so the input must report
	// collapsed and not point aria-controls at an unrendered popover.
	it('reports collapsed when only the local search bar is open', async () => {
		const wrapper = factory()
		wrapper.vm.showLocalSearch = true
		wrapper.vm.showUnifiedSearch = false
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'UnifiedSearchInput' }).props('expanded')).toBe(false)
	})

	it('reports expanded when the unified results popover is open', async () => {
		const wrapper = factory()
		wrapper.vm.showUnifiedSearch = true
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'UnifiedSearchInput' }).props('expanded')).toBe(true)
	})
})

describe('UnifiedSearch selection relay', () => {
	it('passes the active descendant id from the results down to the input', async () => {
		const wrapper = factory()

		wrapper.findComponent({ name: 'UnifiedSearchModal' }).vm.$emit('update:activeDescendant', 'unified-search-result-files-0')
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'UnifiedSearchInput' }).props('activeDescendantId')).toBe('unified-search-result-files-0')
	})

	it('relays input arrow navigation to the results', () => {
		const wrapper = factory()
		const modal = { moveActive: vi.fn(), activateActive: vi.fn() }
		wrapper.vm.$refs.searchModal = modal

		wrapper.findComponent({ name: 'UnifiedSearchInput' }).vm.$emit('navigate', 'next')

		expect(modal.moveActive).toHaveBeenCalledWith('next')
	})

	it('relays input activation (Enter) to the results', () => {
		const wrapper = factory()
		const modal = { moveActive: vi.fn(), activateActive: vi.fn() }
		wrapper.vm.$refs.searchModal = modal

		wrapper.findComponent({ name: 'UnifiedSearchInput' }).vm.$emit('activate')

		expect(modal.activateActive).toHaveBeenCalled()
	})
})
