import { render } from '@testing-library/vue'
import CalDavSettings from './CalDavSettings.vue'
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
jest.mock('@nextcloud/initial-state', () => {
	return {
		loadState: jest.fn(() => 'https://docs.nextcloud.com/server/23/go.php?to=user-sync-calendars'),
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

	test('interactions', async () => {
		const TLUtils = render(
			CalDavSettings,
			{
				data() {
					return {
						sendInvitations: true,
						generateBirthdayCalendar: true,
						sendEventReminders: true,
						sendEventRemindersToSharedUsers: true,
						sendEventRemindersPush: true,
					}
				},
			},
			Vue => {
				Vue.prototype.$t = jest.fn((app, text) => text)
			},
		)
		expect(TLUtils.container).toMatchSnapshot()
		const sendInvitations = TLUtils.getByLabelText(
			'Send invitations to attendees',
		)
		expect(sendInvitations).toBeChecked()
		const generateBirthdayCalendar = TLUtils.getByLabelText(
			'Automatically generate a birthday calendar',
		)
		expect(generateBirthdayCalendar).toBeChecked()
		const sendEventReminders = TLUtils.getByLabelText(
			'Send notifications for events',
		)
		expect(sendEventReminders).toBeChecked()
		const sendEventRemindersToSharedUsers = TLUtils.getByLabelText(
			'Send reminder notifications to calendar sharees as well',
		)
		expect(sendEventRemindersToSharedUsers).toBeChecked()
		const sendEventRemindersPush = TLUtils.getByLabelText(
			'Enable notifications for events via push',
		)
		expect(sendEventRemindersPush).toBeChecked()

		/*
		FIXME userEvent.click is broken with nextcloud-vue/Button

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

		expect(sendEventRemindersToSharedUsers).toBeDisabled()
		expect(sendEventRemindersPush).toBeDisabled()

		OCP.AppConfig.setValue.mockClear()
		await userEvent.click(sendEventReminders)
		expect(sendEventReminders).toBeChecked()
		expect(OCP.AppConfig.setValue).toHaveBeenCalledWith(
			'dav',
			'sendEventReminders',
			'yes'
		)

		expect(sendEventRemindersToSharedUsers).toBeEnabled()
		expect(sendEventRemindersPush).toBeEnabled()
		*/
	})
})
