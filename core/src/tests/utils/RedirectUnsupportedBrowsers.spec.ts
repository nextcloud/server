/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { testSupportedBrowser } from '../../utils/RedirectUnsupportedBrowsers.js'

// Mock the router so generateUrl returns a predictable path
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string) => `/index.php${path}`,
}))

// Mock the logger to suppress output
vi.mock('../../logger.js', () => ({
	default: { debug: vi.fn() },
}))

const browserStorage = vi.hoisted(() => ({ getItem: vi.fn(() => null) }))
vi.mock('../../services/BrowserStorageService.js', () => ({ default: browserStorage }))

const supportedUA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36'
const unsupportedUA = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)'

describe('testSupportedBrowser', () => {
	let originalLocation: Location

	beforeEach(() => {
		originalLocation = window.location

		// Reset the override flag
		browserStorage.getItem.mockReturnValue(null)

		// Default to a path that isn't the unsupported-browser page
		Object.defineProperty(window, 'location', {
			configurable: true,
			writable: true,
			value: {
				href: 'http://localhost/apps/files',
				origin: 'http://localhost',
				pathname: '/apps/files',
				reload: vi.fn(),
			},
		})

		vi.spyOn(window.history, 'pushState').mockImplementation(() => {})
	})

	afterEach(() => {
		Object.defineProperty(window, 'location', {
			configurable: true,
			writable: true,
			value: originalLocation,
		})
		vi.restoreAllMocks()
	})

	it('does nothing for a supported browser', () => {
		Object.defineProperty(window.navigator, 'userAgent', { configurable: true, value: supportedUA })

		testSupportedBrowser()

		expect(window.history.pushState).not.toHaveBeenCalled()
		expect(window.location.reload).not.toHaveBeenCalled()
	})

	it('redirects an unsupported browser to the warning page', () => {
		Object.defineProperty(window.navigator, 'userAgent', { configurable: true, value: unsupportedUA })

		testSupportedBrowser()

		expect(window.history.pushState).toHaveBeenCalledOnce()
		const [, , url] = (window.history.pushState as ReturnType<typeof vi.fn>).mock.calls[0]
		expect(url).toMatch(/^\/index\.php\/unsupported\?redirect_url=/)
		expect(window.location.reload).toHaveBeenCalledOnce()
	})

	it('encodes the redirect URL with btoa, not window.Buffer', () => {
		Object.defineProperty(window.navigator, 'userAgent', { configurable: true, value: unsupportedUA })

		testSupportedBrowser()

		const [, , url] = (window.history.pushState as ReturnType<typeof vi.fn>).mock.calls[0]
		const encoded = new URL(`http://localhost${url}`).searchParams.get('redirect_url')
		expect(encoded).toBe(btoa('/apps/files'))
	})

	it('does not throw regardless of override flag state', () => {
		// isBrowserOverridden is read at module-load time so the mock won't flip it
		// retroactively — but we can at least assert the function never throws,
		// which is the regression guard for the window.Buffer removal.
		Object.defineProperty(window.navigator, 'userAgent', { configurable: true, value: unsupportedUA })
		expect(() => testSupportedBrowser()).not.toThrow()
	})

	it('does not redirect when already on the unsupported-browser page', () => {
		Object.defineProperty(window.navigator, 'userAgent', { configurable: true, value: unsupportedUA })
		Object.defineProperty(window, 'location', {
			configurable: true,
			writable: true,
			value: {
				href: 'http://localhost/index.php/unsupported',
				origin: 'http://localhost',
				pathname: '/index.php/unsupported',
				reload: vi.fn(),
			},
		})

		testSupportedBrowser()

		expect(window.history.pushState).not.toHaveBeenCalled()
		expect(window.location.reload).not.toHaveBeenCalled()
	})
})
