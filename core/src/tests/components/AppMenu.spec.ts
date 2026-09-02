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

// Mimics a page where the active entry is a settings one, so it is excluded
// from the `apps` list. The object shape matches PHP's serialization, which
// ships getAll('settings') keyed by entry id.
function mockActiveSettingsEntry(overrides: Partial<INavigationEntry>): void {
	const entry = makeApp({ type: 'settings', active: true, ...overrides })
	initialState.loadState.mockImplementation(stateFor({
		apps: [makeApp({ id: 'files', name: 'Files', active: false })],
		settingsNavEntries: { [entry.id]: entry },
	}))
}

// Navigation actions (INavigationManager::TYPE_ACTION). Without an `href` the
// entry is handler-only and activation is broadcast on the event bus.
function makeAction(id: string, href: string = ''): INavigationEntry {
	return makeApp({
		id,
		name: id.charAt(0).toUpperCase() + id.slice(1),
		type: 'action',
		href,
		icon: `/core/img/actions/${id}.svg`,
	})
}

function fakeActions(count: number): INavigationEntry[] {
	const ids = ['logout', 'help', 'settings', 'status', 'about', 'shortcuts']
	return ids.slice(0, count).map((id) => makeAction(id))
}

// AppMenu hides the app store tile when the state is absent, so a default
// instance has to supply it.
function stateFor(states: Record<string, unknown>) {
	const all: Record<string, unknown> = { appStoreLinkShown: true, ...states }
	return (_app: string, key: string, fallback: unknown) => key in all ? all[key] : fallback
}

// loadState implementation serving both apps and navigation actions.
function stateWith(apps: INavigationEntry[], actions: INavigationEntry[]) {
	return stateFor({ apps, navigationActions: actions })
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
	initialState.loadState.mockImplementation(stateFor({ apps: fakeApps() }))
	auth.getCurrentUser.mockReturnValue({ isAdmin: false })
	AppMenu = (await import('../../components/AppMenu.vue')).default
})

afterEach(() => {
	// NcPopover teleports to <body>; clear teleported nodes between tests.
	while (document.body.firstChild) {
		document.body.removeChild(document.body.firstChild)
	}
})

