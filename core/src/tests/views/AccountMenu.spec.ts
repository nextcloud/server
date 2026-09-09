/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import AccountMenu from '../../views/AccountMenu.vue'

const capabilities = vi.hoisted(() => ({
	getCapabilities: vi.fn(() => ({}) as Record<string, unknown>),
}))
vi.mock('@nextcloud/capabilities', () => capabilities)

// Components in this tree read initial state at module scope, so the default
// must honour the fallback before any test runs
const initialState = vi.hoisted(() => ({
	loadState: vi.fn((_app: string, _key: string, fallback: unknown) => fallback),
}))
vi.mock('@nextcloud/initial-state', () => initialState)

const eventBus = vi.hoisted(() => ({
	emit: vi.fn(),
	subscribe: vi.fn(),
	unsubscribe: vi.fn(),
}))
vi.mock('@nextcloud/event-bus', () => eventBus)

const axiosMock = vi.hoisted(() => ({
	default: { get: vi.fn() },
}))
vi.mock('@nextcloud/axios', () => axiosMock)

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'alice', displayName: 'Alice' }),
	getRequestToken: () => 'token',
}))

vi.mock('@nextcloud/password-confirmation', () => ({
	addPasswordConfirmationInterceptors: vi.fn(),
	PwdConfirmationMode: { Strict: 0 },
}))

const NO_STATUS = { status: null, icon: null, message: null }

const MOUNT_OPTIONS = {
	stubs: {
		AccountMenuEntry: true,
		AccountMenuProfileEntry: true,
	},
}

const SETTINGS_NAV_ENTRIES = {
	profile: {
		id: 'profile',
		name: 'View profile',
		href: '/u/alice',
		active: false,
	},
}

/**
 * NcAvatar subscribes to the status event too, and children mount first, so
 * indexing `subscribe.mock.calls` would grab the wrong handler.
 */
function emitToSubscribers(event: string, payload: unknown) {
	const handlers = eventBus.subscribe.mock.calls
		.filter(([name]) => name === event)
		.map(([, handler]) => handler)

	expect(handlers.length).toBeGreaterThan(0)
	handlers.forEach((handler) => handler(payload))
}

function mockUserStatusState(userStatus: unknown) {
	initialState.loadState.mockImplementation((app: string, key: string, fallback: unknown) => {
		if (app === 'core' && key === 'settingsNavEntries') {
			return SETTINGS_NAV_ENTRIES
		}
		if (app === 'user_status' && key === 'status') {
			return userStatus
		}
		return fallback
	})
}

describe('core: AccountMenu', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		capabilities.getCapabilities.mockReturnValue({ user_status: { enabled: true } })
		mockUserStatusState({
			userId: 'alice',
			status: 'dnd',
			icon: '🎉',
			message: 'Party time',
			statusIsUserDefined: true,
			messageIsPredefined: false,
			messageId: null,
			clearAt: null,
		})
	})

	it('preloads the avatar with the status from the initial state', () => {
		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		expect(wrapper.findComponent(NcAvatar).props('preloadedUserStatus'))
			.toEqual({ status: 'dnd', icon: '🎉', message: 'Party time' })
	})

	it('describes the status for assistive technologies', () => {
		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		expect(wrapper.findComponent(NcHeaderMenu).props('description'))
			.toBe('Avatar of Alice — Do not disturb — 🎉 — Party time')
	})

	it('does not request the status over the network', () => {
		mount(AccountMenu, MOUNT_OPTIONS)

		expect(axiosMock.default.get).not.toHaveBeenCalled()
	})

	it('preloads an empty status when the user_status app is disabled', () => {
		capabilities.getCapabilities.mockReturnValue({})
		mockUserStatusState(null)

		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		expect(wrapper.findComponent(NcAvatar).props('preloadedUserStatus')).toEqual(NO_STATUS)
		expect(wrapper.findComponent(NcHeaderMenu).props('description')).toBe('Avatar of Alice')
	})

	it('preloads an empty status when the app provided an unusable payload', () => {
		mockUserStatusState([])

		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		expect(wrapper.findComponent(NcAvatar).props('preloadedUserStatus')).toEqual(NO_STATUS)
		expect(wrapper.findComponent(NcHeaderMenu).props('description')).toBe('Avatar of Alice')
	})

	it('updates the status when the event bus announces a change', async () => {
		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		emitToSubscribers('user_status:status.updated', { userId: 'alice', status: 'online', icon: null, message: null })
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent(NcAvatar).props('preloadedUserStatus')).toEqual({
			status: 'online',
			icon: null,
			message: null,
		})
		expect(wrapper.findComponent(NcHeaderMenu).props('description')).toBe('Avatar of Alice — Online')
	})

	it('ignores status updates for other users', async () => {
		const wrapper = mount(AccountMenu, MOUNT_OPTIONS)

		emitToSubscribers('user_status:status.updated', { userId: 'bob', status: 'online', icon: null, message: null })
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent(NcAvatar).props('preloadedUserStatus'))
			.toEqual({ status: 'dnd', icon: '🎉', message: 'Party time' })
	})
})
