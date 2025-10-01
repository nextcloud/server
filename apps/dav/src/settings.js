/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import CalDavSettings from './views/CalDavSettings.vue'

Vue.prototype.$t = t

const View = Vue.extend(CalDavSettings)
const CalDavSettingsView = new View({
	name: 'CalDavSettingsView',
	data() {
		return {
			sendInvitations: loadState('dav', 'sendInvitations'),
			generateBirthdayCalendar: loadState(
				'dav',
				'generateBirthdayCalendar',
			),
			sendEventReminders: loadState('dav', 'sendEventReminders'),
			sendEventRemindersToSharedUsers: loadState(
				'dav',
				'sendEventRemindersToSharedUsers',
			),
			sendEventRemindersPush: loadState('dav', 'sendEventRemindersPush'),
		}
	},
})

CalDavSettingsView.$mount('#settings-admin-caldav')
