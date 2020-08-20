<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
	<li :class="{ inline }">
		<div id="user-status-menu-item">
			<span
				v-if="!inline"
				id="user-status-menu-item__header"
				:title="displayName">
				{{ displayName }}
			</span>
			<Actions
				id="user-status-menu-item__subheader"
				:default-icon="statusIcon"
				:menu-title="visibleMessage"
				:title="visibleMessage">
				<ActionButton
					v-for="status in statuses"
					:key="status.type"
					:icon="status.icon"
					:close-after-click="true"
					@click.prevent.stop="changeStatus(status.type)">
					{{ status.label }}
				</ActionButton>
				<ActionButton
					icon="icon-rename"
					:close-after-click="true"
					@click.prevent.stop="openModal">
					{{ $t('user_status', 'Set custom status') }}
				</ActionButton>
			</Actions>
			<SetStatusModal
				v-if="isModalOpen"
				@close="closeModal" />
		</div>
	</li>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import SetStatusModal from './components/SetStatusModal'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import { mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { getAllStatusOptions } from './services/statusOptionsService'
import { sendHeartbeat } from './services/heartbeatService'
import debounce from 'debounce'

export default {
	name: 'App',
	components: {
		Actions,
		ActionButton,
		SetStatusModal,
	},
	props: {
		inline: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			isModalOpen: false,
			statuses: getAllStatusOptions(),
			heartbeatInterval: null,
			setAwayTimeout: null,
			mouseMoveListener: null,
			isAway: false,
		}
	},
	computed: {
		...mapState({
			statusType: state => state.userStatus.status,
			statusIsUserDefined: state => state.userStatus.statusIsUserDefined,
			customIcon: state => state.userStatus.icon,
			customMessage: state => state.userStatus.message,
		}),
		/**
		 * The display-name of the current user
		 *
		 * @returns {String}
		 */
		displayName() {
			return getCurrentUser().displayName
		},
		/**
		 * The message displayed in the top right corner
		 *
		 * @returns {String}
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
		/**
		 * The status indicator icon
		 *
		 * @returns {String|null}
		 */
		statusIcon() {
			switch (this.statusType) {
			case 'online':
				return 'icon-user-status-online'

			case 'away':
				return 'icon-user-status-away'

			case 'dnd':
				return 'icon-user-status-dnd'

			case 'invisible':
			case 'offline':
				return 'icon-user-status-invisible'
			}

			return ''
		},
	},
	/**
	 * Loads the current user's status from initial state
	 * and stores it in Vuex
	 */
	mounted() {
		this.$store.dispatch('loadStatusFromInitialState')

		if (OC.config.session_keepalive) {
			// Send the latest status to the server every 5 minutes
			this.heartbeatInterval = setInterval(this._backgroundHeartbeat.bind(this), 1000 * 60 * 5)
			this.setAwayTimeout = () => {
				this.isAway = true
			}
			// Catch mouse movements, but debounce to once every 30 seconds
			this.mouseMoveListener = debounce(() => {
				const wasAway = this.isAway
				this.isAway = false
				// Reset the two minute counter
				clearTimeout(this.setAwayTimeout)
				// If the user did not move the mouse within two minutes,
				// mark them as away
				setTimeout(this.setAwayTimeout, 1000 * 60 * 2)

				if (wasAway) {
					this._backgroundHeartbeat()
				}
			}, 1000 * 2, true)
			window.addEventListener('mousemove', this.mouseMoveListener, {
				capture: true,
				passive: true,
			})

			this._backgroundHeartbeat()
		}
	},
	/**
	 * Some housekeeping before destroying the component
	 */
	beforeDestroy() {
		window.removeEventListener('mouseMove', this.mouseMoveListener)
		clearInterval(this.heartbeatInterval)
	},
	methods: {
		/**
		 * Opens the modal to set a custom status
		 */
		openModal() {
			this.isModalOpen = true
		},
		/**
		 * Closes the modal
		 */
		closeModal() {
			this.isModalOpen = false
		},
		/**
		 * Changes the user-status
		 *
		 * @param {String} statusType (online / away / dnd / invisible)
		 */
		async changeStatus(statusType) {
			try {
				await this.$store.dispatch('setStatus', { statusType })
			} catch (err) {
				showError(this.$t('user_status', 'There was an error saving the new status'))
				console.debug(err)
			}
		},
		/**
		 * Sends the status heartbeat to the server
		 *
		 * @returns {Promise<void>}
		 * @private
		 */
		async _backgroundHeartbeat() {
			await sendHeartbeat(this.isAway)
			await this.$store.dispatch('reFetchStatusFromServer')
		},
	},
}
</script>

<style lang="scss">
$max-width-user-status: 200px;

li:not(.inline) #user-status-menu-item {
	&__header {
		display: block;
		box-sizing: border-box;
		color: var(--color-text-maxcontrast);
		padding: 10px 12px 5px 38px;
		opacity: 1;
		white-space: nowrap;
		text-align: left;
		max-width: $max-width-user-status;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	&__subheader {
		width: 100%;

		> button {
			display: block;
			box-sizing: border-box;
			background-color: var(--color-main-background);
			background-position: 12px center;
			background-size: 16px;
			border: 0;
			border-radius: 0;
			font-weight: normal;
			padding-left: 38px;
			opacity: 1;
			max-width: $max-width-user-status;
			overflow: hidden;
			text-overflow: ellipsis;

			&:hover,
			&:focus {
				box-shadow: inset 4px 0 var(--color-primary-element);
			}
		}
	}
}

.inline #user-status-menu-item__subheader {
	width: 100%;

	> button {
		background-size: 16px;
		border: 0;
		border-radius: var(--border-radius-pill);
		font-weight: normal;
		padding-left: 40px;

		&.icon-loading-small {
			&::after {
				left: 21px;
			}
		}
	}
}

	li {
		list-style-type: none;
	}
</style>
