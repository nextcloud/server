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
			sendEventRemindersPush: loadState('dav', 'sendEventRemindersPush'),
		}
	},
})

CalDavSettingsView.$mount('#settings-admin-caldav')