function gridLabels(): string[] {
	return Array.from(document.querySelectorAll('.app-menu__grid [role="menuitem"]'))
		.map((el) => el.querySelector('.app-item__label')?.textContent?.trim() ?? '')
}

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

		expect(gridLabels()).toEqual(['Files', 'Mail', 'Calendar', 'App store'])
	})

	it('omits the "App store" tile when the instance does not offer it', async () => {
		initialState.loadState.mockImplementation(stateFor({ apps: fakeApps(), appStoreLinkShown: false }))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		expect(gridLabels()).toEqual(['Files', 'Mail', 'Calendar'])
	})

	it('keeps the "More apps" tile for admins when the app store link is hidden', async () => {
		initialState.loadState.mockImplementation(stateFor({ apps: fakeApps(), appStoreLinkShown: false }))
		auth.getCurrentUser.mockReturnValue({ isAdmin: true })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		expect(gridLabels()).toEqual(['Files', 'Mail', 'Calendar', 'More apps'])
	})

	it('renders the "More apps" tile when the current user is an admin', async () => {
		auth.getCurrentUser.mockReturnValue({ isAdmin: true })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const items = document.querySelectorAll('.app-menu__grid [role="menuitem"]')
		expect(items).toHaveLength(4)
		const moreApps = Array.from(items).find((el) => el.textContent?.includes('More apps'))
		expect(moreApps).toBeTruthy()
	})

	it('marks the "More apps" tile active on the app management page', async () => {
		auth.getCurrentUser.mockReturnValue({ isAdmin: true })
		mockActiveSettingsEntry({ id: 'appstore', name: 'Apps', href: '/settings/apps' })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const moreApps = Array.from(document.querySelectorAll('[role="menuitem"]'))
			.find((el) => el.textContent?.includes('More apps'))
		expect(moreApps?.classList.contains('app-item--active')).toBe(true)
		expect(moreApps?.getAttribute('aria-current')).toBe('page')
	})

	it('ArrowRight moves the roving stop from index 0 to index 1 and focuses it', async () => {
		initialState.loadState.mockImplementation(stateFor({ apps: eightApps() }))
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
		// Mimics being on /settings/admin/*
		mockActiveSettingsEntry({
			id: 'settings_administration',
			name: 'Administration settings',
			href: '/settings/admin/overview',
			icon: '/settings/img/admin.svg',
		})
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app').exists()).toBe(true)
		// Settings sub-section names are collapsed to a single "Settings" label.
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Settings')
	})

	it('keeps the own name of settings entries outside the settings app', () => {
		// On /settings/apps the active entry is the app management one, which
		// shows "Apps" here and in the account menu, not "Settings".
		mockActiveSettingsEntry({ id: 'appstore', name: 'Apps', href: '/settings/apps', icon: '/apps/appstore/img/app-dark.svg' })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Apps')
		// Its own icon, not the generic cog of the settings sections
		expect(wrapper.find('.app-menu__current-app-glyph').attributes('style'))
			.toContain('/apps/appstore/img/app-dark.svg')
	})

	it('shows the profile page as "Profile" with the generic user icon', () => {
		// The profile app names its entry "View profile" for the account menu
		// and ships no icon, so both are replaced here.
		mockActiveSettingsEntry({ id: 'profile', name: 'View profile', href: '/u/admin', icon: '' })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Profile')
		expect(wrapper.find('.app-menu__current-app-glyph').attributes('style'))
			.toContain('/core/img/actions/user.svg')
	})

	it('falls back to the cog for entries without an icon', () => {
		mockActiveSettingsEntry({ id: 'help', name: 'Help & privacy', href: '/settings/help', icon: '' })
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app-cog').exists()).toBe(true)
		expect(wrapper.find('.app-menu__current-app-glyph').exists()).toBe(false)
	})

	it('prefers the active app over a settings entry when both are marked active', () => {
		initialState.loadState.mockImplementation(stateFor({
			apps: [makeApp({ id: 'files', name: 'Files', active: true })],
			settingsNavEntries: { settings_administration: makeApp({ id: 'settings_administration', name: 'Administration settings', type: 'settings', active: true }) },
		}))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app-name').text()).toBe('Files')
	})

	it('does not render the current-app button when only the logout entry is active', () => {
		// Defensive: logout is an action, not a page, so it should never be the
		// "current section" even though it carries type=settings. NavigationManager
		// today never marks it active, but a future regression shouldn't leak a
		// "Log out" label into the header.
		initialState.loadState.mockImplementation(stateFor({
			apps: [makeApp({ id: 'files', name: 'Files', active: false })],
			settingsNavEntries: { logout: makeApp({ id: 'logout', name: 'Log out', type: 'settings', href: '/logout', active: true }) },
		}))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		expect(wrapper.find('.app-menu__current-app').exists()).toBe(false)
	})

	// Hover-to-open behaviour. Uses fake timers to drive the open/close delays
	// without real waits. We assert on the reactive `opened` flag rather than the
	// teleported DOM so the tests stay independent of NcPopover's portal timing.
	describe('hover-to-open', () => {
		beforeEach(() => {
			vi.useFakeTimers()
			// jsdom reports the document as unfocused, which the hover guard checks.
			vi.spyOn(document, 'hasFocus').mockReturnValue(true)
		})
		afterEach(() => {
			vi.useRealTimers()
			vi.restoreAllMocks()
		})

		it('opens after a short delay when hovering the trigger, not immediately', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')

			// The intent-pause means it must not open on the same tick.
			expect(wrapper.vm.opened).toBe(false)
			vi.advanceTimersByTime(90)
			expect(wrapper.vm.opened).toBe(true)
		})

		it('cancels opening when the cursor leaves before the delay elapses', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(60)
			await wrapper.get('.app-menu__trigger').trigger('mouseleave')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('stays open when the cursor moves from the trigger into the popover', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)
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
			vi.advanceTimersByTime(90)
			wrapper.vm.onPopoverPointerEnter()
			expect(wrapper.vm.opened).toBe(true)

			wrapper.vm.onPointerLeave()
			vi.advanceTimersByTime(180)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('does not open on focus, only on hover', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__waffle').trigger('focus')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
		})

		it('opens on hover without the focus trap, so focus is not stolen', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)

			// Hovering must not pull focus out of e.g. the search field.
			expect(wrapper.vm.hoverOpen).toBe(true)
		})

		it('keeps the focus trap for clicks, so keyboard use is unchanged', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__waffle').trigger('click')

			expect(wrapper.vm.opened).toBe(true)
			expect(wrapper.vm.hoverOpen).toBe(false)
		})

		it('restores the focus trap when a click follows a hover-open', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)
			vi.advanceTimersByTime(500) // click grace over
			await wrapper.get('.app-menu__waffle').trigger('click')

			expect(wrapper.vm.hoverOpen).toBe(false)
		})

		it('returns focus to the button that opened the menu', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__current-app').trigger('click')

			expect(wrapper.vm.returnFocusTarget()).toBe(wrapper.get('.app-menu__current-app').element)
		})

		it('ignores a trigger click right after a hover-open (habitual click-to-open)', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)
			expect(wrapper.vm.opened).toBe(true)

			// A click within the grace window must not toggle the menu shut.
			await wrapper.get('.app-menu__waffle').trigger('click')
			expect(wrapper.vm.opened).toBe(true)
		})

		it('allows closing by click once the grace window elapses', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)
			vi.advanceTimersByTime(500) // grace window elapses

			await wrapper.get('.app-menu__waffle').trigger('click')
			expect(wrapper.vm.opened).toBe(false)
		})

		it('blocks the popover auto-hide during the grace window, allows it after', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(90)

			// autoHideCheck() feeds floating-ui: false = don't close on outside
			// click (e.g. a habitual click on the trigger) during the grace window.
			expect(wrapper.vm.autoHideCheck()).toBe(false)

			vi.advanceTimersByTime(500)
			expect(wrapper.vm.autoHideCheck()).toBe(true)
		})

		it('does not open on hover without a fine, hovering pointer (touch)', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			// jsdom has no matchMedia, so define it for this test only.
			window.matchMedia = (() => ({ matches: false })) as unknown as typeof window.matchMedia

			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
			// @ts-expect-error restore the jsdom default
			delete window.matchMedia
		})

		it('does not open on hover while the window is unfocused', async () => {
			const wrapper = mount(AppMenu, { attachTo: document.body })
			vi.spyOn(document, 'hasFocus').mockReturnValue(false)

			await wrapper.get('.app-menu__trigger').trigger('mouseenter')
			vi.advanceTimersByTime(500)

			expect(wrapper.vm.opened).toBe(false)
		})
	})
})

