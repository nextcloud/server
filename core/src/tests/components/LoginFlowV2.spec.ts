/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as NextcloudAuth from '@nextcloud/auth'
import type { VueWrapper } from '@vue/test-utils'
import type { LoginFlowV2Context } from '../../types/loginFlowV2.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { confirmPassword, isPasswordConfirmationRequired } from '@nextcloud/password-confirmation'
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import LoginFlowContext from '../../components/LoginFlow/LoginFlowContext.vue'
import LoginFlowAuth from '../../views/LoginFlowAuth.vue'
import LoginFlowGrant from '../../views/LoginFlowGrant.vue'
import LoginFlowV2 from '../../views/LoginFlowV2.vue'

vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn() }))
vi.mock('@nextcloud/capabilities', () => ({ getCapabilities: () => ({}) }))
vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal<typeof NextcloudAuth>(),
	getRequestToken: () => 'request-token',
}))
vi.mock('@nextcloud/password-confirmation', () => ({
	confirmPassword: vi.fn(),
	isPasswordConfirmationRequired: vi.fn(),
	PwdConfirmationMode: { Lax: 'lax' },
}))

let context: LoginFlowV2Context
let page: 'auth' | 'grant'

const auth = {
	stateToken: 'state-token',
	loginRedirectUrl: '/login/v2/grant',
	appTokenUrl: '/login/v2/apptoken',
	direct: true,
}
const grant = {
	stateToken: 'state-token',
	actionUrl: '/login/v2/grant',
	direct: false,
}

const global = {
	stubs: {
		NcAvatar: true,
		NcDateTime: true,
		NcPopover: {
			name: 'NcPopover',
			props: ['shown'],
			template: '<div><slot name="trigger" /><slot v-if="shown" /></div>',
		},
	},
}

function button(wrapper: VueWrapper, label: string) {
	const result = wrapper.findAll('button, a').find((element) => element.text() === label)
	if (!result) {
		throw new Error('Missing button: ' + label)
	}
	return result
}

enableAutoUnmount(afterEach)

beforeEach(() => {
	page = 'auth'
	context = {
		userAgent: 'Mozilla/5.0 (Macintosh) mirall/35.0.1 (Nextcloud, macos-24.6.0 ClientArchitecture: arm64 OsArchitecture: arm64)',
		serverUrl: 'https://cloud.example.org/nextcloud',
		userId: null,
		userDisplayName: null,
		declineUrl: '/login/v2/decline',
		securitySettingsUrl: '/settings/user/security',
		clientIp: '2001:db8::1',
		browserIp: '2001:db8::1',
		createdAt: 1700000000,
	}
	vi.mocked(loadState).mockImplementation((_app, key, fallback) => {
		if (key === 'loginFlowContext') {
			return context
		}
		if (key === 'loginFlowAuth') {
			return page === 'auth' ? auth : fallback
		}
		if (key === 'loginFlowGrant') {
			return page === 'grant' ? grant : fallback
		}
		return fallback
	})
	vi.mocked(isPasswordConfirmationRequired).mockReturnValue(false)
	vi.mocked(confirmPassword).mockResolvedValue()
	vi.spyOn(axios, 'post').mockResolvedValue({ data: [] })
})

afterEach(() => {
	vi.restoreAllMocks()
	vi.clearAllMocks()
})

