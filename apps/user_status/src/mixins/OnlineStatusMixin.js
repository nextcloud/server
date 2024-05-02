/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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

import { mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'

export default {
	computed: {
		...mapState({
			statusType: state => state.userStatus.status,
			statusIsUserDefined: state => state.userStatus.statusIsUserDefined,
			customIcon: state => state.userStatus.icon,
			customMessage: state => state.userStatus.message,
		}),

		/**
		 * The message displayed in the top right corner
		 *
		 * @return {string}
		 */
		visibleMessage() {
			if (this.customIcon && this.customMessage) {
				return `${this.customIcon} ${this.customMessage}`
			}

			if (this.customMessage) {
				return this.customMessage
			}

			if (this.statusIsUserDefined) {
				switch (this.statusType) {
				case 'online':
					return this.$t('user_status', 'Online')

				case 'away':
				case 'busy':
					return this.$t('user_status', 'Away')

				case 'dnd':
					return this.$t('user_status', 'Do not disturb')

				case 'invisible':
					return this.$t('user_status', 'Invisible')

				case 'offline':
					return this.$t('user_status', 'Offline')
				}
			}

			return this.$t('user_status', 'Set status')
		},
	},

	methods: {
		/**
		 * Changes the user-status
		 *
		 * @param {string} statusType (online / away / dnd / invisible)
		 */
		async changeStatus(statusType) {
			try {
				await this.$store.dispatch('setStatus', { statusType })
			} catch (err) {
				showError(this.$t('user_status', 'There was an error saving the new status'))
				console.debug(err)
			}
		},
	},
}
