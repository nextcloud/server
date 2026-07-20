/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type * as L10n from '@nextcloud/l10n'

import { shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

const mobile = ref(false)
vi.mock('@nextcloud/vue/composables/useIsMobile', () => ({
	useIsSmallMobile: () => mobile,
	useIsMobile: () => ref(false),
}))
// Keep the real module (NcKbd's translation registration needs getLanguage at
// import time) but stub t to the identity so assertions stay locale-independent.
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...(await importOriginal<typeof L10n>()),
	t: (_: string, s: string) => s,
}))

import NcKbd from '@nextcloud/vue/components/NcKbd'
import UnifiedSearchInput from '../../components/UnifiedSearch/UnifiedSearchInput.vue'

function factory(propsData = {}) {
	return shallowMount(UnifiedSearchInput, {
		propsData: { query: '', ...propsData },
	})
}

/**
 * Dispatch a real keydown so Vue's @keydown listener runs, and report whether the
 * handler claimed the key (called preventDefault).
 */
function dispatchKey(wrapper: ReturnType<typeof factory>, key: string, init: KeyboardEventInit = {}) {
	const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...init })
	const prevented = vi.spyOn(event, 'preventDefault')
	wrapper.find('input').element.dispatchEvent(event)
	return prevented
}

beforeEach(() => {
	mobile.value = false
})
afterEach(() => vi.clearAllMocks())

describe('UnifiedSearchInput combobox ARIA', () => {
	it('references the active option and the results container while expanded', () => {
		const input = factory({ expanded: true, activeDescendantId: 'unified-search-result-files-0' }).find('input')

		expect(input.attributes('aria-activedescendant')).toBe('unified-search-result-files-0')
		expect(input.attributes('aria-controls')).toBe('unified-search-results')
	})

	it('drops the dangling references when the popover is closed', () => {
		const input = factory({ expanded: false, activeDescendantId: 'unified-search-result-files-0' }).find('input')

		expect(input.attributes('aria-activedescendant')).toBeUndefined()
		expect(input.attributes('aria-controls')).toBeUndefined()
	})
})

describe('UnifiedSearchInput keyboard navigation', () => {
	it.each([
		['ArrowDown', 'next'],
		['ArrowUp', 'prev'],
	])('emits navigate on %s and claims the key while expanded', (key, direction) => {
		const wrapper = factory({ expanded: true })

		const prevented = dispatchKey(wrapper, key)

		expect(wrapper.emitted('navigate')?.at(-1)).toEqual([direction])
		expect(prevented).toHaveBeenCalled()
	})

	// Home/End belong to the textbox caret in the combobox pattern (APG), so they
	// must not be hijacked for first/last-option jumps.
	it.each(['Home', 'End'])('leaves %s for caret movement even while expanded', (key) => {
		const wrapper = factory({ expanded: true })

		const prevented = dispatchKey(wrapper, key)

		expect(wrapper.emitted('navigate')).toBeUndefined()
		expect(prevented).not.toHaveBeenCalled()
	})

	it('emits activate on Enter while expanded', () => {
		const wrapper = factory({ expanded: true })

		const prevented = dispatchKey(wrapper, 'Enter')

		expect(wrapper.emitted('activate')).toHaveLength(1)
		expect(prevented).toHaveBeenCalled()
	})

	// During IME composition (CJK etc.) the popover is already open, so Enter/Arrows
	// must reach the input to commit/edit the composition, not drive the results.
	it('ignores keydown during IME composition', () => {
		const wrapper = factory({ expanded: true })

		const enter = dispatchKey(wrapper, 'Enter', { isComposing: true })
		const arrow = dispatchKey(wrapper, 'ArrowDown', { isComposing: true })

		expect(wrapper.emitted('activate')).toBeUndefined()
		expect(wrapper.emitted('navigate')).toBeUndefined()
		expect(enter).not.toHaveBeenCalled()
		expect(arrow).not.toHaveBeenCalled()
	})

	it('leaves navigation keys alone so typing/caret works when the popover is closed', () => {
		const wrapper = factory({ expanded: false })

		const prevented = dispatchKey(wrapper, 'ArrowDown')

		expect(wrapper.emitted('navigate')).toBeUndefined()
		expect(prevented).not.toHaveBeenCalled()
	})

	it('does not interfere with other keys', () => {
		const wrapper = factory({ expanded: true })

		const prevented = dispatchKey(wrapper, 'a')

		expect(wrapper.emitted('navigate')).toBeUndefined()
		expect(wrapper.emitted('activate')).toBeUndefined()
		expect(prevented).not.toHaveBeenCalled()
	})

	// Nothing else handles Escape while the popover is closed, so the input drops
	// focus itself, like a native find bar.
	it('drops focus on Escape while the popover is closed', () => {
		const wrapper = factory({ expanded: false })
		const blur = vi.spyOn(wrapper.find('input').element, 'blur')

		dispatchKey(wrapper, 'Escape')

		expect(blur).toHaveBeenCalled()
	})

	// While open, the modal owns Escape; the input must not also blur, or it fights
	// the focus-trap's return-focus.
	it('leaves Escape to the modal while the popover is open', () => {
		const wrapper = factory({ expanded: true })
		const blur = vi.spyOn(wrapper.find('input').element, 'blur')

		dispatchKey(wrapper, 'Escape')

		expect(blur).not.toHaveBeenCalled()
	})
})

describe('UnifiedSearchInput focus', () => {
	it('focuses the text field when asked (for the global shortcut)', () => {
		const wrapper = factory()
		const focusSpy = vi.spyOn(wrapper.find('input').element, 'focus')

		wrapper.vm.focus()

		expect(focusSpy).toHaveBeenCalled()
	})
})

describe('UnifiedSearchInput shortcut hint', () => {
	it('shows the keycap hint while the input is resting', () => {
		expect(factory({ query: '' }).findAllComponents(NcKbd)).toHaveLength(2)
	})

	it('hides the hint once the input is focused', async () => {
		const wrapper = factory({ query: '' })
		expect(wrapper.findAllComponents(NcKbd)).toHaveLength(2)

		wrapper.find('input').element.dispatchEvent(new FocusEvent('focus'))
		await wrapper.vm.$nextTick()

		expect(wrapper.findAllComponents(NcKbd)).toHaveLength(0)
	})

	it('hides the hint when a query is present', () => {
		expect(factory({ query: 'abc' }).findAllComponents(NcKbd)).toHaveLength(0)
	})

	// The ⌘-vs-Ctrl glyph and its localisation now live inside NcKbd; here we only
	// assert we hand it the right keys. symbol="Control" makes NcKbd pick the platform
	// glyph, "K" falls through to a literal cap.
	it('renders the modifier and K keycaps', () => {
		const keys = factory({ query: '' }).findAllComponents(NcKbd)
		expect(keys.at(0)!.props('symbol')).toBe('Control')
		expect(keys.at(1)!.props('symbol')).toBe('K')
	})

	it('hides the decorative hint from assistive tech', () => {
		const firstKey = factory({ query: '' }).findAllComponents(NcKbd).at(0)!
		expect(firstKey.element.closest('[aria-hidden="true"]')).not.toBeNull()
	})
})
