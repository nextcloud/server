<!--
  - @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
  - @author Carl Schwan <carl@carlschwan.eu>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div v-if="hasLoaded && backupStatus.status">
		{{ $t('user_status', 'Automatically set during calls and events.') }}<br />
		{{ $t('user_status', 'Reset to') }}
		<a
			href="#"
			role="button"
			@click.prevent.stop="revertCurrentStatus">
			{{ message }}
		</a>
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { getCurrentUser } from '@nextcloud/auth'

export default {
	name: 'ResetStatus',
	computed: {
		...mapState({
			backupStatus: state => state.backupStatus,
			userStatus: state => state.userStatus,
		}),
		/**
		 * Indicator whether the backup status has already been loaded
		 *
		 * @returns {boolean}
		 */
		hasLoaded() {
			return this.backupStatus.status !== null
		},
		message() {
			if (this.backupStatus.icon && this.backupStatus.message) {
				return `${this.backupStatus.icon} ${this.backupStatus.message}`
			}

			if (this.backupStatus.message) {
				return this.backupStatus.message
			}

			switch (this.backupStatus.status) {
			case 'online':
				return this.$t('user_status', 'Online')

			case 'away':
				return this.$t('user_status', 'Away')

			case 'dnd':
				return this.$t('user_status', 'Do not disturb')

			case 'invisible':
				return this.$t('user_status', 'Invisible')

			case 'offline':
				return this.$t('user_status', 'Offline')
			}
			return ''
		},
	},
	/**
	 * Loads all predefined statuses from the server
	 * when this component is mounted
	 */
	mounted() {
		this.$store.dispatch('loadBackupStatus')
		subscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},
	beforeDestroy() {
		unsubscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},
	methods: {
		/**
		 * Revert the current status.
		 */
		revertCurrentStatus() {
			this.$store.dispatch('revertStatus', {
				status: this.userStatus,
				backupStatus: this.backupStatus,
			})
		},
		/**
		 * Handle change of status, and check if we still need to display the
		 * option to revert the current status.
		 * @param {Object} status The current status
		 */
		handleUserStatusUpdated(status) {
			if (getCurrentUser().uid === status.userId) {
				// Update backup information
				this.$store.dispatch('loadBackupStatus')
			}
		},
	},
}
</script>

<style lang="scss" scoped>
a {
  font-weight: bold;
  &:focus, &:hover {
    text-decoration: underline;
  }
}
</style>
