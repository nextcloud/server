/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type VueRouter from 'vue-router'

import { describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import RouterService from './RouterService.ts'

const route = {
	name: 'filelist',
	params: { view: 'files', fileid: '42' },
	query: { dir: '/folder' },
}

/** A vue-router 5 router, where `currentRoute` is a ref. */
function routerWithRefRoute() {
	return {
		currentRoute: ref(route),
		push: vi.fn().mockResolvedValue(undefined),
		replace: vi.fn().mockResolvedValue(undefined),
	} as unknown as VueRouter
}

/** A vue-router 3 router (public share bundle), where `currentRoute` is the route. */
function routerWithPlainRoute() {
	return {
		currentRoute: route,
		push: vi.fn().mockResolvedValue(undefined),
		replace: vi.fn().mockResolvedValue(undefined),
	} as unknown as VueRouter
}

describe('RouterService', () => {
	it.each([
		['vue-router 5 (ref)', routerWithRefRoute],
		['vue-router 3 (plain object)', routerWithPlainRoute],
	])('exposes the current route for %s', (_name, createRouter) => {
		const service = new RouterService(createRouter())

		expect(service.name).toBe('filelist')
		expect(service.params).toEqual({ view: 'files', fileid: '42' })
		expect(service.query).toEqual({ dir: '/folder' })
	})

	it('keeps the current route name when navigating without one', () => {
		const router = routerWithRefRoute()
		const service = new RouterService(router)

		service.goToRoute(null, { view: 'files', fileid: '7' }, { dir: '/other' })

		expect(router.push).toHaveBeenCalledWith({
			name: 'filelist',
			params: { view: 'files', fileid: '7' },
			query: { dir: '/other' },
		})
	})

	it('replaces the current entry when asked to', () => {
		const router = routerWithRefRoute()
		const service = new RouterService(router)

		service.goToRoute('filelist', { view: 'files' }, undefined, true)

		expect(router.replace).toHaveBeenCalledOnce()
		expect(router.push).not.toHaveBeenCalled()
	})

	// vue-router navigates asynchronously, so two stores reacting to the same
	// state change would otherwise both merge into the pre-navigation route and
	// the second would drop what the first had set.
	it('reports a requested route before the navigation is applied', async () => {
		const router = routerWithRefRoute()
		const service = new RouterService(router)

		const navigation = service.goToRoute(null, { view: 'files', fileid: '7' }, { dir: '/other' })

		expect(service.params).toEqual({ view: 'files', fileid: '7' })
		expect(service.query).toEqual({ dir: '/other' })

		// a second consumer merges its own change on top of the requested route
		service.goToRoute(null, service.params, { ...service.query, opendetails: 'true' }, true)

		expect(router.replace).toHaveBeenCalledWith({
			name: 'filelist',
			params: { view: 'files', fileid: '7' },
			query: { dir: '/other', opendetails: 'true' },
		})

		await navigation
	})

	it('falls back to the applied route once the navigation settled', async () => {
		const router = routerWithRefRoute()
		const service = new RouterService(router)

		await service.goToRoute(null, { view: 'files', fileid: '7' }, { dir: '/other' })

		expect(service.params).toEqual({ view: 'files', fileid: '42' })
		expect(service.query).toEqual({ dir: '/folder' })
	})
})
