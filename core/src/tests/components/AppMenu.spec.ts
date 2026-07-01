/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INavigationEntry } from '../../types/navigation.d.ts'

import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

// Hoisted so mocks exist before the SFC's imports run.
const initialState = vi.hoisted(() => ({
	loadState: vi.fn(),
}))
vi.mock('@nextcloud/initial-state', () => initialState)

const auth = vi.hoisted(() => ({
	getCurrentUser: vi.fn(() => ({ isAdmin: false })),
}))
vi.mock('@nextcloud/auth', () => auth)

const eventBus = vi.hoisted(() => {
	const handlers: Record<string, Array<(payload: unknown) => void>> = {}
	return {
		subscribe: vi.fn((name: string, fn: (payload: unknown) => void) => {
			(handlers[name] ||= []).push(fn)
		}),
		unsubscribe: vi.fn((name: string, fn: (payload: unknown) => void) => {
			handlers[name] = (handlers[name] ?? []).filter((h) => h !== fn)
		}),
		emit: vi.fn((name: string, payload: unknown) => {
			(handlers[name] ?? []).forEach((h) => h(payload))
		}),
		__handlers: handlers,
	}
})
vi.mock('@nextcloud/event-bus', () => eventBus)

// Stub @nextcloud/router so we don't need a webroot for the moreApps URL.
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string) => path,
	imagePath: (app: string, file: string) => `/${app}/img/${file}`,
}))

// Build a minimal nav entry that satisfies INavigationEntry.
function makeApp(overrides: Partial<INavigationEntry> = {}): INavigationEntry {
	return {
		id: 'files',
		active: false,
		order: 0,
		href: '/apps/files',
		icon: '/apps/files/img/app.svg',
		type: 'link',
		name: 'Files',
		unread: 0,
		...overrides,
	}
}

function fakeApps(): INavigationEntry[] {
	return [
		makeApp({ id: 'files', name: 'Files', href: '/apps/files', active: true }),
		makeApp({ id: 'mail', name: 'Mail', href: '/apps/mail' }),
		makeApp({ id: 'calendar', name: 'Calendar', href: '/apps/calendar' }),
	]
}

function eightApps(activeIndex: number = -1): INavigationEntry[] {
	const ids = ['files', 'mail', 'calendar', 'contacts', 'notes', 'photos', 'talk', 'deck']
	return ids.map((id, i) => makeApp({
		id,
		name: id.charAt(0).toUpperCase() + id.slice(1),
		href: `/apps/${id}`,
		active: i === activeIndex,
	}))
}

// Import AFTER mocks are registered. Static `import` would hoist above
// vi.mock() and break the wiring; dynamic import in beforeAll/await is the
// idiomatic Vitest workaround when you need to control mock state per test.
import type AppMenuModule from '../../components/AppMenu.vue'
let AppMenu: typeof AppMenuModule

beforeEach(async () => {
	vi.clearAllMocks()
	for (const k of Object.keys(eventBus.__handlers)) {
		delete eventBus.__handlers[k]
	}
	initialState.loadState.mockImplementation((_app: string, key: string, fallback: unknown) => key === 'apps' ? fakeApps() : fallback)
	auth.getCurrentUser.mockReturnValue({ isAdmin: false })
	AppMenu = (await import('../../components/AppMenu.vue')).default
})

afterEach(() => {
	// NcPopover teleports to <body>; clear teleported nodes between tests.
	while (document.body.firstChild) {
		document.body.removeChild(document.body.firstChild)
	}
})

// Click the waffle trigger and poll until the teleported menuitems are in the
// DOM. NcPopover teleports to <body> so wrapper.find() can't see them; vi.waitFor
// retries the DOM query rather than relying on flaky nextTick/setTimeout flushes.
async function openPopover(wrapper: ReturnType<typeof mount>) {
	await wrapper.get('.app-menu__waffle').trigger('click')
	await vi.waitFor(() => {
		expect(document.querySelectorAll('[role="menuitem"]').length).toBeGreaterThan(0)
	})
}

