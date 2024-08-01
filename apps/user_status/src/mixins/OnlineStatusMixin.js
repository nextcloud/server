/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