describe('Login flow request details', () => {
	it('identifies the user agent and each address and preserves the request timestamp', () => {
		context.browserIp = '2001:db8::2'
		const wrapper = mount(LoginFlowContext, { props: { context }, global })
		const details = Object.fromEntries(wrapper.findAll('dt').map((label) => [
			label.text(),
			label.element.nextElementSibling?.textContent?.trim(),
		]))

		expect(details).toMatchObject({
			'App user agent': expect.stringContaining(context.userAgent),
			'App IP address': context.clientIp,
			'Your browser’s IP address': context.browserIp,
		})
		expect(details['App user agent']).toContain('Self-reported')
		expect(wrapper.get('time').attributes('datetime')).toBe('2023-11-14T22:13:20.000Z')
		expect(wrapper.find('[aria-label="About the app user agent"]').exists()).toBe(true)
	})

	it('shows a fallback for missing user agent and addresses', () => {
		const wrapper = mount(LoginFlowContext, {
			props: { context: { ...context, userAgent: '', clientIp: null, browserIp: '' } },
			global,
		})

		const values = wrapper.findAll('dd > bdi').map((value) => value.text())
		expect(values).toEqual(['Unavailable', 'Unavailable', 'Unavailable'])
	})

	it('renders a long user agent as text, without interpreting markup', () => {
		context.userAgent = '<img src=x onerror=alert(1)>' + 'x'.repeat(1000)
		const wrapper = mount(LoginFlowContext, { props: { context }, global })

		expect(wrapper.get('bdi').text()).toBe(context.userAgent)
		expect(wrapper.find('dd img').exists()).toBe(false)
	})

	it.each([
		['initial connection', 'auth'],
		['account approval', 'grant'],
	] as const)('leaves the legacy %s page unchanged', (_name, state) => {
		page = state
		vi.mocked(loadState).mockReturnValueOnce({
			...(state === 'auth' ? auth : grant),
			client: 'Desktop app',
			instanceName: 'Nextcloud',
			userId: 'user',
			userDisplayName: 'User',
		})
		const wrapper = mount(state === 'auth' ? LoginFlowAuth : LoginFlowGrant, { global })

		expect(wrapper.findComponent(LoginFlowContext).exists()).toBe(false)
		expect(wrapper.text()).toContain('Desktop app')
	})
})

describe('Login flow v2 review', () => {
	it('offers decline and continue before signing in, with no grant action', () => {
		const wrapper = mount(LoginFlowV2, { global })

		expect(wrapper.findComponent(LoginFlowContext).exists()).toBe(false)
		expect(button(wrapper, 'Decline').attributes('type')).toBe('button')
		expect(button(wrapper, 'Continue').attributes('href')).toBe(auth.loginRedirectUrl)
		expect(wrapper.find('button[type="submit"]').exists()).toBe(false)
	})

	it.each([
		['User', ['User', 'user']],
		[null, ['user']],
		['user', ['user']],
	] as const)('identifies the account and offers explicit approval with display name %s', (displayName, identifiers) => {
		page = 'grant'
		context.userId = 'user'
		context.userDisplayName = displayName
		const wrapper = mount(LoginFlowV2, { global })

		const account = wrapper.get('[aria-label="Account to connect"]')
		expect(account.findAll('bdi').map((element) => element.text())).toEqual([...identifiers, context.serverUrl])
		expect(account.get('nc-avatar-stub').attributes('user')).toBe('user')
		expect(account.get('nc-avatar-stub').attributes('displayname')).toBe(identifiers[0])
		expect(wrapper.text()).toContain('reading, changing, and deleting files')
		expect(wrapper.getComponent(LoginFlowContext).props('context')).toMatchObject(context)
		expect(wrapper.get('form').attributes('action')).toBe(grant.actionUrl)
		expect(wrapper.get('input[name="requesttoken"]').attributes('value')).toBe('request-token')
		expect(wrapper.get('input[name="stateToken"]').attributes('value')).toBe('state-token')
		expect(wrapper.find('input[name="direct"]').exists()).toBe(false)
		expect(button(wrapper, 'Grant access').attributes('type')).toBe('submit')
		expect(button(wrapper, 'Decline').attributes('type')).toBe('button')
	})

	it('requires password confirmation before submitting approval', async () => {
		page = 'grant'
		const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {})
		vi.mocked(isPasswordConfirmationRequired).mockReturnValue(true)
		const wrapper = mount(LoginFlowV2, { global })

		await wrapper.get('form').trigger('submit')
		await flushPromises()

		expect(confirmPassword).toHaveBeenCalledOnce()
		expect(submit).toHaveBeenCalledOnce()
	})

	it('does not submit if password confirmation is cancelled', async () => {
		page = 'grant'
		const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {})
		vi.mocked(isPasswordConfirmationRequired).mockReturnValue(true)
		vi.mocked(confirmPassword).mockRejectedValue(new Error('Cancelled'))
		const wrapper = mount(LoginFlowV2, { global })

		await wrapper.get('form').trigger('submit')
		await flushPromises()

		expect(submit).not.toHaveBeenCalled()
		expect(button(wrapper, 'Decline').attributes('disabled')).toBeUndefined()
	})

	it('keeps the context and decline action when using an existing app password', async () => {
		const wrapper = mount(LoginFlowV2, { global })
		await button(wrapper, 'Use an existing app password').trigger('click')

		expect(wrapper.get('form').attributes('action')).toBe(auth.appTokenUrl)
		expect(wrapper.text()).toContain(context.serverUrl)
		expect(wrapper.getComponent(LoginFlowContext).props('context')).toMatchObject(context)
		expect(button(wrapper, 'Decline').exists()).toBe(true)
		expect(button(wrapper, 'Grant access').exists()).toBe(true)
		expect(wrapper.get('input[name="direct"]').attributes('value')).toBe('1')
		await wrapper.get('input[name="user"]').setValue('user')
		await wrapper.get('input[name="password"]').setValue('existing-password')
		let credentials: FormData | undefined
		vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(function(this: HTMLFormElement) {
			credentials = new FormData(this)
		})

		await wrapper.get('form').trigger('submit')

		expect(credentials?.get('user')).toBe('user')
		expect(credentials?.get('password')).toBe('existing-password')
		expect(confirmPassword).not.toHaveBeenCalled()
	})
})

