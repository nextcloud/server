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

// jsdom has no layout, so useElementSize would report 0 and no pinned entry
// would ever render. Expose the width ref so tests can set the available space.
const elementSize = vi.hoisted(() => ({ width: { value: 0 } }))
vi.mock('@vueuse/core', async () => {
	const { ref } = await import('vue')
	elementSize.width = ref(400)
	return {
		useElementSize: () => ({ width: elementSize.width }),
	}
})

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
	elementSize.width.value = 400
	AppMenu = (await import('../../components/AppMenu.vue')).default
})

/** loadState mock shipping both the app list and a set of pinned entry ids */
function mockAppsWithPinned(apps: INavigationEntry[], pinned: string[]) {
	initialState.loadState.mockImplementation((_app: string, key: string, fallback: unknown) => {
		if (key === 'apps') {
			return apps
		}
		if (key === 'apps-pinned') {
			return pinned
		}
		return fallback
	})
}

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

	it('renders pinned apps inline following the navigation order', () => {
		// Pinned ids are stored unordered; the rendered order follows appList.
		mockAppsWithPinned(fakeApps(), ['calendar', 'files'])
		mount(AppMenu, { attachTo: document.body })

		const labels = Array.from(document.querySelectorAll('.app-menu-entry__label')).map((el) => el.textContent?.trim())
		expect(labels).toEqual(['Files', 'Calendar'])
	})

	it('ignores pinned ids without a matching navigation entry', () => {
		mockAppsWithPinned(fakeApps(), ['mail', 'disabled-app'])
		mount(AppMenu, { attachTo: document.body })

		const labels = Array.from(document.querySelectorAll('.app-menu-entry__label')).map((el) => el.textContent?.trim())
		expect(labels).toEqual(['Mail'])
	})

	it('renders no pinned list when nothing is pinned', () => {
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__list').exists()).toBe(false)
	})

	it('only renders as many pinned entries as fit the measured width', () => {
		// 100px fits two 44px entries
		elementSize.width.value = 100
		mockAppsWithPinned(fakeApps(), ['files', 'mail', 'calendar'])
		const wrapper = mount(AppMenu, { attachTo: document.body })

		expect(wrapper.findAll('.app-menu-entry')).toHaveLength(2)
	})

	it('hides the current-app button while the active app is visible in the pinned list', () => {
		mockAppsWithPinned(fakeApps(), ['files'])
		const wrapper = mount(AppMenu, { attachTo: document.body })

		expect(wrapper.find('.app-menu__current-app').exists()).toBe(false)
		expect(wrapper.get('.app-menu-entry a').attributes('aria-current')).toBe('page')
	})

	it('keeps the current-app button when the active app is not pinned', () => {
		mockAppsWithPinned(fakeApps(), ['mail'])
		const wrapper = mount(AppMenu, { attachTo: document.body })

		expect(wrapper.find('.app-menu__current-app').exists()).toBe(true)
		expect(wrapper.get('.app-menu__current-app-name').text()).toBe('Files')
	})

	it('keeps the current-app button when the active app is pinned but truncated away', () => {
		// One 44px slot: only Files renders, active Calendar is cut off
		elementSize.width.value = 50
		mockAppsWithPinned(eightApps(2).slice(0, 3), ['files', 'calendar'])
		const wrapper = mount(AppMenu, { attachTo: document.body })

		expect(wrapper.findAll('.app-menu-entry')).toHaveLength(1)
		expect(wrapper.find('.app-menu__current-app').exists()).toBe(true)
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
})
