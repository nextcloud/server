/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import UpdaterAdmin from './UpdaterAdmin.vue'

class FakeEventSource extends EventTarget {
	static instance: FakeEventSource
	closed = false

	constructor(public url: string) {
		super()
		FakeEventSource.instance = this
	}

	send(type: string, data: string) {
		this.dispatchEvent(new MessageEvent(type, { data: JSON.stringify(data) }))
	}

	close() {
		this.closed = true
	}
}

const defaultInfo = {
	appsToUpgrade: [],
	incompatibleAppsList: [],
	isAppsOnlyUpgrade: false,
	oldTheme: null,
	productName: 'Nextcloud',
	version: '34.0.0',
}

function setUpdateInfo(info: object = {}) {
	const input = document.createElement('input')
	input.id = 'initial-state-core-updateInfo'
	input.type = 'hidden'
	input.value = btoa(JSON.stringify({ ...defaultInfo, ...info }))
	document.head.appendChild(input)
}

async function startUpdate() {
	const view = render(UpdaterAdmin)
	await fireEvent.click(view.getByRole('button', { name: 'Start update' }))
	return { view, source: FakeEventSource.instance }
}

describe('UpdaterAdmin', () => {
	beforeEach(() => {
		delete globalThis._nc_initial_state
		vi.stubGlobal('EventSource', FakeEventSource)
		document.head.dataset.requesttoken = 'test-token'
	})

	afterEach(() => {
		cleanup()
		document.getElementById('initial-state-core-updateInfo')?.remove()
		delete globalThis._nc_initial_state
		delete document.head.dataset.requesttoken
		vi.useRealTimers()
		vi.unstubAllGlobals()
	})

	it('shows apps to update even when there are no incompatible apps', () => {
		setUpdateInfo({
			appsToUpgrade: [{ id: 'calendar', name: 'Calendar', oldVersion: '1.0', version: '2.0' }],
		})
		const view = render(UpdaterAdmin)

		expect(view.getByText('These apps will be updated:')).toBeInTheDocument()
		expect(view.getByText('Calendar (calendar) from 1.0 to 2.0')).toBeInTheDocument()
		expect(view.queryByText('These incompatible apps will be disabled:')).not.toBeInTheDocument()
	})

	it('does not show an empty update list when an incompatible app exists', () => {
		setUpdateInfo({ incompatibleAppsList: [{ id: 'example', name: 'Example' }] })
		const view = render(UpdaterAdmin)

		expect(view.getByText('Example (example)')).toBeInTheDocument()
		expect(view.queryByText('These apps will be updated:')).not.toBeInTheDocument()
	})

	it('shows success and starts the redirect countdown on done', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()
		vi.useFakeTimers()

		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()
		source.send('success', 'Preparing update')
		await fireEvent.click(view.getByRole('button', { name: 'Show details' }))
		source.send('done', '')
		await nextTick()

		expect(source.closed).toBe(true)
		expect(view.getByText('The update completed successfully.')).toBeInTheDocument()
		expect(view.getByRole('list', { name: 'Update details' })).toBeVisible()
		expect(view.getByText('You will be redirected to Nextcloud in 5 seconds.')).toBeInTheDocument()
		vi.advanceTimersByTime(1000)
		await nextTick()
		expect(view.getByText('You will be redirected to Nextcloud in 4 seconds.')).toBeInTheDocument()
	})

	it('shows repair warnings and does not redirect automatically', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()
		vi.useFakeTimers()

		source.send('warning', 'Repair warning: Unable to clear the frontend cache')
		source.send('done', '')
		await nextTick()

		expect(view.getByText(/The update completed with warnings/)).toBeInTheDocument()
		expect(view.getByText('Repair warning: Unable to clear the frontend cache')).toBeVisible()
		expect(view.queryByText(/You will be redirected/)).not.toBeInTheDocument()
		expect(vi.getTimerCount()).toBe(0)
	})

	it('keeps monitoring after a repair error until done', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()

		source.send('error', 'Repair error: step failed')
		await nextTick()
		expect(source.closed).toBe(false)
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()

		source.send('success', 'Later step completed')
		source.send('done', '')
		await nextTick()
		expect(source.closed).toBe(true)
		expect(view.getByText(/The update ended with errors/)).toBeInTheDocument()
		expect(view.getByText('Repair error: step failed')).toBeVisible()
		expect(view.getByText('Later step completed')).toBeVisible()
		expect(view.queryByText(/You will be redirected/)).not.toBeInTheDocument()
	})

	it('keeps monitoring after a failure that is followed by done', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()

		source.send('failure', 'An app could not be installed')
		source.send('success', 'Later step completed')
		await nextTick()
		expect(source.closed).toBe(false)
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()

		source.send('done', '')
		await nextTick()
		expect(view.getByText(/The update ended with errors/)).toBeInTheDocument()
		expect(view.getByText('An app could not be installed')).toBeVisible()
		expect(view.getByText('Later step completed')).toBeVisible()
		expect(view.queryByText(/You will be redirected/)).not.toBeInTheDocument()
	})

	it('finishes with an error when the server closes after a failure without done', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()

		source.send('failure', 'Upgrade failed')
		await nextTick()
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()
		source.send('__internal__', 'close')
		await nextTick()

		expect(source.closed).toBe(true)
		expect(view.getByText(/The update ended with errors/)).toBeInTheDocument()
		expect(view.getByText('Upgrade failed')).toBeVisible()
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeEnabled()
		expect(view.queryByText(/You will be redirected/)).not.toBeInTheDocument()
	})

	it('treats a server close without done or an error as an unknown outcome', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()

		source.send('__internal__', 'close')
		await nextTick()
		expect(source.closed).toBe(true)
		expect(view.getByText(/update status is unknown/)).toBeInTheDocument()
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()
	})

	it('treats a transport error as an unknown outcome', async () => {
		setUpdateInfo()
		const { view, source } = await startUpdate()

		source.dispatchEvent(new Event('error'))
		await nextTick()

		expect(source.closed).toBe(true)
		expect(view.getByText(/update status is unknown/)).toBeInTheDocument()
		expect(view.getByRole('button', { name: 'Continue to Nextcloud' })).toBeDisabled()
		expect(view.queryByText(/The update completed/)).not.toBeInTheDocument()
		expect(view.queryByText(/You will be redirected/)).not.toBeInTheDocument()
		const beforeUnload = new Event('beforeunload', { cancelable: true })
		window.dispatchEvent(beforeUnload)
		expect(beforeUnload.defaultPrevented).toBe(true)
	})
})
