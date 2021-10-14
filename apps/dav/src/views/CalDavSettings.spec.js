/**
 * @copyright Copyright (c) 2016 François Freitag <mail@franek.fr>
 *
 * @author François Freitag <mail@franek.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from '@nextcloud/axios'
import { render } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import CalDavSettings from './CalDavSettings'
// eslint-disable-next-line no-unused-vars
import { generateUrl } from '@nextcloud/router'
jest.mock('@nextcloud/axios')
jest.mock('@nextcloud/router', () => {
	return {
		generateUrl(url) {
			return url
		},
	}
})

describe('CalDavSettings', () => {
	const originalOC = global.OC
	const originalOCP = global.OCP

	beforeEach(() => {
		global.OC = { requestToken: 'secret' }
		global.OCP = {
			AppConfig: {
				setValue: jest.fn(),
			},
		}
	})
	afterAll(() => {
		global.OC = originalOC
		global.OCP = originalOCP
	})

	test('interactions', async() => {
		const TLUtils = render(
			CalDavSettings,
			{
				data() {
					return {
						sendInvitations: true,
						generateBirthdayCalendar: true,
						sendEventReminders: true,
						sendEventRemindersPush: true,
					}
				},
			},
			Vue => {
				Vue.prototype.$t = jest.fn((app, text) => text)
			}
		)
		expect(TLUtils.container).toMatchSnapshot()
		const sendInvitations = TLUtils.getByLabelText(
			'Send invitations to attendees'
		)
		expect(sendInvitations).toBeChecked()
		const generateBirthdayCalendar = TLUtils.getByLabelText(
			'Automatically generate a birthday calendar'
		)
		expect(generateBirthdayCalendar).toBeChecked()
		const sendEventReminders = TLUtils.getByLabelText(
			'Send notifications for events'
		)
		expect(sendEventReminders).toBeChecked()
		const sendEventRemindersPush = TLUtils.getByLabelText(
			'Enable notifications for events via push'
		)
		expect(sendEventRemindersPush).toBeChecked()

		await userEvent.click(sendInvitations)
		expect(sendInvitations).not.toBeChecked()
		expect(OCP.AppConfig.setValue).toHaveBeenCalledWith(
			'dav',
			'sendInvitations',
			'no'
		)
		OCP.AppConfig.setValue.mockClear()
		await userEvent.click(sendInvitations)
		expect(sendInvitations).toBeChecked()
		expect(OCP.AppConfig.setValue).toHaveBeenCalledWith(
			'dav',
			'sendInvitations',
			'yes'
		)

		axios.post.mockImplementationOnce((uri) => {
			expect(uri).toBe('/apps/dav/disableBirthdayCalendar')
			return Promise.resolve()
		})
		await userEvent.click(generateBirthdayCalendar)
		axios.post.mockImplementationOnce((uri) => {
			expect(uri).toBe('/apps/dav/enableBirthdayCalendar')
			return Promise.resolve()
		})
		await userEvent.click(generateBirthdayCalendar)
		expect(generateBirthdayCalendar).toBeEnabled()

		OCP.AppConfig.setValue.mockClear()
		await userEvent.click(sendEventReminders)
		expect(sendEventReminders).not.toBeChecked()
		expect(OCP.AppConfig.setValue).toHaveBeenCalledWith(
			'dav',
			'sendEventReminders',
			'no'
		)
		expect(sendEventRemindersPush).toBeDisabled()
		OCP.AppConfig.setValue.mockClear()
		await userEvent.click(sendEventReminders)
		expect(sendEventReminders).toBeChecked()
		expect(OCP.AppConfig.setValue).toHaveBeenCalledWith(
			'dav',
			'sendEventReminders',
			'yes'
		)
		expect(sendEventRemindersPush).toBeEnabled()
	})
})