describe('core: AppMenu', () => {
	it('labels the app menu trigger buttons for assistive technologies', () => {
		const wrapper = mount(AppMenu, { attachTo: document.body })

		expect(wrapper.get('.app-menu__waffle').attributes('aria-label')).toBe('Open apps menu')
		expect(wrapper.get('.app-menu__current-app').attributes('aria-label')).toBe('Open apps menu, currently in Files')
	})

	it('renders one AppItem per app in the list, plus the "App store" tile for non-admins', async () => {
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const items = document.querySelectorAll('[role="menuitem"]')
		expect(items).toHaveLength(4)
		const labels = Array.from(items).map((el) => el.querySelector('.app-item__label')?.textContent?.trim() ?? '')
		expect(labels).toEqual(['Files', 'Mail', 'Calendar', 'App store'])
	})

	it('renders the "More apps" tile when the current user is an admin', async () => {
		auth.getCurrentUser.mockReturnValue({ isAdmin: true })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const items = document.querySelectorAll('[role="menuitem"]')
		expect(items).toHaveLength(4)
		const moreApps = Array.from(items).find((el) => el.textContent?.includes('More apps'))
		expect(moreApps).toBeTruthy()
	})

	it('ArrowRight moves the roving stop from index 0 to index 1 and focuses it', async () => {
		initialState.loadState.mockImplementation((_a: string, key: string, fallback: unknown) => key === 'apps' ? eightApps() : fallback)
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const grid = document.querySelector('.app-menu__grid') as HTMLElement | null
		if (!grid) {
			throw new Error('app-menu__grid not in document')
		}
		grid.dispatchEvent(new KeyboardEvent('keydown', {
			key: 'ArrowRight',
			bubbles: true,
			cancelable: true,
		}))
		await wrapper.vm.$nextTick()
		// One extra tick: the handler awaits $nextTick before calling
		// .focus(), so we need a second flush before activeElement settles.
		await wrapper.vm.$nextTick()

		const items = document.querySelectorAll('[role="menuitem"]')
		expect(items[1].getAttribute('tabindex')).toBe('0')
		expect(items[0].getAttribute('tabindex')).toBe('-1')
		expect(document.activeElement).toBe(items[1])
	})

	it('returnFocusTarget points at the trigger that opened the popover', async () => {
		// focus-trap doesn't activate in jsdom (needs layout), so we can't assert
		// on document.activeElement. Instead we call returnFocusTarget() directly
		// (the same method NcPopover calls on deactivation).
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await wrapper.get('.app-menu__current-app').trigger('click')

		const currentApp = wrapper.get('.app-menu__current-app').element
		expect(wrapper.vm.returnFocusTarget()).toBe(currentApp)
	})

	it('falls back to the active settings entry when no app is active', () => {
		// Mimics being on /settings/admin/* where the active entry is registered
		// as type=settings (NavigationManager) and excluded from the `apps` list.
		initialState.loadState.mockImplementation((_a: string, key: string, fallback: unknown) => {
			if (key === 'apps') {
				return [makeApp({ id: 'files', name: 'Files', active: false })]
			}
			if (key === 'settingsNavEntries') {
				// Object keyed by entry id — matches PHP's serialization shape
				// (TemplateLayout ships the filtered associative array as-is).
				return {
					admin_settings: makeApp({
						id: 'admin_settings',
						name: 'Administration settings',
						type: 'settings',
						href: '/settings/admin/overview',
						icon: '/settings/img/admin.svg',
						active: true,
					}),
				}
			}
			return fallback
		})
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app').exists()).toBe(true)
		// Settings sub-section names are collapsed to a single "Settings" label.
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Settings')
	})

	it('prefers the active app over a settings entry when both are marked active', () => {
		initialState.loadState.mockImplementation((_a: string, key: string, fallback: unknown) => {
			if (key === 'apps') {
				return [makeApp({ id: 'files', name: 'Files', active: true })]
			}
			if (key === 'settingsNavEntries') {
				return { admin_settings: makeApp({ id: 'admin_settings', name: 'Administration settings', type: 'settings', active: true }) }
			}
			return fallback
		})
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Files')
	})

	it('does not render the current-app button when only the logout entry is active', () => {
		// Defensive: logout is an action, not a page, so it should never be the
		// "current section" even though it carries type=settings. NavigationManager
		// today never marks it active, but a future regression shouldn't leak a
		// "Log out" label into the header.
		initialState.loadState.mockImplementation((_a: string, key: string, fallback: unknown) => {
			if (key === 'apps') {
				return [makeApp({ id: 'files', name: 'Files', active: false })]
			}
			if (key === 'settingsNavEntries') {
				return { logout: makeApp({ id: 'logout', name: 'Log out', type: 'settings', href: '/logout', active: true }) }
			}
			return fallback
		})
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app').exists()).toBe(false)
	})

	// Hover-to-open behaviour. Uses fake timers to drive the open/close delays
	// without real waits. We assert on the reactive `opened` flag rather than the
	// teleported DOM so the tests stay independent of NcPopover's portal timing.
	describe('hover-to-open', () => {
		beforeEach(() => vi.useFakeTimers())
		afterEach(() => vi.useRealTimers())

		it('opens after a short delay when hovering the trigger, not immediately', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')

			// The intent-pause means it must not open on the same tick.
			expect(wrapper.vm.opened).toBe(false)
			vi.advanceTimersByTime(150)
			expect(wrapper.vm.opened).toBe(true)
		})

		it('cancels opening when the cursor leaves before the delay elapses', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(100)
			await wrapper.get('.app-menu__trigger').trigger('mouseleave')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('stays open when the cursor moves from the trigger into the popover', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)
			expect(wrapper.vm.opened).toBe(true)

			// Leaving the trigger schedules a close; entering the popover within the
			// grace period must cancel it.
			await wrapper.get('.app-menu__trigger').trigger('mouseleave')
			wrapper.vm.onPopoverPointerEnter()
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(true)
		})

		it('closes shortly after the cursor leaves the popover', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)
			wrapper.vm.onPopoverPointerEnter()
			expect(wrapper.vm.opened).toBe(true)

			wrapper.vm.onPointerLeave()
			vi.advanceTimersByTime(300)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('does not open on focus, only on hover', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__waffle').trigger('focus')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('drops the focus ring after a hover-out close (blurs the returned trigger)', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)

			// Simulate the focus-trap returning focus to the waffle on close.
			const waffle = wrapper.get('.app-menu__waffle').element as HTMLElement
			waffle.focus()
			expect(document.activeElement).toBe(waffle)

			// Hover-out closes via pointer; after-hide should blur the trigger.
			await wrapper.get('.app-menu__trigger').trigger('mouseleave')
			vi.advanceTimersByTime(300)
			wrapper.vm.onPopoverAfterHide()

			expect(document.activeElement).not.toBe(waffle)
		})

		it('keeps focus on the trigger after a keyboard close (ring stays)', () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			const waffle = wrapper.get('.app-menu__waffle').element as HTMLElement
			waffle.focus()

			// Keyboard-style close: no pointer flag set, so focus must be retained.
			wrapper.vm.opened = true
			wrapper.vm.opened = false
			wrapper.vm.onPopoverAfterHide()

			expect(document.activeElement).toBe(waffle)
		})

		it('tells the focus-trap not to restore focus on a pointer close', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)
			await wrapper.get('.app-menu__trigger').trigger('mouseleave')
			vi.advanceTimersByTime(300)

			// The focus-trap consults returnFocusTarget() on deactivation; false
			// means "leave focus where it is", so no ring flashes on the trigger.
			expect(wrapper.vm.returnFocusTarget()).toBe(false)
		})

		it('ignores a trigger click right after a hover-open (habitual click-to-open)', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)
			expect(wrapper.vm.opened).toBe(true)

			// A click within the grace window must not toggle the menu shut.
			await wrapper.get('.app-menu__waffle').trigger('click')
			expect(wrapper.vm.opened).toBe(true)
		})

		it('allows closing by click once the grace window elapses', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)
			vi.advanceTimersByTime(500) // grace window elapses

			await wrapper.get('.app-menu__waffle').trigger('click')
			expect(wrapper.vm.opened).toBe(false)
		})

		it('blocks the popover auto-hide during the grace window, allows it after', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)

			// autoHideCheck() feeds floating-ui: false = don't close on outside
			// click (e.g. a habitual click on the trigger) during the grace window.
			expect(wrapper.vm.autoHideCheck()).toBe(false)

			vi.advanceTimersByTime(500)
			expect(wrapper.vm.autoHideCheck()).toBe(true)
		})

		it('suppresses the tile focus ring on a hover-open and reveals it on keyboard nav', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(150)

			// Pointer (hover) open: the active tile is focused but its ring is hidden.
			expect(wrapper.vm.suppressGridFocusRing).toBe(true)

			// Any keyboard navigation of the grid reveals the ring again.
			wrapper.vm.onGridKeydown({ key: 'ArrowRight', preventDefault() {}, stopPropagation() {} } as unknown as KeyboardEvent)
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.suppressGridFocusRing).toBe(false)
		})

		it('shows the tile focus ring on a keyboard open but not a mouse-click open', () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })

			// Keyboard activation reports detail 0 → ring shown (matches master).
			wrapper.vm.onTriggerClick('waffle', { detail: 0 } as MouseEvent)
			expect(wrapper.vm.opened).toBe(true)
			expect(wrapper.vm.suppressGridFocusRing).toBe(false)

			// Close, then a real mouse click (detail > 0) → ring suppressed.
			wrapper.vm.onTriggerClick('waffle', { detail: 1 } as MouseEvent)
			expect(wrapper.vm.opened).toBe(false)
			wrapper.vm.onTriggerClick('waffle', { detail: 1 } as MouseEvent)
			expect(wrapper.vm.opened).toBe(true)
			expect(wrapper.vm.suppressGridFocusRing).toBe(true)
		})
	})
})