// The submenu of the overflowing actions is teleported next to the app menu
// popover, so it is queried from the document rather than from the row.
function rowItems(): NodeListOf<HTMLElement> {
	return document.querySelectorAll('.app-menu-actions [role="menuitem"]')
}

function submenuItems(): NodeListOf<HTMLElement> {
	return document.querySelectorAll('.app-menu-actions__submenu [role="menuitem"]')
}

function pressKey(target: Element, key: string) {
	target.dispatchEvent(new KeyboardEvent('keydown', {
		key,
		bubbles: true,
		cancelable: true,
	}))
}

describe('core: AppMenu navigation actions', () => {
	it('does not render the actions row when no actions are registered', async () => {
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		expect(document.querySelector('.app-menu-actions')).toBeNull()
	})

	it('renders one item per navigation action below the app grid', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(3)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const labels = Array.from(rowItems()).map((el) => el.querySelector('.app-item__label')?.textContent?.trim())
		expect(labels).toEqual(['Logout', 'Help', 'Settings'])
		// The row is a sibling of the scrolling grid, so it stays visible.
		expect(document.querySelector('.app-menu__popover > :last-child')).toBe(document.querySelector('.app-menu-actions'))
	})

	it('renders actions with the flat action icon and apps with the app icon', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(3)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		expect(rowItems()[0].querySelector('.app-action-icon')).toBeTruthy()
		expect(rowItems()[0].querySelector('.app-icon')).toBeNull()
		expect(document.querySelector('.app-menu__grid .app-icon')).toBeTruthy()
	})

	it('shows an indicator in the color of the action, and none without a color', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), [
			{ ...makeAction('upload'), color: '#ff00ff' },
			makeAction('logout'),
		]))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const indicator = rowItems()[0].querySelector('.app-action-icon__indicator') as HTMLElement | null
		expect(indicator).toBeTruthy()
		expect(indicator!.style.getPropertyValue('--app-action-icon-indicator-color')).toBe('#ff00ff')
		expect(rowItems()[1].querySelector('.app-action-icon__indicator')).toBeNull()
	})

	it('renders an action with a link as an anchor and one without as a button', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), [
			makeAction('help', '/settings/help'),
			makeAction('logout'),
		]))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const items = rowItems()
		expect(items[0].tagName).toBe('A')
		expect(items[0].getAttribute('href')).toBe('/settings/help')
		expect(items[1].tagName).toBe('BUTTON')
		expect(items[1].hasAttribute('href')).toBe(false)
	})

	it('broadcasts the action entry on the event bus and closes the menu for actions without a link', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(2)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		rowItems()[0].click()
		await wrapper.vm.$nextTick()

		expect(eventBus.emit).toHaveBeenCalledWith('core:navigation:action', makeAction('logout'))
		expect(wrapper.vm.opened).toBe(false)
	})

	it('does not broadcast an event for actions that have a link', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), [makeAction('help', '/settings/help')]))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		// jsdom cannot navigate, so swallow the anchor's default action.
		const swallowNavigation = (event: Event) => event.preventDefault()
		document.addEventListener('click', swallowNavigation)
		rowItems()[0].click()
		document.removeEventListener('click', swallowNavigation)
		await wrapper.vm.$nextTick()

		expect(eventBus.emit).not.toHaveBeenCalledWith('core:navigation:action', expect.anything())
		expect(wrapper.vm.opened).toBe(false)
	})

	it('arrow keys move the roving stop within the actions row', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(3)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		pressKey(document.querySelector('.app-menu-actions')!, 'ArrowRight')
		await wrapper.vm.$nextTick()

		expect(document.activeElement).toBe(rowItems()[1])
		expect(rowItems()[1].getAttribute('tabindex')).toBe('0')
		expect(rowItems()[0].getAttribute('tabindex')).toBe('-1')

		// Clamps at the start of the row instead of wrapping around.
		pressKey(rowItems()[1], 'ArrowLeft')
		pressKey(rowItems()[0], 'ArrowLeft')
		await wrapper.vm.$nextTick()

		expect(document.activeElement).toBe(rowItems()[0])
	})

	it('moves the actions that do not fit into a "More" submenu', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(6)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		const items = rowItems()
		// Three actions plus the trailing overflow item make up the single row.
		expect(items).toHaveLength(4)
		expect(items[3].textContent).toContain('More')
		expect(items[3].getAttribute('aria-haspopup')).toBe('menu')
		expect(items[3].getAttribute('aria-expanded')).toBe('false')
		expect(document.querySelector('.app-menu-actions__submenu')).toBeNull()

		items[3].click()
		await vi.waitFor(() => {
			expect(submenuItems().length).toBeGreaterThan(0)
		})

		const labels = Array.from(submenuItems()).map((el) => el.querySelector('.app-item__label')?.textContent?.trim())
		expect(labels).toEqual(['Status', 'About', 'Shortcuts'])
		expect(rowItems()[3].getAttribute('aria-expanded')).toBe('true')
		// The app menu itself stays open while the submenu is shown.
		expect(wrapper.vm.opened).toBe(true)
	})

	it('focuses the first submenu entry on open and navigates it with arrow keys', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(6)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		rowItems()[3].click()
		await vi.waitFor(() => {
			expect(document.activeElement).toBe(submenuItems()[0])
		})

		pressKey(submenuItems()[0], 'ArrowDown')
		await wrapper.vm.$nextTick()
		expect(document.activeElement).toBe(submenuItems()[1])
		expect(submenuItems()[1].getAttribute('tabindex')).toBe('0')

		// Clamps at the end of the list.
		pressKey(submenuItems()[1], 'End')
		await wrapper.vm.$nextTick()
		expect(document.activeElement).toBe(submenuItems()[2])
		pressKey(submenuItems()[2], 'ArrowDown')
		await wrapper.vm.$nextTick()
		expect(document.activeElement).toBe(submenuItems()[2])
	})

	it('activating a submenu action closes both the submenu and the app menu', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(6)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		rowItems()[3].click()
		await vi.waitFor(() => {
			expect(submenuItems().length).toBeGreaterThan(0)
		})

		// Enter has to run the same path as a click (the event-bus broadcast).
		pressKey(submenuItems()[0], 'Enter')
		await wrapper.vm.$nextTick()

		expect(eventBus.emit).toHaveBeenCalledWith('core:navigation:action', makeAction('status'))
		expect(wrapper.vm.opened).toBe(false)
		// The popover keeps its content mounted once shown, so the collapsed
		// state is asserted on the trigger rather than on the submenu node.
		expect(rowItems()[3].getAttribute('aria-expanded')).toBe('false')
	})

	it('Escape closes only the submenu, not the app menu', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(6)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		rowItems()[3].click()
		await vi.waitFor(() => {
			expect(submenuItems().length).toBeGreaterThan(0)
		})

		pressKey(submenuItems()[0], 'Escape')
		await wrapper.vm.$nextTick()

		expect(rowItems()[3].getAttribute('aria-expanded')).toBe('false')
		expect(wrapper.vm.opened).toBe(true)
	})

	it('Enter on the overflow item opens the submenu instead of closing the menu', async () => {
		initialState.loadState.mockImplementation(stateWith(fakeApps(), fakeActions(6)))
		const wrapper = mount(AppMenu, { attachTo: document.body })
		await openPopover(wrapper)

		// Roving stop 3 is the overflow item.
		pressKey(document.querySelector('.app-menu-actions')!, 'End')
		await wrapper.vm.$nextTick()
		expect(document.activeElement).toBe(rowItems()[3])

		pressKey(rowItems()[3], 'Enter')
		await vi.waitFor(() => {
			expect(submenuItems().length).toBeGreaterThan(0)
		})
		expect(wrapper.vm.opened).toBe(true)
	})
})
