import Vue from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { translate } from '@nextcloud/l10n'
import CalDavSettings from './views/CalDavSettings'

Vue.prototype.$t = translate

const View = Vue.extend(CalDavSettings)
const CalDavSettingsView = new View({
	name: 'CalDavSettingsView',
	data() {
		return {
			sendInvitations: loadState('dav', 'sendInvitations'),
			generateBirthdayCalendar: loadState(
				'dav',
				'generateBirthdayCalendar'
			),
			sendEventReminders: loadState('dav', 'sendEventReminders'),
			sendEventRemindersToSharedGroupMembers: loadState(
				'dav',
				'sendEventRemindersToSharedGroupMembers'
			),
			sendEventRemindersPush: loadState('dav', 'sendEventRemindersPush'),
		}
	},
})

CalDavSettingsView.$mount('#settings-admin-caldav')
