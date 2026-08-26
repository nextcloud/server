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
 * Dispatch Ctrl+<key> from an element and report whether the handler claimed it.
 * The guard inspects `event.target`, so this cannot dispatch on the window.
 *
 * @param key The key to press
 * @param target The element the keystroke originates from
 */
function pressCtrl(key = 'k', target: EventTarget = document.body) {
	const event = new KeyboardEvent('keydown', { key, ctrlKey: true, bubbles: true, cancelable: true })
	const prevented = vi.spyOn(event, 'preventDefault')
	target.dispatchEvent(event)
	return prevented
}

/**
 * jsdom has no layout, so the mask's visibility has to be faked.
 *
 * @param parent Element to append the mask to
 */
function addVisibleModalMask(parent: Element = document.body) {
	const mask = document.createElement('div')
	mask.classList.add('modal-mask')
	parent.appendChild(mask)
	Element.prototype.checkVisibility = () => true
	return mask
}

/**
 * jsdom does not derive isContentEditable from the attribute.
 */
function addContentEditable() {
	const editor = document.createElement('div')
	Object.defineProperty(editor, 'isContentEditable', { value: true })
	document.body.appendChild(editor)
	return editor
}

beforeEach(() => {
	mobile.value = false
	location.value = { pathname: '/' }
	// useHotKey reads the accessibility opt-out once, when its module is first imported,
	// so it cannot be toggled per test. Opting out is the library's behaviour, not ours.
	window.OCP = { Accessibility: { disableKeyboardShortcuts: () => false } }
})
afterEach(() => {
	vi.clearAllMocks()
	document.body.replaceChildren()
	// @ts-expect-error Restore the jsdom default (absent).
	delete Element.prototype.checkVisibility
})

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
	it('desktop: focuses the header input and claims the key', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl()

		expect(focusInput).toHaveBeenCalled()
		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('mobile: opens the modal instead (no header input to focus)', () => {
		mobile.value = true
		const wrapper = factory()

		pressCtrl()

		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		wrapper.destroy()
	})

	// The allowlist means the page owns Ctrl+F, not Ctrl+K. Nothing on those pages binds
	// Ctrl+K, so bailing out would hand it to the browser (Firefox opens its search bar).
	it('still claims the key on pages that own Ctrl+F', () => {
		location.value = { pathname: '/settings/users' }
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl()

		expect(focusInput).toHaveBeenCalled()
		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('unbinds the shortcut when the component is torn down', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		wrapper.destroy()
		pressCtrl()

		expect(focusInput).not.toHaveBeenCalled()
	})

	// Under Caps Lock / Shift, event.key is 'K'. The shortcut must still fire.
	it('fires regardless of key case', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		pressCtrl('K')

		expect(focusInput).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('leaves the key to the editor the user is typing in', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('k', addContentEditable())

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('stays quiet behind an open modal from another app', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		addVisibleModalMask()

		const prevented = pressCtrl('k')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	// useHotKey has no way to exempt a component's own scrim, so the open results panel
	// silences Ctrl+K. Re-focusing an already-focused input is a no-op, so this is only
	// a papercut: the browser gets the key instead.
	it('gives up the key behind its own results scrim', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		document.body.appendChild(wrapper.vm.$el)
		addVisibleModalMask(wrapper.vm.$el)

		const prevented = pressCtrl('k')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})
})

describe('UnifiedSearch find shortcut (Ctrl+F) aligns with Ctrl+K', () => {
	// Ctrl+F used to open the modal on an empty query; it now mirrors Ctrl+K.
	it('desktop: focuses the input instead of opening an empty modal', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f')

		expect(focusInput).toHaveBeenCalled()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})

	it('mobile: opens the modal (no header input to focus)', () => {
		mobile.value = true
		const wrapper = factory()

		pressCtrl('f')

		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		wrapper.destroy()
	})

	it('stays out of the way on pages that own the search shortcut', () => {
		location.value = { pathname: '/settings/users' }
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	// Deck filters cards in place and binds Ctrl+F to its own board input, so the header
	// must not steal the key there. This replaces the local search bar we used to render.
	it('leaves Ctrl+F to Deck, which filters in its own board input', () => {
		location.value = { pathname: '/apps/deck' }
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
		wrapper.destroy()
	})

	// Once search is engaged, Ctrl+F belongs to the browser again: a second press must
	// reach the native find bar instead of being swallowed to re-focus what is already focused.
	it('falls through to the browser once the results are open', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		wrapper.vm.showUnifiedSearch = true

		const prevented = pressCtrl('f')

		expect(prevented).not.toHaveBeenCalled()
		expect(focusInput).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('falls through to the browser while the header input already holds focus', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		// The engaged check tests the real focused element against the input's DOM subtree,
		// so it needs a focusable node that is actually in the document.
		const host = document.createElement('div')
		const field = document.createElement('input')
		host.appendChild(field)
		document.body.appendChild(host)
		wrapper.vm.$refs.searchInput = { $el: host }
		field.focus()

		const prevented = pressCtrl('f')

		expect(prevented).not.toHaveBeenCalled()
		expect(focusInput).not.toHaveBeenCalled()
		host.remove()
		wrapper.destroy()
	})

	it('leaves the key to the editor the user is typing in', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})

		const prevented = pressCtrl('f', addContentEditable())

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('leaves the key to an input the user is typing in', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		const field = document.createElement('input')
		document.body.appendChild(field)

		const prevented = pressCtrl('f', field)

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	it('stays quiet behind an open modal from another app', () => {
		const wrapper = factory()
		const focusInput = vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		addVisibleModalMask()

		const prevented = pressCtrl('f')

		expect(focusInput).not.toHaveBeenCalled()
		expect(prevented).not.toHaveBeenCalled()
		wrapper.destroy()
	})

	// Only Ctrl+F defers to the browser. Ctrl+K has no native meaning worth preserving
	// (in Firefox it focuses the address bar), so it stays claimed even when engaged.
	it('does not make Ctrl+K fall through as well', () => {
		const wrapper = factory()
		vi.spyOn(wrapper.vm, 'focusInput').mockImplementation(() => {})
		wrapper.vm.showUnifiedSearch = true

		const prevented = pressCtrl('k')

		expect(prevented).toHaveBeenCalled()
		wrapper.destroy()
	})
})

describe('UnifiedSearch combobox expanded state', () => {
	// The header input is the combobox for the results popover, so while that popover is
	// shut it must report collapsed rather than point aria-controls at an unrendered panel.
	it('reports collapsed while the results popover is closed', async () => {
		const wrapper = factory()
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

describe('UnifiedSearch funnel reveal', () => {
	it('opens the modal and reveals filters when the input emits open-filters', async () => {
		const wrapper = factory()
		expect(wrapper.vm.showUnifiedSearch).toBe(false)
		wrapper.findComponent({ name: 'UnifiedSearchInput' }).vm.$emit('open-filters')
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showUnifiedSearch).toBe(true)
		expect(wrapper.vm.filtersRevealed).toBe(true)
	})

	it('resets the reveal state once the modal closes', async () => {
		const wrapper = factory()
		wrapper.vm.showUnifiedSearch = true
		wrapper.vm.filtersRevealed = true
		await wrapper.vm.$nextTick()
		wrapper.vm.showUnifiedSearch = false
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.filtersRevealed).toBe(false)
	})
})

describe('UnifiedSearch input dismiss', () => {
	it('closes the modal when the input emits close', async () => {
		const wrapper = factory()
		wrapper.vm.showUnifiedSearch = true
		await wrapper.vm.$nextTick()

		wrapper.findComponent({ name: 'UnifiedSearchInput' }).vm.$emit('close')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.showUnifiedSearch).toBe(false)
	})
})
