/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
/* eslint-disable n/no-extraneous-import */
import { expect } from '@jest/globals'
import axios from '@nextcloud/axios'

import { type OCSResponse } from '../services/SharingService'
import registerSharingViews from './shares'

import '../main'
import { Folder, getNavigation } from '@nextcloud/files'

describe('Sharing views definition', () => {
	let Navigation
	beforeEach(() => {
		Navigation = getNavigation()
	})

	afterAll(() => {
		delete window.OCP
	})

	test('Default values', () => {
		jest.spyOn(Navigation, 'register')

		expect(Navigation.views.length).toBe(0)

		registerSharingViews()
		const shareOverviewView = Navigation.views.find(view => view.id === 'shareoverview') as Navigation
		const sharesChildViews = Navigation.views.filter(view => view.parent === 'shareoverview') as Navigation[]

		expect(Navigation.register).toHaveBeenCalledTimes(6)

		// one main view and no children
		expect(Navigation.views.length).toBe(6)
		expect(shareOverviewView).toBeDefined()
		expect(sharesChildViews.length).toBe(5)

		expect(shareOverviewView?.id).toBe('shareoverview')
		expect(shareOverviewView?.name).toBe('Shares')
		expect(shareOverviewView?.caption).toBe('Overview of shared files.')
		expect(shareOverviewView?.icon).toBe('<svg>SvgMock</svg>')
		expect(shareOverviewView?.order).toBe(20)
		expect(shareOverviewView?.columns).toStrictEqual([])
		expect(shareOverviewView?.getContents).toBeDefined()

		const dataProvider = [
			{ id: 'sharingin', name: 'Shared with you' },
			{ id: 'sharingout', name: 'Shared with others' },
			{ id: 'sharinglinks', name: 'Shared by link' },
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
			expect(view?.icon).toBe('<svg>SvgMock</svg>')
			expect(view?.order).toBe(index + 1)
			expect(view?.columns).toStrictEqual([])
			expect(view?.getContents).toBeDefined()
		})
	})
})

describe('Sharing views contents', () => {
	let Navigation
	beforeEach(() => {
		Navigation = new NavigationService()
		window.OCP = { Files: { Navigation } }
	})

	afterAll(() => {
		delete window.OCP
	})

	test('Sharing overview get contents', async () => {
		jest.spyOn(axios, 'get').mockImplementation(async (): Promise<any> => {
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
		expect(Navigation.views.length).toBe(6)
		Navigation.views.forEach(async (view: Navigation) => {
			const content = await view.getContents('/')
			expect(content.contents).toStrictEqual([])
			expect(content.folder).toBeInstanceOf(Folder)
		})
	})
})