describe('Declining a login flow v2 request', () => {
	it.each(['auth', 'grant'] as const)('cancels from the %s page before showing confirmation', async (state) => {
		page = state
		const wrapper = mount(LoginFlowV2, { global, attachTo: document.body })
		await button(wrapper, 'Decline').trigger('click')
		await flushPromises()

		expect(axios.post).toHaveBeenCalledWith(context.declineUrl, { stateToken: 'state-token' })
		expect(wrapper.find('form').exists()).toBe(false)
		expect(document.activeElement).toBe(wrapper.get('h2').element)
	})

	it('blocks approval and repeated decline while cancellation is pending', async () => {
		page = 'grant'
		vi.mocked(axios.post).mockReturnValue(new Promise(() => {}))
		const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {})
		const wrapper = mount(LoginFlowV2, { global })

		await button(wrapper, 'Decline').trigger('click')
		expect(button(wrapper, 'Grant access').attributes('disabled')).toBeDefined()
		await wrapper.get('form').trigger('submit')
		expect(submit).not.toHaveBeenCalled()
		expect(axios.post).toHaveBeenCalledOnce()
	})

	it('retains the request and offers a retry when cancellation fails', async () => {
		vi.mocked(axios.post).mockRejectedValueOnce(new Error('Offline'))
		const wrapper = mount(LoginFlowV2, { global })

		await button(wrapper, 'Decline').trigger('click')
		await flushPromises()

		expect(wrapper.find('[role="alert"]').exists()).toBe(true)
		expect(wrapper.find('form').exists()).toBe(true)
		await button(wrapper, 'Retry decline').trigger('click')
		await flushPromises()
		expect(wrapper.find('form').exists()).toBe(false)
		expect(wrapper.find('[role="alert"]').exists()).toBe(false)
	})

	it.each([403, 409, 410])('does not claim cancellation succeeded for HTTP %s', async (status) => {
		vi.mocked(axios.post).mockRejectedValue({ isAxiosError: true, response: { status } })
		const wrapper = mount(LoginFlowV2, { global })

		await button(wrapper, 'Decline').trigger('click')
		await flushPromises()

		expect(wrapper.find('[role="alert"]').exists()).toBe(true)
		expect(wrapper.find('form').exists()).toBe(false)
		expect(button(wrapper, 'Security settings').attributes('href')).toBe(context.securitySettingsUrl)
	})
})
