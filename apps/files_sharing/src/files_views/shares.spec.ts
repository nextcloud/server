/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable n/no-extraneous-import */
/* eslint-disable @typescript-eslint/no-explicit-any */
import type { OCSResponse } from '@nextcloud/typings/ocs'
import { Folder, Navigation, View, getNavigation } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import axios from '@nextcloud/axios'

import '../main'
import registerSharingViews from './shares'

declare global {
	interface Window {
		_nc_navigation?: Navigation
	}
}

describe('Sharing views definition', () => {
	let Navigation
	beforeEach(() => {
		delete window._nc_navigation
		Navigation = getNavigation()
		expect(window._nc_navigation).toBeDefined()
	})

	test('Default values', () => {
		vi.spyOn(Navigation, 'register')

		expect(Navigation.views.length).toBe(0)

		registerSharingViews()
		const shareOverviewView = Navigation.views.find(view => view.id === 'shareoverview') as View
		const sharesChildViews = Navigation.views.filter(view => view.parent === 'shareoverview') as View[]

		expect(Navigation.register).toHaveBeenCalledTimes(7)

		// one main view and no children
		expect(Navigation.views.length).toBe(7)
		expect(shareOverviewView).toBeDefined()
		expect(sharesChildViews.length).toBe(6)

		expect(shareOverviewView?.id).toBe('shareoverview')
		expect(shareOverviewView?.name).toBe('Shares')
		expect(shareOverviewView?.caption).toBe('Overview of shared files.')
		expect(shareOverviewView?.icon).toMatch(/<svg.+<\/svg>/i)
		expect(shareOverviewView?.order).toBe(20)
		expect(shareOverviewView?.columns).toStrictEqual([])
		expect(shareOverviewView?.getContents).toBeDefined()

		const dataProvider = [
			{ id: 'sharingin', name: 'Shared with you' },
			{ id: 'sharingout', name: 'Shared with others' },
			{ id: 'sharinglinks', name: 'Shared by link' },
			{ id: 'filerequest', name: 'File requests' },
			{ id: 'deletedshares', name: 'Deleted shares' },
			{ id: 'pendingshares', name: 'Pending shares' },
		]

		sharesChildViews.forEach((view, index) => {
			expect(view?.id).toBe(dataProvider[index].id)
			expect(view?.parent).toBe('shareoverview')
			expect(view?.name).toBe(dataProvider[index].name)
			expect(view?.caption).toBeDefined()
			expect(view?.emptyTitle).toBeDefined()
			expect(view?.emptyCaption).toBeDefined()
			expect(view?.icon).match(/<svg.+<\/svg>/)
			expect(view?.order).toBe(index + 1)
			expect(view?.columns).toStrictEqual([])
			expect(view?.getContents).toBeDefined()
		})
	})
})

describe('Sharing views contents', () => {
	let Navigation
	beforeEach(() => {
		delete window._nc_navigation
		Navigation = getNavigation()
		expect(window._nc_navigation).toBeDefined()
	})

	test('Sharing overview get contents', async () => {
		vi.spyOn(axios, 'get').mockImplementation(async (): Promise<any> => {
			return {
				data: {
					ocs: {
						meta: {
							status: 'ok',
							statuscode: 200,
							message: 'OK',
						},
						data: [],
					},
				} as OCSResponse<any>,
			}
		})

		registerSharingViews()
		expect(Navigation.views.length).toBe(7)
		Navigation.views.forEach(async (view: View) => {
			const content = await view.getContents('/')
			expect(content.contents).toStrictEqual([])
			expect(content.folder).toBeInstanceOf(Folder)
		})
	})
})
