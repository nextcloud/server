/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { getNavigation } from '@nextcloud/files'
import * as ncInitialState from '@nextcloud/initial-state'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import registerSharingViews from './shares.ts'

import '../main.ts'

const navigation = getNavigation()
beforeEach(() => {
	vi.resetAllMocks()

	const views = [...navigation.views]
	for (const view of views) {
		navigation.remove(view.id)
	}
	expect(navigation.views).toHaveLength(0)
})

describe('Sharing views definition', () => {
	test('Default values', () => {
		vi.spyOn(navigation, 'register')

		registerSharingViews()
		const shareOverviewView = navigation.views.find((view) => view.id === 'shareoverview') as View
		const sharesChildViews = navigation.views.filter((view) => view.parent === 'shareoverview') as View[]

		expect(navigation.register).toHaveBeenCalledTimes(7)

		// one main view and no children
		expect(navigation.views.length).toBe(7)
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

	test('Shared with others view is not registered if user has no storage quota', () => {
		vi.spyOn(navigation, 'register')
		const spy = vi.spyOn(ncInitialState, 'loadState').mockImplementationOnce(() => ({ quota: 0 }))

		expect(navigation.views.length).toBe(0)
		registerSharingViews()
		expect(navigation.register).toHaveBeenCalledTimes(6)
		expect(navigation.views.length).toBe(6)

		const shareOverviewView = navigation.views.find((view) => view.id === 'shareoverview') as View
		const sharesChildViews = navigation.views.filter((view) => view.parent === 'shareoverview') as View[]
		expect(shareOverviewView).toBeDefined()
		expect(sharesChildViews.length).toBe(5)

		expect(spy).toHaveBeenCalled()
		expect(spy).toHaveBeenCalledWith('files', 'storageStats', { quota: -1 })

		const sharedWithOthersView = navigation.views.find((view) => view.id === 'sharingout')
		expect(sharedWithOthersView).toBeUndefined()
	})
})

describe('Sharing views contents', () => {
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
		expect(navigation.views.length).toBe(7)
		for (const view of navigation.views) {
			const content = await view.getContents('/', { signal: new AbortController().signal })
			expect(content.contents).toStrictEqual([])
			expect(content.folder).toBeTypeOf('object')
		}
	})
})
