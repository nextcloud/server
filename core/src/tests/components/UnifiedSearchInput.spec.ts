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
		propsData: { query: '', expanded: false, filtersRevealed: false, ...propsData },
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

// The field wraps the input and its trailing controls; focus is tracked here.
async function focusField(wrapper) {
	await wrapper.find('.unified-search-input__field').trigger('focusin')
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

		await focusField(wrapper)

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

describe('UnifiedSearchInput trailing controls', () => {
	// Locate the funnel / clear / close buttons by their accessible name so we
	// assert what the user sees rather than internal classes. The l10n mock returns
	// the source string, and the label lands in either props or attrs depending on
	// how NcButton declares it, so read both.
	const labelOf = (button) => button.attributes('aria-label') ?? button.props('ariaLabel')
	const byLabel = (wrapper, label) => wrapper.findAllComponents({ name: 'NcButton' }).wrappers.find((button) => labelOf(button) === label)

	it('shows no trailing control while resting (blurred + empty)', () => {
		const wrapper = factory()
		expect(byLabel(wrapper, 'Filters')).toBeUndefined()
		expect(byLabel(wrapper, 'Clear search')).toBeUndefined()
		expect(byLabel(wrapper, 'Close search')).toBeUndefined()
	})

	it('shows the funnel and the close-X together when the empty field gains focus', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		expect(byLabel(wrapper, 'Filters')).toBeTruthy()
		expect(byLabel(wrapper, 'Close search')).toBeTruthy()
	})

	it('emits open-filters when the funnel is clicked', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		byLabel(wrapper, 'Filters').vm.$emit('click')
		expect(wrapper.emitted('open-filters')).toBeTruthy()
	})

	it('dismisses the search when the empty-field close-X is clicked', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		byLabel(wrapper, 'Close search').vm.$emit('click')
		expect(wrapper.emitted('close')).toBeTruthy()
		// Nothing to clear on an empty field, so no query update is emitted.
		expect(wrapper.emitted('update:query')).toBeUndefined()
	})

	it('swaps the funnel for a clear-X once a query is present', async () => {
		const wrapper = factory({ query: 'abc' })
		await focusField(wrapper)
		expect(byLabel(wrapper, 'Filters')).toBeUndefined()
		expect(byLabel(wrapper, 'Clear search')).toBeTruthy()
	})

	it('clears the query without closing when the clear-X is clicked', async () => {
		const wrapper = factory({ query: 'abc' })
		await focusField(wrapper)
		byLabel(wrapper, 'Clear search').vm.$emit('click')
		expect(wrapper.emitted('update:query')?.at(-1)).toEqual([''])
		expect(wrapper.emitted('close')).toBeUndefined()
		expect(wrapper.emitted('open-filters')).toBeUndefined()
	})

	it('keeps the close-X but drops the funnel once filters are revealed', async () => {
		const wrapper = factory({ filtersRevealed: true })
		await focusField(wrapper)
		expect(byLabel(wrapper, 'Filters')).toBeUndefined()
		expect(byLabel(wrapper, 'Close search')).toBeTruthy()
	})

	// expanded keeps the field active even when focus has moved into a teleported
	// filter menu (which blurs the input), so the close affordance must stay put.
	it('keeps the close-X while the popover is expanded, even without focus', () => {
		const wrapper = factory({ expanded: true })
		expect(byLabel(wrapper, 'Close search')).toBeTruthy()
	})

	it('hides all trailing controls when focus leaves the empty field', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		expect(byLabel(wrapper, 'Filters')).toBeTruthy()
		expect(byLabel(wrapper, 'Close search')).toBeTruthy()
		// relatedTarget outside the field -> no longer focused.
		await wrapper.find('.unified-search-input__field').trigger('focusout', { relatedTarget: document.body })
		expect(byLabel(wrapper, 'Filters')).toBeUndefined()
		expect(byLabel(wrapper, 'Close search')).toBeUndefined()
	})

	// Focus moving from the input onto a trailing control (funnel / close-X) stays
	// inside the field, so the controls must remain; this is why focus is tracked at
	// field level rather than on the bare input.
	it('keeps the trailing controls when focus moves within the field', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		const funnelEl = byLabel(wrapper, 'Filters')!.element
		// relatedTarget inside the field (the funnel button) -> still focused.
		await wrapper.find('.unified-search-input__field').trigger('focusout', { relatedTarget: funnelEl })
		expect(byLabel(wrapper, 'Filters')).toBeTruthy()
		expect(byLabel(wrapper, 'Close search')).toBeTruthy()
	})

	// The funnel must refocus the input before opening filters: revealing them unmounts
	// the funnel, and if focus were left on it the modal's focus trap would return focus
	// to <body> on close instead of the input.
	it('focuses the input when the funnel opens the filters', async () => {
		const wrapper = factory()
		await focusField(wrapper)
		const focusSpy = vi.spyOn(wrapper.find('input').element, 'focus')

		byLabel(wrapper, 'Filters')!.vm.$emit('click')

		expect(focusSpy).toHaveBeenCalled()
		expect(wrapper.emitted('open-filters')).toBeTruthy()
	})
})

describe('UnifiedSearchInput loading spinner', () => {
	const hasSpinner = (wrapper: ReturnType<typeof factory>) => wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()

	it('shows a spinner while a search is loading', () => {
		expect(hasSpinner(factory({ query: 'abc', loading: true }))).toBe(true)
	})

	it('shows no spinner when not loading', () => {
		expect(hasSpinner(factory({ query: 'abc', loading: false }))).toBe(false)
	})
})
