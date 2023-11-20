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
	<component :is="inline ? 'div' : 'li'">
		<!-- User Status = Status modal toggle -->
		<button v-if="!inline"
			class="user-status-menu-item"
			@click.stop="openModal">
			<span aria-hidden="true" :class="statusIcon" class="user-status-icon" />
			{{ visibleMessage }}
		</button>

		<!-- Dashboard Status -->
		<NcButton v-else
			:icon="statusIcon"
			@click.stop="openModal">
			<template #icon>
				<span aria-hidden="true" :class="statusIcon" class="user-status-icon" />
			</template>
			{{ visibleMessage }}
		</NcButton>

		<!-- Status management modal -->
		<SetStatusModal v-if="isModalOpen" @close="closeModal" />
	</component>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import debounce from 'debounce'

import { sendHeartbeat } from './services/heartbeatService.js'
import OnlineStatusMixin from './mixins/OnlineStatusMixin.js'

export default {
	name: 'UserStatus',

	components: {
		NcButton,
		SetStatusModal: () => import(/* webpackChunkName: 'user-status-modal' */'./components/SetStatusModal.vue'),
	},
	mixins: [OnlineStatusMixin],

	props: {
		/**
		 * Whether the component should be rendered as a Dashboard Status or a User Menu Entries
		 * true = Dashboard Status
		 * false = User Menu Entries
		 */
		inline: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			heartbeatInterval: null,
			isAway: false,
			isModalOpen: false,
			mouseMoveListener: null,
			setAwayTimeout: null,
		}
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
		subscribe('user_status:status.updated', this.handleUserStatusUpdated)
	},

	/**
	 * Some housekeeping before destroying the component
	 */
	beforeDestroy() {
		window.removeEventListener('mouseMove', this.mouseMoveListener)
		clearInterval(this.heartbeatInterval)
		unsubscribe('user_status:status.updated', this.handleUserStatusUpdated)
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
		 * Sends the status heartbeat to the server
		 *
		 * @return {Promise<void>}
		 * @private
		 */
		async _backgroundHeartbeat() {
			try {
				const status = await sendHeartbeat(this.isAway)
				if (status?.userId) {
					this.$store.dispatch('setStatusFromHeartbeat', status)
				} else {
					await this.$store.dispatch('reFetchStatusFromServer')
				}
			} catch (error) {
				console.debug('Failed sending heartbeat, got: ' + error.response?.status)
			}
		},
		handleUserStatusUpdated(state) {
			if (OC.getCurrentUser().uid === state.userId) {
				this.$store.dispatch('setStatusFromObject', {
					status: state.status,
					icon: state.icon,
					message: state.message,
				})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.user-status-menu-item {
	width: auto;
	min-width: 44px;
	height: 44px;
	margin: 0;
	border: 0;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-main-background-blur);
	font-size: inherit;
	font-weight: normal;

	-webkit-backdrop-filter: var(--background-blur);
	backdrop-filter: var(--background-blur);

	&:active,
	&:hover,
	&:focus-visible {
		background-color: var(--color-background-hover);
	}
	&:focus-visible {
		box-shadow: 0 0 0 4px var(--color-main-background) !important;
		outline: 2px solid var(--color-main-text) !important;
	}
}

.user-status-icon {
	width: 16px;
	height: 16px;
	margin-right: 10px;
	opacity: 1 !important;
	background-size: 16px;
	vertical-align: middle !important;
}
</style>
